<?php
require 'db.php';

// Initialize sticky variables and error messages
$product_name = $price = "";
$name_err = $price_err = $image_err = $success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize text inputs
    $product_name = htmlspecialchars(stripslashes(trim($_POST["product_name"])));
    $price = trim($_POST["price"]);

    // 2. Validate Name
    if (empty($product_name)) {
        $name_err = "Please enter a product name.";
    }

    // 3. Validate Price (Strict numeric check)
    if (empty($price)) {
        $price_err = "Please enter a price.";
    } elseif (!is_numeric($price) || $price < 0) {
        $price_err = "Please enter a valid positive number.";
    }

    // 4. Validate and Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_tmp = $_FILES['image']['tmp_name'];

        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Check size (2MB limit)
        if ($file_size > 2097152) {
            $image_err = "File size must not exceed 2MB.";
        }
        // Check extension
        elseif (!in_array($file_ext, $allowed_ext)) {
            $image_err = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        } else {
            // Rename file to prevent overwriting
            $new_file_name = uniqid() . '.' . $file_ext;
            $upload_path = 'product_images/' . $new_file_name;
        }
    } else {
        $image_err = "Please select an image to upload.";
    }

    // 5. Insert into Database using Prepared Statements (If no errors)
    if (empty($name_err) && empty($price_err) && empty($image_err)) {

        if (!is_dir('product_images/')) {
            mkdir('product_images/', 0777, true);
        }

        if (move_uploaded_file($file_tmp, $upload_path)) {
            $sql = "INSERT INTO products (product_name, price, image_path) VALUES (?, ?, ?)";

            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sds", $product_name, $price, $upload_path);

                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "Product added successfully!";
                    // Clear form after success
                    $product_name = $price = "";
                } else {
                    $image_err = "Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $image_err = "Failed to move uploaded file.";
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
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0 py-2">Add New Product</h4>
                    </div>
                    <div class="card-body rounded-bottom-3 bg-dark text-white">
                        <?php if (!empty($success_msg)): ?>
                            <div class="alert alert-success"><?php echo $success_msg; ?></div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">

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

                            <div class="mb-3">
                                <label class="form-label">Product Image</label>
                                <input type="file" name="image" id="image" class="form-control <?php echo (!empty($image_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $image_err; ?></span>
                            </div>

                            <button type="submit" class="btn btn-success w-100">Add Product</button>
                            <a href="view_products.php" class="btn btn-primary w-100 mt-2">View Catalog</a>
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
            let image = document.getElementById("image").value;

            if (name.trim() == "") {
                alert("Product name must be filled out");
                return false;
            }
            if (price.trim() == "" || isNaN(price) || price < 0) {
                alert("Please enter a valid positive price");
                return false;
            }
            if (image == "") {
                alert("Please select an image");
                return false;
            }
            return true;
        }
    </script>
</body>

</html>