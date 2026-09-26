<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "scrs_pmu";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Kawalan Anti-Cache: Menghalang pelayar web menyimpan cache halaman apabila pengguna menekan butang Back selepas log keluar
if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}
?>