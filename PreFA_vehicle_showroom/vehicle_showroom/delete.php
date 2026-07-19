<?php
session_start();
include 'db.php';

// Strict access control
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Step 1: Fetch the image path before deleting
    $sql = "SELECT image_path FROM vehicles WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // Step 2: Physically delete the image file
        if (!empty($row['image_path']) && file_exists($row['image_path'])) {
            unlink($row['image_path']);
        }
        mysqli_stmt_close($stmt);

        // Step 3: Delete the database record using prepared statement
        $delSql = "DELETE FROM vehicles WHERE id = ?";
        $delStmt = mysqli_prepare($conn, $delSql);
        mysqli_stmt_bind_param($delStmt, "i", $id);
        mysqli_stmt_execute($delStmt);
        mysqli_stmt_close($delStmt);

        header("Location: index.php?msg=deleted");
        exit();
    }
}

header("Location: index.php");
exit();
?>
