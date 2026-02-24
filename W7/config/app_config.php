<?php

$host = "127.0.0.1:3307";
$user = "root";
$pass = "";
$db = "spmp";

$conn = mysqli_connect($host,$user,$pass,$db);

if(!$conn){
    die("Gagal sambungan, connection failed". mysqli_connect_error());
}

?>