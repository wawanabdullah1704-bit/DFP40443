<?php
require 'db.php';

$product_name = $price = $current_image = "";
$name_err = $price_err = $image_err = "";

// 1. Fetch existing data when page loads via GET request
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = trim($_GET['id']);
    
    $sql = "SELECT product_name, price, image_path FROM products WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_bind_result($stmt, $product_name, $price, $current_image);
            if (!mysqli_stmt_fetch($stmt)) {
                header("Location: view_products.php");
                exit();
            }
        }
        mysqli_stmt_close($stmt);
    }
} 
// 2. Process the form submission via POST request
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id']; // Retrieved from hidden input
    $current_image = $_POST['current_image']; // Retrieved from hidden input
    
    // Sanitize and Validate Name
    $product_name = htmlspecialchars(stripslashes(trim($_POST["product_name"])));
    if (empty($product_name)) {
        $name_err = "Please enter a product name.";
    }

    // Sanitize and Validate Price
    $price = trim($_POST["price"]);
    if (empty($price)) {
        $price_err = "Please enter a price.";
    } elseif (!is_numeric($price) || $price < 0) {
        $price_err = "Please enter a valid positive number.";
    }

    // Handle Image Update Logic
    $new_image_uploaded = false;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_size > 2097152) {
            $image_err = "File size must not exceed 2MB.";
        } elseif (!in_array($file_ext, $allowed_ext)) {
            $image_err = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        } else {
            $new_file_name = uniqid() . '.' . $file_ext;
            $upload_path = 'product_images/' . $new_file_name;
            $new_image_uploaded = true;
        }
    }

    // Execute UPDATE if there are no errors
    if (empty($name_err) && empty($price_err) && empty($image_err)) {
        
        if ($new_image_uploaded) {
            // SCENARIO A: User uploaded a new image
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Delete the old physical image using unlink()
                if (!empty($current_image) && file_exists($current_image)) {
                    unlink($current_image); 
                }

                $sql = "UPDATE products SET product_name = ?, price = ?, image_path = ? WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "sdsi", $product_name, $price, $upload_path, $id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            } else {
                $image_err = "Failed to upload new image.";
            }
        } else {
            // SCENARIO B: User only updated text fields (Name/Price)
            $sql = "UPDATE products SET product_name = ?, price = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sdi", $product_name, $price, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        
        // Redirect back to dashboard if no errors occurred
        if(empty($image_err)) {
            header("Location: view_products.php");
            exit();
        }
    }
} else {
    // Redirect if accessed directly without ID or POST
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
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                            
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($current_image); ?>">

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

                            <div class="mb-3">
                                <label class="form-label">Current Image</label><br>
                                <img src="<?php echo htmlspecialchars($current_image); ?>" alt="Current Product Image" class="img-thumbnail mb-2" style="max-width: 150px;">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload New Image (Leave blank to keep current image)</label>
                                <input type="file" name="image" id="image" class="form-control <?php echo (!empty($image_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $image_err; ?></span>
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
            // We don't validate image here because leaving it empty is allowed during an update!
            return true;
        }
    </script>
</body>
</html>