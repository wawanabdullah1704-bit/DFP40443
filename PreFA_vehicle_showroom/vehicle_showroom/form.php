<?php
session_start();
include 'db.php';

// Strict access control
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$errors    = [];
$mode      = "Add";
$vehicle   = ['id' => '', 'make' => '', 'model' => '', 'year' => '', 'price' => '', 'image_path' => ''];

// --- EDIT MODE: Pre-fill data ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM vehicles WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $vehicle = $row;
        $mode = "Edit";
    } else {
        header("Location: index.php");
        exit();
    }
    mysqli_stmt_close($stmt);
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize using htmlspecialchars (XSS mitigation)
    $id    = $_POST['id'] ?? '';
    $make  = htmlspecialchars(trim($_POST['make'] ?? ''));
    $model = htmlspecialchars(trim($_POST['model'] ?? ''));
    $year  = trim($_POST['year'] ?? '');
    $price = trim($_POST['price'] ?? '');

    // Sticky form values
    $vehicle['id']    = $id;
    $vehicle['make']  = $make;
    $vehicle['model'] = $model;
    $vehicle['year']  = $year;
    $vehicle['price'] = $price;

    // Validation
    if (empty($make))  $errors[] = "Make is required.";
    if (empty($model)) $errors[] = "Model is required.";
    if (empty($year) || !is_numeric($year) || $year < 1900 || $year > (date('Y') + 1)) {
        $errors[] = "Please enter a valid year.";
    }
    if (empty($price) || !is_numeric($price) || $price < 0) {
        $errors[] = "Please enter a valid price.";
    }

    // Determine if updating with existing image, or new upload
    $imagePath  = $vehicle['image_path']; // keep existing in edit mode
    $newUpload  = false;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);

        if (!in_array($fileType, $allowed)) {
            $errors[] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image size must be less than 5MB.";
        } else {
            // Build a safe unique filename
            $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newName  = "uploads/vehicle_" . time() . "_" . rand(1000, 9999) . "." . $ext;

            if (!is_dir("uploads")) mkdir("uploads", 0777, true);

            if (move_uploaded_file($_FILES['image']['tmp_name'], $newName)) {
                // If editing and there is an old image, delete it from server
                if ($mode == "Edit" && !empty($vehicle['image_path']) && file_exists($vehicle['image_path'])) {
                    unlink($vehicle['image_path']);
                }
                $imagePath = $newName;
                $newUpload = true;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    } else {
        // New entry must have an image
        if ($mode == "Add") {
            $errors[] = "Vehicle image is required.";
        }
    }

    // If no errors, save to DB
    if (empty($errors)) {
        if ($mode == "Add") {
            $sql = "INSERT INTO vehicles (make, model, year, price, image_path) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssids", $make, $model, $year, $price, $imagePath);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: index.php?msg=added");
                exit();
            } else {
                $errors[] = "Failed to add vehicle: " . mysqli_error($conn);
            }
        } else {
            // Update
            $sql = "UPDATE vehicles SET make=?, model=?, year=?, price=?, image_path=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssidsi", $make, $model, $year, $price, $imagePath, $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: index.php?msg=updated");
                exit();
            } else {
                $errors[] = "Failed to update vehicle: " . mysqli_error($conn);
            }
        }
    }

    // Update sticky image_path so the preview reflects newly uploaded one
    $vehicle['image_path'] = $imagePath;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode; ?> Vehicle - Vehicle Showroom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-custom { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link { color: white !important; }
        .form-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .preview-img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-car-front-fill"></i> Vehicle Showroom
            </a>
            <ul class="navbar-nav ms-auto flex-row">
                <li class="nav-item me-3">
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
    </nav>

    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="card form-card">
                    <div class="card-body p-4">
                        <h3 class="mb-4">
                            <i class="bi bi-<?php echo $mode == 'Add' ? 'plus-circle' : 'pencil-square'; ?>"></i>
                            <?php echo $mode; ?> Vehicle
                        </h3>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $e): ?>
                                        <li><?php echo htmlspecialchars($e); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- multipart/form-data for file upload -->
                        <form method="POST" action="form.php<?php echo !empty($vehicle['id']) ? '?id=' . $vehicle['id'] : ''; ?>" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($vehicle['id']); ?>">

                            <div class="mb-3">
                                <label class="form-label">Make <span class="text-danger">*</span></label>
                                <input type="text" name="make" class="form-control"
                                       value="<?php echo htmlspecialchars($vehicle['make']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Model <span class="text-danger">*</span></label>
                                <input type="text" name="model" class="form-control"
                                       value="<?php echo htmlspecialchars($vehicle['model']); ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Year <span class="text-danger">*</span></label>
                                    <input type="number" name="year" class="form-control"
                                           min="1900" max="<?php echo date('Y') + 1; ?>"
                                           value="<?php echo htmlspecialchars($vehicle['year']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Price (RM) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="price" class="form-control"
                                           min="0"
                                           value="<?php echo htmlspecialchars($vehicle['price']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Vehicle Image
                                    <?php if ($mode == 'Add'): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="file" name="image" class="form-control" accept="image/*"
                                       <?php echo $mode == 'Add' ? 'required' : ''; ?>>
                                <small class="text-muted">Allowed: JPG, PNG, GIF, WEBP (max 5MB)</small>

                                <?php if ($mode == 'Edit' && !empty($vehicle['image_path']) && file_exists($vehicle['image_path'])): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Current image:</small><br>
                                        <img src="<?php echo htmlspecialchars($vehicle['image_path']); ?>"
                                             class="preview-img mt-1" alt="current">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?php echo $mode == 'Add' ? 'Add Vehicle' : 'Update Vehicle'; ?>
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
