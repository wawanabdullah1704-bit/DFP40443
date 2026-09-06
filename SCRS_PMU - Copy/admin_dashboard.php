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
                               c.car_brand, c.car_model, c.car_plate, p.full_name AS provider_name
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
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="neo-style.css">

    <style>
        .page-header {
            margin-bottom: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background-color: var(--white) !important;
            border: var(--border-thick) !important;
            border-radius: var(--radius-lg) !important;
            box-shadow: var(--shadow-solid) !important;
            padding: 18px 20px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            justify-content: space-between !important;
            text-align: left !important;
            gap: 0 !important;
            min-height: 135px;
            transition: var(--transition) !important;
        }
        .stat-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow-lg) !important;
        }

        .stat-card-top {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            width: 100% !important;
            margin-bottom: 12px !important;
        }
        .stat-title { 
            font-size: 0.85rem !important; 
            font-weight: 900 !important; 
            text-transform: uppercase !important; 
            color: #555 !important;
            letter-spacing: 0.5px;
            margin: 0 !important;
            line-height: 1.2 !important;
        }
        .stat-icon-badge {
            font-size: 1.35rem !important;
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: var(--border-thin) !important;
            border-radius: var(--radius-md) !important;
            box-shadow: var(--shadow-sm) !important;
            color: var(--black) !important;
            flex-shrink: 0 !important;
        }
        .stat-number { 
            font-size: 2rem !important; 
            font-weight: 900 !important; 
            line-height: 1 !important; 
            margin-bottom: 6px !important; 
            color: var(--black) !important;
            text-align: left !important;
        }
        .stat-sub { 
            font-size: 0.775rem !important; 
            font-weight: 700 !important; 
            color: #666 !important; 
            text-align: left !important;
            line-height: 1.3 !important;
        }

        /* QUICK ACTIONS GRID */
        .mgmt-shortcuts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .shortcut-card {
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 16px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.85rem;
            transition: var(--transition);
            text-decoration: none;
            color: var(--black);
        }
        .shortcut-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow-lg);
        }
        .shortcut-card:active {
            transform: translate(2px, 2px);
            box-shadow: var(--shadow-active);
        }
        .shortcut-icon-box {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border: var(--border-thin);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: var(--black);
        }

        /* DATA SECTIONS */
        .section-box {
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-solid);
            padding: 18px;
            margin-bottom: 24px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2.5px solid var(--black);
            padding-bottom: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .section-title { font-size: 1.1rem; font-weight: 900; text-transform: uppercase; display: flex; align-items: center; gap: 8px; }

        .neo-table-wrapper { overflow-x: auto; }
        .neo-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: var(--border-thin);
            border-radius: var(--radius-md);
            overflow: hidden;
            text-align: left;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .neo-table th {
            background-color: var(--yellow);
            border-bottom: var(--border-thin);
            border-right: var(--border-thin);
            padding: 9px 12px;
            text-transform: uppercase;
            font-weight: 900;
            font-size: 0.8rem;
        }
        .neo-table td {
            border-bottom: var(--border-thin);
            border-right: var(--border-thin);
            padding: 9px 12px;
            vertical-align: middle;
        }
        .neo-table th:last-child, .neo-table td:last-child {
            border-right: none;
        }
        .neo-table tr:last-child td {
            border-bottom: none;
        }
        .neo-table tr:nth-child(even) { background-color: #fafafa; }

        @media (max-width: 768px) {
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
            <button class="menu-toggle-btn" id="open-sidebar" aria-label="Buka Menu"><i class="bi bi-list"></i></button>
            <a href="admin_dashboard.php" class="neo-brand"><i class="bi bi-car-front-fill me-1"></i>SCRS <span>PMU</span></a>
        </div>

        <div class="profile-container">
            <button class="profile-btn" id="profile-toggle">
                <i class="bi bi-person-circle fs-5"></i>
                <span><?php echo htmlspecialchars($admin_username); ?></span>
            </button>
            <ul class="dropdown-menu" id="profile-menu">
                <li><a href="edit_profile.php" class="dropdown-item"><i class="bi bi-gear-fill me-2"></i> Edit Profil</a></li>
                <li><a href="logout.php" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Log Keluar</a></li>
            </ul>
        </div>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Panel Admin</h2>
            <button class="close-btn" id="close-sidebar" aria-label="Tutup Menu"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="sidebar-link active"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="admin_students.php" class="sidebar-link"><i class="bi bi-mortarboard-fill"></i> Urus Pelajar</a>
            <a href="admin_providers.php" class="sidebar-link"><i class="bi bi-people-fill"></i> Urus Penyedia</a>
            <a href="admin_cars.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Urus Kenderaan</a>
            <a href="admin_bookings.php" class="sidebar-link"><i class="bi bi-calendar-check-fill"></i> Urus Tempahan</a>
            <a href="admin_staff.php" class="sidebar-link"><i class="bi bi-shield-shaded"></i> Urus Admin & JHEPP</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <div class="page-header">
            <div>
                <h1 style="font-size: 1.6rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black); display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-shield-lock-fill"></i> Papan Pemuka Pentadbir
                </h1>
                <p style="font-weight: 700; color: #555; margin: 4px 0 0 0; font-size: 0.9rem;">
                    Pusat kawalan utama dan statistik pengurusan sistem SCRS PMU.
                </p>
            </div>
        </div>

        <!-- STATISTIK KAD -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-title">Jumlah Pelajar</span>
                    <div class="stat-icon-badge" style="background: var(--yellow);">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-sub">
                    <span style="color: #007700; font-weight: 800;"><?php echo $approved_students; ?> Lulus</span> &bull; 
                    <span style="color: #d97706; font-weight: 800;"><?php echo $pending_students; ?> Menunggu</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-title">Penyedia Kereta</span>
                    <div class="stat-icon-badge" style="background: var(--green);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $total_providers; ?></div>
                <div class="stat-sub">
                    <span style="color: #007700; font-weight: 800;"><?php echo $approved_providers; ?> Aktif & Lulus</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-title">Jumlah Kenderaan</span>
                    <div class="stat-icon-badge" style="background: var(--blue);">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $total_cars; ?></div>
                <div class="stat-sub">
                    <span style="color: #007700; font-weight: 800;"><?php echo $available_cars; ?> Sedia Disewa</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-title">Nilai Sewaan</span>
                    <div class="stat-icon-badge" style="background: var(--pink);">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
                <div class="stat-number">RM <?php echo number_format($sum_revenue, 2); ?></div>
                <div class="stat-sub">
                    <span style="font-weight: 800;"><?php echo $total_bookings; ?> Tempahan</span> (<?php echo $completed_bookings; ?> Selesai)
                </div>
            </div>
        </div>

        <!-- PINTASAN PENGURUSAN -->
        <h2 style="font-size: 1.15rem; font-weight: 900; text-transform: uppercase; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-grid-fill"></i> Modul Pengurusan Sistem
        </h2>

        <div class="mgmt-shortcuts">
            <a href="admin_students.php" class="shortcut-card">
                <div class="shortcut-icon-box" style="background: var(--yellow);">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <div style="font-weight: 900; font-size: 0.95rem; text-transform: uppercase;">Pelajar</div>
                    <span style="font-size: 0.75rem; color: #666; font-weight: 700;">Data & pengesahan</span>
                </div>
            </a>

            <a href="admin_providers.php" class="shortcut-card">
                <div class="shortcut-icon-box" style="background: var(--green);">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div style="font-weight: 900; font-size: 0.95rem; text-transform: uppercase;">Penyedia</div>
                    <span style="font-size: 0.75rem; color: #666; font-weight: 700;">Akaun tuan kereta</span>
                </div>
            </a>

            <a href="admin_cars.php" class="shortcut-card">
                <div class="shortcut-icon-box" style="background: var(--blue);">
                    <i class="bi bi-car-front-fill"></i>
                </div>
                <div>
                    <div style="font-weight: 900; font-size: 0.95rem; text-transform: uppercase;">Kenderaan</div>
                    <span style="font-size: 0.75rem; color: #666; font-weight: 700;">Senarai & kadar sewa</span>
                </div>
            </a>

            <a href="admin_bookings.php" class="shortcut-card">
                <div class="shortcut-icon-box" style="background: var(--pink);">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div>
                    <div style="font-weight: 900; font-size: 0.95rem; text-transform: uppercase;">Tempahan</div>
                    <span style="font-size: 0.75rem; color: #666; font-weight: 700;">Rekod & status</span>
                </div>
            </a>

            <a href="admin_staff.php" class="shortcut-card">
                <div class="shortcut-icon-box" style="background: #e1f5fe;">
                    <i class="bi bi-shield-lock-fill" style="color: #0277bd;"></i>
                </div>
                <div>
                    <div style="font-weight: 900; font-size: 0.95rem; text-transform: uppercase;">Admin & JHEPP</div>
                    <span style="font-size: 0.75rem; color: #666; font-weight: 700;">Akaun staf sistem</span>
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
                                        <strong><?php echo (!empty($b['car_brand']) ? htmlspecialchars($b['car_brand']) . ' ' : '') . htmlspecialchars($b['car_model']); ?></strong><br>
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
        const profileToggle = document.getElementById('profile-toggle');
        const profileMenu = document.getElementById('profile-menu');

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('show');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        }

        if (openSidebarBtn) openSidebarBtn.addEventListener('click', openSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
            });
            document.addEventListener('click', () => {
                profileMenu.classList.remove('show');
            });
        }

        // Cegah paparan semula melalui butang Back selepas log keluar
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
