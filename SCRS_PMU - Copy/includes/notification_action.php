<?php
// includes/notification_action.php - Pengendali AJAX Notifikasi
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/notifications.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'error' => 'Sesi tidak sah']);
    exit();
}

$user_type = $_SESSION['role'];
$user_id = 0;
if ($user_type === 'student') $user_id = $_SESSION['student_id'] ?? 0;
else if ($user_type === 'provider') $user_id = $_SESSION['provider_id'] ?? 0;
else if ($user_type === 'admin') $user_id = $_SESSION['admin_id'] ?? 0;
else if ($user_type === 'jhepp') $user_id = $_SESSION['jhepp_id'] ?? 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID pengguna tidak sah']);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'mark_all_read') {
    init_notification_system($conn);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_type = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $user_type, $user_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit();
    }
}

if ($action === 'mark_read') {
    $notif_id = (int)($_GET['id'] ?? 0);
    if ($notif_id > 0) {
        init_notification_system($conn);
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_type = ? AND user_id = ?");
        if ($stmt) {
            $stmt->bind_param("isi", $notif_id, $user_type, $user_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
            exit();
        }
    }
}

if ($action === 'get_latest') {
    $unread = get_unread_notifications_count($conn, $user_type, $user_id);
    $list = get_user_notifications($conn, $user_type, $user_id, 6);
    echo json_encode(['success' => true, 'unread' => $unread, 'notifications' => $list]);
    exit();
}

echo json_encode(['success' => false, 'error' => 'Tindakan tidak sah']);
