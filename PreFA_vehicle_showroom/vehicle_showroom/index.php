<?php
session_start();
include 'db.php';

// Strict access control
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Synchronous search via GET
$search = $_GET['search'] ?? '';
$searchTerm = "%" . $search . "%";

// Procedural prepared statement with LIKE
$sql = "SELECT * FROM vehicles 
        WHERE make LIKE ? OR model LIKE ? OR year LIKE ? 
        ORDER BY id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sss", $searchTerm, $searchTerm, $searchTerm);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Success message handling
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Vehicle Showroom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-custom {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link { color: white !important; }
        .card-stat {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .vehicle-img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
        .table thead { background-color: #1e3c72; color: white; }
        .table-responsive { border-radius: 10px; overflow: hidden; }
        @media (max-width: 576px) {
            .vehicle-img { width: 60px; height: 45px; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-car-front-fill"></i> Vehicle Showroom
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="bi bi-person-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <h2 class="mb-2"><i class="bi bi-speedometer2"></i> Inventory Dashboard</h2>
            <a href="form.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Vehicle
            </a>
        </div>

        <!-- Alert messages -->
        <?php if ($msg == 'added'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> Vehicle added successfully!
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($msg == 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> Vehicle updated successfully!
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($msg == 'deleted'): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="bi bi-trash"></i> Vehicle deleted successfully!
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Synchronous GET Search Form (causes full page reload) -->
        <div class="card card-stat mb-4">
            <div class="card-body">
                <form method="GET" action="index.php" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control"
                               placeholder="Search by make, model, or year..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </form>
                <?php if (!empty($search)): ?>
                    <div class="mt-2">
                        <small class="text-muted">
                            Showing results for: <strong><?php echo htmlspecialchars($search); ?></strong>
                            <a href="index.php" class="ms-2">Clear</a>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vehicles Table -->
        <div class="card card-stat">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Make</th>
                                <th>Model</th>
                                <th>Year</th>
                                <th>Price (RM)</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                                                     class="vehicle-img" alt="vehicle">
                                            <?php else: ?>
                                                <div class="vehicle-img bg-secondary d-flex align-items-center justify-content-center text-white">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['make']); ?></td>
                                        <td><?php echo htmlspecialchars($row['model']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year']); ?></td>
                                        <td>RM <?php echo number_format($row['price'], 2); ?></td>
                                        <td class="text-center">
                                            <a href="form.php?id=<?php echo $row['id']; ?>"
                                               class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $row['id']; ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this vehicle?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mb-0 mt-2">No vehicles found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer class="text-center text-muted mt-4">
            <small>&copy; <?php echo date('Y'); ?> Vehicle Showroom Inventory System</small>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_stmt_close($stmt); ?>
