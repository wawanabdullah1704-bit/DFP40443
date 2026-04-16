<?php
require 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0 py-2">Product Catalog Dashboard</h4>
                <a href="add_product.php" class="btn btn-success btn-sm">Add New Product</a>
            </div>
            <div class="card-body rounded-bottom-3 bg-dark">
                
                <?php
                // Display success message from delete operation if it exists
                if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
                    echo "<div class='alert alert-success'>Product successfully deleted.</div>";
                }
                ?>

                <div class="table-responsive">
                    <table class="table table-dark table-bordered table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Price (RM)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // 1. Execute a SELECT * query to retrieve all items
                            $sql = "SELECT * FROM products";
                            $result = mysqli_query($conn, $sql);

                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . $row['id'] . "</td>";
                                    
                                    // 2. Display the uploaded image using the saved database path
                                    echo "<td><img src='" . htmlspecialchars($row['image_path']) . "' alt='Product Image' style='width: 80px; height: auto; border-radius: 5px;'></td>";
                                    
                                    echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                                    echo "<td>" . number_format($row['price'], 2) . "</td>";
                                    
                                    echo "<td>";
                                    // 3. Edit button passing ID in query string
                                    echo "<a href='edit_product.php?id=" . $row['id'] . "' class='btn btn-warning btn-sm me-2'>Edit</a>";
                                    
                                    // 4. Delete button with JS Confirmation Prompt
                                    echo "<a href='delete_product.php?id=" . $row['id'] . "' class='btn btn-danger btn-sm' onclick=\"return confirm('Are you sure you want to permanently delete this product?');\">Delete</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>No products found in the catalog.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>