<?php
session_start();
require 'db.php';

// Check if the user is logged in and is a student.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['username'];
$message = "";

// 1. PROCESS BOOKING CONFIRMATION (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking'])) {
    $car_id = (int)$_POST['car_id'];
    $rent_type = htmlspecialchars($_POST['rent_type']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_price = (float)$_POST['total_price'];

    // Enter the booking data into the database.
    $sql_book = "INSERT INTO bookings (student_id, car_id, rent_type, start_date, end_date, total_price) 
                 VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_book = $conn->prepare($sql_book);
    $stmt_book->bind_param("iisssd", $student_id, $car_id, $rent_type, $start_date, $end_date, $total_price);

    if ($stmt_book->execute()) {
        // Retrieve the newly created booking ID.
        $booking_id = $stmt_book->insert_id;
        
        // Take the student directly to the payment page (payment.php).
        header("Location: payment.php?id=" . $booking_id);
        exit();
    } else {
        $message = "<div class='alert alert-danger'>Database error: " . $stmt_book->error . "</div>";
    }
    $stmt_book->close();
}

// 2. HANDLE SEARCH & FILTER (GET)
$search_start = $_GET['start_date'] ?? '';
$search_end = $_GET['end_date'] ?? '';
$rent_type = $_GET['rent_type'] ?? '';
$result_cars = null;
$search_error = "";
$duration = 0;

