<?php
// students/delete.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../dashboard.php?msg=deleted");
exit();
?>
