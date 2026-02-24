<?php
require_once = "config/app_config.php";

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM users JOIN roles ON roles.id  = users.roles_id WHERE username=?"

$stmt = mysqli-prepare($conn,$sql);
mysqli_stmt_bind_param($stmt,"s",$username);
mysqli_stmt_execute($stmt);
?>