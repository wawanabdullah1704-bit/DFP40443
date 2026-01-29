<?php
session_start();

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $namapengguna = $_POST['user'];
    $katalaluan = $_POST['pass'];

    if($namapengguna == "admin" && $katalaluan == "root"){
        $_SESSION['username'] = $namapengguna;
        header("dashboard.php")
        exit();
    } else {
        $error = "Invalid user";
    }
}
?>
<form>
    User <input name="user" type="text">
    Password <input name="password" type="password">
    <input type="submit" value="login">
</form>