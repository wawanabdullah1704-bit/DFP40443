<?php
session_start();
$tab = $_GET['tab'] ?? '';
$s = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';

if ($tab === 'pending') {
    header("Location: ../jhepp/jhepp_pending.php" . (!empty($s) ? '?' . ltrim($s, '&') : ''));
    exit();
} elseif ($tab === 'approved') {
    header("Location: ../jhepp/jhepp_approved.php" . (!empty($s) ? '?' . ltrim($s, '&') : ''));
    exit();
} elseif ($tab === 'rejected') {
    header("Location: ../jhepp/jhepp_rejected.php" . (!empty($s) ? '?' . ltrim($s, '&') : ''));
    exit();
} else {
    header("Location: ../jhepp/senarai_pendaftaran.php" . (!empty($s) ? '?' . ltrim($s, '&') : ''));
    exit();
}