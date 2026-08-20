<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_username = $_SESSION['username'] ?? 'Admin';

// Ambil Statistik Keseluruhan Sistem
// 1. Jumlah Pelajar
$total_students = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];
$approved_students = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'approved'")->fetch_assoc()['total'];
$pending_students = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'pending'")->fetch_assoc()['total'];

// 2. Jumlah Penyedia Kereta
$total_providers = $conn->query("SELECT COUNT(*) AS total FROM providers")->fetch_assoc()['total'];
$approved_providers = $conn->query("SELECT COUNT(*) AS total FROM providers WHERE status = 'approved'")->fetch_assoc()['total'];

// 3. Jumlah Kenderaan
$total_cars = $conn->query("SELECT COUNT(*) AS total FROM cars")->fetch_assoc()['total'];
$available_cars = $conn->query("SELECT COUNT(*) AS total FROM cars WHERE status = 'Available'")->fetch_assoc()['total'];

// 4. Jumlah Tempahan & Pendapatan
$total_bookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'];
$active_bookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status IN ('Pending', 'Approved')")->fetch_assoc()['total'];
$completed_bookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'Completed'")->fetch_assoc()['total'];
$sum_revenue = $conn->query("SELECT SUM(total_price) AS total FROM bookings WHERE status IN ('Approved', 'Completed')")->fetch_assoc()['total'] ?? 0;

// 5. Tempahan Terkini (5 rekod)
$sql_recent_bookings = "SELECT b.*, s.full_name AS student_name, s.username AS student_user,
                               c.car_model, c.car_plate, p.full_name AS provider_name
                        FROM bookings b
                        JOIN students s ON b.student_id = s.id
                        JOIN cars c ON b.car_id = c.id
                        JOIN providers p ON c.provider_id = p.id
                        ORDER BY b.created_at DESC LIMIT 5";
$recent_bookings = $conn->query($sql_recent_bookings);

