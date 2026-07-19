<?php
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "showroom_db";

$conn = mysqli_connect($host, $user, $pass, $db_name);
if (mysqli_connect_error()) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>