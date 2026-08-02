<?php
session_start();
require 'db.php';

// Check if user is logged in and is a provider
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit();
}

$provider_id = $_SESSION['provider_id'];
$provider_name = $_SESSION['username'];
$message = "";

// ADD CAR PROCESS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_car'])) {
    
    $car_model = htmlspecialchars($_POST['car_model']);
    $car_plate = htmlspecialchars($_POST['car_plate']);
    $transmission = htmlspecialchars($_POST['transmission']);
    $seat_capacity = (int)$_POST['seat_capacity'];
    $price_per_day = (float)$_POST['price_per_day'];
    $price_per_hour = (float)$_POST['price_per_hour']; // Get hourly price

    // Image Upload Management
    $targetDir = "uploads/cars/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $imageName = basename($_FILES["car_image"]["name"]);
    // Create unique image name
    $newImageName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imageName);
    $targetPath = $targetDir . $newImageName;

    // Upload car image
    if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $targetPath)) {
        
        // SQL updated to include price_per_hour
        $sql = "INSERT INTO cars (provider_id, car_model, car_plate, transmission, seat_capacity, price_per_day, price_per_hour, car_image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        // Bind 8 parameters: isssidds (integer, string, string, string, integer, double, double, string)
        $stmt->bind_param("isssidds", $provider_id, $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $targetPath);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Success: <strong>{$car_model}</strong> has been added to your car list!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Database error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger'>Error: Failed to upload the car image.</div>";
    }
}

// FETCH CARS BELONGING TO THIS PROVIDER
$sql_cars = "SELECT * FROM cars WHERE provider_id = ? ORDER BY created_at DESC";
$stmt_cars = $conn->prepare($sql_cars);
$stmt_cars->bind_param("i", $provider_id);
$stmt_cars->execute();
$result_cars = $stmt_cars->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard - SCRS PMU</title>
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
        </div>
    </nav>

    <!-- OFFCANVAS MENU -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold" id="sidebarMenuLabel">Provider Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-speedometer2 text-primary me-3 fs-4"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-person-circle text-secondary me-3 fs-4"></i> My Profile
                    </a>
                </li>
                <li class="nav-item mb-2 mt-auto">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="login.php">
                        <i class="bi bi-box-arrow-left text-danger me-3 fs-4"></i> Log Out
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="container mt-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">My Car List</h3>
                <!-- Button to trigger Add Car Modal -->
                <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addCarModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Car
                </button>
            </div>

            <?php echo $message; ?>

            <!-- CAR LIST GRID -->
            <div class="row g-4">
                <?php 
                if ($result_cars->num_rows > 0) {
                    while ($car = $result_cars->fetch_assoc()) { 
                        // Determine status badge color
                        $status_badge = ($car['status'] == 'Available') ? 'bg-success' : 'bg-danger';
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 h-100 rounded-4">
                        <img src="<?php echo $car['car_image']; ?>" class="card-img-top car-image" alt="Car Image">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($car['car_model']); ?></h5>
                                <span class="badge <?php echo $status_badge; ?> rounded-pill"><?php echo $car['status']; ?></span>
                            </div>
                            <p class="text-secondary mb-3"><i class="bi bi-123 me-2"></i><?php echo htmlspecialchars($car['car_plate']); ?></p>
                            
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-light text-dark border"><i class="bi bi-gear-fill me-1"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                                <span class="badge bg-light text-dark border"><i class="bi bi-people-fill me-1"></i> <?php echo htmlspecialchars($car['seat_capacity']); ?> Seats</span>
                            </div>
                            
                            <!-- Pricing Section (Daily and Hourly) -->
                            <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-3">
                                <div>
                                    <span class="text-secondary" style="font-size: 0.85rem;">Daily Rate</span><br>
                                    <span class="text-primary fw-bold fs-5">RM <?php echo number_format($car['price_per_day'], 2); ?></span>
                                </div>
                                <div class="text-end">
                                    <span class="text-secondary" style="font-size: 0.85rem;">Hourly Rate</span><br>
                                    <span class="text-primary fw-bold fs-5">RM <?php echo number_format($car['price_per_hour'], 2); ?></span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <?php 
                    } 
                } else {
                    echo '<div class="col-12"><div class="alert alert-light border shadow-sm text-secondary text-center py-4"><i class="bi bi-car-front fs-1 d-block mb-2"></i>You have not uploaded any cars yet. Click <strong>Add Car</strong> to get started.</div></div>';
                }
                ?>
            </div>

        </div>
    </div>

    <!-- ADD CAR MODAL -->
    <div class="modal fade" id="addCarModal" tabindex="-1" aria-labelledby="addCarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold text-primary" id="addCarModalLabel">Add New Car</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Car Model</label>
                            <input type="text" class="form-control" name="car_model" placeholder="e.g. Perodua Myvi 1.5" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Plate Number</label>
                            <input type="text" class="form-control" name="car_plate" placeholder="e.g. VAA 1234" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Transmission</label>
                                <select class="form-select" name="transmission" required>
                                    <option value="" disabled selected>Select...</option>
                                    <option value="Auto">Auto</option>
                                    <option value="Manual">Manual</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Seat Capacity</label>
                                <input type="number" class="form-control" name="seat_capacity" min="2" max="12" placeholder="e.g. 5" required>
                            </div>
                        </div>

                        <!-- Added Hourly Rate Input Here -->
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Price / Day (RM)</label>
                                <input type="number" step="0.01" class="form-control" name="price_per_day" placeholder="e.g. 100.00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Price / Hour (RM)</label>
                                <input type="number" step="0.01" class="form-control" name="price_per_hour" placeholder="e.g. 15.00" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Upload Car Image</label>
                            <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" name="car_image" accept=".jpg, .jpeg, .png" required>
                            <small class="text-muted">Accepted formats: JPG, JPEG, PNG only.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="add_car" class="btn btn-primary fw-bold py-2">Submit Car Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- COPYRIGHT FOOTER/WATERMARK -->
    <footer class="text-center py-3 mt-auto text-secondary">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. All Rights Reserved.</small>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>