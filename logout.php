<?php
session_start();
include 'layouts/config.php';

if (isset($_SESSION['token'])) {
    $stmt = mysqli_prepare($link, "DELETE FROM tbltoken WHERE sToken = ?");
    mysqli_stmt_bind_param($stmt, "s", $_SESSION['token']);
    mysqli_stmt_execute($stmt);
}

$_SESSION = [];
session_destroy();
header("location: auth-login.php");
exit;
