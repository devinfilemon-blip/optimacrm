<?php
session_start();
include 'layouts/config.php';
require_once 'xlsx-helper.php';
date_default_timezone_set('Asia/Kolkata');

header('Content-Type: application/json');

function sendResponse($status, $message, $data = null) {
    $resp = ["status" => $status, "message" => $message];
    if ($data !== null) {
        $resp["data"] = $data;
    }
    echo json_encode($resp);
    exit;
}

$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);
if (!is_array($inputData)) {
    $inputData = $_POST;
}

if (!isset($inputData['action'])) {
    sendResponse("error", "No action specified.");
}
$action = $inputData['action'];

// ---- Auth guard: everything except loginUser requires a logged-in session ----
if ($action !== 'loginUser') {
    if (empty($_SESSION['loggedin'])) {
        sendResponse("error", "Not authenticated. Please log in again.");
    }
}

$currentUserId = $_SESSION['user_id'] ?? 0;
$currentUserRole = $_SESSION['userRole'] ?? 'Recruiter';
$isAdmin = ($currentUserRole === 'Admin');
$isTeamLeader = ($currentUserRole === 'Team Leader');

function reqStr($arr, $key, $default = null) {
    return isset($arr[$key]) && $arr[$key] !== '' ? trim((string) $arr[$key]) : $default;
}
function reqNum($arr, $key, $default = 0) {
    return isset($arr[$key]) && $arr[$key] !== '' ? (float) $arr[$key] : $default;
}
/**
 * Bind an arbitrary number of params to a prepared statement without
 * hand-counting a type string. $pairs is a list of [type, value].
 */
