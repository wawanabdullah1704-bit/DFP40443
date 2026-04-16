<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard - MLBB Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></h2>
        <div>
            <a href="add_team.php" class="btn btn-success">Register New Team</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
    <div class="mb-4">
        <input type="text" id="searchInput" class="form-control form-control-lg bg-secondary text-light border-0" placeholder="Search by Team or Captain Name..." onkeyup="searchTeams()">
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
            <thead>
                <tr>
                    <th>Logo</th>
                    <th>Team Name</th>
                    <th>Captain (IGN)</th>
                    <th>Rank Tier</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="teamResults">
                </tbody>
        </table>
    </div>
</div>

<script>
window.onload = function() { searchTeams(); };
function searchTeams() {
    let query = document.getElementById('searchInput').value;
    fetch('ajax_search.php?q=' + encodeURIComponent(query))
        .then(response => response.text())
        .then(data => { document.getElementById('teamResults').innerHTML = data; })
        .catch(error => console.error('Error fetching data:', error));
}
</script>
</body>
</html>