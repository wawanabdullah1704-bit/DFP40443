<?php
require 'db.php';

$product_name = $price = "";
$name_err = $price_err = "";

// 1. Fetch existing data
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = trim($_GET['id']);
    
    $sql = "SELECT product_name, price FROM products WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_bind_result($stmt, $product_name, $price);
            if (!mysqli_stmt_fetch($stmt)) {
                header("Location: view_products.php");
                exit();
            }
        }
        mysqli_stmt_close($stmt);
    }
} 
// 2. Process form submission
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    
    $product_name = htmlspecialchars(stripslashes(trim($_POST["product_name"])));
    if (empty($product_name)) {
        $name_err = "Please enter a product name.";
    }

    $price = trim($_POST["price"]);
    if (empty($price)) {
        $price_err = "Please enter a price.";
    } elseif (!is_numeric($price) || $price < 0) {
        $price_err = "Please enter a valid positive number.";
    }

    if (empty($name_err) && empty($price_err)) {
        $sql = "UPDATE products SET product_name = ?, price = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sdi", $product_name, $price, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: view_products.php");
        exit();
    }
} else {
    header("Location: view_products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">Edit Product</h4>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" onsubmit="return validateForm()">
                            
                            <input type="hidden" name="id" value="<?php echo $id; ?>">

                            <div class="mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="product_name" id="product_name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($product_name); ?>">
                                <span class="invalid-feedback"><?php echo $name_err; ?></span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Price (RM)</label>
                                <input type="text" name="price" id="price" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($price); ?>">
                                <span class="invalid-feedback"><?php echo $price_err; ?></span>
                            </div>

                            <button type="submit" class="btn btn-warning w-100">Update Product</button>
                            <a href="view_products.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function validateForm() {
            let name = document.getElementById("product_name").value;
            let price = document.getElementById("price").value;

            if (name.trim() == "") {
                alert("Product name must be filled out");
                return false;
            }
            if (price.trim() == "" || isNaN(price) || price < 0) {
                alert("Please enter a valid positive price");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>