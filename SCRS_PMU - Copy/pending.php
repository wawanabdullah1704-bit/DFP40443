<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tunggu Pengesahan - SCRS PMU</title>
    
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

        .pending-card {
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            padding: 36px 24px;
            width: 100%;
            max-width: 480px;
            text-align: center;
        }

        .hourglass-icon {
            font-size: 3.2rem;
            color: var(--black);
            background-color: var(--yellow);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
        }

        .pending-title {
            font-size: 1.35rem;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .pending-desc {
            font-weight: 700;
            color: #444;
            margin-bottom: 24px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        @media (max-width: 480px) {
            .pending-card { padding: 24px 16px; }
            .pending-title { font-size: 1.25rem; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar"><i class="bi bi-list"></i></button>
            <a href="index.php" class="neo-brand"><i class="bi bi-car-front-fill me-1"></i>SCRS <span>PMU</span></a>
        </div>
        <a href="index.php" class="neo-btn" style="padding: 6px 12px; font-size: 0.8rem; background: var(--yellow);">Log Masuk</a>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Menu Utama</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="sidebar-link"><i class="bi bi-box-arrow-in-right"></i> Log Masuk</a>
            <a href="choose_role.php" class="sidebar-link"><i class="bi bi-person-plus-fill"></i> Pilih Peranan / Daftar</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="pending-card">
            <?php 
            $type = isset($_GET['type']) ? $_GET['type'] : 'default';
            $email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
            ?>

            <?php if ($type === 'verify_email'): ?>
                <div class="hourglass-icon" style="background-color: var(--blue);">
                    <i class="bi bi-envelope-check-fill"></i>
                </div>
                <h1 class="pending-title"><i class="bi bi-send-check-fill me-1"></i> Sila Sahkan E-mel Anda</h1>
                
                <p class="pending-desc">
                    <strong>Pendaftaran Berjaya Dihantar!</strong><br>
                    Kami telah menghantar satu pautan pengesahan ke alamat e-mel anda <?php echo $email ? "<strong>($email)</strong>" : ""; ?>. 
                    <br><br>
                    <span style="display: block; background: #fafaf5; border: 2px solid var(--black); padding: 10px; text-align: left; font-size: 0.85rem;">
                        <i class="bi bi-info-circle-fill text-primary me-1"></i> <strong>Langkah Seterusnya:</strong>
                        <ol style="margin-left: 18px; margin-top: 5px;">
                            <li>Buka peti masuk e-mel anda (atau semak folder <em>Spam / Junk</em>).</li>
                            <li>Tekan butang <strong>"Sahkan E-mel Saya Sekarang"</strong>.</li>
                            <li>Selepas e-mel disahkan, permohonan anda akan dihantar kepada <strong>JHEPP PMU</strong> untuk kelulusan dokumen.</li>
                        </ol>
                    </span>
                </p>
            <?php else: ?>
                <div class="hourglass-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <h1 class="pending-title"><i class="bi bi-clock-history me-1"></i> Menunggu Kelulusan JHEPP</h1>
                
                <p class="pending-desc">
                    <strong>Status: E-mel Telah Disahkan.</strong><br>
                    Permohonan pendaftaran akaun dan dokumen anda kini sedang dalam semakan oleh pihak <strong>Pentadbir JHEPP PMU</strong>. Kami akan menyemak dokumen yang anda muat naik.
                    <br><br>
                    Notifikasi kelulusan rasmi akan dihantar ke e-mel anda sebaik sahaja akaun anda diluluskan.
                </p>
            <?php endif; ?>

            <a href="index.php" class="neo-btn">
                <i class="bi bi-arrow-left-circle-fill me-1"></i> Kembali ke Log Masuk
            </a>
        </div>
    </main>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- SKRIP ASLI (VANILLA JS) -->
    <script>
        const openSidebarBtn = document.getElementById('open-sidebar');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('show');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        }

        openSidebarBtn.addEventListener('click', openSidebar);
        closeSidebarBtn.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);
    </script>
</body>
</html>