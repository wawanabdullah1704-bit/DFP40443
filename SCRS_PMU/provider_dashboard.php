<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan Penyedia Kereta (Provider)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit();
}

$provider_id = $_SESSION['provider_id'];
$provider_name = $_SESSION['username'];
$message = "";

// 1. PROSES TAMBAH KERETA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_car'])) {
    
    $car_model = htmlspecialchars($_POST['car_model']);
    $car_plate = htmlspecialchars($_POST['car_plate']);
    $transmission = htmlspecialchars($_POST['transmission']);
    $seat_capacity = (int)$_POST['seat_capacity'];
    $price_per_day = (float)$_POST['price_per_day'];
    $price_per_hour = (float)$_POST['price_per_hour'];

    // Pengurusan Muat Naik Gambar
    $targetDir = "uploads/cars/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $imageName = basename($_FILES["car_image"]["name"]);
    // Cipta nama gambar yang unik
    $newImageName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imageName);
    $targetPath = $targetDir . $newImageName;

    // Muat naik gambar kereta
    if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $targetPath)) {
        
        $sql = "INSERT INTO cars (provider_id, car_model, car_plate, transmission, seat_capacity, price_per_day, price_per_hour, car_image, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Available')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssidds", $provider_id, $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $targetPath);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Berjaya: <strong>{$car_model}</strong> telah ditambah ke dalam senarai kereta anda!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Ralat pangkalan data: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger'>Ralat: Gagal memuat naik gambar kereta.</div>";
    }
}

// 2. PROSES KEMASKINI KERETA (EDIT)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_car'])) {
    
    $car_id = (int)$_POST['car_id'];
    $car_model = htmlspecialchars($_POST['car_model']);
    $car_plate = htmlspecialchars($_POST['car_plate']);
    $transmission = htmlspecialchars($_POST['transmission']);
    $seat_capacity = (int)$_POST['seat_capacity'];
    $price_per_day = (float)$_POST['price_per_day'];
    $price_per_hour = (float)$_POST['price_per_hour'];

    // Jika pengguna memuat naik gambar baharu
    if (!empty($_FILES["car_image"]["name"])) {
        $targetDir = "uploads/cars/";
        $imageName = basename($_FILES["car_image"]["name"]);
        $newImageName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imageName);
        $targetPath = $targetDir . $newImageName;

        if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $targetPath)) {
            $sql = "UPDATE cars SET car_model=?, car_plate=?, transmission=?, seat_capacity=?, price_per_day=?, price_per_hour=?, car_image=? WHERE id=? AND provider_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssidssii", $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $targetPath, $car_id, $provider_id);
        }
    } else {
        // Jika gambar tidak ditukar
        $sql = "UPDATE cars SET car_model=?, car_plate=?, transmission=?, seat_capacity=?, price_per_day=?, price_per_hour=? WHERE id=? AND provider_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiddii", $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $car_id, $provider_id);
    }

    if (isset($stmt) && $stmt->execute()) {
        $message = "<div class='alert alert-success'>Berjaya: Maklumat <strong>{$car_model}</strong> telah dikemaskini!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Ralat: Gagal mengemaskini maklumat kereta.</div>";
    }
    if (isset($stmt)) $stmt->close();
}

