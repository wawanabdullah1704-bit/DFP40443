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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Team</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light d-flex align-items-center py-4 min-vh-100">
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6">
                
                <h2 class="text-center mb-4">Edit Team Profile</h2>
                
                <?php echo $message; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" class="bg-secondary p-4 rounded shadow-sm">
                    
                    <div class="mb-3">
                        <label class="form-label">Team Name</label>
                        <input type="text" name="team_name" class="form-control form-control-lg" value="<?php echo htmlspecialchars($team['team_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Captain (IGN)</label>
                        <input type="text" name="captain_name" class="form-control form-control-lg" value="<?php echo htmlspecialchars($team['captain_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rank Tier</label>
                        <select name="rank_tier" class="form-select form-select-lg" required>
                            <option value="Epic" <?php if($team['rank_tier']=='Epic') echo 'selected'; ?>>Epic</option>
                            <option value="Legend" <?php if($team['rank_tier']=='Legend') echo 'selected'; ?>>Legend</option>
                            <option value="Mythic" <?php if($team['rank_tier']=='Mythic') echo 'selected'; ?>>Mythic</option>
                            <option value="Mythical Glory" <?php if($team['rank_tier']=='Mythical Glory') echo 'selected'; ?>>Mythical Glory</option>
                        </select>
                    </div>
                    
                    <div class="mb-4 d-flex flex-column flex-sm-row align-items-sm-center">
                        <img src="<?php echo htmlspecialchars($team['logo_path']); ?>" width="60" class="rounded mb-3 mb-sm-0 me-sm-3 border border-dark align-self-start align-self-sm-center">
                        <div class="flex-grow-1 w-100">
                            <label class="form-label">Replace Logo (Leave blank to keep)</label>
                            <input type="file" name="team_logo" class="form-control form-control-lg" accept="image/*">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-sm-block mt-4">
                        <button type="submit" class="btn btn-warning btn-lg">Update Team</button>
                        <a href="dashboard.php" class="btn btn-outline-light btn-lg">Cancel</a>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>

</body>
</html>