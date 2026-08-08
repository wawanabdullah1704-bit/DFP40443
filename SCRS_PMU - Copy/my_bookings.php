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

// PROSES MUAT NAIK GAMBAR PULANGAN KERETA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_return_image'])) {
    $booking_id = (int)$_POST['booking_id'];
    if (!empty($_FILES['return_image']['name'])) {
        $targetDir = "uploads/returns/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $imgName = basename($_FILES["return_image"]["name"]);
        $newImgName = "Return_" . $booking_id . "_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imgName);
        $targetPath = $targetDir . $newImgName;

        if (move_uploaded_file($_FILES["return_image"]["tmp_name"], $targetPath)) {
            $sql_ret = "UPDATE bookings SET return_image = ? WHERE id = ? AND student_id = ?";
            $stmt_ret = $conn->prepare($sql_ret);
            $stmt_ret->bind_param("sii", $targetPath, $booking_id, $student_id);
            $stmt_ret->execute();
            $stmt_ret->close();
        }
    }
    header("Location: my_bookings.php");
    exit();
}

// Ambil senarai tempahan yang MASIH AKTIF (Pending atau Approved)
$sql_bookings = "SELECT b.*, c.car_model, c.car_plate, c.car_image, 
                 p.full_name AS provider_name, p.phone_no AS provider_phone 
                 FROM bookings b
                 JOIN cars c ON b.car_id = c.id
                 JOIN providers p ON c.provider_id = p.id
                 WHERE b.student_id = ? AND b.status IN ('Pending', 'Approved')
                 ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql_bookings);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result_bookings = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Status Tempahan Saya - SCRS PMU</title>
    
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
        .profile-btn:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }

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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-title {
            display: inline-block;
            background: var(--black);
            color: var(--white);
            font-weight: 900;
            text-transform: uppercase;
            padding: 10px 20px;
            box-shadow: 4px 4px 0px var(--yellow);
        }

        .neo-btn {
            background-color: var(--yellow);
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            font-weight: 900;
            text-transform: uppercase;
            padding: 10px 18px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }
        .btn-green { background-color: var(--green); }
        .btn-blue { background-color: var(--blue); }
        .btn-pink { background-color: var(--pink); }

        /* Kad Tempahan */
        .booking-card {
            background: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            margin-bottom: 25px;
            display: flex;
            flex-direction: row;
            overflow: hidden;
        }
        .booking-img {
            width: 280px;
            height: 100%;
            min-height: 220px;
            object-fit: cover;
            border-right: var(--border-thick);
        }
        .booking-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid var(--black);
            padding-bottom: 12px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .car-name { font-size: 1.3rem; font-weight: 900; text-transform: uppercase; }
        .car-plate { font-weight: 700; color: #555; }

        .neo-badge {
            border: 3px solid var(--black);
            padding: 6px 14px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.85rem;
            box-shadow: 3px 3px 0px var(--black);
        }
        .badge-pending { background-color: var(--yellow); }
        .badge-approved { background-color: var(--green); }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        .detail-item { font-weight: 700; font-size: 0.9rem; }
        .detail-label { display: block; text-transform: uppercase; font-size: 0.75rem; color: #666; font-weight: 800; }
        .detail-value { display: flex; align-items: center; gap: 6px; margin-top: 2px; }

        .price-box {
            background-color: var(--bg-color);
            border: 3px solid var(--black);
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 900;
            font-size: 1.1rem;
            margin-top: 10px;
        }

        .empty-box {
            background: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 3rem 1.5rem;
            text-align: center;
        }
        .empty-box i { font-size: 4rem; display: block; margin-bottom: 15px; }

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

        /* RESPONSIVE MOBILE */
        @media (max-width: 768px) {
            .booking-card { flex-direction: column; }
            .booking-img { width: 100%; height: 200px; border-right: none; border-bottom: var(--border-thick); }
            .details-grid { grid-template-columns: 1fr; gap: 10px; }
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
            <a href="my_bookings.php" class="sidebar-link active"><i class="bi bi-clipboard-check-fill"></i> Status Tempahan</a>
            <a href="booking_history.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Sejarah Rekod</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        
        <div class="page-header">
            <div>
                <div class="section-title"><i class="bi bi-clipboard-check-fill me-2"></i> Status Tempahan Aktif</div>
                <p style="font-weight: 700; color: #555; margin-top: 8px;">Pantau status permohonan tempahan kenderaan anda.</p>
            </div>
            <a href="booking_history.php" class="neo-btn bg-w">
                <i class="bi bi-clock-history"></i> Sejarah Rekod
            </a>
        </div>

        <?php if ($result_bookings->num_rows > 0): ?>
            <?php while ($booking = $result_bookings->fetch_assoc()): 
                $is_approved = ($booking['status'] == 'Approved');
                $badge_class = $is_approved ? 'badge-approved' : 'badge-pending';
                $status_text = $is_approved ? 'Diluluskan' : 'Menunggu Pengesahan';
                $status_icon = $is_approved ? 'bi-check-circle-fill' : 'bi-hourglass-split';
            ?>
                <div class="booking-card">
                    <img src="<?php echo htmlspecialchars($booking['car_image']); ?>" class="booking-img" alt="Kereta">
                    <div class="booking-body">
                        <div>
                            <div class="booking-header">
                                <div>
                                    <div class="car-name"><?php echo htmlspecialchars($booking['car_model']); ?></div>
                                    <div class="car-plate"><i class="bi bi-123 me-1"></i><?php echo htmlspecialchars($booking['car_plate']); ?></div>
                                </div>
                                <span class="neo-badge <?php echo $badge_class; ?>">
                                    <i class="bi <?php echo $status_icon; ?> me-1"></i> <?php echo $status_text; ?>
                                </span>
                            </div>

                            <div class="details-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Tarikh & Masa Ambil</span>
                                    <div class="detail-value"><i class="bi bi-calendar-check text-success"></i> <?php echo date('d M Y, h:i A', strtotime($booking['start_date'])); ?></div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Tarikh & Masa Pulang</span>
                                    <div class="detail-value"><i class="bi bi-calendar-x text-danger"></i> <?php echo date('d M Y, h:i A', strtotime($booking['end_date'])); ?></div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Penyedia Kereta</span>
                                    <div class="detail-value"><i class="bi bi-person-badge text-primary"></i> <?php echo htmlspecialchars($booking['provider_name']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Jenis Sewaan</span>
                                    <div class="detail-value"><i class="bi bi-clock"></i> <?php echo ($booking['rent_type'] == 'Daily') ? 'Harian (Daily)' : 'Jam (Hourly)'; ?></div>
                                </div>
                            </div>

                            <div class="price-box">
                                <span>Jumlah Bayaran:</span>
                                <span style="color: #007700;">RM <?php echo number_format($booking['total_price'], 2); ?></span>
                            </div>
                        </div>

                        <div style="margin-top: 18px; text-align: right;">
                            <?php if ($is_approved): 
                                $phone = preg_replace('/[^0-9]/', '', $booking['provider_phone']);
                                if (strpos($phone, '0') === 0) {
                                    $phone = '6' . $phone;
                                }
                            ?>
                                <a href="https://wa.me/<?php echo $phone; ?>?text=Hai,%20saya%20pelajar%20dari%20SCRS%20PMU.%20Tempahan%20kereta%20<?php echo urlencode($booking['car_model']); ?>%20saya%20telah%20diluluskan." target="_blank" class="neo-btn btn-green">
                                    <i class="bi bi-whatsapp"></i> Hubungi Penyedia
                                </a>

                                <!-- RETURN IMAGE UPLOAD -->
                                <div style="margin-top: 15px; border-top: 2px dashed var(--black); padding-top: 15px; text-align: left;">
                                    <?php if (!empty($booking['return_image']) && file_exists($booking['return_image'])): ?>
                                        <p style="font-weight:900; text-transform:uppercase; font-size:0.8rem; margin-bottom:8px; color:#007700;">
                                            <i class="bi bi-check-circle-fill me-1"></i> Gambar Pulangan Dimuat Naik
                                        </p>
                                        <img src="<?php echo htmlspecialchars($booking['return_image']); ?>" alt="Gambar Pulangan" style="max-width:100%; max-height:160px; border:3px solid var(--black); box-shadow:3px 3px 0 var(--black); object-fit:cover; margin-bottom:8px;">
                                        <br>
                                    <?php else: ?>
                                        <p style="font-weight:900; text-transform:uppercase; font-size:0.8rem; margin-bottom:8px; color:#555;">
                                            <i class="bi bi-camera-fill me-1"></i> Muat Naik Gambar Kereta Selepas Dipulangkan
                                        </p>
                                    <?php endif; ?>
                                    <form action="" method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="file" name="return_image" accept=".jpg,.jpeg,.png" required
                                            style="border:3px solid var(--black); padding:6px; font-weight:700; background:var(--bg-color); flex:1; min-width:0;">
                                        <button type="submit" name="upload_return_image" class="neo-btn btn-blue" style="margin-top:0; padding:8px 16px; font-size:0.85rem; white-space:nowrap;">
                                            <i class="bi bi-cloud-arrow-up-fill"></i> Muat Naik
                                        </button>
                                    </form>
                                </div>

                            <?php else: ?>
                                <span style="font-weight: 800; font-size: 0.85rem; color: #777;">
                                    <i class="bi bi-info-circle me-1"></i> Anda boleh menghubungi penyedia selepas tempahan diluluskan.
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-box">
                <i class="bi bi-folder-x"></i>
                <h2 style="font-weight: 900; text-transform: uppercase;">Tiada Tempahan Aktif</h2>
                <p style="font-weight: 700; color: #666; margin: 10px 0 20px 0;">Anda belum membuat sebarang tempahan baru atau tempahan anda telah selesai.</p>
                <a href="booking.php" class="neo-btn btn-green">
                    <i class="bi bi-plus-lg me-1"></i> Mula Tempah Kereta
                </a>
            </div>
        <?php endif; ?>

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