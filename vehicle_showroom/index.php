<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once 'db.php';
global $conn;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search%";

$stmt = mysqli_prepare($conn, "SELECT * FROM vehicles WHERE make LIKE ? OR model LIKE ?");
mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Vehicle Showroom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Showroom Inventory</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="form.php" class="btn btn-success">Add New Vehicle</a>
    </div>

    <form method="GET" action="index.php" class="d-flex mb-4">
        <input type="text" name="search" class="form-control me-2" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="index.php" class="btn btn-secondary ms-2">Reset</a>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Image</th>
                    <th>Make</th>
                    <th>Model</th>
                    <th>Year</th>
                    <th>Price (RM)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Vehicle Image" width="80"></td>
                    <td><?= htmlspecialchars($row['make']) ?></td>
                    <td><?= htmlspecialchars($row['model']) ?></td>
                    <td><?= htmlspecialchars($row['year']) ?></td>
                    <td><?= number_format($row['price'], 2) ?></td>
                    <td>
                        <a href="form.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this vehicle?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) == 0): ?>
                <tr><td colspan="6" class="text-center">No vehicles found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
<?php mysqli_stmt_close($stmt); ?>