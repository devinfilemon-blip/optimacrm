<?php
session_start();
include 'layouts/config.php';
date_default_timezone_set('Asia/Kolkata');

header('Content-Type: application/json');

function checkTokenExpiration($link) {
    if (!isset($_SESSION['token'])) {
        return json_encode(["status" => "error", "message" => "No token found. Please log in."]);
    }

    $stmt = mysqli_prepare($link, "SELECT sToken, sExpire FROM tbltoken WHERE sToken = ? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $_SESSION['token']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if (time() < strtotime($row['sExpire'])) {
            return json_encode(["status" => "success", "message" => "Token is valid."]);
        }
        $del = mysqli_prepare($link, "DELETE FROM tbltoken WHERE sToken = ?");
        mysqli_stmt_bind_param($del, "s", $_SESSION['token']);
        mysqli_stmt_execute($del);
        return json_encode(["status" => "error", "message" => "Token has expired. Please log in again."]);
    }
    return json_encode(["status" => "error", "message" => "Token not found. Please log in."]);
}

echo checkTokenExpiration($link);
