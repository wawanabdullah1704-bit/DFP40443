<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

// Semak jika pengguna telah log masuk dan merupakan seorang pelajar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['username'];
$flash_msg = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Cari & Tempah (Langkah 1) - SCRS PMU</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        /* Borang Carian */
        .search-card { 
            background-color: var(--white); 
            border: var(--border-thick); 
            border-radius: var(--radius-xl); 
            box-shadow: var(--shadow-solid); 
            padding: 24px; 
            margin-bottom: 25px; 
        }
        .search-title { 
            font-weight: 900; 
            text-transform: uppercase; 
            font-size: 1.15rem; 
            margin-bottom: 18px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .rent-toggle-btn {
            background-color: var(--white);
            color: var(--black);
            padding: 9px 18px;
            font-size: 0.9rem;
            font-weight: 900;
            text-transform: uppercase;
            border: var(--border-thick);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-solid);
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .rent-toggle-btn.active {
            background-color: var(--yellow) !important;
            box-shadow: var(--shadow-sm);
            transform: translate(2px, 2px);
        }
        .rent-toggle-btn:hover:not(.active) {
            background-color: #f0f0f0;
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow-lg);
        }
        .form-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr auto; 
            gap: 14px; 
            align-items: end; 
        }

        .booking-page-header {
            margin-bottom: 20px;
        }
        .booking-title-bar {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 8px;
        }
        .booking-title {
            font-size: 1.55rem;
            font-weight: 900;
            text-transform: uppercase;
            margin: 0;
            color: var(--black);
            line-height: 1.2;
        }
        .booking-subtitle {
            font-weight: 700;
            color: #555;
            font-size: 0.92rem;
            margin: 0;
            line-height: 1.5;
            width: 100%;
        }
        .btn-arrow-back {
            width: 40px;
            height: 40px;
            min-width: 40px;
            padding: 0 !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            border-radius: var(--radius-md);
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .booking-page-header { margin-bottom: 18px; }
            .booking-title-bar { gap: 16px; margin-bottom: 8px; }
            .booking-title { font-size: 1.22rem; }
            .booking-subtitle { font-size: 0.88rem; }
            .btn-arrow-back { width: 36px; height: 36px; min-width: 36px; font-size: 1.15rem; }
            .form-grid, .form-grid.grid-hourly { grid-template-columns: 1fr !important; gap: 12px; } 
            .search-card { padding: 16px; margin-bottom: 18px; } 
            .main-content { padding: 1rem 14px 1.5rem 14px; }
            footer {
                display: block !important;
                position: relative !important;
                flex-shrink: 0 !important;
                padding: 14px 16px !important;
                padding-bottom: calc(14px + env(safe-area-inset-bottom, 0px)) !important;
                font-size: 0.78rem !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar"><i class="bi bi-list"></i></button>
            <a href="dashboard.php" class="neo-brand">SCRS PMU</a>
        </div>
        <?php render_navbar_actions($conn, 'student', $student_id, $student_name, '../'); ?>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Menu Utama</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="booking.php" class="sidebar-link active"><i class="bi bi-car-front-fill"></i> Cari & Tempah</a>
            <a href="my_bookings.php" class="sidebar-link"><i class="bi bi-calendar-check-fill"></i> Tempahan Saya</a>
            <a href="booking_history.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Rekod Tempahan</a>
        </nav>
    </aside>

    <main class="main-content">
        
        <?php if (!empty($flash_msg)): ?>
            <?php echo $flash_msg; ?>
        <?php endif; ?>

        <!-- HEADING PANDUAN PENGGUNA (DENGAN BUTANG KEMBALI DI SEBELAH KIRI) -->
        <div class="booking-page-header">
            <div class="booking-title-bar">
                <a href="dashboard.php" class="neo-btn btn-yellow btn-arrow-back" title="Papan Pemuka" aria-label="Kembali ke Papan Pemuka">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="booking-title">Cari & Tempah Kenderaan</h1>
            </div>
            <p class="booking-subtitle">
                Sila pilih mod sewaan dan tetapkan tarikh serta masa pengambilan dan pemulangan kenderaan.
            </p>
        </div>

        <!-- BORANG CARIAN -->
        <div class="search-card">
            <div class="search-title">Tetapkan Pilihan Sewaan Anda</div>
            
            <!-- Pilihan Butang Jenis Sewaan -->
            <div style="margin-bottom: 20px;">
                <label class="form-label" style="display: block; margin-bottom: 8px;">Pilih Mod Sewaan:</label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="rent-toggle-btn active" id="btnTypeDaily" onclick="switchRentType('Daily')">
                        <i class="bi bi-calendar-range-fill"></i> Sewaan Harian (Daily)
                    </button>
                    <button type="button" class="rent-toggle-btn" id="btnTypeHourly" onclick="switchRentType('Hourly')">
                        <i class="bi bi-clock-fill"></i> Sewaan Jam (Hourly)
                    </button>
                </div>
            </div>

            <form id="step1Form" action="booking_cars.php" method="GET">
                <input type="hidden" name="rent_type" id="rent_type_input" value="Daily">
                <input type="hidden" name="start_date" id="final_start_date" value="">
                <input type="hidden" name="end_date" id="final_end_date" value="">

                <!-- Borang Carian Harian -->
                <div class="form-grid" id="dailyFormGrid">
                    <div class="form-group">
                        <label class="form-label">Tarikh & Masa Ambil</label>
                        <input type="datetime-local" class="form-control" id="daily_start" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarikh & Masa Pulang</label>
                        <input type="datetime-local" class="form-control" id="daily_end" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="neo-btn btn-yellow" style="width: 100%; white-space: nowrap;">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>
                    </div>
                </div>

                <!-- Borang Carian Jam (Hourly) -->
                <div class="form-grid" id="hourlyFormGrid" style="display: none; grid-template-columns: 1.2fr 1fr 1fr auto;">
                    <div class="form-group">
                        <label class="form-label">Tarikh Sewaan</label>
                        <input type="date" class="form-control" id="hourly_date">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Masa Ambil (Mula)</label>
                        <input type="time" class="form-control" id="hourly_start_time">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Masa Pulang (Tamat)</label>
                        <input type="time" class="form-control" id="hourly_end_time">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="neo-btn btn-yellow" style="width: 100%; white-space: nowrap;">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </main>

    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- SKRIP ASLI (VANILLA JS) -->
    <script>
        // --- 1. SWITCH JENIS SEWAAN (DAILY / HOURLY) ---
        function switchRentType(type) {
            const btnDaily = document.getElementById('btnTypeDaily');
            const btnHourly = document.getElementById('btnTypeHourly');
            const rentTypeInput = document.getElementById('rent_type_input');
            const dailyGrid = document.getElementById('dailyFormGrid');
            const hourlyGrid = document.getElementById('hourlyFormGrid');
            const dailyStart = document.getElementById('daily_start');
            const dailyEnd = document.getElementById('daily_end');
            const hourlyDate = document.getElementById('hourly_date');
            const hourlyStartTime = document.getElementById('hourly_start_time');
            const hourlyEndTime = document.getElementById('hourly_end_time');

            rentTypeInput.value = type;

            if (type === 'Hourly') {
                btnHourly.classList.add('active');
                btnDaily.classList.remove('active');
                
                dailyGrid.style.display = 'none';
                hourlyGrid.style.display = 'grid';
                
                dailyStart.required = false;
                dailyEnd.required = false;
                dailyStart.disabled = true;
                dailyEnd.disabled = true;

                hourlyDate.required = true;
                hourlyStartTime.required = true;
                hourlyEndTime.required = true;
                hourlyDate.disabled = false;
                hourlyStartTime.disabled = false;
                hourlyEndTime.disabled = false;
            } else {
                btnDaily.classList.add('active');
                btnHourly.classList.remove('active');
                
                hourlyGrid.style.display = 'none';
                dailyGrid.style.display = 'grid';

                dailyStart.required = true;
                dailyEnd.required = true;
                dailyStart.disabled = false;
                dailyEnd.disabled = false;

                hourlyDate.required = false;
                hourlyStartTime.required = false;
                hourlyEndTime.required = false;
                hourlyDate.disabled = true;
                hourlyStartTime.disabled = true;
                hourlyEndTime.disabled = true;
            }
        }

        // --- 2. PENGESAHAN & PENGHANTARAN KE LANGKAH 2 (booking_cars.php) ---
        const step1Form = document.getElementById('step1Form');
        if (step1Form) {
            step1Form.addEventListener('submit', function(e) {
                e.preventDefault();

                const rentType = document.getElementById('rent_type_input').value;
                let startDateVal = '';
                let endDateVal = '';

                if (rentType === 'Hourly') {
                    const dateVal = document.getElementById('hourly_date').value;
                    const startVal = document.getElementById('hourly_start_time').value;
                    const endVal = document.getElementById('hourly_end_time').value;

                    if (!dateVal || !startVal || !endVal) {
                        alert('Sila lengkapkan tarikh, masa mula dan masa tamat sewaan jam!');
                        return;
                    }
                    if (startVal >= endVal) {
                        alert('Masa tamat mestilah selepas masa mula sewaan!');
                        return;
                    }
                    startDateVal = `${dateVal}T${startVal}`;
                    endDateVal = `${dateVal}T${endVal}`;
                } else {
                    const startVal = document.getElementById('daily_start').value;
                    const endVal = document.getElementById('daily_end').value;

                    if (!startVal || !endVal) {
                        alert('Sila lengkapkan tarikh & masa ambil serta pulang sewaan harian!');
                        return;
                    }
                    if (new Date(startVal) >= new Date(endVal)) {
                        alert('Tarikh & masa pulang mestilah selepas tarikh & masa ambil!');
                        return;
                    }
                    startDateVal = startVal;
                    endDateVal = endVal;
                }

                // Tetapkan input tersembunyi dan hantar borang secara terus ke booking_cars.php
                document.getElementById('final_start_date').value = startDateVal;
                document.getElementById('final_end_date').value = endDateVal;

                const params = new URLSearchParams();
                params.append('rent_type', rentType);
                params.append('start_date', startDateVal);
                params.append('end_date', endDateVal);

                window.location.href = 'booking_cars.php?' + params.toString();
            });
        }

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

        function openSidebar() { sidebar.classList.add('open'); sidebarOverlay.classList.add('show'); }
        function closeSidebar() { sidebar.classList.remove('open'); sidebarOverlay.classList.remove('show'); }

        openSidebarBtn.addEventListener('click', openSidebar);
        closeSidebarBtn.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);

        // Cegah paparan semula melalui butang Back selepas log keluar
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>