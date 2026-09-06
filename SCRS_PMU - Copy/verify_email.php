<?php
require 'db.php';

$status = 'invalid'; // 'success', 'already_verified', 'invalid'
$student_name = '';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);

    // Cari pelajar berdasarkan token pengesahan
    $sql = "SELECT id, full_name, email, email_verified, status FROM students WHERE verification_token = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $student_id = $row['id'];
        $student_name = $row['full_name'];

        // Kemaskini status pengesahan e-mel pelajar
        $update_sql = "UPDATE students SET email_verified = 1, verification_token = NULL WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $student_id);

        if ($update_stmt->execute()) {
            $status = 'success';
        } else {
            $status = 'error';
        }
        $update_stmt->close();
    } else {
        $status = 'invalid';
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengesahan E-mel Pelajar - SCRS PMU</title>
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="neo-style.css">

    <style>
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 16px;
        }

        .confirm-card {
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            padding: 36px 24px;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .status-icon-box {
            font-size: 3.2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            margin-bottom: 20px;
        }
        .status-icon-box.success { background-color: var(--green); color: var(--black); }
        .status-icon-box.error { background-color: var(--pink); color: var(--black); }

        .confirm-title {
            font-size: 1.35rem;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .confirm-desc {
            font-weight: 700;
            color: #444;
            margin-bottom: 22px;
            font-size: 0.9rem;
            line-height: 1.5;
            text-align: left;
            background: #fafafa;
            border: var(--border-thin);
            border-radius: var(--radius-md);
            padding: 14px;
        }
    </style>
</head>
<body>

    <header class="neo-navbar">
        <a href="index.php" class="neo-brand"><i class="bi bi-car-front-fill me-1"></i>SCRS <span>PMU</span></a>
        <a href="index.php" class="neo-btn btn-sm btn-yellow">Log Masuk</a>
    </header>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="confirm-card">
            <?php if ($status === 'success'): ?>
                <div class="status-icon-box success">
                    <i class="bi bi-envelope-check-fill"></i>
                </div>
                <h1 class="confirm-title" style="color: #008800;"><i class="bi bi-patch-check-fill me-1"></i> E-mel Berjaya Disahkan!</h1>
                
                <div class="confirm-desc">
                    <p style="margin-bottom: 8px;">Salam <strong><?php echo htmlspecialchars($student_name); ?></strong>,</p>
                    <p style="margin-bottom: 8px;">Terima kasih kerana mengesahkan e-mel anda. E-mel anda kini telah <strong>disahkan secara rasmi</strong> dalam sistem SCRS PMU.</p>
                    <p style="margin: 0; color: #0055ff; font-weight: 800;"><i class="bi bi-hourglass-split me-1"></i> Langkah Seterusnya: Permohonan pendaftaran anda kini telah dihantar kepada pihak <strong>JHEPP PMU</strong> untuk semakan dokumen dan kelulusan akaun. Anda akan menerima notifikasi e-mel sebaik sahaja akaun anda diluluskan.</p>
                </div>

                <a href="index.php" class="neo-btn btn-green" style="width: 100%;">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Pergi ke Halaman Log Masuk
                </a>

            <?php else: ?>
                <div class="status-icon-box error">
                    <i class="bi bi-x-octagon-fill"></i>
                </div>
                <h1 class="confirm-title" style="color: #d32f2f;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Pautan Tidak Sah</h1>
                
                <div class="confirm-desc">
                    <p style="margin-bottom: 8px;">Pautan pengesahan e-mel ini <strong>tidak sah</strong> atau telah <strong>tamat tempoh / telah digunakan sebelum ini</strong>.</p>
                    <p style="margin: 0;">Sekiranya anda telah mengesahkan e-mel anda sebelum ini, permohonan anda sedang dalam semakan oleh pihak JHEPP. Sila cuba log masuk atau hubungi pihak pengurusan jika memerlukan bantuan.</p>
                </div>

                <a href="index.php" class="neo-btn" style="width: 100%;">
                    <i class="bi bi-arrow-left-circle-fill me-1"></i> Kembali ke Log Masuk
                </a>
            <?php endif; ?>
        </div>
    </main>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

</body>
</html>
