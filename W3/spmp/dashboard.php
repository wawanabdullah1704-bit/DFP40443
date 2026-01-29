<?php
session_start();

if(!isset($_SESSION['loggedin']!==true)){
    header("Location:login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h3>This is a dashboard <?php echo $_SESSION['username'] ?></h3>
</body>
</html>