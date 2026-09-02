<?php
include 'layouts/session.php';
include 'layouts/config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { http_response_code(404); die('Invalid placement id.'); }

$stmt = mysqli_prepare($link, "SELECT sResumePath, sCandidateName FROM tblplacement WHERE iPlacementId = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$row = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$row || !$row['sResumePath']) { http_response_code(404); die('Resume not found.'); }

$path = __DIR__ . '/uploads/resumes/' . basename($row['sResumePath']);
if (!is_file($path)) { http_response_code(404); die('Resume file is missing.'); }

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['sCandidateName'] ?: 'resume') . '.' . $ext;

header('Content-Type: ' . ($mimeMap[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . $safeName . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
