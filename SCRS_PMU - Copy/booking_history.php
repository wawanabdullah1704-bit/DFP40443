<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan seorang pelajar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['username'];

// Ambil SEMUA rekod tempahan lama / tamat (Completed, Rejected, Cancelled atau sejarah)
$sql_history = "SELECT b.*, c.car_model, c.car_plate, c.car_image, 
                 p.full_name AS provider_name 
                 FROM bookings b
                 JOIN cars c ON b.car_id = c.id
                 JOIN providers p ON c.provider_id = p.id
                 WHERE b.student_id = ?
                 ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql_history);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result_history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sejarah Rekod Tempahan - SCRS PMU</title>
    
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
        ul { list-style: none; }
        button { border: none; background: none; cursor: pointer; font-family: inherit; }

        /* --- NAVBAR --- */
        .neo-navbar {
            background-color: var(--white);
            border-bottom: var(--border-thick);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .neo-nav-left { display: flex; align-items: center; gap: 15px; }
        .menu-toggle-btn { font-size: 2rem; color: var(--black); transition: var(--transition); }
        .menu-toggle-btn:hover { transform: scale(1.1); }

        .neo-brand { font-size: 1.5rem; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }

        /* Dropdown Profil */
        .profile-container { position: relative; }
        .profile-btn {
            background-color: var(--yellow);
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            padding: 8px 15px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }
        .profile-btn:hover { transform: translate(-2px, -2px); box-shadow: var(--shadow-solid); }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background-color: var(--white);
            border: 3px solid var(--black);
            box-shadow: 6px 6px 0px var(--black);
            width: 200px;
            display: none;
            flex-direction: column;
            z-index: 1050;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .dropdown-menu.show { display: flex; }
        .dropdown-menu li { width: 100%; margin: 0; padding: 0; }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 12px 15px;
            font-weight: 800;
            color: var(--black);
            border-bottom: 2px solid var(--black);
            transition: background 0.1s;
            text-decoration: none;
        }
        .dropdown-item:last-child { border-bottom: none; background-color: var(--pink); }
        .dropdown-item:hover { background-color: var(--yellow); }
        .dropdown-item:last-child:hover { background-color: #ff33aa; }

        /* --- SIDEBAR --- */
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
        .close-btn { border: 3px solid var(--black); background: var(--white); padding: 5px 10px; font-weight: 900; box-shadow: 2px 2px 0px var(--black); }

        .sidebar-nav { padding: 20px; display: flex; flex-direction: column; gap: 10px; }
        .sidebar-link {
            padding: 12px 15px; border: 3px solid transparent; font-weight: 800;
            text-transform: uppercase; display: flex; align-items: center; gap: 15px; transition: var(--transition);
        }
        .sidebar-link.active, .sidebar-link:hover { border: 3px solid var(--black); background: var(--white); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--black); }

        /* --- KANDUNGAN UTAMA --- */
        .main-content { flex: 1; padding: 2rem 20px; max-width: 1200px; margin: 0 auto; width: 100%; }

        .section-title {
            display: inline-block;
            background: var(--black);
            color: var(--white);
            font-weight: 900;
            text-transform: uppercase;
            padding: 10px 20px;
            box-shadow: 4px 4px 0px var(--yellow);
            margin-bottom: 20px;
        }

        .neo-table-card {
            background: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 20px;
            overflow-x: auto;
        }

        table.neo-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        table.neo-table th, table.neo-table td {
            padding: 12px 15px;
            border: 2px solid var(--black);
            font-weight: 700;
        }

        table.neo-table th {
            background-color: var(--yellow);
            font-weight: 900;
            text-transform: uppercase;
        }

        .neo-badge {
            border: 2px solid var(--black);
            padding: 4px 8px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.75rem;
            display: inline-block;
        }
        .badge-approved { background-color: var(--green); }
        .badge-pending { background-color: var(--yellow); }
        .badge-rejected { background-color: var(--pink); }
        .badge-completed { background-color: var(--blue); }

        /* FOOTER */
        footer {
            background-color: var(--yellow);
            border-top: var(--border-thick);
            padding: 20px;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .main-content { padding: 1rem 10px; }
            .neo-brand { font-size: 1.2rem; }
            .profile-btn { padding: 6px 10px; font-size: 0.85rem; }
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

        <div class="profile-container">
            <button class="profile-btn" id="profile-toggle">
                <i class="bi bi-person-fill fs-5"></i>
                <span><?php echo htmlspecialchars($student_name); ?></span>
            </button>
            <ul class="dropdown-menu" id="profile-menu">
                <li><a href="edit_profile.php" class="dropdown-item"><i class="bi bi-gear-fill me-2"></i> Edit Profil</a></li>
                <li><a href="login.php" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Log Keluar</a></li>
            </ul>
        </div>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Menu Utama</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link"><i class="bi bi-house-door-fill"></i> Papan Pemuka</a>
            <a href="booking.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Cari & Tempah</a>
            <a href="my_bookings.php" class="sidebar-link"><i class="bi bi-clipboard-check-fill"></i> Status Tempahan</a>
            <a href="booking_history.php" class="sidebar-link active"><i class="bi bi-clock-history"></i> Sejarah Rekod</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        <div class="section-title"><i class="bi bi-clock-history me-2"></i> Sejarah Rekod Tempahan</div>
        <p style="font-weight: 700; color: #555; margin-bottom: 20px;">Senarai lengkap kesemua rekod tempahan kenderaan anda.</p>

        <div class="neo-table-card">
            <?php if ($result_history->num_rows > 0): ?>
                <table class="neo-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Kereta</th>
                            <th>Penyedia</th>
                            <th>Tarikh Ambil</th>
                            <th>Tarikh Pulang</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = $result_history->fetch_assoc()): 
                            $status = $row['status'];
                            $badge_class = 'badge-pending';
                            if ($status == 'Approved') $badge_class = 'badge-approved';
                            else if ($status == 'Completed') $badge_class = 'badge-completed';
                            else if ($status == 'Rejected') $badge_class = 'badge-rejected';
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['car_model']); ?></strong><br><small><?php echo htmlspecialchars($row['car_plate']); ?></small></td>
                                <td><?php echo htmlspecialchars($row['provider_name']); ?></td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['start_date'])); ?></td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['end_date'])); ?></td>
                                <td><strong>RM <?php echo number_format($row['total_price'], 2); ?></strong></td>
                                <td><span class="neo-badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem;">
                    <i class="bi bi-folder-x" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                    <h3 style="font-weight: 900; text-transform: uppercase;">Tiada Sejarah Rekod</h3>
                    <p style="font-weight: 700; color: #666;">Anda belum membuat sebarang tempahan lagi.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- SKRIP ASLI (VANILLA JS) -->
    <script>
        // Dropdown Profil
        const profileToggle = document.getElementById('profile-toggle');
        const profileMenu = document.getElementById('profile-menu');
        
        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!profileToggle.contains(e.target) && !profileMenu.contains(e.target)) {
                    profileMenu.classList.remove('show');
                }
            });
        }

        // Sidebar Offcanvas
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
