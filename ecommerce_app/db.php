<?php
$host = "127.0.0.1:3307";
$user = "root";
$password = "";
$database = "ecommerce_db";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed" . mysqli_connect_error());
}
?>