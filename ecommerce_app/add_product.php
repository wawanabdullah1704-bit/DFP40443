<?php
include 'db.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = htmlspeciualchars(trim($_POST[product_name]));
    $price = trim($_POST["price"]);

    if (empty($name) || empty($price) || is_numeric($price) || $price <= 0) {

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar_band" href="">E-shop Admin</a>
            <a class="btn btn-outline-light btn-sm" href="">View Products</a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="card p-4 mx-auto">
            <h5>Add New Product</h5>

            <?= $msg ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="product_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Price (RM)</label>
                    <input type="number" name="price" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Product Image</label>
                    <input type="file" name="image" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-warning" w-100>Add Product</button>
            </form>
        </div>
    </div>
</body>
</html>