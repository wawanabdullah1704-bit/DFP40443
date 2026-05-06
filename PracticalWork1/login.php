<?php
require_once 'config/app_config.php';
$error='';
if($_SERVER['REQUEST_METHOD'] ==='POST'){
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);

    if(isset($users[$username]) && $users[$username] === $password){
        $_SESSION['username'] = $username;
        $_SESSION['score'] = 0;
        $_SESSION['soalanSemasa'] = 0;
        
        header('Location: quiz.php');
    } else {
        $error="Invalid Username or Password";
    }
}


$pageTitle = 'Login';
require_once 'include/header.php';
?>

    <h1 class="mt-3">Welcome to the Quiz</h1>
    <p class="m-3">Enter your name and password</p>
        <?php if ($error): ?>
            <?php echo $error ?>
         <?php endif; ?>
    <form method="POST" action="login.php" class="mb-5">
        Your Name: <br>
        <input type="text"  name="username" required> <br>  
        Your Password : <br>
        <input type="password"  name="password" required> <br>      
        <input type="submit" value="login">     
    </form> 
<?php require_once 'include/footer.php'; ?>      