// 3. PROSES TUKAR STATUS (AVAILABLE / UNAVAILABLE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $car_id = (int)$_POST['car_id'];
    $new_status = htmlspecialchars($_POST['new_status']);

    $sql = "UPDATE cars SET status = ? WHERE id = ? AND provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_status, $car_id, $provider_id);
    
    if ($stmt->execute()) {
        $status_text = ($new_status == 'Available') ? 'Tersedia' : 'Tidak Tersedia';
        $message = "<div class='alert alert-success'>Berjaya: Status kereta telah ditukar kepada <strong>{$status_text}</strong>.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Ralat pangkalan data: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// AMBIL SENARAI KERETA MILIK PROVIDER INI
$sql_cars = "SELECT * FROM cars WHERE provider_id = ? ORDER BY created_at DESC";
$stmt_cars = $conn->prepare($sql_cars);
$stmt_cars->bind_param("i", $provider_id);
$stmt_cars->execute();
$result_cars = $stmt_cars->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Pemuka Penyedia Kereta - SCRS PMU</title>
    <style>
        body { 
            background-color: #f8f9fa; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .main-content { flex-grow: 1; }
        .car-image { 
            height: 200px; 
            object-fit: cover; 
            border-top-left-radius: calc(.25rem - 1px);
            border-top-right-radius: calc(.25rem - 1px);
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
            <button class="btn btn-light border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand me-auto" href="#">SCRS PMU</a>
            <span class="navbar-text d-none d-md-block fw-bold text-primary">
                Hai, <?php echo htmlspecialchars($provider_name); ?>
            </span>
        </div>
    </nav>

    <!-- OFFCANVAS MENU -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold" id="sidebarMenuLabel">Menu Penyedia Kereta</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-speedometer2 text-primary me-3 fs-4"></i> Papan Pemuka
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-person-circle text-secondary me-3 fs-4"></i> Profil Saya
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
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Senarai Kereta Saya</h3>
                <!-- Butang Buka Modal Tambah Kereta -->
                <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addCarModal">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Kereta
                </button>
            </div>

            <?php echo $message; ?>

            <!-- GRID SENARAI KERETA -->
            <div class="row g-4 mb-5">
                <?php 
                if ($result_cars->num_rows > 0) {
                    while ($car = $result_cars->fetch_assoc()) { 
                        // Warna lencana status
                        $is_available = ($car['status'] == 'Available');
                        $status_badge = $is_available ? 'bg-success' : 'bg-danger';
                        $status_text = $is_available ? 'Tersedia' : 'Tidak Tersedia';
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 h-100 rounded-4">
                        <img src="<?php echo $car['car_image']; ?>" class="card-img-top car-image" alt="Gambar Kereta">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($car['car_model']); ?></h5>
                                <span class="badge <?php echo $status_badge; ?> rounded-pill px-3 py-2"><?php echo $status_text; ?></span>
                            </div>
                            <p class="text-secondary mb-3"><i class="bi bi-123 me-2"></i><?php echo htmlspecialchars($car['car_plate']); ?></p>
                            
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-light text-dark border"><i class="bi bi-gear-fill me-1"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                                <span class="badge bg-light text-dark border"><i class="bi bi-people-fill me-1"></i> <?php echo htmlspecialchars($car['seat_capacity']); ?> Tempat Duduk</span>
                            </div>
                            
                            <!-- Harga (Harian dan Jam) -->
                            <div class="d-flex justify-content-between align-items-center mt-auto border-top pt-3">
                                <div>
                                    <span class="text-secondary" style="font-size: 0.85rem;">Kadar Harian</span><br>
                                    <span class="text-primary fw-bold fs-5">RM <?php echo number_format($car['price_per_day'], 2); ?></span>
                                </div>
                                <div class="text-end">
                                    <span class="text-secondary" style="font-size: 0.85rem;">Kadar Jam</span><br>
                                    <span class="text-primary fw-bold fs-5">RM <?php echo number_format($car['price_per_hour'], 2); ?></span>
                                </div>
                            </div>

                            <!-- Tindakan: Edit & Tukar Status -->
                            <div class="d-flex gap-2 mt-3 pt-3 border-top">
                                <!-- Butang Edit (Buka Modal Edit) -->
                                <button type="button" class="btn btn-outline-primary flex-fill fw-bold" data-bs-toggle="modal" data-bs-target="#editCarModal<?php echo $car['id']; ?>">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>

                                <!-- Borang Tukar Status -->
                                <form action="" method="POST" class="flex-fill m-0">
                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                    <?php if ($is_available): ?>
                                        <input type="hidden" name="new_status" value="Unavailable">
                                        <button type="submit" name="toggle_status" class="btn btn-outline-danger w-100 fw-bold" onclick="return confirm('Tandakan kereta ini sebagai Tidak Tersedia?');">
                                            <i class="bi bi-x-circle"></i> Tutup
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="new_status" value="Available">
                                        <button type="submit" name="toggle_status" class="btn btn-outline-success w-100 fw-bold" onclick="return confirm('Tandakan kereta ini sebagai Tersedia?');">
                                            <i class="bi bi-check-circle"></i> Buka
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- MODAL KEMASKINI KERETA (EDIT) UNTUK SETIAP KERETA -->
                <div class="modal fade" id="editCarModal<?php echo $car['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <div class="modal-header border-bottom-0">
                                <h5 class="modal-title fw-bold text-primary">Kemaskini Kereta</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Model Kereta</label>
                                        <input type="text" class="form-control" name="car_model" value="<?php echo htmlspecialchars($car['car_model']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nombor Plat</label>
                                        <input type="text" class="form-control" name="car_plate" value="<?php echo htmlspecialchars($car['car_plate']); ?>" required>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label fw-bold">Transmisi</label>
                                            <select class="form-select" name="transmission" required>
                                                <option value="Auto" <?php if($car['transmission'] == 'Auto') echo 'selected'; ?>>Auto</option>
                                                <option value="Manual" <?php if($car['transmission'] == 'Manual') echo 'selected'; ?>>Manual</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-bold">Kapasiti Tempat Duduk</label>
                                            <input type="number" class="form-control" name="seat_capacity" min="2" max="12" value="<?php echo $car['seat_capacity']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label fw-bold">Harga / Hari (RM)</label>
                                            <input type="number" step="0.01" class="form-control" name="price_per_day" value="<?php echo $car['price_per_day']; ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-bold">Harga / Jam (RM)</label>
                                            <input type="number" step="0.01" class="form-control" name="price_per_hour" value="<?php echo $car['price_per_hour']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Gambar Kereta (Pilihan)</label>
                                        <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" name="car_image" accept=".jpg, .jpeg, .png">
                                        <small class="text-muted">Biarkan kosong jika tidak mahu menukar gambar.</small>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" name="edit_car" class="btn btn-primary fw-bold py-2">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- TAMAT MODAL KEMASKINI -->

                <?php 
                    } 
                } else {
                    echo '<div class="col-12"><div class="alert alert-light border shadow-sm text-secondary text-center py-4"><i class="bi bi-car-front fs-1 d-block mb-2"></i>Anda belum memuat naik sebarang kereta. Klik <strong>Tambah Kereta</strong> untuk bermula.</div></div>';
                }
                ?>
            </div>

        </div>
    </div>

    <!-- MODAL TAMBAH KERETA -->
    <div class="modal fade" id="addCarModal" tabindex="-1" aria-labelledby="addCarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold text-primary" id="addCarModalLabel">Tambah Kereta Baharu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Model Kereta</label>
                            <input type="text" class="form-control" name="car_model" placeholder="Cth: Perodua Myvi 1.5" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombor Plat</label>
                            <input type="text" class="form-control" name="car_plate" placeholder="Cth: VAA 1234" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Transmisi</label>
                                <select class="form-select" name="transmission" required>
                                    <option value="" disabled selected>Pilih...</option>
                                    <option value="Auto">Auto</option>
                                    <option value="Manual">Manual</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Kapasiti Tempat Duduk</label>
                                <input type="number" class="form-control" name="seat_capacity" min="2" max="12" placeholder="Cth: 5" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Harga / Hari (RM)</label>
                                <input type="number" step="0.01" class="form-control" name="price_per_day" placeholder="Cth: 100.00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Harga / Jam (RM)</label>
                                <input type="number" step="0.01" class="form-control" name="price_per_hour" placeholder="Cth: 15.00" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Muat Naik Gambar Kereta</label>
                            <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" name="car_image" accept=".jpg, .jpeg, .png" required>
                            <small class="text-muted">Format diterima: JPG, JPEG, PNG sahaja.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="add_car" class="btn btn-primary fw-bold py-2">Hantar Maklumat Kereta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- COPYRIGHT FOOTER/WATERMARK -->
    <footer class="text-center py-3 mt-auto text-secondary">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. Hak Cipta Terpelihara.</small>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>