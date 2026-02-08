<?php
session_start();
// Redirect to login if session isn't set [cite: 13]
if (!isset($_SESSION['username'])) { header("Location: index.php"); exit(); }

$questions = [
    ["q" => "What does PHP stand for?", "options" => ["Private Home Page", "PHP: Hypertext Preprocessor", "Personal Hypertext Processor"], "ans" => "PHP: Hypertext Preprocessor"],
    ["q" => "Which function starts a session?", "options" => ["session_start()", "begin_session()", "start_session()"], "ans" => "session_start()"]
];

if (isset($_POST['next'])) {
    $idx = $_SESSION['current_index'];
    $user_ans = $_POST['answer'] ?? '';
    $correct_ans = $questions[$idx]['ans'];

    if ($user_ans === $correct_ans) {
        $_SESSION['score']++;
    } else {
        // Store mistake data for the review table [cite: 11, 12]
        $_SESSION['mistakes'][] = [
            'question' => $questions[$idx]['q'],
            'user_ans' => $user_ans,
            'correct_ans' => $correct_ans
        ];
    }

    $_SESSION['current_index']++;
    if ($_SESSION['current_index'] >= count($questions)) {
        header("Location: result.php");
        exit();
    }
}

$current_q = $questions[$_SESSION['current_index']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Quiz - Question <?php echo $_SESSION['current_index'] + 1; ?></title>
</head>
<body class="container mt-5">
    <h4>Question <?php echo $_SESSION['current_index'] + 1; ?>:</h4>
    <p><?php echo $current_q['q']; ?></p>
    <form method="post">
        <?php foreach ($current_q['options'] as $opt): ?>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="answer" value="<?php echo $opt; ?>" required>
                <label class="form-check-label"><?php echo $opt; ?></label>
            </div>
        <?php endforeach; ?>
        <button type="submit" name="next" class="btn btn-success mt-3">Next Question</button>
    </form>
</body>
</html>