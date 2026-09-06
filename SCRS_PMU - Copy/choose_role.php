<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pilih Peranan - SCRS PMU</title>
    
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

        .role-container {
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            padding: 32px 24px;
            width: 100%;
            max-width: 480px;
            text-align: center;
        }

        .role-title {
            font-size: 1.5rem;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 12px;
            line-height: 1.2;
            color: var(--black);
        }

        .role-card {
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 20px;
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            cursor: pointer;
            text-decoration: none;
            color: var(--black);
        }
        .role-card:hover { 
            transform: translate(-3px, -3px); 
            box-shadow: var(--shadow-lg); 
        }
        .role-card:active { 
            transform: translate(2px, 2px); 
            box-shadow: var(--shadow-active); 
        }

        .role-card.student-card { background-color: var(--yellow); }
        .role-card.provider-card { background-color: var(--green); }

        .role-card i { font-size: 2.4rem; }
        .role-card .label { font-size: 0.8rem; font-weight: 800; text-transform: uppercase; }
        .role-card .title { font-size: 1.35rem; font-weight: 900; text-transform: uppercase; }

        .role-badge {
            font-size: 0.75rem; 
            font-weight: 900; 
            background: var(--black); 
            color: var(--white); 
            padding: 3px 10px; 
            margin-top: 6px; 
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: var(--radius-full);
        }

        .login-prompt {
            margin-top: 22px;
            font-weight: 800;
            font-size: 0.875rem;
            border-top: 2px dashed var(--black);
            padding-top: 15px;
        }

        @media (max-width: 480px) {
            .role-container { padding: 24px 16px; }
            .role-title { font-size: 1.35rem; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar" aria-label="Buka Menu"><i class="bi bi-list"></i></button>
            <a href="index.php" class="neo-brand"><i class="bi bi-car-front-fill me-1"></i>SCRS <span>PMU</span></a>
        </div>
        <a href="index.php" class="neo-btn btn-sm btn-blue" style="text-decoration: none;">
            <i class="bi bi-box-arrow-in-right"></i> Log Masuk
        </a>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Menu Utama</h3>
            <button class="close-btn" id="close-sidebar" aria-label="Tutup Menu"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="sidebar-link"><i class="bi bi-box-arrow-in-right"></i> Log Masuk</a>
            <a href="choose_role.php" class="sidebar-link active"><i class="bi bi-person-plus-fill"></i> Pilih Peranan / Daftar</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        <div class="role-container">
            <h1 class="role-title"><i class="bi bi-person-check-fill me-1"></i> Pilih Peranan Anda</h1>
            <p style="font-weight: 700; color: #444; font-size: 0.875rem; margin-bottom: 22px; line-height: 1.45;">
                Sila pilih jenis akaun pendaftaran anda. Pilih <strong>"Pelajar"</strong> untuk membuat tempahan kenderaan, atau <strong>"Penyedia Kereta"</strong> jika anda ingin menyewakan kenderaan kepada pelajar PMU.
            </p>

            <a href="register_student.php" class="role-card student-card">
                <i class="bi bi-mortarboard-fill"></i>
                <span class="label">Daftar Sebagai</span>
                <span class="title">Pelajar</span>
                <span class="role-badge">Tekan Untuk Pilih <i class="bi bi-arrow-right-short"></i></span>
            </a>

            <a href="register_provider.php" class="role-card provider-card">
                <i class="bi bi-car-front-fill"></i>
                <span class="label">Daftar Sebagai</span>
                <span class="title">Penyedia Kereta</span>
                <span class="role-badge">Tekan Untuk Pilih <i class="bi bi-arrow-right-short"></i></span>
            </a>

            <div class="login-prompt">
                Sudah mempunyai akaun? <br>
                <a href="index.php" style="color: #0055ff; font-weight: 900; text-decoration: underline;">Log Masuk Di Sini</a>
            </div>
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