// 6. Pendaftaran Terkini (5 Pelajar & Penyedia Terkini)
$sql_recent_students = "SELECT id, full_name, username, email, status, created_at FROM students ORDER BY created_at DESC LIMIT 5";
$recent_students = $conn->query($sql_recent_students);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Papan Pemuka Pentadbir (Admin) - SCRS PMU</title>
    
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
            --orange: #ff914d;
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
        button, input, select { font-family: inherit; }

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

        .admin-badge {
            background-color: var(--pink);
            border: 3px solid var(--black);
            box-shadow: 3px 3px 0px var(--black);
            padding: 6px 14px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

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

        .sidebar-nav { padding: 20px; display: flex; flex-direction: column; gap: 8px; overflow-y: auto; }
        .sidebar-link {
            padding: 10px 14px; border: 3px solid transparent; font-weight: 800;
            text-transform: uppercase; display: flex; align-items: center; gap: 12px; transition: var(--transition);
            font-size: 0.9rem;
        }
        .sidebar-link.active, .sidebar-link:hover { border: 3px solid var(--black); background: var(--white); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--black); }
        .sidebar-link.logout-link:hover { background-color: var(--pink); }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 2rem 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .page-header {
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .neo-btn {
            background-color: var(--yellow);
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            font-weight: 900;
            text-transform: uppercase;
            padding: 10px 16px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }
        .btn-green { background-color: var(--green); }
        .btn-blue { background-color: var(--blue); }
        .btn-pink { background-color: var(--pink); }
        .btn-orange { background-color: var(--orange); }

        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: var(--transition);
        }
        .stat-card:hover { transform: translate(-2px, -2px); box-shadow: 8px 8px 0px var(--black); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .stat-title { font-size: 0.85rem; font-weight: 900; text-transform: uppercase; color: #555; }
        .stat-icon {
            font-size: 1.8rem;
            border: 2px solid var(--black);
            padding: 6px 10px;
            box-shadow: 2px 2px 0px var(--black);
        }
        .stat-number { font-size: 2.2rem; font-weight: 900; line-height: 1; margin-bottom: 8px; }
        .stat-sub { font-size: 0.8rem; font-weight: 700; color: #666; }

        /* QUICK ACTIONS GRID */
        .mgmt-shortcuts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .shortcut-card {
            background-color: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 18px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.9rem;
            transition: var(--transition);
        }
        .shortcut-card:hover { transform: translate(-3px, -3px); box-shadow: 8px 8px 0px var(--black); }
        .shortcut-card i { font-size: 1.8rem; }

        /* DATA SECTIONS */
        .section-box {
            background-color: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 20px;
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--black);
            padding-bottom: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .section-title { font-size: 1.2rem; font-weight: 900; text-transform: uppercase; display: flex; align-items: center; gap: 8px; }

        .neo-table-wrapper { overflow-x: auto; }
        .neo-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .neo-table th {
            background-color: var(--yellow);
            border: 2px solid var(--black);
            padding: 10px;
            text-transform: uppercase;
            font-weight: 900;
            font-size: 0.85rem;
        }
        .neo-table td {
            border: 2px solid var(--black);
            padding: 10px;
            vertical-align: middle;
        }
        .neo-table tr:nth-child(even) { background-color: #fafafa; }

        .neo-badge {
            border: 2px solid var(--black);
            padding: 3px 8px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-pending { background-color: var(--yellow); }
        .badge-approved { background-color: var(--green); }
        .badge-completed { background-color: var(--blue); }
        .badge-rejected { background-color: var(--pink); }

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
            .stats-grid { grid-template-columns: 1fr; }
            .mgmt-shortcuts { grid-template-columns: 1fr; }
            .stat-number { font-size: 1.8rem; }
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

        <div class="admin-badge">
            <i class="bi bi-shield-fill-check"></i>
            <span>ADMIN: <?php echo htmlspecialchars($admin_username); ?></span>
        </div>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Panel Admin</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="sidebar-link active"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="admin_students.php" class="sidebar-link"><i class="bi bi-mortarboard-fill"></i> Urus Pelajar</a>
            <a href="admin_providers.php" class="sidebar-link"><i class="bi bi-people-fill"></i> Urus Penyedia</a>
            <a href="admin_cars.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Urus Kenderaan</a>
            <a href="admin_bookings.php" class="sidebar-link"><i class="bi bi-calendar-check-fill"></i> Urus Tempahan</a>
            <a href="logout.php" class="sidebar-link logout-link"><i class="bi bi-box-arrow-right"></i> Log Keluar</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <div class="page-header">
            <div>
                <h1 style="font-size: 1.8rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black); display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-shield-lock-fill"></i> Papan Pemuka Pentadbir
                </h1>
                <p style="font-weight: 700; color: #555; margin: 5px 0 0 0; font-size: 0.95rem;">
                    Selamat kembali, <strong><?php echo htmlspecialchars($admin_username); ?></strong>. Pusat kawalan dan pengurusan sistem SCRS PMU.
                </p>
            </div>
        </div>

        <!-- STATISTIK KAD -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Jumlah Pelajar</span>
                    <i class="bi bi-mortarboard-fill stat-icon" style="background: var(--yellow);"></i>
                </div>
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-sub">
                    <span style="color: #007700; font-weight: 800;"><?php echo $approved_students; ?> Diluluskan</span> &bull; 
                    <span style="color: #d97706; font-weight: 800;"><?php echo $pending_students; ?> Pending</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Penyedia Kereta</span>
                    <i class="bi bi-people-fill stat-icon" style="background: var(--green);"></i>
                </div>
                <div class="stat-number"><?php echo $total_providers; ?></div>
                <div class="stat-sub">
                    <span style="color: #007700; font-weight: 800;"><?php echo $approved_providers; ?> Aktif & Diluluskan</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Jumlah Kenderaan</span>
                    <i class="bi bi-car-front-fill stat-icon" style="background: var(--blue);"></i>
                </div>
                <div class="stat-number"><?php echo $total_cars; ?></div>
                <div class="stat-sub">
                    <span style="color: #007700; font-weight: 800;"><?php echo $available_cars; ?> Sedia Disewa</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Tempahan & Nilai Sewaan</span>
                    <i class="bi bi-cash-stack stat-icon" style="background: var(--orange);"></i>
                </div>
                <div class="stat-number">RM <?php echo number_format($sum_revenue, 2); ?></div>
                <div class="stat-sub">
                    <span style="font-weight: 800;"><?php echo $total_bookings; ?> Tempahan</span> (<?php echo $completed_bookings; ?> Selesai)
                </div>
            </div>
        </div>

        <!-- PINTASAN PENGURUSAN -->
        <h2 style="font-size: 1.2rem; font-weight: 900; text-transform: uppercase; margin-bottom: 15px; border-left: 6px solid var(--black); padding-left: 10px;">
            Modul Pengurusan Sistem
        </h2>

        <div class="mgmt-shortcuts">
            <a href="admin_students.php" class="shortcut-card" style="border-left: 8px solid var(--yellow);">
                <i class="bi bi-mortarboard-fill text-warning"></i>
                <div>
                    <div>Pelajar</div>
                    <span style="font-size: 0.75rem; color: #666; text-transform: none; font-weight: 700;">Tambah, Edit, Padam Pelajar</span>
                </div>
            </a>

            <a href="admin_providers.php" class="shortcut-card" style="border-left: 8px solid var(--green);">
                <i class="bi bi-people-fill text-success"></i>
                <div>
                    <div>Penyedia Kereta</div>
                    <span style="font-size: 0.75rem; color: #666; text-transform: none; font-weight: 700;">Tambah, Edit, Padam Penyedia</span>
                </div>
            </a>

            <a href="admin_cars.php" class="shortcut-card" style="border-left: 8px solid var(--blue);">
                <i class="bi bi-car-front-fill text-info"></i>
                <div>
                    <div>Kenderaan</div>
                    <span style="font-size: 0.75rem; color: #666; text-transform: none; font-weight: 700;">Tambah, Edit, Padam Kereta</span>
                </div>
            </a>

            <a href="admin_bookings.php" class="shortcut-card" style="border-left: 8px solid var(--pink);">
                <i class="bi bi-calendar-check-fill" style="color: #ff007f;"></i>
                <div>
                    <div>Tempahan</div>
                    <span style="font-size: 0.75rem; color: #666; text-transform: none; font-weight: 700;">Cipta, Kemaskini, Padam Rekod</span>
                </div>
            </a>
        </div>

        <!-- TEMPAHAN TERKINI -->
        <div class="section-box">
            <div class="section-header">
                <div class="section-title">
                    <i class="bi bi-clock-history"></i> Tempahan Terkini
                </div>
                <a href="admin_bookings.php" class="neo-btn btn-blue" style="padding: 6px 12px; font-size: 0.8rem;">
                    Lihat Semua Tempahan <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="neo-table-wrapper">
                <table class="neo-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelajar</th>
                            <th>Kenderaan</th>
                            <th>Penyedia</th>
                            <th>Tarikh Ambil & Pulang</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
                            <?php while ($b = $recent_bookings->fetch_assoc()): 
                                $b_status = $b['status'];
                                $badge_cls = 'badge-pending';
                                if ($b_status == 'Approved') $badge_cls = 'badge-approved';
                                elseif ($b_status == 'Completed') $badge_cls = 'badge-completed';
                                elseif ($b_status == 'Rejected') $badge_cls = 'badge-rejected';
                            ?>
                                <tr>
                                    <td>#<?php echo $b['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($b['student_name']); ?></strong><br>
                                        <small style="color: #666;">@<?php echo htmlspecialchars($b['student_user']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($b['car_model']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($b['car_plate']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($b['provider_name']); ?></td>
                                    <td>
                                        <small>Ambil: <?php echo date('d/m/Y h:i A', strtotime($b['start_date'])); ?></small><br>
                                        <small>Pulang: <?php echo date('d/m/Y h:i A', strtotime($b['end_date'])); ?></small>
                                    </td>
                                    <td><strong>RM <?php echo number_format($b['total_price'], 2); ?></strong></td>
                                    <td>
                                        <span class="neo-badge <?php echo $badge_cls; ?>">
                                            <?php echo $b['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px; color: #666;">Tiada rekod tempahan dijumpai.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. PANEL PENTADBIR SISTEM.
    </footer>

    <!-- SKRIP ASLI -->
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