function bindDynamic($stmt, array $pairs) {
    $types = '';
    $values = [];
    foreach ($pairs as $p) {
        $types .= $p[0];
        $values[] = $p[1];
    }
    $refs = [$types];
    foreach ($values as $k => $v) {
        $refs[] = &$values[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function reqInt($arr, $key, $default = null) {
    return isset($arr[$key]) && $arr[$key] !== '' ? (int) $arr[$key] : $default;
}

/** Display-only: "REQ-0046" -> "0046". The stored sReqNo keeps its prefix. */
function reqNoDisplay($reqNo) {
    return $reqNo ? preg_replace('/^REQ-?/i', '', $reqNo) : $reqNo;
}

/** Mobile numbers are globally unique across candidates (trashed ones included). */
function candidateMobileDuplicateExists($link, $mobile, $excludeId = null) {
    if ($mobile === null || $mobile === '') return false;
    if ($excludeId) {
        $stmt = mysqli_prepare($link, "SELECT iCandidateId FROM tblcandidate WHERE sMobile = ? AND iCandidateId <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $mobile, $excludeId);
    } else {
        $stmt = mysqli_prepare($link, "SELECT iCandidateId FROM tblcandidate WHERE sMobile = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $mobile);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
}

// =====================================================================
// AUTH
// =====================================================================
if ($action === 'loginUser') {
    $phone = reqStr($inputData, 'phone');
    $password = $inputData['password'] ?? '';

    if (!$phone || !$password) {
        sendResponse("error", "Please provide both username and password.");
    }

    $query = "SELECT iUserid, sPhone, sPassword_hash, sName, sIs_active, sRole
              FROM tbluser WHERE (sPhone = ? OR sName = ?) AND dDeletedAt IS NULL LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, "ss", $phone, $phone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        sendResponse("error", "Invalid username or phone number.");
    }
    $user = mysqli_fetch_assoc($result);

    if ((int) $user['sIs_active'] === 0) {
        sendResponse("error", "User is inactive.");
    }
    if (!password_verify($password, $user['sPassword_hash'])) {
        sendResponse("error", "Invalid password.");
    }

    $_SESSION['user_id'] = (int) $user['iUserid'];
    $_SESSION['username'] = $user['sName'];
    $_SESSION['userRole'] = $user['sRole'];
    $_SESSION['loggedin'] = true;

    $token = bin2hex(random_bytes(16));
    $expireDateTime = date('Y-m-d H:i:s', strtotime('+8 hours'));
    $userId = (int) $user['iUserid'];

    $stmtInsert = mysqli_prepare($link, "INSERT INTO tbltoken (user_id, sToken, sExpire) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmtInsert, "iss", $userId, $token, $expireDateTime);
    mysqli_stmt_execute($stmtInsert);
    $_SESSION['token'] = $token;

    sendResponse("success", "Login successful", ["token" => $token, "role" => $user['sRole']]);
}

if ($action === 'changePassword') {
    $current = $inputData['current'] ?? '';
    $newpass = $inputData['newpass'] ?? '';
    $confirm = $inputData['confirm'] ?? '';

    if (!$current || !$newpass || !$confirm) {
        sendResponse("error", "Please fill all password fields.");
    }
    if ($newpass !== $confirm) {
        sendResponse("error", "New password and confirm password do not match.");
    }
    if (strlen($newpass) < 6) {
        sendResponse("error", "New password must be at least 6 characters.");
    }

    $stmt = mysqli_prepare($link, "SELECT sPassword_hash FROM tbluser WHERE iUserid = ?");
    mysqli_stmt_bind_param($stmt, "i", $currentUserId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);

    if (!$row || !password_verify($current, $row['sPassword_hash'])) {
        sendResponse("error", "Current password is incorrect.");
    }

    $newHash = password_hash($newpass, PASSWORD_DEFAULT);
    $stmtU = mysqli_prepare($link, "UPDATE tbluser SET sPassword_hash = ? WHERE iUserid = ?");
    mysqli_stmt_bind_param($stmtU, "si", $newHash, $currentUserId);
    mysqli_stmt_execute($stmtU);

    sendResponse("success", "Password updated successfully.");
}

// =====================================================================
// REPORTS
// =====================================================================
if ($action === 'reportsData') {
    $out = [];

    $period = reqStr($inputData, 'period', 'yearly');
    $today = new DateTime();
    if ($period === 'quarterly') {
        $qStartMonth = intdiv((int) $today->format('n') - 1, 3) * 3 + 1;
        $rangeStart = new DateTime($today->format('Y') . '-' . str_pad($qStartMonth, 2, '0', STR_PAD_LEFT) . '-01');
        $monthCount = 3;
        $periodLabel = 'This Quarter';
    } else {
        $period = 'yearly';
        $rangeStart = new DateTime($today->format('Y') . '-01-01');
        $monthCount = 12;
        $periodLabel = 'This Year';
    }
    $windowStart = $rangeStart->format('Y-m-d');
    $windowEnd = (clone $rangeStart)->modify("+$monthCount months")->modify('-1 day')->format('Y-m-d');
    $out['period'] = $period;
    $out['periodLabel'] = $periodLabel;

    // ---- Build a continuous month-by-month scaffold (3 months for a quarter,
    //      12 for a year) so quiet months show as zero instead of just being
    //      missing from the chart. ----
    $monthByKey = [];
    for ($i = 0; $i < $monthCount; $i++) {
        $ym = (clone $rangeStart)->modify("+$i months")->format('Y-m');
        $monthByKey[$ym] = ['ym' => $ym, 'openings' => 0, 'placements' => 0, 'invoiced' => 0, 'received' => 0];
    }

    $r = mysqli_query($link, "SELECT DATE_FORMAT(dJoiningDate, '%Y-%m') ym, COUNT(*) placements, COALESCE(SUM(dAmount),0) invoiced, COALESCE(SUM(dRecAmount),0) received
                               FROM tblplacement
                               WHERE dJoiningDate BETWEEN '$windowStart' AND '$windowEnd'
                               GROUP BY ym");
    while ($row = mysqli_fetch_assoc($r)) {
        if (isset($monthByKey[$row['ym']])) {
            $monthByKey[$row['ym']]['placements'] = (int) $row['placements'];
            $monthByKey[$row['ym']]['invoiced'] = (float) $row['invoiced'];
            $monthByKey[$row['ym']]['received'] = (float) $row['received'];
        }
    }

    $r = mysqli_query($link, "SELECT DATE_FORMAT(dOpenDate, '%Y-%m') ym, COALESCE(SUM(iNoOfVacancy),0) openings
                               FROM tblrequirement
                               WHERE dOpenDate BETWEEN '$windowStart' AND '$windowEnd'
                               GROUP BY ym");
    while ($row = mysqli_fetch_assoc($r)) {
        if (isset($monthByKey[$row['ym']])) {
            $monthByKey[$row['ym']]['openings'] = (int) $row['openings'];
        }
    }
    $out['monthly'] = array_values($monthByKey);

    // Keep the top N-1 rows (their own color slot each) and fold anything past
    // that into a single "Other" bucket, so a chart never has to cycle a
    // categorical color past the point colors stop being distinguishable —
    // 8 for a bar chart, 6 for a donut (part-to-whole reads best with fewer slices).
    function topNWithOther($rows, $labelKey, $sumKeys, $max = 8, $otherLabel = 'Other') {
        if (count($rows) <= $max) return $rows;
        $top = array_slice($rows, 0, $max - 1);
        $rest = array_slice($rows, $max - 1);
        $other = [$labelKey => $otherLabel];
        foreach ($sumKeys as $k) {
            $other[$k] = array_sum(array_map(function ($r) use ($k) { return $r[$k]; }, $rest));
        }
        $top[] = $other;
        return $top;
    }

    $byCompany = [];
    $r = mysqli_query($link, "SELECT c.sCompanyName, COUNT(p.iPlacementId) placements, COALESCE(SUM(p.dRecAmount),0) received
                               FROM tblplacement p LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
                               WHERE p.dJoiningDate BETWEEN '$windowStart' AND '$windowEnd'
                               GROUP BY p.iCompanyId ORDER BY placements DESC");
    while ($row = mysqli_fetch_assoc($r)) {
        $row['placements'] = (int) $row['placements'];
        $row['received'] = (float) $row['received'];
        $byCompany[] = $row;
    }
    $out['byCompany'] = topNWithOther($byCompany, 'sCompanyName', ['placements', 'received']);

    // Base dataset is the recruiter roster itself (LEFT JOIN placements), not
    // the placements table — otherwise a recruiter with zero placements this
    // period would simply never appear in the report.
    $byRecruiter = [];
    $stmt = mysqli_prepare($link, "SELECT u.sName AS sWorkedBy, COUNT(p.iPlacementId) placements
                                    FROM tbluser u
                                    LEFT JOIN tblplacement p
                                        ON p.sWorkedBy = u.sName AND p.dDeletedAt IS NULL
                                        AND p.dJoiningDate BETWEEN ? AND ?
                                    WHERE u.sRole = 'Recruiter' AND u.dDeletedAt IS NULL
                                    GROUP BY u.iUserid, u.sName
                                    ORDER BY placements DESC, u.sName ASC");
    mysqli_stmt_bind_param($stmt, "ss", $windowStart, $windowEnd);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $row['placements'] = (int) $row['placements'];
        $byRecruiter[] = $row;
    }
    // Unlike the other ranked charts, this one must never fold recruiters into
    // an "Other" bucket — a 0-placement recruiter has to appear by name.
    $out['byRecruiter'] = $byRecruiter;

    $funnel = [];
    $r = mysqli_query($link, "SELECT sStatus, COUNT(*) c FROM tblrequirement WHERE dOpenDate BETWEEN '$windowStart' AND '$windowEnd' GROUP BY sStatus ORDER BY c DESC");
    while ($row = mysqli_fetch_assoc($r)) {
        $row['c'] = (int) $row['c'];
        $funnel[] = $row;
    }
    $out['requirementFunnel'] = topNWithOther($funnel, 'sStatus', ['c'], 6);

    // ---- Year-at-a-glance summary for the stat cards ----
    $summary = ['placements' => 0, 'openings' => 0, 'revenue' => 0];
    foreach ($monthByKey as $m) {
        $summary['placements'] += $m['placements'];
        $summary['openings'] += $m['openings'];
        $summary['revenue'] += $m['received'];
    }
    $r = mysqli_query($link, "SELECT COUNT(*) c FROM tblrequirement WHERE dOpenDate BETWEEN '$windowStart' AND '$windowEnd'");
    $summary['requirements'] = (int) mysqli_fetch_assoc($r)['c'];
    $summary['placementRate'] = $summary['requirements'] > 0
        ? round(($summary['placements'] / $summary['requirements']) * 100)
        : 0;
    $out['summary'] = $summary;

    sendResponse("success", "ok", $out);
}

// =====================================================================
// DASHBOARD
// =====================================================================
if ($action === 'dashboardStats') {
    $stats = [];

    $monthStartStr = date('Y-m-01');
    $monthEndStr = date('Y-m-t');

    // ---- Vacancy Overview (all-time snapshot of the requirement pipeline) ----
    $r = mysqli_query($link, "SELECT COUNT(*) c FROM tblrequirement WHERE dDeletedAt IS NULL");
    $stats['totalVacancies'] = (int) mysqli_fetch_assoc($r)['c'];

    // "Open" mirrors the openOnly filter on the requirement list (everything
    // still actionable — only a company close-out or a no-join ends it).
    $r = mysqli_query($link, "SELECT COUNT(*) c FROM tblrequirement WHERE dDeletedAt IS NULL AND sStatus NOT IN ('Closed by Co.', 'Not Joining')");
    $stats['openVacancies'] = (int) mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($link, "SELECT COUNT(*) c FROM tblrequirement WHERE dDeletedAt IS NULL AND sStatus IN ('Closed by Co.', 'Not Joining')");
    $stats['closedVacancies'] = (int) mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($link, "SELECT COUNT(*) c FROM tblrequirement WHERE dDeletedAt IS NULL AND sStatus = 'Selected'");
    $stats['selectedCandidates'] = (int) mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($link, "SELECT COUNT(*) c FROM tblcompany WHERE sStatus = 'Active' AND dDeletedAt IS NULL");
    $stats['companies'] = (int) mysqli_fetch_assoc($r)['c'];

    // ---- Placement Tracking ----
    $stmt = mysqli_prepare($link, "SELECT COUNT(*) c FROM tblplacement WHERE dDeletedAt IS NULL AND dJoiningDate BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt, "ss", $monthStartStr, $monthEndStr);
    mysqli_stmt_execute($stmt);
    $stats['placementsThisMonth'] = (int) mysqli_stmt_get_result($stmt)->fetch_assoc()['c'];

    $r = mysqli_query($link, "SELECT COUNT(*) c FROM tblplacement WHERE dDeletedAt IS NULL");
    $stats['placementsTillDate'] = (int) mysqli_fetch_assoc($r)['c'];

    // ---- Revenue & Financial ----
    $stmt = mysqli_prepare($link, "SELECT COALESCE(SUM(dRecAmount),0) s FROM tblplacement WHERE dDeletedAt IS NULL AND dPaymentRecDate BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt, "ss", $monthStartStr, $monthEndStr);
    mysqli_stmt_execute($stmt);
    $stats['revenueThisMonth'] = (float) mysqli_stmt_get_result($stmt)->fetch_assoc()['s'];

    $r = mysqli_query($link, "SELECT COALESCE(SUM(dRecAmount),0) s FROM tblplacement WHERE dDeletedAt IS NULL");
    $stats['revenueTillDate'] = (float) mysqli_fetch_assoc($r)['s'];
    $stats['amountReceived'] = $stats['revenueTillDate'];

    $r = mysqli_query($link, "SELECT COALESCE(SUM(dAmount),0) s FROM tblplacement WHERE dDeletedAt IS NULL");
    $stats['totalInvoicedAmount'] = (float) mysqli_fetch_assoc($r)['s'];

    $r = mysqli_query($link, "SELECT COALESCE(SUM(dAmount - dRecAmount),0) s FROM tblplacement WHERE dDeletedAt IS NULL AND dAmount > dRecAmount");
    $stats['amountPending'] = (float) mysqli_fetch_assoc($r)['s'];

    $r = mysqli_query($link, "SELECT COUNT(*) c FROM tblreminders WHERE sStatus = 'Pending' AND sDate <= CURDATE()");
    $stats['dueReminders'] = (int) mysqli_fetch_assoc($r)['c'];

    $statusCounts = [];
    $r = mysqli_query($link, "SELECT sStatus, COUNT(*) c FROM tblrequirement WHERE dDeletedAt IS NULL GROUP BY sStatus");
    while ($row = mysqli_fetch_assoc($r)) { $statusCounts[$row['sStatus']] = (int) $row['c']; }

    $stats['statusBoard'] = [
        ['label' => 'Total Vacancy',      'count' => $stats['totalVacancies']],
        ['label' => 'Searching',          'count' => $statusCounts['Searching'] ?? 0],
        ['label' => 'Refine Search',      'count' => $statusCounts['Refine Search'] ?? 0],
        ['label' => 'Profiles Sent',      'count' => $statusCounts['Profiles Sent'] ?? 0],
        ['label' => 'Interview Arranged', 'count' => $statusCounts['Interview Scheduled'] ?? 0],
        ['label' => 'Result Pending',     'count' => $statusCounts['Result Pending'] ?? 0],
        ['label' => 'Selected',           'count' => $statusCounts['Selected'] ?? 0],
        ['label' => 'Offer Accepted',     'count' => $statusCounts['Offer Made'] ?? 0],
        ['label' => 'Joined',             'count' => $statusCounts['Joined'] ?? 0],
        ['label' => 'Not Joined',         'count' => $statusCounts['Not Joining'] ?? 0],
        ['label' => 'Job Left',           'count' => 0],
        ['label' => 'Closed by Company',  'count' => $statusCounts['Closed by Co.'] ?? 0],
    ];

    // Capture today's numbers into the daily history so the weekly board fills in over time.
    $snapStmt = mysqli_prepare($link, "INSERT INTO tblstatussnapshot (dSnapshotDate, sLabel, iCount) VALUES (CURDATE(), ?, ?)
        ON DUPLICATE KEY UPDATE iCount = VALUES(iCount)");
    foreach ($stats['statusBoard'] as $row) {
        bindDynamic($snapStmt, [['s', $row['label']], ['i', $row['count']]]);
        mysqli_stmt_execute($snapStmt);
    }

    // Work week runs Tuesday -> Sunday (Monday is a holiday). Find the Tuesday on/before today.
    $daysSinceTuesday = ((int) date('N') - 2 + 7) % 7;
    $weekDates = [];
    for ($i = 0; $i < 6; $i++) {
        $weekDates[] = date('Y-m-d', strtotime("-{$daysSinceTuesday} days +{$i} days"));
    }

    $snapByDateLabel = [];
    $pairs = array_map(function ($d) { return ['s', $d]; }, $weekDates);
    $placeholders = implode(',', array_fill(0, count($weekDates), '?'));
    $weekStmt = mysqli_prepare($link, "SELECT dSnapshotDate, sLabel, iCount FROM tblstatussnapshot WHERE dSnapshotDate IN ($placeholders)");
    bindDynamic($weekStmt, $pairs);
    mysqli_stmt_execute($weekStmt);
    $weekRes = mysqli_stmt_get_result($weekStmt);
    while ($row = mysqli_fetch_assoc($weekRes)) {
        $snapByDateLabel[$row['dSnapshotDate']][$row['sLabel']] = (int) $row['iCount'];
    }

    $weeklyRows = [];
    foreach ($stats['statusBoard'] as $row) {
        $values = [];
        foreach ($weekDates as $d) {
            $values[] = $snapByDateLabel[$d][$row['label']] ?? null;
        }
        $weeklyRows[] = ['label' => $row['label'], 'values' => $values];
    }
    $stats['weeklyStatusBoard'] = ['days' => ['Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 'dates' => $weekDates, 'rows' => $weeklyRows];

    $leadSourceRows = [];
    $r = mysqli_query($link, "SELECT sSource, COUNT(*) c FROM tblcandidate WHERE dDeletedAt IS NULL AND sSource IS NOT NULL AND sSource <> '' GROUP BY sSource ORDER BY c DESC");
    while ($row = mysqli_fetch_assoc($r)) { $leadSourceRows[] = $row; }
    $stats['leadSourceBreakdown'] = $leadSourceRows;

    $monthlyByYm = [];
    $r = mysqli_query($link, "SELECT DATE_FORMAT(dJoiningDate, '%Y-%m') ym, COUNT(*) placements, COALESCE(SUM(dRecAmount),0) received
                               FROM tblplacement
                               WHERE dDeletedAt IS NULL AND dJoiningDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                               GROUP BY ym ORDER BY ym ASC");
    while ($row = mysqli_fetch_assoc($r)) {
        $monthlyByYm[$row['ym']] = ['ym' => $row['ym'], 'openings' => 0, 'placements' => (int) $row['placements'], 'received' => (float) $row['received']];
    }

    $r = mysqli_query($link, "SELECT DATE_FORMAT(dOpenDate, '%Y-%m') ym, COALESCE(SUM(iNoOfVacancy),0) openings
                               FROM tblrequirement
                               WHERE dDeletedAt IS NULL AND dOpenDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                               GROUP BY ym ORDER BY ym ASC");
    while ($row = mysqli_fetch_assoc($r)) {
        if (!isset($monthlyByYm[$row['ym']])) {
            $monthlyByYm[$row['ym']] = ['ym' => $row['ym'], 'openings' => 0, 'placements' => 0, 'received' => 0];
        }
        $monthlyByYm[$row['ym']]['openings'] = (int) $row['openings'];
    }

    ksort($monthlyByYm);
    $stats['monthlyTrend'] = array_values($monthlyByYm);

    $recentReq = [];
    $r = mysqli_query($link, "SELECT r.iReqId, r.sReqNo, r.sPost, r.sStatus, r.dOpenDate, c.sCompanyName
                               FROM tblrequirement r LEFT JOIN tblcompany c ON c.iCompanyId = r.iCompanyId
                               WHERE r.dDeletedAt IS NULL
                               ORDER BY r.iReqId DESC LIMIT 6");
    while ($row = mysqli_fetch_assoc($r)) { $recentReq[] = $row; }
    $stats['recentRequirements'] = $recentReq;

    $recentPlace = [];
    $r = mysqli_query($link, "SELECT p.iPlacementId, cd.sCandidateName, p.sPost, p.sJoiningStatus, p.dJoiningDate, c.sCompanyName
                               FROM tblplacement p
                               LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
                               LEFT JOIN tblcandidate cd ON cd.iCandidateId = p.iCandidateId
                               WHERE p.dDeletedAt IS NULL
                               ORDER BY p.iPlacementId DESC LIMIT 6");
    while ($row = mysqli_fetch_assoc($r)) { $recentPlace[] = $row; }
    $stats['recentPlacements'] = $recentPlace;

    sendResponse("success", "ok", $stats);
}

// =====================================================================
// COMPANIES
// =====================================================================
if ($action === 'fngetlistcompany') {
    $rows = [];
    $r = mysqli_query($link, "SELECT c.*,
        (SELECT COUNT(*) FROM tblrequirement req WHERE req.iCompanyId = c.iCompanyId AND req.dDeletedAt IS NULL) AS reqCount
        FROM tblcompany c WHERE c.dDeletedAt IS NULL ORDER BY c.iCompanyId DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'fngetlisttrashcompany') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $rows = [];
    $r = mysqli_query($link, "SELECT * FROM tblcompany WHERE dDeletedAt IS NOT NULL ORDER BY dDeletedAt DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'restorecompany') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid company id.");
    $stmt = mysqli_prepare($link, "UPDATE tblcompany SET dDeletedAt = NULL WHERE iCompanyId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Company restored successfully.");
}

if ($action === 'permanentlydeletecompany') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid company id.");
    $stmt = mysqli_prepare($link, "DELETE FROM tblcompany WHERE iCompanyId = ? AND dDeletedAt IS NOT NULL");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Company permanently deleted.");
}

if ($action === 'fngetcompanydropdown') {
    $rows = [];
    $r = mysqli_query($link, "SELECT iCompanyId, sCompanyName FROM tblcompany WHERE sStatus='Active' AND dDeletedAt IS NULL ORDER BY sCompanyName ASC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'getcompanybyid') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "SELECT * FROM tblcompany WHERE iCompanyId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    if ($row) sendResponse("success", "ok", $row);
    sendResponse("error", "Company not found.");
}

if ($action === 'getcompanydetails') {
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid company id.");

    $stmt = mysqli_prepare($link, "SELECT * FROM tblcompany WHERE iCompanyId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $company = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$company) sendResponse("error", "Company not found.");

    $stmt = mysqli_prepare($link, "SELECT iReqId, sReqNo, sPost, sType, iNoOfVacancy, sLocation, sEducation, sExperience,
                                           sSalary, dOpenDate, dFollowupDate, sStatus, sRecruiter, sRemark
                                    FROM tblrequirement WHERE iCompanyId = ? AND dDeletedAt IS NULL
                                    ORDER BY iReqId DESC");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $requirements = [];
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $requirements[] = $row; }

    $stmt = mysqli_prepare($link, "SELECT p.iPlacementId, cd.iCandidateId, cd.sCandidateName, p.sPost, p.dJoiningDate,
                                           p.sJoiningStatus, p.dAmount, p.dRecAmount, p.dCtc
                                    FROM tblplacement p LEFT JOIN tblcandidate cd ON cd.iCandidateId = p.iCandidateId
                                    WHERE p.iCompanyId = ? AND p.dDeletedAt IS NULL
                                    ORDER BY p.iPlacementId DESC");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $placements = [];
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $placements[] = $row; }

    // ---- Aggregate stats + chart data server-side, so the page is one round trip ----
    $totalVacancies = 0;
    $openRequirements = 0;
    $closedRequirements = 0;
    $statusCounts = [];
    $postCounts = [];
    $locationCounts = [];
    foreach ($requirements as $r) {
        $totalVacancies += (int) $r['iNoOfVacancy'];
        if (in_array($r['sStatus'], ['Closed by Co.', 'Not Joining'], true)) $closedRequirements++;
        else $openRequirements++;
        $statusCounts[$r['sStatus']] = ($statusCounts[$r['sStatus']] ?? 0) + 1;
        if ($r['sPost']) $postCounts[$r['sPost']] = ($postCounts[$r['sPost']] ?? 0) + 1;
        if ($r['sLocation']) $locationCounts[$r['sLocation']] = ($locationCounts[$r['sLocation']] ?? 0) + 1;
    }

    $totalRevenue = 0;
    $totalInvoiced = 0;
    $monthlyByYm = [];
    foreach ($placements as $p) {
        $totalRevenue += (float) $p['dRecAmount'];
        $totalInvoiced += (float) $p['dAmount'];
        if ($p['dJoiningDate']) {
            $ym = substr($p['dJoiningDate'], 0, 7);
            if (!isset($monthlyByYm[$ym])) $monthlyByYm[$ym] = ['ym' => $ym, 'placements' => 0, 'revenue' => 0];
            $monthlyByYm[$ym]['placements']++;
            $monthlyByYm[$ym]['revenue'] += (float) $p['dRecAmount'];
        }
    }
    ksort($monthlyByYm);

    $toBreakdown = function ($counts, $limit = 8) {
        arsort($counts);
        $counts = array_slice($counts, 0, $limit, true);
        $out = [];
        foreach ($counts as $label => $count) { $out[] = ['label' => $label, 'count' => $count]; }
        return $out;
    };

    $totalPlacements = count($placements);
    $company['stats'] = [
        'totalRequirements'  => count($requirements),
        'openRequirements'   => $openRequirements,
        'closedRequirements' => $closedRequirements,
        'totalVacancies'     => $totalVacancies,
        'totalPlacements'    => $totalPlacements,
        'totalRevenue'       => $totalRevenue,
        'totalInvoiced'      => $totalInvoiced,
        'fillRate'           => $totalVacancies > 0 ? round(($totalPlacements / $totalVacancies) * 100) : 0,
        'statusBreakdown'    => $toBreakdown($statusCounts),
        'postBreakdown'      => $toBreakdown($postCounts),
        'locationBreakdown'  => $toBreakdown($locationCounts),
        'monthlyTrend'       => array_values($monthlyByYm),
    ];
    $company['requirements'] = $requirements;
    $company['placements'] = $placements;

    sendResponse("success", "ok", $company);
}

if ($action === 'addcompany' || $action === 'updatecompany') {
    $name = reqStr($inputData, 'companyName');
    if (!$name) sendResponse("error", "Company name is required.");

    $contactPerson = reqStr($inputData, 'contactPerson');
    $phone = reqStr($inputData, 'phone');
    $email = reqStr($inputData, 'email');
    $industry = reqStr($inputData, 'industry');
    $location = reqStr($inputData, 'location');
    $address = reqStr($inputData, 'address');
    $gstin = reqStr($inputData, 'gstin');
    $status = reqStr($inputData, 'status', 'Active');
    $notes = reqStr($inputData, 'notes');

    if ($action === 'addcompany') {
        $stmt = mysqli_prepare($link, "INSERT INTO tblcompany
            (sCompanyName, sContactPerson, sPhone, sEmail, sIndustry, sLocation, sAddress, sGstin, sStatus, sNotes, iCreatedBy)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "ssssssssssi", $name, $contactPerson, $phone, $email, $industry, $location, $address, $gstin, $status, $notes, $currentUserId);
        mysqli_stmt_execute($stmt);
        sendResponse("success", "Company added successfully.");
    } else {
        $id = reqInt($inputData, 'id', 0);
        if (!$id) sendResponse("error", "Invalid company id.");
        $stmt = mysqli_prepare($link, "UPDATE tblcompany SET sCompanyName=?, sContactPerson=?, sPhone=?, sEmail=?, sIndustry=?, sLocation=?, sAddress=?, sGstin=?, sStatus=?, sNotes=? WHERE iCompanyId=?");
        mysqli_stmt_bind_param($stmt, "ssssssssssi", $name, $contactPerson, $phone, $email, $industry, $location, $address, $gstin, $status, $notes, $id);
        mysqli_stmt_execute($stmt);
        sendResponse("success", "Company updated successfully.");
    }
}

if ($action === 'deletecompany') {
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid company id.");
    $stmt = mysqli_prepare($link, "UPDATE tblcompany SET dDeletedAt = NOW() WHERE iCompanyId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Company moved to trash.");
}

// =====================================================================
// REQUIREMENTS
// =====================================================================
if ($action === 'fngetlistrequirement') {
    $rows = [];
    $r = mysqli_query($link, "SELECT r.*, c.sCompanyName
                               FROM tblrequirement r LEFT JOIN tblcompany c ON c.iCompanyId = r.iCompanyId
                               WHERE r.dDeletedAt IS NULL
                               ORDER BY r.iReqId DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'fngetlisttrashrequirement') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $rows = [];
    $r = mysqli_query($link, "SELECT r.*, c.sCompanyName
                               FROM tblrequirement r LEFT JOIN tblcompany c ON c.iCompanyId = r.iCompanyId
                               WHERE r.dDeletedAt IS NOT NULL
                               ORDER BY r.dDeletedAt DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'restorerequirement') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid requirement id.");
    $stmt = mysqli_prepare($link, "UPDATE tblrequirement SET dDeletedAt = NULL WHERE iReqId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Requirement restored successfully.");
}

if ($action === 'permanentlydeleterequirement') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid requirement id.");
    $stmt = mysqli_prepare($link, "DELETE FROM tblrequirement WHERE iReqId = ? AND dDeletedAt IS NOT NULL");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Requirement permanently deleted.");
}

if ($action === 'getrequirementbyid') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "SELECT * FROM tblrequirement WHERE iReqId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    if ($row) sendResponse("success", "ok", $row);
    sendResponse("error", "Requirement not found.");
}

if ($action === 'addrequirement' || $action === 'updaterequirement') {
    $companyId = reqInt($inputData, 'companyId', null);
    $post = reqStr($inputData, 'post');
    if (!$post) sendResponse("error", "Post / designation is required.");
    if (!$companyId) sendResponse("error", "Please select a company.");

    $noOfVacancy = reqInt($inputData, 'noOfVacancy', 1);
    if ($noOfVacancy < 1) $noOfVacancy = 1;
    $type = reqStr($inputData, 'type', 'NT');
    $type = in_array($type, ['T', 'NT'], true) ? $type : 'NT';
    $location = reqStr($inputData, 'location');
    $education = reqStr($inputData, 'education');
    $experience = reqStr($inputData, 'experience');
    $salary = reqStr($inputData, 'salary');
    $openDate = reqStr($inputData, 'openDate');
    $followupDate = reqStr($inputData, 'followupDate');
    $rank = reqStr($inputData, 'rank');
    $status = reqStr($inputData, 'status', 'Searching');
    $followupBy = reqStr($inputData, 'followupBy');
    $recruiter = reqStr($inputData, 'recruiter');
    $remark = reqStr($inputData, 'remark');

    if ($action === 'addrequirement') {
        $r = mysqli_query($link, "SELECT COALESCE(MAX(iReqId),0)+1 n FROM tblrequirement");
        $nextId = (int) mysqli_fetch_assoc($r)['n'];
        $reqNo = 'REQ-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = mysqli_prepare($link, "INSERT INTO tblrequirement
            (sReqNo, iCompanyId, sPost, iNoOfVacancy, sType, sLocation, sEducation, sExperience, sSalary, dOpenDate, dFollowupDate, sRank, sStatus, sFollowupBy, sRecruiter, sRemark, iCreatedBy)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        bindDynamic($stmt, [
            ['s', $reqNo], ['i', $companyId], ['s', $post], ['i', $noOfVacancy], ['s', $type], ['s', $location],
            ['s', $education], ['s', $experience], ['s', $salary], ['s', $openDate], ['s', $followupDate],
            ['s', $rank], ['s', $status], ['s', $followupBy], ['s', $recruiter], ['s', $remark], ['i', $currentUserId],
        ]);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_errno($stmt)) sendResponse("error", "Could not save requirement. Please check the values entered.");
        sendResponse("success", "Requirement added successfully.", ["reqNo" => $reqNo]);
    } else {
        $id = reqInt($inputData, 'id', 0);
        if (!$id) sendResponse("error", "Invalid requirement id.");
        $stmt = mysqli_prepare($link, "UPDATE tblrequirement SET iCompanyId=?, sPost=?, iNoOfVacancy=?, sType=?, sLocation=?, sEducation=?, sExperience=?, sSalary=?, dOpenDate=?, dFollowupDate=?, sRank=?, sStatus=?, sFollowupBy=?, sRecruiter=?, sRemark=? WHERE iReqId=?");
        bindDynamic($stmt, [
            ['i', $companyId], ['s', $post], ['i', $noOfVacancy], ['s', $type], ['s', $location],
            ['s', $education], ['s', $experience], ['s', $salary], ['s', $openDate], ['s', $followupDate],
            ['s', $rank], ['s', $status], ['s', $followupBy], ['s', $recruiter], ['s', $remark], ['i', $id],
        ]);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_errno($stmt)) sendResponse("error", "Could not save requirement. Please check the values entered.");
        sendResponse("success", "Requirement updated successfully.");
    }
}

if ($action === 'deleterequirement') {
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid requirement id.");
    $stmt = mysqli_prepare($link, "UPDATE tblrequirement SET dDeletedAt = NOW() WHERE iReqId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Requirement moved to trash.");
}

// =====================================================================
// CANDIDATES
// =====================================================================
if ($action === 'fngetlistcandidate') {
    $rows = [];
    $r = mysqli_query($link, "SELECT cd.*,
        (SELECT COUNT(*) FROM tblplacement p WHERE p.iCandidateId = cd.iCandidateId AND p.dDeletedAt IS NULL) AS placementCount
        FROM tblcandidate cd WHERE cd.dDeletedAt IS NULL ORDER BY cd.iCandidateId DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'fngetlisttrashcandidate') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $rows = [];
    $r = mysqli_query($link, "SELECT * FROM tblcandidate WHERE dDeletedAt IS NOT NULL ORDER BY dDeletedAt DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'getcandidatebyid') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "SELECT * FROM tblcandidate WHERE iCandidateId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row) sendResponse("error", "Candidate not found.");

    $stmt = mysqli_prepare($link, "SELECT p.iPlacementId, p.sPost, p.sJoiningStatus, p.dJoiningDate, c.sCompanyName
                                    FROM tblplacement p LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
                                    WHERE p.iCandidateId = ? AND p.dDeletedAt IS NULL ORDER BY p.iPlacementId DESC");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $placements = [];
    $res = mysqli_stmt_get_result($stmt);
    while ($p = mysqli_fetch_assoc($res)) { $placements[] = $p; }
    $row['placements'] = $placements;

    sendResponse("success", "ok", $row);
}

if ($action === 'checkcandidatemobile') {
    $mobile = reqStr($inputData, 'mobile');
    $excludeId = reqInt($inputData, 'excludeId', 0);
    $exists = $mobile ? candidateMobileDuplicateExists($link, $mobile, $excludeId ?: null) : false;
    sendResponse("success", "ok", ["exists" => $exists]);
}

if ($action === 'addcandidate' || $action === 'updatecandidate') {
    $candidateName = reqStr($inputData, 'candidateName');
    if (!$candidateName) sendResponse("error", "Candidate name is required.");

    $id = ($action === 'updatecandidate') ? reqInt($inputData, 'id', 0) : null;
    if ($action === 'updatecandidate' && !$id) sendResponse("error", "Invalid candidate id.");

    $mobile = reqStr($inputData, 'mobile');
    $type = reqStr($inputData, 'type', 'NT');
    $type = in_array($type, ['T', 'NT'], true) ? $type : 'NT';
    $education = reqStr($inputData, 'education');
    $experience = reqStr($inputData, 'experience');
    $currentCompany = reqStr($inputData, 'currentCompany');
    $address = reqStr($inputData, 'address');
    $source = reqStr($inputData, 'source');
    $ref1 = reqStr($inputData, 'ref1');
    $ref2 = reqStr($inputData, 'ref2');
    $remark = reqStr($inputData, 'remark');

    if ($mobile && candidateMobileDuplicateExists($link, $mobile, $id)) {
        sendResponse("error", "A candidate with this mobile number already exists.");
    }

    if ($action === 'addcandidate') {
        $stmt = mysqli_prepare($link, "INSERT INTO tblcandidate
            (sCandidateName, sMobile, sType, sEducation, sExperience, sCurrentCompany, sAddress, sSource, sRef1, sRef2, sRemark, iCreatedBy)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        bindDynamic($stmt, [
            ['s', $candidateName], ['s', $mobile], ['s', $type], ['s', $education], ['s', $experience],
            ['s', $currentCompany], ['s', $address], ['s', $source], ['s', $ref1], ['s', $ref2], ['s', $remark], ['i', $currentUserId],
        ]);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_errno($stmt)) {
            $msg = (mysqli_stmt_errno($stmt) === 1062)
                ? "A candidate with this mobile number already exists."
                : "Could not save candidate. Please check the values entered.";
            sendResponse("error", $msg);
        }
        sendResponse("success", "Candidate added successfully.", ["id" => mysqli_insert_id($link)]);
    } else {
        $stmt = mysqli_prepare($link, "UPDATE tblcandidate SET
            sCandidateName=?, sMobile=?, sType=?, sEducation=?, sExperience=?, sCurrentCompany=?, sAddress=?, sSource=?, sRef1=?, sRef2=?, sRemark=?
            WHERE iCandidateId=?");
        bindDynamic($stmt, [
            ['s', $candidateName], ['s', $mobile], ['s', $type], ['s', $education], ['s', $experience],
            ['s', $currentCompany], ['s', $address], ['s', $source], ['s', $ref1], ['s', $ref2], ['s', $remark], ['i', $id],
        ]);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_errno($stmt)) {
            $msg = (mysqli_stmt_errno($stmt) === 1062)
                ? "A candidate with this mobile number already exists."
                : "Could not save candidate. Please check the values entered.";
            sendResponse("error", $msg);
        }
        sendResponse("success", "Candidate updated successfully.");
    }
}

if ($action === 'uploadresume') {
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Save the candidate before uploading a resume.");
    if (empty($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        sendResponse("error", "Please choose a resume file to upload.");
    }
    $file = $_FILES['resume'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
        sendResponse("error", "Only PDF, DOC, or DOCX files are allowed.");
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        sendResponse("error", "Resume file must be under 5 MB.");
    }

    $stmt = mysqli_prepare($link, "SELECT sResumePath FROM tblcandidate WHERE iCandidateId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$existing) sendResponse("error", "Candidate not found.");

    $uploadDir = __DIR__ . '/uploads/resumes/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $storedName = 'resume_' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $storedName)) {
        sendResponse("error", "Could not save the uploaded file.");
    }

    $stmt = mysqli_prepare($link, "UPDATE tblcandidate SET sResumePath = ? WHERE iCandidateId = ?");
    mysqli_stmt_bind_param($stmt, "si", $storedName, $id);
    mysqli_stmt_execute($stmt);

    $oldPath = $existing['sResumePath'];
    if ($oldPath && $oldPath !== $storedName) {
        $oldFull = $uploadDir . basename($oldPath);
        if (is_file($oldFull)) @unlink($oldFull);
    }

    sendResponse("success", "Resume uploaded successfully.", ["path" => $storedName]);
}

if ($action === 'deleteresume') {
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid candidate id.");
    $stmt = mysqli_prepare($link, "SELECT sResumePath FROM tblcandidate WHERE iCandidateId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row && $row['sResumePath']) {
        $full = __DIR__ . '/uploads/resumes/' . basename($row['sResumePath']);
        if (is_file($full)) @unlink($full);
    }
    $stmt = mysqli_prepare($link, "UPDATE tblcandidate SET sResumePath = NULL WHERE iCandidateId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Resume removed.");
}

if ($action === 'deletecandidate') {
    if (!$isAdmin && !$isTeamLeader) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid candidate id.");
    // Soft delete only — placements linked to this candidate keep working
    // (the join just won't show the candidate under the active list).
    $stmt = mysqli_prepare($link, "UPDATE tblcandidate SET dDeletedAt = NOW() WHERE iCandidateId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Candidate moved to trash.");
}

if ($action === 'restorecandidate') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid candidate id.");
    $stmt = mysqli_prepare($link, "UPDATE tblcandidate SET dDeletedAt = NULL WHERE iCandidateId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Candidate restored successfully.");
}

if ($action === 'permanentlydeletecandidate') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid candidate id.");
    $stmt = mysqli_prepare($link, "SELECT sResumePath FROM tblcandidate WHERE iCandidateId = ? AND dDeletedAt IS NOT NULL");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row) sendResponse("error", "Candidate not found in trash.");
    if ($row['sResumePath']) {
        $full = __DIR__ . '/uploads/resumes/' . basename($row['sResumePath']);
        if (is_file($full)) @unlink($full);
    }
    $stmt = mysqli_prepare($link, "DELETE FROM tblcandidate WHERE iCandidateId = ? AND dDeletedAt IS NOT NULL");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Candidate permanently deleted.");
}

// =====================================================================
// BULK CANDIDATE IMPORT (Excel)
// =====================================================================
if ($action === 'importcandidates') {
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        sendResponse("error", "Please choose an Excel (.xlsx) file to upload.");
    }
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'xlsx') {
        sendResponse("error", "Only .xlsx Excel files are supported. Please use the downloadable template.");
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        sendResponse("error", "File must be under 5 MB.");
    }

    try {
        $rows = xlsx_read_first_sheet($file['tmp_name']);
    } catch (Throwable $e) {
        sendResponse("error", $e->getMessage());
    }
    if (count($rows) === 0) {
        sendResponse("error", "The uploaded file is empty.");
    }

    $expectedHeaders = [
        'Candidate Name', 'Mobile Number', 'Type (T/NT)',
        'Education', 'Experience', 'Current Company', 'Address', 'Source', 'Remark',
    ];
    $headerRow = array_map(function ($h) { return trim((string) $h); }, $rows[0]);
    $colIndex = [];
    foreach ($expectedHeaders as $name) {
        $idx = array_search($name, $headerRow, true);
        $colIndex[$name] = ($idx === false) ? null : $idx;
    }
    if ($colIndex['Candidate Name'] === null || $colIndex['Mobile Number'] === null) {
        sendResponse("error", "This file doesn't match the candidate template. Please download the template and try again.");
    }

    $dataRows = array_slice($rows, 1);
    $maxRows = 2000;
    if (count($dataRows) > $maxRows) {
        sendResponse("error", "This file has too many rows. Please split it into batches of $maxRows or fewer.");
    }

    $getCell = function ($row, $key) use ($colIndex) {
        $idx = $colIndex[$key];
        return ($idx !== null && isset($row[$idx])) ? trim((string) $row[$idx]) : '';
    };

    $existingMobiles = [];
    $mr = mysqli_query($link, "SELECT sMobile FROM tblcandidate WHERE sMobile IS NOT NULL AND sMobile <> ''");
    while ($mrow = mysqli_fetch_assoc($mr)) { $existingMobiles[$mrow['sMobile']] = true; }

    $seenMobilesInFile = [];
    $validRows = [];
    $rowErrors = [];
    $emptyRowCount = 0;
    $duplicateCount = 0;
    $failedCount = 0;

    foreach ($dataRows as $i => $row) {
        $excelRowNumber = $i + 2; // header occupies row 1
        $allBlank = true;
        foreach ($row as $cell) { if (trim((string) $cell) !== '') { $allBlank = false; break; } }
        if ($allBlank) {
            $emptyRowCount++;
            $rowErrors[] = ['row' => $excelRowNumber, 'errors' => ['Empty row — skipped.']];
            continue;
        }

        $errors = [];
        $name = $getCell($row, 'Candidate Name');
        if ($name === '') $errors[] = 'Candidate Name is required.';

        $mobileRaw = $getCell($row, 'Mobile Number');
        $mobile = $mobileRaw !== '' ? $mobileRaw : null;
        $isDuplicate = false;
        if ($mobile !== null) {
            if (!preg_match('/^[0-9+\-\s()]{6,20}$/', $mobile)) {
                $errors[] = 'Mobile Number looks invalid.';
            } elseif (isset($existingMobiles[$mobile])) {
                $errors[] = 'A candidate with this mobile number already exists.';
                $isDuplicate = true;
            } elseif (isset($seenMobilesInFile[$mobile])) {
                $errors[] = 'Duplicate mobile number — also appears in row ' . $seenMobilesInFile[$mobile] . ' of this file.';
                $isDuplicate = true;
            }
        }

        $type = strtoupper($getCell($row, 'Type (T/NT)'));
        if ($type === '') $type = 'NT';
        if (!in_array($type, ['T', 'NT'], true)) { $errors[] = "Type must be T or NT (got '$type')."; }

        if (!empty($errors)) {
            $failedCount++;
            if ($isDuplicate) $duplicateCount++;
            $rowErrors[] = ['row' => $excelRowNumber, 'errors' => $errors];
            continue;
        }

        if ($mobile !== null) { $seenMobilesInFile[$mobile] = $excelRowNumber; }

        $validRows[] = [
            'candidateName' => $name, 'mobile' => $mobile, 'type' => $type,
            'education' => $getCell($row, 'Education'), 'experience' => $getCell($row, 'Experience'),
            'currentCompany' => $getCell($row, 'Current Company'), 'address' => $getCell($row, 'Address'),
            'source' => $getCell($row, 'Source'), 'remark' => $getCell($row, 'Remark'),
        ];
    }

    $successCount = 0;
    if (!empty($validRows)) {
        mysqli_begin_transaction($link);
        try {
            $stmt = mysqli_prepare($link, "INSERT INTO tblcandidate
                (sCandidateName, sMobile, sType, sEducation, sExperience, sCurrentCompany, sAddress, sSource, sRemark, iCreatedBy)
                VALUES (?,?,?,?,?,?,?,?,?,?)");
            foreach ($validRows as $vr) {
                bindDynamic($stmt, [
                    ['s', $vr['candidateName']], ['s', $vr['mobile']], ['s', $vr['type']],
                    ['s', $vr['education']], ['s', $vr['experience']], ['s', $vr['currentCompany']], ['s', $vr['address']],
                    ['s', $vr['source']], ['s', $vr['remark']], ['i', $currentUserId],
                ]);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException(mysqli_stmt_error($stmt));
                }
                $successCount++;
            }
            mysqli_commit($link);
        } catch (Throwable $e) {
            mysqli_rollback($link);
            sendResponse("error", "Import failed — no candidates were saved. Please try again. (" . $e->getMessage() . ")");
        }
    }

    sendResponse("success", "Import complete.", [
        "totalRows" => count($dataRows),
        "successCount" => $successCount,
        "failedCount" => $failedCount,
        "duplicateCount" => $duplicateCount,
        "emptyRowCount" => $emptyRowCount,
        "rowErrors" => $rowErrors,
    ]);
}

// =====================================================================
// PLACEMENTS
// =====================================================================
if ($action === 'fngetlistplacement') {
    $rows = [];
    $r = mysqli_query($link, "SELECT p.*, c.sCompanyName, cd.sCandidateName, cd.sMobile, cd.sResumePath
                               FROM tblplacement p
                               LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
                               LEFT JOIN tblcandidate cd ON cd.iCandidateId = p.iCandidateId
                               WHERE p.dDeletedAt IS NULL
                               ORDER BY p.iPlacementId DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'fngetlisttrashplacement') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $rows = [];
    $r = mysqli_query($link, "SELECT p.*, c.sCompanyName, cd.sCandidateName, cd.sMobile, cd.sResumePath
                               FROM tblplacement p
                               LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
                               LEFT JOIN tblcandidate cd ON cd.iCandidateId = p.iCandidateId
                               WHERE p.dDeletedAt IS NOT NULL
                               ORDER BY p.dDeletedAt DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'fngetlistoutstandinginvoices') {
    $rows = [];
    $r = mysqli_query($link, "SELECT p.iPlacementId, p.sInvoiceNo, p.dInvoiceDate, cd.sCandidateName, p.dAmount, p.dRecAmount, c.sCompanyName
                               FROM tblplacement p
                               LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
                               LEFT JOIN tblcandidate cd ON cd.iCandidateId = p.iCandidateId
                               WHERE p.dDeletedAt IS NULL AND p.dAmount > p.dRecAmount
                               ORDER BY (p.dInvoiceDate IS NULL) ASC, p.dInvoiceDate ASC, p.iPlacementId DESC");
    while ($row = mysqli_fetch_assoc($r)) {
        $pending = (float) $row['dAmount'] - (float) $row['dRecAmount'];
        $row['dPendingAmount'] = $pending;
        $row['sPaymentStatus'] = ((float) $row['dRecAmount'] > 0) ? 'Partially Paid' : 'Unpaid';
        $rows[] = $row;
    }
    sendResponse("success", "ok", $rows);
}

if ($action === 'restoreplacement') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid placement id.");
    $stmt = mysqli_prepare($link, "UPDATE tblplacement SET dDeletedAt = NULL WHERE iPlacementId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Placement restored successfully.");
}

if ($action === 'permanentlydeleteplacement') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid placement id.");
    $stmt = mysqli_prepare($link, "DELETE FROM tblplacement WHERE iPlacementId = ? AND dDeletedAt IS NOT NULL");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Placement permanently deleted.");
}

if ($action === 'getplacementbyid') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "SELECT p.*, cd.sCandidateName, cd.sMobile, cd.sResumePath
                                    FROM tblplacement p LEFT JOIN tblcandidate cd ON cd.iCandidateId = p.iCandidateId
                                    WHERE p.iPlacementId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    if ($row) sendResponse("success", "ok", $row);
    sendResponse("error", "Placement not found.");
}

if ($action === 'addplacement' || $action === 'updateplacement') {
    $candidateId = reqInt($inputData, 'candidateId', 0);
    if (!$candidateId) sendResponse("error", "Please select a candidate.");

    $id = ($action === 'updateplacement') ? reqInt($inputData, 'id', 0) : null;
    if ($action === 'updateplacement' && !$id) sendResponse("error", "Invalid placement id.");

    $stmt = mysqli_prepare($link, "SELECT iCandidateId FROM tblcandidate WHERE iCandidateId = ? AND dDeletedAt IS NULL");
    mysqli_stmt_bind_param($stmt, "i", $candidateId);
    mysqli_stmt_execute($stmt);
    if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 0) {
        sendResponse("error", "Selected candidate was not found.");
    }

    $companyId = reqInt($inputData, 'companyId', null);
    $reqId = reqInt($inputData, 'reqId', null);
    $post = reqStr($inputData, 'post');
    $salary = reqNum($inputData, 'salary', 0);
    $ctc = reqNum($inputData, 'ctc', 0);
    $joiningDate = reqStr($inputData, 'joiningDate');
    $joiningStatus = reqStr($inputData, 'joiningStatus', 'Offer Accepted');
    $workedBy = reqStr($inputData, 'workedBy');
    $remark = reqStr($inputData, 'remark');
    $invoiceDate = reqStr($inputData, 'invoiceDate');
    $invoiceNo = reqStr($inputData, 'invoiceNo');
    $charges = reqNum($inputData, 'charges', 0);
    $cgst = reqNum($inputData, 'cgst', 0);
    $sgst = reqNum($inputData, 'sgst', 0);
    $totalGst = round($cgst + $sgst, 2);
    $amount = round($charges + $totalGst, 2);
    $recAmount = reqNum($inputData, 'recAmount', 0);
    $paymentRecDate = reqStr($inputData, 'paymentRecDate');
    $paymentMode = reqStr($inputData, 'paymentMode');
    $tds = reqNum($inputData, 'tds', 0);
    $ipcInvDate = reqStr($inputData, 'ipcInvDate');
    $ipcInvNo = reqStr($inputData, 'ipcInvNo');
    $ipcInvAmt = reqNum($inputData, 'ipcInvAmt', 0);
    $paymentDate = reqStr($inputData, 'paymentDate');
    $paymentDetails = reqStr($inputData, 'paymentDetails');

    if ($action === 'addplacement') {
        $r = mysqli_query($link, "SELECT COALESCE(MAX(iPlacementId),0)+1 n FROM tblplacement");
        $nextId = (int) mysqli_fetch_assoc($r)['n'];
        $selNo = date('y') . date('m') . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $stmt = mysqli_prepare($link, "INSERT INTO tblplacement
            (sSelectionNo, iCandidateId, iReqId, sPost, iCompanyId, dSalary, dCtc, dJoiningDate, sJoiningStatus,
             sWorkedBy, sRemark, dInvoiceDate, sInvoiceNo, dCharges, dCgst, dSgst, dTotalGst, dAmount, dRecAmount,
             dPaymentRecDate, sPaymentMode, dTds, dIpcInvDate, sIpcInvNo, dIpcInvAmt, dPaymentDate, sPaymentDetails, iCreatedBy)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        bindDynamic($stmt, [
            ['s', $selNo], ['i', $candidateId], ['i', $reqId], ['s', $post],
            ['i', $companyId], ['d', $salary], ['d', $ctc], ['s', $joiningDate], ['s', $joiningStatus],
            ['s', $workedBy], ['s', $remark], ['s', $invoiceDate], ['s', $invoiceNo],
            ['d', $charges], ['d', $cgst], ['d', $sgst], ['d', $totalGst], ['d', $amount], ['d', $recAmount],
            ['s', $paymentRecDate], ['s', $paymentMode], ['d', $tds], ['s', $ipcInvDate], ['s', $ipcInvNo],
            ['d', $ipcInvAmt], ['s', $paymentDate], ['s', $paymentDetails], ['i', $currentUserId],
        ]);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_errno($stmt)) sendResponse("error", "Could not save placement. Please check the values entered.");
        sendResponse("success", "Placement added successfully.", ["selectionNo" => $selNo, "id" => mysqli_insert_id($link)]);
    } else {
        $stmt = mysqli_prepare($link, "UPDATE tblplacement SET
            iCandidateId=?, iReqId=?, sPost=?, iCompanyId=?, dSalary=?, dCtc=?, dJoiningDate=?, sJoiningStatus=?,
            sWorkedBy=?, sRemark=?, dInvoiceDate=?, sInvoiceNo=?, dCharges=?, dCgst=?, dSgst=?, dTotalGst=?, dAmount=?, dRecAmount=?,
            dPaymentRecDate=?, sPaymentMode=?, dTds=?, dIpcInvDate=?, sIpcInvNo=?, dIpcInvAmt=?, dPaymentDate=?, sPaymentDetails=?
            WHERE iPlacementId=?");
        bindDynamic($stmt, [
            ['i', $candidateId], ['i', $reqId], ['s', $post], ['i', $companyId],
            ['d', $salary], ['d', $ctc], ['s', $joiningDate], ['s', $joiningStatus],
            ['s', $workedBy], ['s', $remark], ['s', $invoiceDate], ['s', $invoiceNo],
            ['d', $charges], ['d', $cgst], ['d', $sgst], ['d', $totalGst], ['d', $amount], ['d', $recAmount],
            ['s', $paymentRecDate], ['s', $paymentMode], ['d', $tds], ['s', $ipcInvDate], ['s', $ipcInvNo],
            ['d', $ipcInvAmt], ['s', $paymentDate], ['s', $paymentDetails], ['i', $id],
        ]);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_errno($stmt)) sendResponse("error", "Could not save placement. Please check the values entered.");
        sendResponse("success", "Placement updated successfully.");
    }
}

if ($action === 'deleteplacement') {
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid placement id.");
    // Soft delete only — the row is kept until removed from the trash for
    // good (see permanentlydeleteplacement). The candidate's resume is a
    // separate record now and isn't touched by a placement's lifecycle.
    $stmt = mysqli_prepare($link, "UPDATE tblplacement SET dDeletedAt = NOW() WHERE iPlacementId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Placement moved to trash.");
}

// =====================================================================
// USERS — Admin manages everyone; a Team Leader may add/manage only the
// Recruiters that report to them (iManagerId = their own user id).
// =====================================================================
if ($action === 'fngetlistuser') {
    if (!$isAdmin && !$isTeamLeader) sendResponse("error", "Not authorized.");
    $rows = [];
    if ($isAdmin) {
        $r = mysqli_query($link, "SELECT u.iUserid, u.sName, u.sEmail, u.sPhone, u.sRole, u.iManagerId, u.sIs_active, u.sCreatedTimeStamp,
                                          m.sName AS sManagerName
                                   FROM tbluser u LEFT JOIN tbluser m ON m.iUserid = u.iManagerId
                                   WHERE u.dDeletedAt IS NULL
                                   ORDER BY u.iUserid DESC");
        while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    } else {
        $stmt = mysqli_prepare($link, "SELECT iUserid, sName, sEmail, sPhone, sRole, iManagerId, sIs_active, sCreatedTimeStamp
                                        FROM tbluser WHERE iManagerId = ? AND dDeletedAt IS NULL ORDER BY iUserid DESC");
        mysqli_stmt_bind_param($stmt, "i", $currentUserId);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    }
    sendResponse("success", "ok", $rows);
}

if ($action === 'fngetlisttrashuser') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $rows = [];
    $r = mysqli_query($link, "SELECT u.iUserid, u.sName, u.sEmail, u.sPhone, u.sRole, u.iManagerId, u.sIs_active, u.dDeletedAt,
                                      m.sName AS sManagerName
                               FROM tbluser u LEFT JOIN tbluser m ON m.iUserid = u.iManagerId
                               WHERE u.dDeletedAt IS NOT NULL
                               ORDER BY u.dDeletedAt DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'restoreuser') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid user id.");
    $stmt = mysqli_prepare($link, "UPDATE tbluser SET dDeletedAt = NULL WHERE iUserid = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "User restored successfully.");
}

if ($action === 'permanentlydeleteuser') {
    if (!$isAdmin) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid user id.");
    $stmt = mysqli_prepare($link, "DELETE FROM tbluser WHERE iUserid = ? AND dDeletedAt IS NOT NULL");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "User permanently deleted.");
}

if ($action === 'fngetteamleaderdropdown') {
    $rows = [];
    $r = mysqli_query($link, "SELECT iUserid, sName FROM tbluser WHERE sRole = 'Team Leader' AND sIs_active = 1 ORDER BY sName ASC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'getuserbyid') {
    if (!$isAdmin && !$isTeamLeader) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "SELECT iUserid, sName, sEmail, sPhone, sRole, iManagerId, sIs_active FROM tbluser WHERE iUserid = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    if (!$row) sendResponse("error", "User not found.");
    if (!$isAdmin && (int) $row['iManagerId'] !== $currentUserId) sendResponse("error", "Not authorized.");
    sendResponse("success", "ok", $row);
}

// Recruiters/leads/candidates aren't linked to a user by a real foreign key —
// sRecruiter (on requirements) and sWorkedBy (on placements) are free-text
// name fields, same as the existing "Placements by Recruiter" report already
// relies on. tblcandidate.iCreatedBy is the one genuine id-based link.
if ($action === 'getuserdetails') {
    if (!$isAdmin && !$isTeamLeader) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid user id.");

    $stmt = mysqli_prepare($link, "SELECT u.iUserid, u.sName, u.sEmail, u.sPhone, u.sRole, u.iManagerId, u.sIs_active, u.sCreatedTimeStamp,
                                           m.sName AS sManagerName
                                    FROM tbluser u LEFT JOIN tbluser m ON m.iUserid = u.iManagerId
                                    WHERE u.iUserid = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$user) sendResponse("error", "User not found.");

    $isSelf = ((int) $id === $currentUserId);
    $isOwnRecruiter = ((int) $user['iManagerId'] === $currentUserId);
    if (!$isAdmin && !$isSelf && !$isOwnRecruiter) sendResponse("error", "Not authorized.");

    $name = $user['sName'];
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');

    $stmt = mysqli_prepare($link, "SELECT
        (SELECT COUNT(*) FROM tblrequirement WHERE sRecruiter = ? AND dDeletedAt IS NULL) totalLeads,
        (SELECT COUNT(*) FROM tblrequirement WHERE sRecruiter = ? AND dDeletedAt IS NULL AND dOpenDate BETWEEN ? AND ?) monthLeads,
        (SELECT COUNT(*) FROM tblcandidate WHERE iCreatedBy = ? AND dDeletedAt IS NULL) totalCandidates,
        (SELECT COUNT(*) FROM tblcandidate WHERE iCreatedBy = ? AND dDeletedAt IS NULL AND DATE(dCreatedAt) BETWEEN ? AND ?) monthCandidates,
        (SELECT COUNT(*) FROM tblplacement WHERE sWorkedBy = ? AND dDeletedAt IS NULL) totalPlacements,
        (SELECT COUNT(*) FROM tblplacement WHERE sWorkedBy = ? AND dDeletedAt IS NULL AND dJoiningDate BETWEEN ? AND ?) monthPlacements,
        (SELECT COALESCE(SUM(dRecAmount),0) FROM tblplacement WHERE sWorkedBy = ? AND dDeletedAt IS NULL) totalRevenue
    ");
    bindDynamic($stmt, [
        ['s', $name], ['s', $name], ['s', $monthStart], ['s', $monthEnd],
        ['i', $id], ['i', $id], ['s', $monthStart], ['s', $monthEnd],
        ['s', $name], ['s', $name], ['s', $monthStart], ['s', $monthEnd],
        ['s', $name],
    ]);
    mysqli_stmt_execute($stmt);
    $counts = mysqli_stmt_get_result($stmt)->fetch_assoc();

    // ---- 6-month trend (leads / candidates / placements per month) ----
    $trendStart = date('Y-m-01', strtotime('-5 months'));
    $monthly = [];
    for ($i = 0; $i < 6; $i++) {
        $ym = date('Y-m', strtotime("$trendStart +$i months"));
        $monthly[$ym] = ['ym' => $ym, 'leads' => 0, 'candidates' => 0, 'placements' => 0];
    }
    $stmt = mysqli_prepare($link, "SELECT DATE_FORMAT(dOpenDate, '%Y-%m') ym, COUNT(*) c FROM tblrequirement WHERE sRecruiter = ? AND dDeletedAt IS NULL AND dOpenDate >= ? GROUP BY ym");
    mysqli_stmt_bind_param($stmt, "ss", $name, $trendStart);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { if (isset($monthly[$row['ym']])) $monthly[$row['ym']]['leads'] = (int) $row['c']; }

    $stmt = mysqli_prepare($link, "SELECT DATE_FORMAT(dCreatedAt, '%Y-%m') ym, COUNT(*) c FROM tblcandidate WHERE iCreatedBy = ? AND dDeletedAt IS NULL AND dCreatedAt >= ? GROUP BY ym");
    mysqli_stmt_bind_param($stmt, "is", $id, $trendStart);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { if (isset($monthly[$row['ym']])) $monthly[$row['ym']]['candidates'] = (int) $row['c']; }

    $stmt = mysqli_prepare($link, "SELECT DATE_FORMAT(dJoiningDate, '%Y-%m') ym, COUNT(*) c FROM tblplacement WHERE sWorkedBy = ? AND dDeletedAt IS NULL AND dJoiningDate >= ? GROUP BY ym");
    mysqli_stmt_bind_param($stmt, "ss", $name, $trendStart);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { if (isset($monthly[$row['ym']])) $monthly[$row['ym']]['placements'] = (int) $row['c']; }

    $user['stats'] = [
        'totalLeads' => (int) $counts['totalLeads'], 'monthLeads' => (int) $counts['monthLeads'],
        'totalCandidates' => (int) $counts['totalCandidates'], 'monthCandidates' => (int) $counts['monthCandidates'],
        'totalPlacements' => (int) $counts['totalPlacements'], 'monthPlacements' => (int) $counts['monthPlacements'],
        'totalRevenue' => (float) $counts['totalRevenue'],
        'monthlyTrend' => array_values($monthly),
    ];

    // ---- Team members (direct reports — meaningful when viewing a Team Leader) ----
    $team = [];
    $stmt = mysqli_prepare($link, "SELECT iUserid, sName, sRole, sIs_active FROM tbluser WHERE iManagerId = ? AND dDeletedAt IS NULL ORDER BY sName ASC");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $members = [];
    while ($row = mysqli_fetch_assoc($res)) { $members[] = $row; }

    if (!empty($members)) {
        $memberNames = array_column($members, 'sName');
        $memberIds = array_map('intval', array_column($members, 'iUserid'));

        $leadsByName = [];
        $namePairs = array_map(function ($n) { return ['s', $n]; }, $memberNames);
        $namePlaceholders = implode(',', array_fill(0, count($memberNames), '?'));
        $stmt = mysqli_prepare($link, "SELECT sRecruiter, COUNT(*) c FROM tblrequirement WHERE sRecruiter IN ($namePlaceholders) AND dDeletedAt IS NULL GROUP BY sRecruiter");
        bindDynamic($stmt, $namePairs);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) { $leadsByName[$row['sRecruiter']] = (int) $row['c']; }

        $candidatesById = [];
        $idPairs = array_map(function ($i) { return ['i', $i]; }, $memberIds);
        $idPlaceholders = implode(',', array_fill(0, count($memberIds), '?'));
        $stmt = mysqli_prepare($link, "SELECT iCreatedBy, COUNT(*) c FROM tblcandidate WHERE iCreatedBy IN ($idPlaceholders) AND dDeletedAt IS NULL GROUP BY iCreatedBy");
        bindDynamic($stmt, $idPairs);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) { $candidatesById[(int) $row['iCreatedBy']] = (int) $row['c']; }

        $monthLeadsByName = [];
        $stmt = mysqli_prepare($link, "SELECT sRecruiter, COUNT(*) c FROM tblrequirement WHERE sRecruiter IN ($namePlaceholders) AND dDeletedAt IS NULL AND dOpenDate BETWEEN ? AND ? GROUP BY sRecruiter");
        bindDynamic($stmt, array_merge($namePairs, [['s', $monthStart], ['s', $monthEnd]]));
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) { $monthLeadsByName[$row['sRecruiter']] = (int) $row['c']; }

        $monthCandidatesById = [];
        $stmt = mysqli_prepare($link, "SELECT iCreatedBy, COUNT(*) c FROM tblcandidate WHERE iCreatedBy IN ($idPlaceholders) AND dDeletedAt IS NULL AND DATE(dCreatedAt) BETWEEN ? AND ? GROUP BY iCreatedBy");
        bindDynamic($stmt, array_merge($idPairs, [['s', $monthStart], ['s', $monthEnd]]));
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) { $monthCandidatesById[(int) $row['iCreatedBy']] = (int) $row['c']; }

        $monthPlacementsByName = [];
        $stmt = mysqli_prepare($link, "SELECT sWorkedBy, COUNT(*) c FROM tblplacement WHERE sWorkedBy IN ($namePlaceholders) AND dDeletedAt IS NULL AND dJoiningDate BETWEEN ? AND ? GROUP BY sWorkedBy");
        bindDynamic($stmt, array_merge($namePairs, [['s', $monthStart], ['s', $monthEnd]]));
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) { $monthPlacementsByName[$row['sWorkedBy']] = (int) $row['c']; }

        foreach ($members as $m) {
            $mId = (int) $m['iUserid'];
            $mName = $m['sName'];
            $team[] = [
                'iUserid' => $mId,
                'sName' => $mName,
                'sRole' => $m['sRole'],
                'sIs_active' => $m['sIs_active'],
                'totalLeads' => $leadsByName[$mName] ?? 0,
                'totalCandidates' => $candidatesById[$mId] ?? 0,
                'monthActivity' => ($monthLeadsByName[$mName] ?? 0) + ($monthCandidatesById[$mId] ?? 0) + ($monthPlacementsByName[$mName] ?? 0),
            ];
        }
    }
    $user['team'] = $team;

    sendResponse("success", "ok", $user);
}

/** Same visibility rule as getuserdetails: Admin, the user themself, or the
 *  Team Leader that specific recruiter reports to. */
function canManageUserDocs($link, $isAdmin, $currentUserId, $targetUserId) {
    if ($isAdmin) return true;
    if ((int) $targetUserId === (int) $currentUserId) return true;
    $stmt = mysqli_prepare($link, "SELECT iManagerId FROM tbluser WHERE iUserid = ?");
    mysqli_stmt_bind_param($stmt, "i", $targetUserId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row && (int) $row['iManagerId'] === (int) $currentUserId;
}

if ($action === 'fnlistuserdocuments') {
    if (!$isAdmin && !$isTeamLeader) sendResponse("error", "Not authorized.");
    $userId = reqInt($inputData, 'userId', 0);
    if (!$userId) sendResponse("error", "Invalid user id.");
    if (!canManageUserDocs($link, $isAdmin, $currentUserId, $userId)) sendResponse("error", "Not authorized.");

    $stmt = mysqli_prepare($link, "SELECT iDocId, sFileName, sFileType, iFileSize, dUploadedAt FROM tbluserdocument WHERE iUserId = ? ORDER BY dUploadedAt DESC");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $rows = [];
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'uploaduserdocument') {
    if (!$isAdmin && !$isTeamLeader) sendResponse("error", "Not authorized.");
    $userId = reqInt($inputData, 'userId', 0);
    if (!$userId) sendResponse("error", "Save the user before uploading documents.");
    if (!canManageUserDocs($link, $isAdmin, $currentUserId, $userId)) sendResponse("error", "Not authorized.");

    if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        sendResponse("error", "Please choose a file to upload.");
    }
    $file = $_FILES['document'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], true)) {
        sendResponse("error", "Only PDF, DOC, DOCX, JPG or PNG files are allowed.");
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        sendResponse("error", "Each file must be under 5 MB.");
    }

    $stmt = mysqli_prepare($link, "SELECT iUserid FROM tbluser WHERE iUserid = ? AND dDeletedAt IS NULL");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 0) sendResponse("error", "User not found.");

    $uploadDir = __DIR__ . '/uploads/documents/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $storedName = 'doc_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $storedName)) {
        sendResponse("error", "Could not save the uploaded file.");
    }

    $origName = basename($file['name']);
    $stmt = mysqli_prepare($link, "INSERT INTO tbluserdocument (iUserId, sFileName, sStoredPath, sFileType, iFileSize, iUploadedBy) VALUES (?,?,?,?,?,?)");
    bindDynamic($stmt, [
        ['i', $userId], ['s', $origName], ['s', $storedName], ['s', $ext], ['i', (int) $file['size']], ['i', $currentUserId],
    ]);
    mysqli_stmt_execute($stmt);

    sendResponse("success", "Document uploaded successfully.", ["iDocId" => mysqli_insert_id($link), "sFileName" => $origName]);
}

if ($action === 'deleteuserdocument') {
    if (!$isAdmin && !$isTeamLeader) sendResponse("error", "Not authorized.");
    $docId = reqInt($inputData, 'id', 0);
    if (!$docId) sendResponse("error", "Invalid document id.");

    $stmt = mysqli_prepare($link, "SELECT iUserId, sStoredPath FROM tbluserdocument WHERE iDocId = ?");
    mysqli_stmt_bind_param($stmt, "i", $docId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row) sendResponse("error", "Document not found.");
    if (!canManageUserDocs($link, $isAdmin, $currentUserId, $row['iUserId'])) sendResponse("error", "Not authorized.");

    $full = __DIR__ . '/uploads/documents/' . basename($row['sStoredPath']);
    if (is_file($full)) @unlink($full);

    $stmt = mysqli_prepare($link, "DELETE FROM tbluserdocument WHERE iDocId = ?");
    mysqli_stmt_bind_param($stmt, "i", $docId);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Document removed.");
}

if ($action === 'adduser' || $action === 'updateuser') {
    if (!$isAdmin && !$isTeamLeader) sendResponse("error", "Not authorized.");

    $name = reqStr($inputData, 'name');
    $phone = reqStr($inputData, 'phone');
    if (!$name || !$phone) sendResponse("error", "Name and phone are required.");

    $email = reqStr($inputData, 'email');
    $isActive = reqInt($inputData, 'isActive', 1);

    if ($isAdmin) {
        $role = reqStr($inputData, 'role', 'Recruiter');
        $role = in_array($role, ['Admin', 'Team Leader', 'Recruiter'], true) ? $role : 'Recruiter';
        $managerId = $role === 'Recruiter' ? reqInt($inputData, 'managerId', null) : null;
    } else {
        // A Team Leader can only ever create/edit their own Recruiters —
        // role and manager are forced server-side regardless of what's posted,
        // so a Team Leader can't grant themselves or anyone else a higher role.
        $role = 'Recruiter';
        $managerId = $currentUserId;
    }

    if ($action === 'adduser') {
        $password = $inputData['password'] ?? '';
        if (strlen($password) < 6) sendResponse("error", "Password must be at least 6 characters.");
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $chk = mysqli_prepare($link, "SELECT iUserid FROM tbluser WHERE sPhone = ?");
        mysqli_stmt_bind_param($chk, "s", $phone);
        mysqli_stmt_execute($chk);
        if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0) {
            sendResponse("error", "Phone number already registered.");
        }

        $stmt = mysqli_prepare($link, "INSERT INTO tbluser (sName, sEmail, sPhone, sRole, iManagerId, sPassword_hash, sIs_active) VALUES (?,?,?,?,?,?,?)");
        bindDynamic($stmt, [['s', $name], ['s', $email], ['s', $phone], ['s', $role], ['i', $managerId], ['s', $hash], ['i', $isActive]]);
        mysqli_stmt_execute($stmt);
        sendResponse("success", "User added successfully.");
    } else {
        $id = reqInt($inputData, 'id', 0);
        if (!$id) sendResponse("error", "Invalid user id.");

        if (!$isAdmin) {
            $chk = mysqli_prepare($link, "SELECT iManagerId FROM tbluser WHERE iUserid = ?");
            mysqli_stmt_bind_param($chk, "i", $id);
            mysqli_stmt_execute($chk);
            $chkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
            if (!$chkRow || (int) $chkRow['iManagerId'] !== $currentUserId) sendResponse("error", "Not authorized.");
        }

        $stmt = mysqli_prepare($link, "UPDATE tbluser SET sName=?, sEmail=?, sPhone=?, sRole=?, iManagerId=?, sIs_active=? WHERE iUserid=?");
        bindDynamic($stmt, [['s', $name], ['s', $email], ['s', $phone], ['s', $role], ['i', $managerId], ['i', $isActive], ['i', $id]]);
        mysqli_stmt_execute($stmt);

        if (!empty($inputData['password'])) {
            if (strlen($inputData['password']) < 6) sendResponse("error", "Password must be at least 6 characters.");
            $hash = password_hash($inputData['password'], PASSWORD_DEFAULT);
            $stmtP = mysqli_prepare($link, "UPDATE tbluser SET sPassword_hash=? WHERE iUserid=?");
            mysqli_stmt_bind_param($stmtP, "si", $hash, $id);
            mysqli_stmt_execute($stmtP);
        }
        sendResponse("success", "User updated successfully.");
    }
}

if ($action === 'deleteuser') {
    if (!$isAdmin && !$isTeamLeader) sendResponse("error", "Not authorized.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid user id.");
    if ($id === $currentUserId) sendResponse("error", "You cannot delete your own account.");
    if (!$isAdmin) {
        $chk = mysqli_prepare($link, "SELECT iManagerId FROM tbluser WHERE iUserid = ?");
        mysqli_stmt_bind_param($chk, "i", $id);
        mysqli_stmt_execute($chk);
        $chkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
        if (!$chkRow || (int) $chkRow['iManagerId'] !== $currentUserId) sendResponse("error", "Not authorized.");
    }
    $stmt = mysqli_prepare($link, "UPDATE tbluser SET dDeletedAt = NOW() WHERE iUserid = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "User moved to trash.");
}

// =====================================================================
// MASTERS: STATUS
// =====================================================================
if ($action === 'fngetliststatus') {
    $rows = [];
    $r = mysqli_query($link, "SELECT * FROM tblstatus ORDER BY iStatusId ASC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}
if ($action === 'getstatusbyid') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "SELECT * FROM tblstatus WHERE iStatusId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row) sendResponse("success", "ok", $row);
    sendResponse("error", "Status not found.");
}
if ($action === 'addstatus') {
    $s = reqStr($inputData, 'status');
    if (!$s) sendResponse("error", "Status name required.");
    $stmt = mysqli_prepare($link, "INSERT INTO tblstatus (sStatus) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $s);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Status added successfully.");
}
if ($action === 'updatestatus') {
    $id = reqInt($inputData, 'id', 0);
    $s = reqStr($inputData, 'status');
    if (!$id) sendResponse("error", "Invalid status id.");
    if (!$s) sendResponse("error", "Status name required.");
    $stmt = mysqli_prepare($link, "UPDATE tblstatus SET sStatus = ? WHERE iStatusId = ?");
    mysqli_stmt_bind_param($stmt, "si", $s, $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Status updated successfully.");
}
if ($action === 'deletestatus') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "DELETE FROM tblstatus WHERE iStatusId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Status deleted successfully.");
}

// =====================================================================
// MASTERS: SOURCE
// =====================================================================
if ($action === 'fngetlistsource') {
    $rows = [];
    $r = mysqli_query($link, "SELECT * FROM tblsource ORDER BY iSourceId ASC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}
if ($action === 'getsourcebyid') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "SELECT * FROM tblsource WHERE iSourceId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row) sendResponse("success", "ok", $row);
    sendResponse("error", "Source not found.");
}
if ($action === 'addsource') {
    $s = reqStr($inputData, 'source');
    if (!$s) sendResponse("error", "Source name required.");
    $stmt = mysqli_prepare($link, "INSERT INTO tblsource (sSource) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $s);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Source added successfully.");
}
if ($action === 'updatesource') {
    $id = reqInt($inputData, 'id', 0);
    $s = reqStr($inputData, 'source');
    if (!$id) sendResponse("error", "Invalid source id.");
    if (!$s) sendResponse("error", "Source name required.");
    $stmt = mysqli_prepare($link, "UPDATE tblsource SET sSource = ? WHERE iSourceId = ?");
    mysqli_stmt_bind_param($stmt, "si", $s, $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Source updated successfully.");
}
if ($action === 'deletesource') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "DELETE FROM tblsource WHERE iSourceId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Source deleted successfully.");
}

// =====================================================================
// RECRUITERS (sourced from tbluser, role=Recruiter — the standalone
// tblrecruiter master was removed in favor of real user accounts)
// =====================================================================
if ($action === 'fngetlistrecruiter') {
    $rows = [];
    $r = mysqli_query($link, "SELECT iUserid, sName AS sRecruiter FROM tbluser WHERE sRole = 'Recruiter' AND sIs_active = 1 ORDER BY sName ASC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

// =====================================================================
// MASTERS: POST / DESIGNATION
// =====================================================================
if ($action === 'fngetlistpost') {
    $rows = [];
    $r = mysqli_query($link, "SELECT * FROM tblpost ORDER BY sPost ASC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}
if ($action === 'getpostbyid') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "SELECT * FROM tblpost WHERE iPostId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row) sendResponse("success", "ok", $row);
    sendResponse("error", "Post not found.");
}
if ($action === 'addpost') {
    $s = reqStr($inputData, 'post');
    if (!$s) sendResponse("error", "Post name required.");
    $stmt = mysqli_prepare($link, "INSERT INTO tblpost (sPost) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $s);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Post added successfully.");
}
if ($action === 'updatepost') {
    $id = reqInt($inputData, 'id', 0);
    $s = reqStr($inputData, 'post');
    if (!$id) sendResponse("error", "Invalid post id.");
    if (!$s) sendResponse("error", "Post name required.");
    $stmt = mysqli_prepare($link, "UPDATE tblpost SET sPost = ? WHERE iPostId = ?");
    mysqli_stmt_bind_param($stmt, "si", $s, $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Post updated successfully.");
}
if ($action === 'deletepost') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "DELETE FROM tblpost WHERE iPostId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Post deleted successfully.");
}

// =====================================================================
// MASTERS: EDUCATION
// =====================================================================
if ($action === 'fngetlisteducation') {
    $rows = [];
    $r = mysqli_query($link, "SELECT * FROM tbleducation ORDER BY sEducation ASC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}
if ($action === 'geteducationbyid') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "SELECT * FROM tbleducation WHERE iEducationId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row) sendResponse("success", "ok", $row);
    sendResponse("error", "Education not found.");
}
if ($action === 'addeducation') {
    $s = reqStr($inputData, 'education');
    if (!$s) sendResponse("error", "Education name required.");
    $stmt = mysqli_prepare($link, "INSERT INTO tbleducation (sEducation) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $s);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Education added successfully.");
}
if ($action === 'updateeducation') {
    $id = reqInt($inputData, 'id', 0);
    $s = reqStr($inputData, 'education');
    if (!$id) sendResponse("error", "Invalid education id.");
    if (!$s) sendResponse("error", "Education name required.");
    $stmt = mysqli_prepare($link, "UPDATE tbleducation SET sEducation = ? WHERE iEducationId = ?");
    mysqli_stmt_bind_param($stmt, "si", $s, $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Education updated successfully.");
}
if ($action === 'deleteeducation') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "DELETE FROM tbleducation WHERE iEducationId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Education deleted successfully.");
}

// =====================================================================
// REMINDERS
// =====================================================================
if ($action === 'fngetlistreminder') {
    $rows = [];
    $r = mysqli_query($link, "SELECT rr.*, u.sName AS sUserName
                               FROM tblreminders rr LEFT JOIN tbluser u ON u.iUserid = rr.iUserid
                               ORDER BY rr.sDate ASC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}
if ($action === 'addreminder') {
    $desc = reqStr($inputData, 'description');
    $date = reqStr($inputData, 'date');
    if (!$desc || !$date) sendResponse("error", "Description and date are required.");
    $userId = reqInt($inputData, 'userId', $currentUserId);
    $assignedBy = $_SESSION['username'] ?? 'System';

    $stmt = mysqli_prepare($link, "INSERT INTO tblreminders (iUserid, sDescription, sDate, sAssignedBy) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "isss", $userId, $desc, $date, $assignedBy);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Reminder added successfully.");
}
if ($action === 'markreminderdone') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "UPDATE tblreminders SET sStatus = 'Done' WHERE rrid = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Reminder marked done.");
}
if ($action === 'deletereminder') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "DELETE FROM tblreminders WHERE rrid = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Reminder deleted successfully.");
}

// =====================================================================
// CALENDAR
// =====================================================================
if ($action === 'calendar_events') {
    $start = reqStr($inputData, 'start');
    $end = reqStr($inputData, 'end');
    if (!$start || !$end) sendResponse("error", "Missing date range.");

    $events = [];

    // ---- Reminders / follow-up tasks ----
    $stmt = mysqli_prepare($link, "SELECT rrid, sDescription, sDate, sStatus, sAssignedBy
                                    FROM tblreminders WHERE sDate >= ? AND sDate < ?");
    bindDynamic($stmt, [['s', $start], ['s', $end]]);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $events[] = [
            "id" => "reminder-" . $row['rrid'],
            "title" => "⏰ " . $row['sDescription'],
            "start" => $row['sDate'],
            "allDay" => true,
            "color" => $row['sStatus'] === 'Done' ? '#94a3b8' : '#059669',
            "extendedProps" => [
                "type" => "reminder",
                "description" => $row['sDescription'],
                "status" => $row['sStatus'],
                "assignedBy" => $row['sAssignedBy'],
                "recordId" => (int) $row['rrid'],
            ],
        ];
    }

    // ---- Requirement follow-ups ----
    $stmt = mysqli_prepare($link, "SELECT r.iReqId, r.sReqNo, r.sPost, r.sStatus, r.dFollowupDate, r.sRecruiter, c.sCompanyName
                                    FROM tblrequirement r LEFT JOIN tblcompany c ON c.iCompanyId = r.iCompanyId
                                    WHERE r.dFollowupDate >= ? AND r.dFollowupDate < ?");
    bindDynamic($stmt, [['s', $start], ['s', $end]]);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $events[] = [
            "id" => "followup-" . $row['iReqId'],
            "title" => "📋 " . $row['sPost'] . ($row['sCompanyName'] ? ' — ' . $row['sCompanyName'] : ''),
            "start" => $row['dFollowupDate'],
            "allDay" => true,
            "color" => '#9333ea',
            "extendedProps" => [
                "type" => "followup",
                "description" => "Follow up on " . reqNoDisplay($row['sReqNo']) . ' (' . $row['sStatus'] . ')' . ($row['sRecruiter'] ? ' — Recruiter: ' . $row['sRecruiter'] : ''),
                "recordId" => (int) $row['iReqId'],
            ],
        ];
    }

    // ---- Candidate joining dates ----
    $stmt = mysqli_prepare($link, "SELECT p.iPlacementId, cd.sCandidateName, p.sPost, p.sJoiningStatus, p.dJoiningDate, c.sCompanyName
                                    FROM tblplacement p
                                    LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
                                    LEFT JOIN tblcandidate cd ON cd.iCandidateId = p.iCandidateId
                                    WHERE p.dJoiningDate >= ? AND p.dJoiningDate < ?");
    bindDynamic($stmt, [['s', $start], ['s', $end]]);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $candidateLabel = $row['sCandidateName'] ?: 'Candidate';
        $events[] = [
            "id" => "joining-" . $row['iPlacementId'],
            "title" => "🧑\u{200D}💼 " . $candidateLabel . ($row['sCompanyName'] ? ' — ' . $row['sCompanyName'] : ''),
            "start" => $row['dJoiningDate'],
            "allDay" => true,
            "color" => '#0891b2',
            "extendedProps" => [
                "type" => "joining",
                "description" => $row['sPost'] . ' — Status: ' . ($row['sJoiningStatus'] ?: 'Offer Accepted'),
                "recordId" => (int) $row['iPlacementId'],
            ],
        ];
    }

    sendResponse("success", "ok", $events);
}

// =====================================================================
sendResponse("error", "Unknown action: " . htmlspecialchars($action));
