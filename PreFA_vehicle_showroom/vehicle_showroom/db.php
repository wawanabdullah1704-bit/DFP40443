<?php
// =============================================
// Database Connection - Procedural MySQLi
// =============================================

$host     = "localhost";
$user     = "root";
$pass     = "";
$dbname   = "showroom_db";

// Step 1: Connect to MySQL server (without DB) to create the database if missing
$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Step 2: Create database if it doesn't exist
$createDb = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!mysqli_query($conn, $createDb)) {
    die("Error creating database: " . mysqli_error($conn));
}

// Step 3: Select the database
mysqli_select_db($conn, $dbname);

// Step 4: Create admins table
$createAdmins = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)";
if (!mysqli_query($conn, $createAdmins)) {
    die("Error creating admins table: " . mysqli_error($conn));
}

// Step 5: Create vehicles table
$createVehicles = "CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255) NOT NULL
)";
if (!mysqli_query($conn, $createVehicles)) {
    die("Error creating vehicles table: " . mysqli_error($conn));
}

// Step 6: Insert default admin if not exists (password hashed)
$checkAdmin = "SELECT id FROM admins WHERE username = 'admin'";
$result = mysqli_query($conn, $checkAdmin);
if (mysqli_num_rows($result) == 0) {
    $defaultUser = "admin";
    $defaultPass = password_hash("admin123", PASSWORD_DEFAULT);
    $insertAdmin = "INSERT INTO admins (username, password) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $insertAdmin);
    mysqli_stmt_bind_param($stmt, "ss", $defaultUser, $defaultPass);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>
