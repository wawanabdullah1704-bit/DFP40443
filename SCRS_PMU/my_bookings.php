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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Tempahan Saya - SCRS PMU</title>
    <style>
        body { 
            background-color: #f8f9fa; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
        }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .main-content { flex-grow: 1; }
        .car-thumbnail {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }
        @media (max-width: 768px) {
            .car-thumbnail {
                border-bottom-left-radius: 0;
                border-top-right-radius: 12px;
            }
        }
    </style>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <button class="btn btn-light border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand me-auto" href="dashboard.php">SCRS PMU</a>
            
            <span class="navbar-text d-none d-md-block fw-bold text-primary">
                Hai, <?php echo htmlspecialchars($student_name); ?>
            </span>
        </div>
    </nav>

    <!-- OFFCANVAS MENU (SIDEBAR) -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold">Menu Pelajar</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="dashboard.php">
                        <i class="bi bi-house-door text-primary me-3 fs-4"></i> Papan Pemuka
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="booking.php">
                        <i class="bi bi-search text-success me-3 fs-4"></i> Cari & Tempah Kereta
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center bg-light rounded" href="my_bookings.php">
                        <i class="bi bi-card-checklist text-warning me-3 fs-4"></i> Status Tempahan
                    </a>
                </li>
                <li class="nav-item mb-2 mt-auto">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="login.php">
                        <i class="bi bi-box-arrow-left text-danger me-3 fs-4"></i> Log Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- KANDUNGAN UTAMA -->
    <div class="main-content">
        <div class="container mt-4 mb-5">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-clipboard-check-fill text-primary me-2"></i>Status Tempahan Aktif</h3>
                    <p class="text-secondary mt-1 mb-0">Pantau status tempahan kereta anda di sini.</p>
                </div>
                <a href="booking_history.php" class="btn btn-outline-secondary fw-bold">
                    <i class="bi bi-clock-history me-1"></i> Sejarah Tempahan
                </a>
            </div>

            <div class="row">
                <?php if ($result_bookings->num_rows > 0): ?>
                    <?php while ($booking = $result_bookings->fetch_assoc()): 
                        // Tetapkan warna lencana status
                        $status_badge = '';
                        $status_icon = '';
                        if ($booking['status'] == 'Pending') {
                            $status_badge = 'bg-warning text-dark';
                            $status_icon = 'bi-hourglass-split';
                            $status_text = 'Menunggu Pengesahan';
                        } else if ($booking['status'] == 'Approved') {
                            $status_badge = 'bg-success text-white';
                            $status_icon = 'bi-check-circle-fill';
                            $status_text = 'Diluluskan';
                        }
                    ?>
                        <div class="col-12 mb-4">
                            <div class="card shadow-sm border-0 rounded-4">
                                <div class="row g-0">
                                    <div class="col-md-3">
                                        <img src="<?php echo htmlspecialchars($booking['car_image']); ?>" class="img-fluid car-thumbnail" alt="Kereta">
                                    </div>
                                    <div class="col-md-9">
                                        <div class="card-body h-100 d-flex flex-column">
                                            
                                            <!-- Pengepala Kad (Model & Status) -->
                                            <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
                                                <div>
                                                    <h5 class="card-title fw-bold text-primary mb-0"><?php echo htmlspecialchars($booking['car_model']); ?></h5>
                                                    <small class="text-secondary fw-bold"><?php echo htmlspecialchars($booking['car_plate']); ?></small>
                                                </div>
                                                <span class="badge <?php echo $status_badge; ?> px-3 py-2 rounded-pill fs-6">
                                                    <i class="bi <?php echo $status_icon; ?> me-1"></i> <?php echo $status_text; ?>
                                                </span>
                                            </div>

                                            <!-- Butiran Tempahan -->
                                            <div class="row mt-2">
                                                <div class="col-sm-6 mb-2">
                                                    <small class="text-muted d-block">Tarikh Ambil</small>
                                                    <span class="fw-bold"><i class="bi bi-calendar-check text-success me-1"></i> <?php echo date('d M Y, h:i A', strtotime($booking['start_date'])); ?></span>
                                                </div>
                                                <div class="col-sm-6 mb-2">
                                                    <small class="text-muted d-block">Tarikh Pulang</small>
                                                    <span class="fw-bold"><i class="bi bi-calendar-x text-danger me-1"></i> <?php echo date('d M Y, h:i A', strtotime($booking['end_date'])); ?></span>
                                                </div>
                                                <div class="col-sm-6 mb-2">
                                                    <small class="text-muted d-block">Penyedia Kereta</small>
                                                    <span class="fw-bold"><i class="bi bi-person-badge text-secondary me-1"></i> <?php echo htmlspecialchars($booking['provider_name']); ?></span>
                                                </div>
                                                <div class="col-sm-6 mb-2">
                                                    <small class="text-muted d-block">Jumlah Bayaran (<?php echo ($booking['rent_type'] == 'Daily') ? 'Harian' : 'Jam'; ?>)</small>
                                                    <span class="fw-bold text-success fs-5">RM <?php echo number_format($booking['total_price'], 2); ?></span>
                                                </div>
                                            </div>

                                            <!-- Tindakan / Hubungi Penyedia -->
                                            <div class="mt-auto pt-3 text-end">
                                                <?php if ($booking['status'] == 'Approved'): ?>
                                                    <!-- Butang WhatsApp Jika Diluluskan -->
                                                    <?php 
                                                        // Buang simbol '-' atau space dari nombor telefon jika ada, dan tambah kod negara 60 jika mula dengan 0
                                                        $phone = preg_replace('/[^0-9]/', '', $booking['provider_phone']);
                                                        if (strpos($phone, '0') === 0) {
                                                            $phone = '6' . $phone;
                                                        }
                                                    ?>
                                                    <a href="https://wa.me/<?php echo $phone; ?>?text=Hai,%20saya%20pelajar%20dari%20SCRS%20PMU.%20Tempahan%20kereta%20<?php echo urlencode($booking['car_model']); ?>%20saya%20telah%20diluluskan." target="_blank" class="btn btn-success fw-bold px-4">
                                                        <i class="bi bi-whatsapp me-2"></i> Hubungi Penyedia
                                                    </a>
                                                <?php else: ?>
                                                    <!-- Jika masih Pending -->
                                                    <span class="text-muted fst-italic small">Anda akan dapat menghubungi penyedia selepas tempahan diluluskan.</span>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5 mt-3">
                        <div class="p-5 bg-white shadow-sm border rounded-4 text-secondary">
                            <i class="bi bi-folder-x display-1 text-muted opacity-50 mb-3 d-block"></i>
                            <h4 class="fw-bold text-dark">Tiada Tempahan Aktif</h4>
                            <p>Anda belum membuat sebarang tempahan baru atau tempahan anda telah selesai.</p>
                            <a href="booking.php" class="btn btn-primary mt-3 fw-bold px-4 py-2">Mula Tempah Kereta</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- FOOTER -->
    <footer class="text-center py-4 mt-auto border-top bg-white text-secondary">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. Hak Cipta Terpelihara.</small>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>