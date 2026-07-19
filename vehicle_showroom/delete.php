<?php
session_start();
include("db.php");
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }

global $conn;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Fetch path to safely delete the physical image file
    $stmt = mysqli_prepare($conn, "SELECT image_path FROM vehicles WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if (file_exists($row['image_path'])) {
            unlink($row['image_path']);
        }
    }
    mysqli_stmt_close($stmt);
    
    // Secure Procedural DELETE using Prepared Statements
    $stmt_del = mysqli_prepare($conn, "DELETE FROM vehicles WHERE id=?");
    mysqli_stmt_bind_param($stmt_del, "i", $id);
    mysqli_stmt_execute($stmt_del);
    mysqli_stmt_close($stmt_del);
}
header("Location: index.php");
exit();
?>