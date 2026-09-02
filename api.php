<?php
session_start();
include 'layouts/config.php';
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
              FROM tbluser WHERE sPhone = ? OR sName = ? LIMIT 1";
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

    $byRecruiter = [];
    $r = mysqli_query($link, "SELECT sWorkedBy, COUNT(*) placements FROM tblplacement
                               WHERE sWorkedBy IS NOT NULL AND sWorkedBy <> '' AND dJoiningDate BETWEEN '$windowStart' AND '$windowEnd'
                               GROUP BY sWorkedBy ORDER BY placements DESC");
    while ($row = mysqli_fetch_assoc($r)) {
        $row['placements'] = (int) $row['placements'];
        $byRecruiter[] = $row;
    }
    $out['byRecruiter'] = topNWithOther($byRecruiter, 'sWorkedBy', ['placements']);

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

    $period = reqStr($inputData, 'period', 'monthly');
    $today = new DateTime();
    switch ($period) {
        case 'weekly':
            $periodStart = (clone $today)->modify('monday this week');
            $periodEnd = (clone $periodStart)->modify('+6 days');
            $periodLabel = 'This Week';
            break;
        case 'quarterly':
            $qStartMonth = intdiv((int) $today->format('n') - 1, 3) * 3 + 1;
            $periodStart = new DateTime($today->format('Y') . '-' . str_pad($qStartMonth, 2, '0', STR_PAD_LEFT) . '-01');
            $periodEnd = (clone $periodStart)->modify('+3 months')->modify('-1 day');
            $periodLabel = 'This Quarter';
            break;
        case 'yearly':
            $periodStart = new DateTime($today->format('Y') . '-01-01');
            $periodEnd = new DateTime($today->format('Y') . '-12-31');
            $periodLabel = 'This Year';
            break;
        case 'monthly':
        default:
            $period = 'monthly';
            $periodStart = new DateTime($today->format('Y-m') . '-01');
            $periodEnd = (clone $periodStart)->modify('+1 month')->modify('-1 day');
            $periodLabel = 'This Month';
            break;
    }
    $periodStartStr = $periodStart->format('Y-m-d');
    $periodEndStr = $periodEnd->format('Y-m-d');
    $stats['period'] = $period;
    $stats['periodLabel'] = $periodLabel;

    $r = mysqli_query($link, "SELECT COUNT(*) c FROM tblcompany WHERE sStatus = 'Active'");
    $stats['companies'] = (int) mysqli_fetch_assoc($r)['c'];

    $stmt = mysqli_prepare($link, "SELECT COALESCE(SUM(iNoOfVacancy),0) c FROM tblrequirement WHERE sStatus NOT IN ('Closed by Co.', 'Not Joining') AND dOpenDate BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt, "ss", $periodStartStr, $periodEndStr);
    mysqli_stmt_execute($stmt);
    $stats['totalVacancy'] = (int) mysqli_stmt_get_result($stmt)->fetch_assoc()['c'];

    $stmt = mysqli_prepare($link, "SELECT COUNT(*) c FROM tblrequirement WHERE sStatus = 'Searching' AND dOpenDate BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt, "ss", $periodStartStr, $periodEndStr);
    mysqli_stmt_execute($stmt);
    $stats['searching'] = (int) mysqli_stmt_get_result($stmt)->fetch_assoc()['c'];

    $stmt = mysqli_prepare($link, "SELECT COUNT(*) c FROM tblrequirement WHERE sStatus = 'Closed by Co.' AND dOpenDate BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt, "ss", $periodStartStr, $periodEndStr);
    mysqli_stmt_execute($stmt);
    $stats['closed'] = (int) mysqli_stmt_get_result($stmt)->fetch_assoc()['c'];

    $stmt = mysqli_prepare($link, "SELECT COUNT(*) c FROM tblrequirement WHERE sStatus = 'Selected' AND dOpenDate BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt, "ss", $periodStartStr, $periodEndStr);
    mysqli_stmt_execute($stmt);
    $stats['selected'] = (int) mysqli_stmt_get_result($stmt)->fetch_assoc()['c'];

    $stmt = mysqli_prepare($link, "SELECT COUNT(*) c FROM tblplacement WHERE dJoiningDate BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt, "ss", $periodStartStr, $periodEndStr);
    mysqli_stmt_execute($stmt);
    $stats['placementsThisMonth'] = (int) mysqli_stmt_get_result($stmt)->fetch_assoc()['c'];

    $stmt = mysqli_prepare($link, "SELECT COALESCE(SUM(iNoOfVacancy),0) c FROM tblrequirement WHERE dOpenDate BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt, "ss", $periodStartStr, $periodEndStr);
    mysqli_stmt_execute($stmt);
    $stats['openingsThisMonth'] = (int) mysqli_stmt_get_result($stmt)->fetch_assoc()['c'];

    $stmt = mysqli_prepare($link, "SELECT COALESCE(SUM(dRecAmount),0) s FROM tblplacement WHERE dPaymentRecDate BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt, "ss", $periodStartStr, $periodEndStr);
    mysqli_stmt_execute($stmt);
    $stats['revenueThisMonth'] = (float) mysqli_stmt_get_result($stmt)->fetch_assoc()['s'];

    $r = mysqli_query($link, "SELECT COALESCE(SUM(dAmount - dRecAmount),0) s FROM tblplacement WHERE dAmount > dRecAmount");
    $stats['pendingReceivables'] = (float) mysqli_fetch_assoc($r)['s'];

    $r = mysqli_query($link, "SELECT COUNT(*) c FROM tblreminders WHERE sStatus = 'Pending' AND sDate <= CURDATE()");
    $stats['dueReminders'] = (int) mysqli_fetch_assoc($r)['c'];

    $statusCounts = [];
    $r = mysqli_query($link, "SELECT sStatus, COUNT(*) c FROM tblrequirement GROUP BY sStatus");
    while ($row = mysqli_fetch_assoc($r)) { $statusCounts[$row['sStatus']] = (int) $row['c']; }

    $stats['statusBoard'] = [
        ['label' => 'Total Vacancy',      'count' => $stats['totalVacancy']],
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
    $r = mysqli_query($link, "SELECT sSource, COUNT(*) c FROM tblplacement WHERE sSource IS NOT NULL AND sSource <> '' GROUP BY sSource ORDER BY c DESC");
    while ($row = mysqli_fetch_assoc($r)) { $leadSourceRows[] = $row; }
    $stats['leadSourceBreakdown'] = $leadSourceRows;

    $monthlyByYm = [];
    $r = mysqli_query($link, "SELECT DATE_FORMAT(dJoiningDate, '%Y-%m') ym, COUNT(*) placements, COALESCE(SUM(dRecAmount),0) received
                               FROM tblplacement
                               WHERE dJoiningDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                               GROUP BY ym ORDER BY ym ASC");
    while ($row = mysqli_fetch_assoc($r)) {
        $monthlyByYm[$row['ym']] = ['ym' => $row['ym'], 'openings' => 0, 'placements' => (int) $row['placements'], 'received' => (float) $row['received']];
    }

    $r = mysqli_query($link, "SELECT DATE_FORMAT(dOpenDate, '%Y-%m') ym, COALESCE(SUM(iNoOfVacancy),0) openings
                               FROM tblrequirement
                               WHERE dOpenDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
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
                               ORDER BY r.iReqId DESC LIMIT 6");
    while ($row = mysqli_fetch_assoc($r)) { $recentReq[] = $row; }
    $stats['recentRequirements'] = $recentReq;

    $recentPlace = [];
    $r = mysqli_query($link, "SELECT p.iPlacementId, p.sCandidateName, p.sPost, p.sJoiningStatus, p.dJoiningDate, c.sCompanyName
                               FROM tblplacement p LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
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
        (SELECT COUNT(*) FROM tblrequirement req WHERE req.iCompanyId = c.iCompanyId) AS reqCount
        FROM tblcompany c ORDER BY c.iCompanyId DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'fngetcompanydropdown') {
    $rows = [];
    $r = mysqli_query($link, "SELECT iCompanyId, sCompanyName FROM tblcompany WHERE sStatus='Active' ORDER BY sCompanyName ASC");
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
    $stmt = mysqli_prepare($link, "DELETE FROM tblcompany WHERE iCompanyId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Company deleted successfully.");
}

// =====================================================================
// REQUIREMENTS
// =====================================================================
if ($action === 'fngetlistrequirement') {
    $rows = [];
    $r = mysqli_query($link, "SELECT r.*, c.sCompanyName
                               FROM tblrequirement r LEFT JOIN tblcompany c ON c.iCompanyId = r.iCompanyId
                               ORDER BY r.iReqId DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
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
    $stmt = mysqli_prepare($link, "DELETE FROM tblrequirement WHERE iReqId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Requirement deleted successfully.");
}

// =====================================================================
// PLACEMENTS
// =====================================================================
if ($action === 'fngetlistplacement') {
    $rows = [];
    $r = mysqli_query($link, "SELECT p.*, c.sCompanyName
                               FROM tblplacement p LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
                               ORDER BY p.iPlacementId DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'getplacementbyid') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "SELECT * FROM tblplacement WHERE iPlacementId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    if ($row) sendResponse("success", "ok", $row);
    sendResponse("error", "Placement not found.");
}

if ($action === 'addplacement' || $action === 'updateplacement') {
    $candidateName = reqStr($inputData, 'candidateName');
    if (!$candidateName) sendResponse("error", "Candidate name is required.");

    $companyId = reqInt($inputData, 'companyId', null);
    $reqId = reqInt($inputData, 'reqId', null);
    $type = reqStr($inputData, 'type', 'NT');
    $type = in_array($type, ['T', 'NT'], true) ? $type : 'NT';
    $mobile = reqStr($inputData, 'mobile');
    $post = reqStr($inputData, 'post');
    $salary = reqNum($inputData, 'salary', 0);
    $joiningDate = reqStr($inputData, 'joiningDate');
    $joiningStatus = reqStr($inputData, 'joiningStatus', 'Pending');
    $workedBy = reqStr($inputData, 'workedBy');
    $source = reqStr($inputData, 'source');
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
    $ref1 = reqStr($inputData, 'ref1');
    $ref2 = reqStr($inputData, 'ref2');

    if ($action === 'addplacement') {
        $r = mysqli_query($link, "SELECT COALESCE(MAX(iPlacementId),0)+1 n FROM tblplacement");
        $nextId = (int) mysqli_fetch_assoc($r)['n'];
        $selNo = date('y') . date('m') . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $stmt = mysqli_prepare($link, "INSERT INTO tblplacement
            (sSelectionNo, iReqId, sType, sCandidateName, sMobile, sPost, iCompanyId, dSalary, dJoiningDate, sJoiningStatus,
             sWorkedBy, sSource, sRemark, dInvoiceDate, sInvoiceNo, dCharges, dCgst, dSgst, dTotalGst, dAmount, dRecAmount,
             dPaymentRecDate, sPaymentMode, dTds, dIpcInvDate, sIpcInvNo, dIpcInvAmt, dPaymentDate, sPaymentDetails, sRef1, sRef2, iCreatedBy)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        bindDynamic($stmt, [
            ['s', $selNo], ['i', $reqId], ['s', $type], ['s', $candidateName], ['s', $mobile], ['s', $post],
            ['i', $companyId], ['d', $salary], ['s', $joiningDate], ['s', $joiningStatus],
            ['s', $workedBy], ['s', $source], ['s', $remark], ['s', $invoiceDate], ['s', $invoiceNo],
            ['d', $charges], ['d', $cgst], ['d', $sgst], ['d', $totalGst], ['d', $amount], ['d', $recAmount],
            ['s', $paymentRecDate], ['s', $paymentMode], ['d', $tds], ['s', $ipcInvDate], ['s', $ipcInvNo],
            ['d', $ipcInvAmt], ['s', $paymentDate], ['s', $paymentDetails], ['s', $ref1], ['s', $ref2], ['i', $currentUserId],
        ]);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_errno($stmt)) sendResponse("error", "Could not save placement. Please check the values entered.");
        sendResponse("success", "Placement added successfully.", ["selectionNo" => $selNo, "id" => mysqli_insert_id($link)]);
    } else {
        $id = reqInt($inputData, 'id', 0);
        if (!$id) sendResponse("error", "Invalid placement id.");
        $stmt = mysqli_prepare($link, "UPDATE tblplacement SET
            iReqId=?, sType=?, sCandidateName=?, sMobile=?, sPost=?, iCompanyId=?, dSalary=?, dJoiningDate=?, sJoiningStatus=?,
            sWorkedBy=?, sSource=?, sRemark=?, dInvoiceDate=?, sInvoiceNo=?, dCharges=?, dCgst=?, dSgst=?, dTotalGst=?, dAmount=?, dRecAmount=?,
            dPaymentRecDate=?, sPaymentMode=?, dTds=?, dIpcInvDate=?, sIpcInvNo=?, dIpcInvAmt=?, dPaymentDate=?, sPaymentDetails=?, sRef1=?, sRef2=?
            WHERE iPlacementId=?");
        bindDynamic($stmt, [
            ['i', $reqId], ['s', $type], ['s', $candidateName], ['s', $mobile], ['s', $post], ['i', $companyId],
            ['d', $salary], ['s', $joiningDate], ['s', $joiningStatus],
            ['s', $workedBy], ['s', $source], ['s', $remark], ['s', $invoiceDate], ['s', $invoiceNo],
            ['d', $charges], ['d', $cgst], ['d', $sgst], ['d', $totalGst], ['d', $amount], ['d', $recAmount],
            ['s', $paymentRecDate], ['s', $paymentMode], ['d', $tds], ['s', $ipcInvDate], ['s', $ipcInvNo],
            ['d', $ipcInvAmt], ['s', $paymentDate], ['s', $paymentDetails], ['s', $ref1], ['s', $ref2], ['i', $id],
        ]);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_errno($stmt)) sendResponse("error", "Could not save placement. Please check the values entered.");
        sendResponse("success", "Placement updated successfully.");
    }
}

if ($action === 'uploadresume') {
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Save the placement before uploading a resume.");
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

    $stmt = mysqli_prepare($link, "SELECT sResumePath FROM tblplacement WHERE iPlacementId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$existing) sendResponse("error", "Placement not found.");

    $uploadDir = __DIR__ . '/uploads/resumes/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $storedName = 'resume_' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $storedName)) {
        sendResponse("error", "Could not save the uploaded file.");
    }

    $stmt = mysqli_prepare($link, "UPDATE tblplacement SET sResumePath = ? WHERE iPlacementId = ?");
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
    if (!$id) sendResponse("error", "Invalid placement id.");
    $stmt = mysqli_prepare($link, "SELECT sResumePath FROM tblplacement WHERE iPlacementId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row && $row['sResumePath']) {
        $full = __DIR__ . '/uploads/resumes/' . basename($row['sResumePath']);
        if (is_file($full)) @unlink($full);
    }
    $stmt = mysqli_prepare($link, "UPDATE tblplacement SET sResumePath = NULL WHERE iPlacementId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Resume removed.");
}

if ($action === 'deleteplacement') {
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid placement id.");
    $stmt = mysqli_prepare($link, "SELECT sResumePath FROM tblplacement WHERE iPlacementId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row && $row['sResumePath']) {
        $full = __DIR__ . '/uploads/resumes/' . basename($row['sResumePath']);
        if (is_file($full)) @unlink($full);
    }
    $stmt = mysqli_prepare($link, "DELETE FROM tblplacement WHERE iPlacementId = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "Placement deleted successfully.");
}

// =====================================================================
// USERS (Admin only for write actions)
// =====================================================================
if ($action === 'fngetlistuser') {
    $rows = [];
    $r = mysqli_query($link, "SELECT iUserid, sName, sEmail, sPhone, sRole, sIs_active, sCreatedTimeStamp FROM tbluser ORDER BY iUserid DESC");
    while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }
    sendResponse("success", "ok", $rows);
}

if ($action === 'getuserbyid') {
    $id = reqInt($inputData, 'id', 0);
    $stmt = mysqli_prepare($link, "SELECT iUserid, sName, sEmail, sPhone, sRole, sIs_active FROM tbluser WHERE iUserid = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    if ($row) sendResponse("success", "ok", $row);
    sendResponse("error", "User not found.");
}

if ($action === 'adduser' || $action === 'updateuser') {
    if (!$isAdmin) sendResponse("error", "Only Admin can manage users.");

    $name = reqStr($inputData, 'name');
    $phone = reqStr($inputData, 'phone');
    if (!$name || !$phone) sendResponse("error", "Name and phone are required.");

    $email = reqStr($inputData, 'email');
    $role = reqStr($inputData, 'role', 'Recruiter');
    $role = in_array($role, ['Admin', 'Recruiter'], true) ? $role : 'Recruiter';
    $isActive = reqInt($inputData, 'isActive', 1);

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

        $stmt = mysqli_prepare($link, "INSERT INTO tbluser (sName, sEmail, sPhone, sRole, sPassword_hash, sIs_active) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $phone, $role, $hash, $isActive);
        mysqli_stmt_execute($stmt);
        sendResponse("success", "User added successfully.");
    } else {
        $id = reqInt($inputData, 'id', 0);
        if (!$id) sendResponse("error", "Invalid user id.");
        $stmt = mysqli_prepare($link, "UPDATE tbluser SET sName=?, sEmail=?, sPhone=?, sRole=?, sIs_active=? WHERE iUserid=?");
        mysqli_stmt_bind_param($stmt, "ssssii", $name, $email, $phone, $role, $isActive, $id);
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
    if (!$isAdmin) sendResponse("error", "Only Admin can manage users.");
    $id = reqInt($inputData, 'id', 0);
    if (!$id) sendResponse("error", "Invalid user id.");
    if ($id === $currentUserId) sendResponse("error", "You cannot delete your own account.");
    $stmt = mysqli_prepare($link, "DELETE FROM tbluser WHERE iUserid = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    sendResponse("success", "User deleted successfully.");
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
                "description" => "Follow up on " . $row['sReqNo'] . ' (' . $row['sStatus'] . ')' . ($row['sRecruiter'] ? ' — Recruiter: ' . $row['sRecruiter'] : ''),
                "recordId" => (int) $row['iReqId'],
            ],
        ];
    }

    // ---- Candidate joining dates ----
    $stmt = mysqli_prepare($link, "SELECT p.iPlacementId, p.sCandidateName, p.sPost, p.sJoiningStatus, p.dJoiningDate, c.sCompanyName
                                    FROM tblplacement p LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
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
                "description" => $row['sPost'] . ' — Status: ' . ($row['sJoiningStatus'] ?: 'Pending'),
                "recordId" => (int) $row['iPlacementId'],
            ],
        ];
    }

    sendResponse("success", "ok", $events);
}

// =====================================================================
sendResponse("error", "Unknown action: " . htmlspecialchars($action));
