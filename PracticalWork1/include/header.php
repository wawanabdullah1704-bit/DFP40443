<?php
$loggedIn= isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg bg-dark">
            <a class="link-light p-3" href='index.php'>Home</a>
            <?php if($loggedIn): ?>
            <a class="link-light p-3" href='quiz.php'>Quiz</a>
            <a class="link-light p-3" href='logout.php'>Logout</a>
            <?php else: ?>
            <a class="link-light p-3" href='login.php'>Login</a>
            <?php endif; ?>
        </nav>
    </header>
<main>