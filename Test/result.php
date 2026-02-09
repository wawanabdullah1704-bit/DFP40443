<?php
session_start();
if (isset($_POST['restart'])) {
    session_destroy(); // Complete system reset [cite: 12, 27]
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Quiz Results</title>
</head>
<body class="container mt-5">
    <h3>Quiz Complete!</h3>
    <p><strong>Student:</strong> <?php echo $_SESSION['username']; ?></p>
    <p><strong>Final Score:</strong> <?php echo $_SESSION['score']; ?></p>

    <h4>Review Incorrect Answers:</h4>
    <table class="table table-bordered">
        <thead class="table-light">
            <tr><th>Question</th><th>Your Answers</th><th>Correct Answer</th></tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['mistakes'] as $m): ?>
                <tr>
                    <td><?php echo $m['question']; ?></td>
                    <td class="text-danger"><?php echo $m['user_ans']; ?></td>
                    <td class="text-success"><?php echo $m['correct_ans']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <form method="post"><button type="submit" name="restart" class="btn btn-dark">Restart Quiz</button></form>
</body>
</html>