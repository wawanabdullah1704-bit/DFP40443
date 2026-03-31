<?php 

$host = "127.0.0.1:3306";
$user = "root";
$pass = "";
$db = "mini_project";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn){
    die("Connection failed". mysqli_connect_error());
}

?>