<?php
$config = include('config/app_config.php')
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['site_name'] ?></title>
</head>
<body>
    <form action="index.php" method="POST">
        User <input name="username" type="text">
        Password <input name="password" type="password">
        <input type="submit" value="Login">
    </form>
</body>
</html>