<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "gms_student";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection and handle errors
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>