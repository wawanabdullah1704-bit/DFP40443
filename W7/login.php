<?php
$config = include('config/app_config.php');
require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['site_name'] ?></title>
</head>
<body>
    <div class="m-5">
        <form action="process_login.php" method="POST">
            User <input name="username" type="text">
            Password <input name="password" type="password">
            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>
<?php
require_once 'includes/footer.php';
?>