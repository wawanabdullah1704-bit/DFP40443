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
$message = "";

// 1. PROSES PENGESAHAN TEMPAHAN (POST) - Terus simpan tanpa guna payment.php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking'])) {
    $car_id = (int)$_POST['car_id'];
    $rent_type = htmlspecialchars($_POST['rent_type']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_price = (float)$_POST['total_price'];
    $status = 'Pending'; // Status awal tempahan

    // Masukkan data tempahan ke dalam pangkalan data
    $sql_book = "INSERT INTO bookings (student_id, car_id, rent_type, start_date, end_date, total_price, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_book = $conn->prepare($sql_book);
    $stmt_book->bind_param("iisssds", $student_id, $car_id, $rent_type, $start_date, $end_date, $total_price, $status);

    if ($stmt_book->execute()) {
        $message = "<div class='alert alert-success'>Tempahan berjaya dihantar! Sila tunggu pengesahan daripada pihak penyedia kereta.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Ralat pangkalan data: " . $stmt_book->error . "</div>";
    }
    $stmt_book->close();
}

// 2. JIKA PERMINTAAN AJAX UNTUK CARIAN KERETA
if (isset($_GET['ajax_search']) && $_GET['ajax_search'] == '1') {
    $search_start = $_GET['start_date'] ?? '';
    $search_end = $_GET['end_date'] ?? '';
    $rent_type = $_GET['rent_type'] ?? '';
    
    if (!$search_start || !$search_end || !$rent_type) {
        echo '<div class="col-12"><div class="alert alert-warning text-center">Sila lengkapkan maklumat carian.</div></div>';
        exit;
    }

    $start_ts = strtotime($search_start);
    $end_ts = strtotime($search_end);

    if ($start_ts >= $end_ts) {
        echo '<div class="col-12"><div class="alert alert-danger text-center">Tarikh/Masa pemulangan mestilah selepas tarikh/masa pengambilan.</div></div>';
        exit;
    }

    // Kira tempoh masa
    if ($rent_type === 'Hourly') {
        $duration = ceil(($end_ts - $start_ts) / 3600);
    } else {
        $duration = ceil(($end_ts - $start_ts) / 86400);
        if ($duration < 1) $duration = 1;
    }

    // Ambil maklumat kereta berserta QR code penyedia (Join dengan jadual providers)
    $sql_cars = "SELECT c.*, p.qr_code_image, p.full_name AS provider_name 
                 FROM cars c 
                 JOIN providers p ON c.provider_id = p.id
                 WHERE c.status = 'Available' 
                 AND c.id NOT IN (
                     SELECT car_id FROM bookings 
                     WHERE status IN ('Pending', 'Approved') 
                     AND (start_date < ? AND end_date > ?)
                 )
                 ORDER BY c.created_at DESC";
                 
    $stmt_cars = $conn->prepare($sql_cars);
    $stmt_cars->bind_param("ss", $search_end, $search_start);
    $stmt_cars->execute();
    $result_cars = $stmt_cars->get_result();

    if ($result_cars->num_rows > 0) {
        echo '<h4 class="fw-bold mb-4"><i class="bi bi-car-front-fill text-success me-2"></i>Langkah 2: Pilih Kereta</h4>';
        echo '<p class="text-secondary">Menampilkan kereta yang tersedia untuk tempoh <strong>' . $duration . ' ' . (($rent_type === 'Daily') ? 'Hari' : 'Jam') . '</strong>.</p>';
        echo '<div class="row g-4">';

        while ($car = $result_cars->fetch_assoc()) {
            $price_rate = ($rent_type === 'Daily') ? $car['price_per_day'] : $car['price_per_hour'];
            $total_calc_price = $duration * $price_rate;
            $qr_image = !empty($car['qr_code_image']) ? $car['qr_code_image'] : 'uploads/default_qr.png';
?>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100 rounded-4">
                    <img src="<?php echo htmlspecialchars($car['car_image']); ?>" class="card-img-top car-image" alt="Gambar Kereta">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($car['car_model']); ?></h5>
                        <p class="text-secondary mb-3"><i class="bi bi-123 me-2"></i><?php echo htmlspecialchars($car['car_plate']); ?></p>
                        
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-light text-dark border"><i class="bi bi-gear-fill me-1"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                            <span class="badge bg-light text-dark border"><i class="bi bi-people-fill me-1"></i> <?php echo htmlspecialchars($car['seat_capacity']); ?> Tempat Duduk</span>
                        </div>
                        
                        <div class="p-3 bg-light rounded-3 mb-3 border mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-secondary" style="font-size: 0.85rem;">Kadar (<?php echo ($rent_type === 'Daily') ? 'Harian' : 'Jam'; ?>)</span>
                                <span class="fw-bold">RM <?php echo number_format($price_rate, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                                <span class="text-secondary fw-bold">Jumlah Harga:</span>
                                <span class="text-primary fw-bold fs-5">RM <?php echo number_format($total_calc_price, 2); ?></span>
                            </div>
                        </div>

                        <button type="button" class="btn btn-success w-100 fw-bold py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#confirmModal<?php echo $car['id']; ?>">
                            Tempah Kereta Ini
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL PENGESAHAN & QR PEMBAYARAN -->
            <div class="modal fade" id="confirmModal<?php echo $car['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-bottom-0 bg-light rounded-top-4">
                            <h5 class="modal-title fw-bold text-dark">Pengesahan & Pembayaran Tempahan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row">
                                <div class="col-md-6 border-end">
                                    <h5 class="fw-bold text-primary mb-3"><?php echo htmlspecialchars($car['car_model']); ?></h5>
                                    <ul class="list-group list-group-flush mb-3">
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-secondary">Ambil:</span> <strong><?php echo date('d M Y, h:i A', strtotime($search_start)); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-secondary">Pulang:</span> <strong><?php echo date('d M Y, h:i A', strtotime($search_end)); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-secondary">Tempoh:</span> <strong><?php echo $duration; ?> <?php echo ($rent_type === 'Daily') ? 'Hari' : 'Jam'; ?></strong>
                                        </li>
                                    </ul>
                                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 mb-3 border">
                                        <span class="fw-bold">Jumlah Perlu Dibayar:</span>
                                        <span class="fw-bold fs-4 text-success">RM <?php echo number_format($total_calc_price, 2); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6 text-center d-flex flex-column justify-content-center align-items-center">
                                    <p class="fw-bold text-dark mb-1">Imbas QR DuitNow untuk Pembayaran</p>
                                    <p class="text-muted small mb-2">Penyedia: <?php echo htmlspecialchars($car['provider_name']); ?></p>
                                    <img src="<?php echo htmlspecialchars($qr_image); ?>" alt="QR DuitNow" class="img-fluid border rounded shadow-sm mb-3" style="max-height: 180px; object-fit: contain;">
                                    <p class="text-danger small mb-0">Sila buat pembayaran sebelum menekan butang sahkan.</p>
                                </div>
                            </div>

                            <form action="" method="POST" class="mt-4">
                                <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                <input type="hidden" name="rent_type" value="<?php echo htmlspecialchars($rent_type); ?>">
                                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($search_start); ?>">
                                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($search_end); ?>">
                                <input type="hidden" name="total_price" value="<?php echo $total_calc_price; ?>">
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" name="confirm_booking" class="btn btn-primary w-50 fw-bold py-2">Sahkan Pembayaran & Hantar</button>
                                    <button type="button" class="btn btn-light border text-secondary w-50 fw-bold py-2" data-bs-dismiss="modal">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
<?php
        }
        echo '</div>';
    } else {
        echo '<div class="col-12"><div class="alert alert-warning border shadow-sm text-center py-4"><i class="bi bi-emoji-frown fs-1 d-block mb-2"></i>Maaf, tiada kereta yang tersedia untuk tarikh/masa yang dipilih.</div></div>';
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Pemuka Pelajar - SCRS PMU</title>
    <style>
        body { background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .main-content { flex-grow: 1; }
        .car-image { 
            height: 200px; object-fit: cover; 
            border-top-left-radius: calc(.25rem - 1px);
            border-top-right-radius: calc(.25rem - 1px);
        }
        .search-card { background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
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
            <a class="navbar-brand me-auto" href="#">SCRS PMU</a>
            
            <span class="navbar-text d-none d-md-block fw-bold text-primary">
                Hai, <?php echo htmlspecialchars($student_name); ?>
            </span>
        </div>
    </nav>

    <!-- OFFCANVAS MENU -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold">Menu Pelajar</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="dashboard.php">
                        <i class="bi bi-search text-primary me-3 fs-4"></i> Cari Kereta
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-card-checklist text-success me-3 fs-4"></i> Tempahan Saya
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
        <div class="container mt-4">
            
            <?php echo $message; ?>

            <!-- LANGKAH 1: BORANG CARIAN (AJAX) -->
            <div class="search-card p-4 border mb-5">
                <h4 class="fw-bold mb-3"><i class="bi bi-calendar-check text-primary me-2"></i>Langkah 1: Pilih Tarikh & Masa</h4>
                <form id="searchForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Jenis Sewaan</label>
                            <select class="form-select" name="rent_type" id="rent_type" required>
                                <option value="" disabled selected>Pilih...</option>
                                <option value="Daily">Harian (Daily)</option>
                                <option value="Hourly">Jam (Hourly)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tarikh & Masa Ambil</label>
                            <input type="datetime-local" class="form-control" name="start_date" id="start_date" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tarikh & Masa Pulang</label>
                            <input type="datetime-local" class="form-control" name="end_date" id="end_date" required>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="button" id="searchBtn" class="btn btn-primary fw-bold py-2"><i class="bi bi-search me-1"></i> Cari</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- KAWASAN HASIL CARIAN AJAX -->
            <div id="carResultsContainer">
                <div class="text-center py-5 mt-4 text-secondary">
                    <i class="bi bi-search fs-1 mb-3 d-block text-muted opacity-50"></i>
                    <h4>Sila pilih tarikh dan masa di atas untuk mencari kereta yang tersedia.</h4>
                </div>
            </div>

        </div>
    </div>

    <!-- FOOTER HAK CIPTA -->
    <footer class="text-center py-3 mt-5 text-secondary border-top bg-white">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. Hak Cipta Terpelihara.</small>
    </footer>

    <!-- Bootstrap JS & Skrip AJAX Carian Kereta -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('searchBtn').addEventListener('click', function () {
            let rentType = document.getElementById('rent_type').value;
            let startDate = document.getElementById('start_date').value;
            let endDate = document.getElementById('end_date').value;

            if (!rentType || !startDate || !endDate) {
                alert('Sila lengkapkan semua maklumat carian terlebih dahulu.');
                return;
            }

            // Papar loading teks
            document.getElementById('carResultsContainer').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-secondary">Mencari kereta yang tersedia...</p>
                </div>
            `;

            // Hantar permintaan AJAX menggunakan Fetch API
            let url = `dashboard.php?ajax_search=1&rent_type=${encodeURIComponent(rentType)}&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;

            fetch(url)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('carResultsContainer').innerHTML = data;
                })
                .catch(error => {
                    console.error('Ralat:', error);
                    document.getElementById('carResultsContainer').innerHTML = '<div class="alert alert-danger text-center">Ralat sistem semasa memuatkan data. Sila cuba lagi.</div>';
                });
        });
    </script>
</body>
</html>