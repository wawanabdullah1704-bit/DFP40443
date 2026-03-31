<?php
// students/delete.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['flash'] = 'Error: You do not have permission to delete records.';
    header("Location: index.php");
    exit;
}
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id === 0) { header("Location: index.php"); exit; }

// Verify record exists - prepared statement
$stmt = $conn->prepare("SELECT id, name FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    $_SESSION['flash'] = 'Error: Student not found.';
    header("Location: index.php");
    exit;
}

// Delete - prepared statement
$stmt2 = $conn->prepare("DELETE FROM students WHERE id = ?");
$stmt2->bind_param("i", $id);

if ($stmt2->execute()) {
    $_SESSION['flash'] = 'Student "' . $student['name'] . '" deleted successfully.';
} else {
    $_SESSION['flash'] = 'Error: Could not delete student. Please try again.';
}
$stmt2->close();

header("Location: index.php");
exit;
?>
