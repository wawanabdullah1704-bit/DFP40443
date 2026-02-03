<?php
$config = include('config/app_config.php');
require_once('includes/alumni_logic.php');

$isLoggedIn=false;
if($_SERVER['REQUEST_METHOD']=='POST') {
    try {
        $user = $_POST['username'];
        $pass = $_POST['password'];

        if($user !== $config['admin_user'] || $pass !== $config['admin_pass']){

        } $isLoggedIn = true;
    } catch (Exception $e) {

    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['site_name']; ?></title>
</head>
<body style="background-color:<?php echo $config['theme_color'];  ?>">
    <header>
        <nav>
            <ul style='display:flexible;list-style-type: style none;'>
                <?php echo generateMenu($pages); ?>
            </ul>
        </nav>
    </header>

    <?php 
        if($isLoggedIn):
    ?>
    Welcome
    <?php
        echo $_POST['username'];
    ?>

    <?php 
        endif;
    ?>

    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla ut pharetra ante, sit amet fermentum est. Curabitur eu nisl et sapien sagittis ornare ut et nulla. Integer ac mattis dolor, in fermentum massa. Donec ullamcorper tortor lorem. Maecenas vitae ornare risus, vitae vulputate erat. Donec euismod tincidunt lorem, vitae ultricies ante convallis et. Sed porta commodo nisl sed blandit. Mauris et arcu vel velit venenatis maximus. Etiam metus massa, aliquam sit amet eros sed, luctus tincidunt ante. Duis sit amet eleifend orci. Quisque dignissim ut ante id imperdiet. Interdum et malesuada fames ac ante ipsum primis in faucibus. Suspendisse potenti. Morbi vulputate, tellus sit amet gravida accumsan, nisi elit tincidunt velit, vel facilisis dui felis porttitor nisi.</p>
    <footer>
        <?php echo $config['admin_email'] ; ?>
    </footer>
</body>
</html>