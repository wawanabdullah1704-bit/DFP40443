<?php
require 'db.php';

$product_name = $price = "";
$name_err = $price_err = $success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = htmlspecialchars(stripslashes(trim($_POST["product_name"])));
    $price = trim($_POST["price"]);

    if (empty($product_name)) {
        $name_err = "Please enter a product name.";
    }

    if (empty($price)) {
        $price_err = "Please enter a price.";
    } elseif (!is_numeric($price) || $price < 0) {
        $price_err = "Please enter a valid positive number.";
    }

    if (empty($name_err) && empty($price_err)) {
        $sql = "INSERT INTO products (product_name, price) VALUES (?, ?)";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            // "sd" means string, double (decimal)
            mysqli_stmt_bind_param($stmt, "sd", $product_name, $price);

            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Product added successfully!";
                $product_name = $price = "";
            } else {
                echo "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Add New Product</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_msg)): ?>
                            <div class="alert alert-success"><?php echo $success_msg; ?></div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" onsubmit="return validateForm()">

                            <div class="mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="product_name" id="product_name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $product_name; ?>">
                                <span class="invalid-feedback"><?php echo $name_err; ?></span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Price (RM)</label>
                                <input type="text" name="price" id="price" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $price; ?>">
                                <span class="invalid-feedback"><?php echo $price_err; ?></span>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Add Product</button>
                            <a href="view_products.php" class="btn btn-secondary w-100 mt-2">View Catalog</a>
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