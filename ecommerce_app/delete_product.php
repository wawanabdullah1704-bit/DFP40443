<?php
require 'db.php';

// Check if ID exists in the URL and is numeric
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = trim($_GET['id']);

    // 1. Retrieve the product's image path so we can physically delete the file
    $sql_select = "SELECT image_path FROM products WHERE id = ?";
    
    if ($stmt_select = mysqli_prepare($conn, $sql_select)) {
        mysqli_stmt_bind_param($stmt_select, "i", $id);
        mysqli_stmt_execute($stmt_select);
        mysqli_stmt_bind_result($stmt_select, $image_path);
        
        // Fetch the result
        if (mysqli_stmt_fetch($stmt_select)) {
            // Execute unlink() to permanently remove the physical image file from the server
            if (!empty($image_path) && file_exists($image_path)) {
                unlink($image_path); 
            }
        }
        mysqli_stmt_close($stmt_select);
    }

    // 2. Execute a DELETE FROM query using a Prepared Statement to wipe the database record
    $sql_delete = "DELETE FROM products WHERE id = ?";
    
    if ($stmt_delete = mysqli_prepare($conn, $sql_delete)) {
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        
        if (mysqli_stmt_execute($stmt_delete)) {
            // Success: Redirect back to the dashboard with a success message in the URL
            header("Location: view_products.php?msg=deleted");
            exit();
        } else {
            echo "Oops! Something went wrong while deleting the record. Please try again.";
        }
        mysqli_stmt_close($stmt_delete);
    }
} else {
    // If someone tries to access this page directly without an ID, send them away
    header("Location: view_products.php");
    exit();
}
?>