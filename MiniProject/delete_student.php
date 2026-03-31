<?php
session_start();
include 'config/db.php';

// Security: Only Admin can perform deletions
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: manage_students.php?error=UnauthorizedAction");
    exit();
}

// Check if an ID was passed in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Redirect back with a success message
        header("Location: manage_students.php?success=StudentDeleted");
    } else {
        // Redirect back with an error message
        header("Location: manage_students.php?error=DeleteFailed");
    }
    
    $stmt->close();
} else {
    // If no ID is set, just send them back
    header("Location: manage_students.php");
}

$conn->close();
exit();
?>