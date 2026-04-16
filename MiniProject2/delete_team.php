<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $team_id = $_GET['id'];
    $sql_select = "SELECT logo_path FROM teams WHERE id = ?";
    $stmt_select = mysqli_prepare($conn, $sql_select);
    mysqli_stmt_bind_param($stmt_select, "i", $team_id);
    mysqli_stmt_execute($stmt_select);
    $result = mysqli_stmt_get_result($stmt_select);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $logo_path = $row['logo_path'];
        if (file_exists($logo_path) && !empty($logo_path)) {
            unlink($logo_path); 
        }

        $sql_delete = "DELETE FROM teams WHERE id = ?";
        $stmt_delete = mysqli_prepare($conn, $sql_delete);
        mysqli_stmt_bind_param($stmt_delete, "i", $team_id);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
    }
    mysqli_stmt_close($stmt_select);
}
header("Location: dashboard.php");
exit();
?>