<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pilih Peranan - SCRS PMU</title>
    
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
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky; top: 0; z-index: 1000;
        }
        .neo-nav-left { display: flex; align-items: center; gap: 15px; }
        .menu-toggle-btn { font-size: 2rem; color: var(--black); background: none; border: none; cursor: pointer; transition: var(--transition); }
        .menu-toggle-btn:hover { transform: scale(1.1); }
        .neo-brand { font-size: 1.5rem; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }

        /* SIDEBAR */
        .sidebar-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1005; display: none; opacity: 0; transition: opacity 0.3s;
        }
        .sidebar-overlay.show { display: block; opacity: 1; }

        .sidebar {
            position: fixed; top: 0; left: -300px; width: 280px; height: 100%;
            background-color: var(--bg-color); border-right: var(--border-thick);
            z-index: 1010; transition: left 0.3s ease; display: flex; flex-direction: column;
        }
        .sidebar.open { left: 0; }
        
        .sidebar-header {
            padding: 20px; background-color: var(--yellow); border-bottom: var(--border-thick);
            display: flex; justify-content: space-between; align-items: center;
        }
        .sidebar-header h2 { font-weight: 900; text-transform: uppercase; font-size: 1.2rem; }
        .close-btn { border: 3px solid var(--black); background: var(--white); padding: 5px 10px; font-weight: 900; box-shadow: 2px 2px 0px var(--black); cursor: pointer; }

        .sidebar-nav { padding: 20px; display: flex; flex-direction: column; gap: 10px; }
        .sidebar-link {
            padding: 12px 15px; border: 3px solid transparent; font-weight: 800;
            text-transform: uppercase; display: flex; align-items: center; gap: 15px; transition: var(--transition);
        }
        .sidebar-link.active, .sidebar-link:hover { border: 3px solid var(--black); background: var(--white); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--black); }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 20px;
        }

        .role-container {
            background-color: var(--white);
            border: var(--border-thick);
            box-shadow: 8px 8px 0px var(--black);
            padding: 35px 25px;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .role-title {
            font-size: 2rem;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 30px;
            line-height: 1.2;
        }

        .role-card {
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
            cursor: pointer;
        }
        .role-card:hover { transform: translate(-4px, -4px); box-shadow: 8px 8px 0px var(--black); }
        .role-card:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }

        .role-card.student-card { background-color: var(--yellow); }
        .role-card.provider-card { background-color: var(--green); }

        .role-card i { font-size: 2.5rem; }
        .role-card .label { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; }
        .role-card .title { font-size: 1.4rem; font-weight: 900; text-transform: uppercase; }

        .login-prompt {
            margin-top: 25px;
            font-weight: 800;
            font-size: 0.9rem;
            border-top: 2px dashed var(--black);
            padding-top: 15px;
        }

        footer {
            background-color: var(--yellow);
            border-top: var(--border-thick);
            padding: 20px;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: auto;
        }

        @media (max-width: 480px) {
            .role-container { padding: 25px 15px; }
            .role-title { font-size: 1.5rem; }
            .neo-brand { font-size: 1.2rem; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar"><i class="bi bi-list"></i></button>
            <div class="neo-brand">SCRS PMU</div>
        </div>
        <a href="login.php" class="role-card" style="margin-bottom: 0; padding: 6px 12px; font-size: 0.8rem; background: var(--blue); border-width: 3px; box-shadow: 3px 3px 0px var(--black);">
            Log Masuk
        </a>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Menu Utama</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="login.php" class="sidebar-link"><i class="bi bi-box-arrow-in-right"></i> Log Masuk</a>
            <a href="choose_role.php" class="sidebar-link active"><i class="bi bi-person-plus-fill"></i> Pilih Peranan / Daftar</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        <div class="role-container">
            <h1 class="role-title">Pilih Peranan<br>Pendaftaran Anda</h1>

            <a href="register_student.php" class="role-card student-card">
                <i class="bi bi-mortarboard-fill"></i>
                <span class="label">Daftar Sebagai</span>
                <span class="title">Pelajar</span>
            </a>

            <a href="register_provider.php" class="role-card provider-card">
                <i class="bi bi-car-front-fill"></i>
                <span class="label">Daftar Sebagai</span>
                <span class="title">Penyedia Kereta</span>
            </a>

            <div class="login-prompt">
                Sudah mempunyai akaun? <br>
                <a href="login.php" style="color: #0055ff; font-weight: 900; text-decoration: underline;">Log Masuk Di Sini</a>
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