if ($search_start && $search_end && $rent_type) {
    $start_ts = strtotime($search_start);
    $end_ts = strtotime($search_end);

    if ($start_ts >= $end_ts) {
        $search_error = "Return date/time must be after Pick-up date/time.";
    } else {
        //Calculate the duration for a price estimate.
        if ($rent_type === 'Hourly') {
            $duration = ceil(($end_ts - $start_ts) / 3600); // Total hours
        } else {
            $duration = ceil(($end_ts - $start_ts) / 86400); // Total days
            if ($duration < 1) $duration = 1;
        }

        // Query to search for available cars (filtering out overlapping bookings)
        $sql_cars = "SELECT * FROM cars 
                     WHERE status = 'Available' 
                     AND id NOT IN (
                         SELECT car_id FROM bookings 
                         WHERE status IN ('Pending', 'Approved') 
                         AND (start_date < ? AND end_date > ?)
                     )
                     ORDER BY created_at DESC";
                     
        $stmt_cars = $conn->prepare($sql_cars);
        $stmt_cars->bind_param("ss", $search_end, $search_start);
        $stmt_cars->execute();
        $result_cars = $stmt_cars->get_result();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SCRS PMU</title>
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
                Hi, <?php echo htmlspecialchars($student_name); ?>
            </span>
        </div>
    </nav>

    <!-- OFFCANVAS MENU -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold">Student Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="dashboard.php">
                        <i class="bi bi-search text-primary me-3 fs-4"></i> Find Car
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-card-checklist text-success me-3 fs-4"></i> My Bookings
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
            
            <?php echo $message; ?>
            <?php if ($search_error) echo "<div class='alert alert-danger'>$search_error</div>"; ?>

            <!-- STEP 1: SEARCH FORM -->
            <div class="search-card p-4 border mb-5">
                <h4 class="fw-bold mb-3"><i class="bi bi-calendar-check text-primary me-2"></i>Step 1: Choose Date & Time</h4>
                <form action="" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Rent Type</label>
                            <select class="form-select" name="rent_type" required>
                                <option value="" disabled <?php echo empty($rent_type) ? 'selected' : ''; ?>>Select...</option>
                                <option value="Daily" <?php echo ($rent_type === 'Daily') ? 'selected' : ''; ?>>Daily</option>
                                <option value="Hourly" <?php echo ($rent_type === 'Hourly') ? 'selected' : ''; ?>>Hourly</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Pick-up Date & Time</label>
                            <input type="datetime-local" class="form-control" name="start_date" value="<?php echo htmlspecialchars($search_start); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Return Date & Time</label>
                            <input type="datetime-local" class="form-control" name="end_date" value="<?php echo htmlspecialchars($search_end); ?>" required>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary fw-bold py-2"><i class="bi bi-search me-1"></i> Search</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- STEP 2: DISPLAY AVAILABLE CARS -->
            <?php if ($result_cars !== null): ?>
                
                <h4 class="fw-bold mb-4"><i class="bi bi-car-front-fill text-success me-2"></i>Step 2: Select a Car</h4>
                <p class="text-secondary">Showing available cars for <strong><?php echo $duration; ?> <?php echo ($rent_type === 'Daily') ? 'Day(s)' : 'Hour(s)'; ?></strong>.</p>
                
                <div class="row g-4">
                    <?php 
                    if ($result_cars->num_rows > 0) {
                        while ($car = $result_cars->fetch_assoc()) { 
                            // Calculate total price based on duration
                            $price_rate = ($rent_type === 'Daily') ? $car['price_per_day'] : $car['price_per_hour'];
                            $total_calc_price = $duration * $price_rate;
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100 rounded-4">
                            <img src="<?php echo $car['car_image']; ?>" class="card-img-top car-image" alt="Car Image">
                            <div class="card-body">
                                
                                <h5 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($car['car_model']); ?></h5>
                                <p class="text-secondary mb-3"><i class="bi bi-123 me-2"></i><?php echo htmlspecialchars($car['car_plate']); ?></p>
                                
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-light text-dark border"><i class="bi bi-gear-fill me-1"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-people-fill me-1"></i> <?php echo htmlspecialchars($car['seat_capacity']); ?> Seats</span>
                                </div>
                                
                                <!-- Total Price Calculation Display -->
                                <div class="p-3 bg-light rounded-3 mb-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-secondary" style="font-size: 0.85rem;">Rate (<?php echo $rent_type; ?>)</span>
                                        <span class="fw-bold">RM <?php echo number_format($price_rate, 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                                        <span class="text-secondary fw-bold">Total Price:</span>
                                        <span class="text-primary fw-bold fs-5">RM <?php echo number_format($total_calc_price, 2); ?></span>
                                    </div>
                                </div>

                                <!-- Trigger Confirmation Modal -->
                                <button type="button" class="btn btn-success w-100 fw-bold py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#confirmModal<?php echo $car['id']; ?>">
                                    Book This Car
                                </button>

                            </div>
                        </div>
                    </div>

                    <!-- CONFIRMATION MODAL -->
                    <div class="modal fade" id="confirmModal<?php echo $car['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header border-bottom-0 bg-light rounded-top-4">
                                    <h5 class="modal-title fw-bold text-dark">Confirm Booking</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <h5 class="fw-bold text-primary mb-3"><?php echo htmlspecialchars($car['car_model']); ?></h5>
                                    
                                    <ul class="list-group list-group-flush mb-4 border-bottom pb-3">
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-secondary">Pick-up:</span> <strong><?php echo date('d M Y, h:i A', strtotime($search_start)); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-secondary">Return:</span> <strong><?php echo date('d M Y, h:i A', strtotime($search_end)); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-secondary">Duration:</span> <strong><?php echo $duration; ?> <?php echo ($rent_type === 'Daily') ? 'Day(s)' : 'Hour(s)'; ?></strong>
                                        </li>
                                    </ul>
                                    
                                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 mb-4 border">
                                        <span class="fw-bold fs-5">Total to Pay:</span>
                                        <span class="fw-bold fs-4 text-success">RM <?php echo number_format($total_calc_price, 2); ?></span>
                                    </div>

                                    <form action="" method="POST">
                                        <!-- Hidden inputs to pass data -->
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <input type="hidden" name="rent_type" value="<?php echo htmlspecialchars($rent_type); ?>">
                                        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($search_start); ?>">
                                        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($search_end); ?>">
                                        <input type="hidden" name="total_price" value="<?php echo $total_calc_price; ?>">
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="confirm_booking" class="btn btn-primary fw-bold py-2">Confirm & Submit</button>
                                            <button type="button" class="btn btn-light border text-secondary fw-bold py-2" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END MODAL -->

                    <?php 
                        } 
                    } else {
                        echo '<div class="col-12"><div class="alert alert-warning border shadow-sm text-center py-4"><i class="bi bi-emoji-frown fs-1 d-block mb-2"></i>Sorry, no cars are available for the selected dates. Please try another date or time.</div></div>';
                    }
                    ?>
                </div>
            <?php elseif (empty($search_error)): ?>
                <!-- Default state before searching -->
                <div class="text-center py-5 mt-4 text-secondary">
                    <i class="bi bi-search fs-1 mb-3 d-block text-muted opacity-50"></i>
                    <h4>Please select your dates above to find available cars.</h4>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- COPYRIGHT FOOTER -->
    <footer class="text-center py-3 mt-5 text-secondary border-top bg-white">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. All Rights Reserved.</small>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>