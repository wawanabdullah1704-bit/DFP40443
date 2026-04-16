<?php
require 'db.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = trim($_GET['id']);

    $sql_delete = "DELETE FROM products WHERE id = ?";
    
    if ($stmt_delete = mysqli_prepare($conn, $sql_delete)) {
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        
        if (mysqli_stmt_execute($stmt_delete)) {
            header("Location: view_products.php?msg=deleted");
            exit();
        } else {
            echo "Oops! Something went wrong while deleting the record. Please try again.";
        }
        mysqli_stmt_close($stmt_delete);
    }
} else {
    header("Location: view_products.php");
    exit();
}
?>