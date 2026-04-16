<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$team_id = $_GET['id'] ?? null;

if (!$team_id) {
    header("Location: dashboard.php");
    exit();
}

$sql_get = "SELECT * FROM teams WHERE id = ?";
$stmt_get = mysqli_prepare($conn, $sql_get);
mysqli_stmt_bind_param($stmt_get, "i", $team_id);
mysqli_stmt_execute($stmt_get);
$result = mysqli_stmt_get_result($stmt_get);
$team = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt_get);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $team_name = htmlspecialchars(trim($_POST['team_name']));
    $captain_name = htmlspecialchars(trim($_POST['captain_name']));
    $rank_tier = htmlspecialchars(trim($_POST['rank_tier']));
    $logo_path = $team['logo_path']; 
    
    if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] == 0) {
        $target_dir = "uploads/";
        $new_filename = uniqid() . '.' . pathinfo($_FILES["team_logo"]["name"], PATHINFO_EXTENSION); 
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["team_logo"]["tmp_name"], $target_file)) {
            if (file_exists($logo_path) && !empty($logo_path)) {
                unlink($logo_path); 
            }
            $logo_path = $target_file; 
        }
    }

    $sql_update = "UPDATE teams SET team_name = ?, captain_name = ?, rank_tier = ?, logo_path = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "ssssi", $team_name, $captain_name, $rank_tier, $logo_path, $team_id);
    
    if (mysqli_stmt_execute($stmt_update)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $message = "<div class='alert alert-danger'>Error updating team.</div>";
    }
    mysqli_stmt_close($stmt_update);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Team</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<div class="container mt-5" style="max-width: 600px;">
    <h2>Edit Team Profile</h2>
    <?php echo $message; ?>
    <form method="POST" action="" enctype="multipart/form-data" class="bg-secondary p-4 rounded mt-3">
        <div class="mb-3">
            <label class="form-label">Team Name</label>
            <input type="text" name="team_name" class="form-control" value="<?php echo htmlspecialchars($team['team_name']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Captain (IGN)</label>
            <input type="text" name="captain_name" class="form-control" value="<?php echo htmlspecialchars($team['captain_name']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Rank Tier</label>
            <select name="rank_tier" class="form-select" required>
                <option value="Epic" <?php if($team['rank_tier']=='Epic') echo 'selected'; ?>>Epic</option>
                <option value="Legend" <?php if($team['rank_tier']=='Legend') echo 'selected'; ?>>Legend</option>
                <option value="Mythic" <?php if($team['rank_tier']=='Mythic') echo 'selected'; ?>>Mythic</option>
                <option value="Mythical Glory" <?php if($team['rank_tier']=='Mythical Glory') echo 'selected'; ?>>Mythical Glory</option>
            </select>
        </div>
        <div class="mb-4 d-flex align-items-center">
            <img src="<?php echo htmlspecialchars($team['logo_path']); ?>" width="60" class="rounded me-3 border border-dark">
            <div class="flex-grow-1">
                <label class="form-label">Replace Logo (Leave blank to keep)</label>
                <input type="file" name="team_logo" class="form-control" accept="image/*">
            </div>
        </div>
        <button type="submit" class="btn btn-warning">Update Team</button>
        <a href="dashboard.php" class="btn btn-outline-light ms-2">Cancel</a>
    </form>
</div>
</body>
</html>