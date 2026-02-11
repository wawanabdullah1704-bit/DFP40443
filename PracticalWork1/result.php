<?php
require_once 'config/app_config.php';
require_once 'include/header.php';
?>
<h3 class="m-5 text-center">Your Score: <?php
echo  $_SESSION['score'] ;
?></h3>
<?php
require_once 'include/footer.php';
?>