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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700;900&display=swap" rel="stylesheet">

    <!-- CSS NEO-BRUTALISM -->
    <style>
        :root {
            --black: #000000;
            --white: #ffffff;
            --yellow: #ffde59;
            --green: #00e676;
            --blue: #00e5ff;
            --pink: #ff66c4;
            --bg-color: #f4f4f0;
            --border-thick: 4px solid var(--black);
            --shadow-solid: 6px 6px 0px var(--black);
            --shadow-hover: 4px 4px 0px var(--black);
            --shadow-active: 0px 0px 0px var(--black);
            --transition: all 0.15s ease-in-out;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Space Grotesk', sans-serif; }

        body {
            background-color: var(--bg-color);
            background-image: radial-gradient(#ccc 1.5px, transparent 1.5px);
            background-size: 20px 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }

        /* NAVBAR */
        .neo-navbar {
            background-color: var(--white);
            border-bottom: var(--border-thick);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky; top: 0; z-index: 1000;
        }
        .neo-brand { font-size: 1.4rem; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 15px;
        }

        .confirm-card {
            background-color: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 35px 25px;
            width: 100%;
            max-width: 520px;
            text-align: center;
        }

        .status-icon-box {
            font-size: 3.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 90px;
            height: 90px;
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            margin-bottom: 20px;
        }
        .status-icon-box.success { background-color: var(--green); color: var(--black); }
        .status-icon-box.error { background-color: var(--pink); color: var(--black); }

        .confirm-title {
            font-size: 1.4rem;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .confirm-desc {
            font-weight: 700;
            color: #444;
            margin-bottom: 25px;
            font-size: 0.95rem;
            line-height: 1.5;
            text-align: left;
            background: #fafaf5;
            border: 2px solid var(--black);
            padding: 15px;
        }

        .neo-btn {
            background-color: var(--yellow);
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            font-weight: 900;
            text-transform: uppercase;
            padding: 12px 22px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            color: var(--black);
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(2px, 2px); box-shadow: var(--shadow-active); }
        .btn-green { background-color: var(--green); }

        footer {
            background-color: var(--yellow);
            border-top: var(--border-thick);
            padding: 18px;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: auto;
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-brand"><i class="bi bi-shield-check me-2"></i>SCRS PMU</div>
        <a href="index.php" class="neo-btn" style="padding: 6px 14px; font-size: 0.8rem;">Log Masuk</a>
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
