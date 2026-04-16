<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$team_name = $captain_name = $rank_tier = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $team_name = htmlspecialchars(trim($_POST['team_name']));
    $captain_name = htmlspecialchars(trim($_POST['captain_name']));
    $rank_tier = htmlspecialchars(trim($_POST['rank_tier']));
    $upload_ok = true;
    $logo_path = '';
    
    if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] == 0) {
        $target_dir = "uploads/";
        $new_filename = uniqid() . '.' . pathinfo($_FILES["team_logo"]["name"], PATHINFO_EXTENSION); 
        $target_file = $target_dir . $new_filename;
        
        if ($_FILES["team_logo"]["size"] > 2000000) {
            $message = "<div class='alert alert-danger'>File too large.</div>";
            $upload_ok = false;
        } elseif (move_uploaded_file($_FILES["team_logo"]["tmp_name"], $target_file)) {
            $logo_path = $target_file; 
        } else {
            $message = "<div class='alert alert-danger'>Upload error.</div>";
            $upload_ok = false;
        }
    } else {
        $message = "<div class='alert alert-warning'>Please upload a logo.</div>";
        $upload_ok = false;
    }

    if ($upload_ok && !empty($team_name) && !empty($captain_name) && !empty($rank_tier)) {
        $sql = "INSERT INTO teams (team_name, captain_name, rank_tier, logo_path) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $team_name, $captain_name, $rank_tier, $logo_path);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "<div class='alert alert-success'>Team registered successfully!</div>";
            $team_name = $captain_name = $rank_tier = ''; 
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register Team</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<div class="container mt-5" style="max-width: 600px;">
    <h2>Register MLBB Team</h2>
    <?php echo $message; ?>
    <form method="POST" action="" enctype="multipart/form-data" class="bg-secondary p-4 rounded mt-3">
        <div class="mb-3">
            <label class="form-label">Team Name</label>
            <input type="text" name="team_name" class="form-control" value="<?php echo $team_name; ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Captain (IGN)</label>
            <input type="text" name="captain_name" class="form-control" value="<?php echo $captain_name; ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Rank Tier</label>
            <select name="rank_tier" class="form-select" required>
                <option value="Epic" <?php if($rank_tier=='Epic') echo 'selected'; ?>>Epic</option>
                <option value="Legend" <?php if($rank_tier=='Legend') echo 'selected'; ?>>Legend</option>
                <option value="Mythic" <?php if($rank_tier=='Mythic') echo 'selected'; ?>>Mythic</option>
                <option value="Mythical Glory" <?php if($rank_tier=='Mythical Glory') echo 'selected'; ?>>Mythical Glory</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="form-label">Team Logo</label>
            <input type="file" name="team_logo" class="form-control" accept="image/*" required>
        </div>
        <button type="submit" class="btn btn-success">Register Team</button>
        <a href="dashboard.php" class="btn btn-outline-light ms-2">Back</a>
    </form>
</div>
</body>
</html>