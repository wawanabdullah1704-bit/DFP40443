<?php
session_start();
include("db.php");
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }

global $conn;

$id = $_GET['id'] ?? null;
$make = $model = $year = $price = $image = $error = "";

// Fetch data for Edit mode (Procedural)
if ($id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM vehicles WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $make = $row['make']; $model = $row['model'];
        $year = $row['year']; $price = $row['price'];
        $image = $row['image_path'];
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $make = htmlspecialchars(trim($_POST['make']));
    $model = htmlspecialchars(trim($_POST['model']));
    $year = trim($_POST['year']);
    $price = trim($_POST['price']);
    $new_image = $image; 

    // File Upload Handling
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'png', 'jpeg', 'webp'])) {
            if (!is_dir('uploads')) mkdir('uploads', 0777, true);
            $new_image = "uploads/veh_" . time() . ".$ext";
            move_uploaded_file($_FILES['image']['tmp_name'], $new_image);
            
            // Unlink old file if updating
            if ($id && file_exists($image)) unlink($image);
        } else { $error = "Only JPG/PNG/WEBP files are allowed."; }
    }

    if (empty($make) || empty($model) || empty($year) || empty($price)) {
        $error = "All text fields are required.";
    } elseif (!is_numeric($price) || !is_numeric($year)) {
        $error = "Year and Price must be valid numbers.";
    } elseif (empty($error)) {
        if ($id) { // Procedural UPDATE
            $stmt = mysqli_prepare($conn, "UPDATE vehicles SET make=?, model=?, year=?, price=?, image_path=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssidsi", $make, $model, $year, $price, $new_image, $id);
        } else { // Procedural INSERT
            $stmt = mysqli_prepare($conn, "INSERT INTO vehicles (make, model, year, price, image_path) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssids", $make, $model, $year, $price, $new_image);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html><head><title>Manage Vehicle</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="container mt-5 w-50">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white"><h4><?= $id ? "Edit" : "Add" ?> Vehicle</h4></div>
        <div class="card-body">
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                
                <div class="mb-3"><label>Make</label><input type="text" name="make" class="form-control" value="<?= htmlspecialchars($make) ?>" required></div>
                <div class="mb-3"><label>Model</label><input type="text" name="model" class="form-control" value="<?= htmlspecialchars($model) ?>" required></div>
                <div class="mb-3"><label>Year</label><input type="number" name="year" class="form-control" value="<?= htmlspecialchars($year) ?>" required></div>
                <div class="mb-3"><label>Price ($)</label><input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($price) ?>" required></div>
                
                <?php if($image): ?><img src="<?= htmlspecialchars($image) ?>" width="120" class="mb-2 d-block rounded border"><?php endif; ?>
                <div class="mb-4"><label>Vehicle Image</label><input type="file" name="image" class="form-control" <?= $id ? "" : "required" ?>></div>
                
                <button type="submit" class="btn btn-primary w-100">Save Vehicle</button>
                <a href="index.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
            </form>
        </div>
    </div>
</body></html>