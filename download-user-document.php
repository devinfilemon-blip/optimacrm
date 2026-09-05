<?php
include 'layouts/session.php';
include 'layouts/config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { http_response_code(404); die('Invalid document id.'); }

$currentUserId = $_SESSION['user_id'] ?? 0;
$currentUserRole = $_SESSION['userRole'] ?? 'Recruiter';
$isAdmin = ($currentUserRole === 'Admin');

$stmt = mysqli_prepare($link, "SELECT d.sFileName, d.sStoredPath, d.iUserId, u.iManagerId
                                FROM tbluserdocument d JOIN tbluser u ON u.iUserid = d.iUserId
                                WHERE d.iDocId = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$row = mysqli_stmt_get_result($stmt)->fetch_assoc();
if (!$row) { http_response_code(404); die('Document not found.'); }

// Same visibility rule as the API: Admin, the owner themself, or the Team
// Leader that owner reports to.
$isSelf = ((int) $row['iUserId'] === (int) $currentUserId);
$isOwnRecruiter = ((int) $row['iManagerId'] === (int) $currentUserId);
if (!$isAdmin && !$isSelf && !$isOwnRecruiter) { http_response_code(403); die('Not authorized.'); }

$path = __DIR__ . '/uploads/documents/' . basename($row['sStoredPath']);
if (!is_file($path)) { http_response_code(404); die('File is missing.'); }

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$safeName = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $row['sFileName'] ?: 'document.' . $ext);

header('Content-Type: ' . ($mimeMap[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . $safeName . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
