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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MLBB Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light py-4">

    <div class="container">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">

            <h2 class="m-0 text-center text-md-start">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></h2>

            <div class="d-grid gap-2 d-md-block w-100" style="max-width: 400px;">
                <a href="add_team.php" class="btn btn-success">Register New Team</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>

        </div>

        <div class="mb-4">
            <input type="text" id="searchInput" class="form-control form-control-lg bg-secondary text-light border-0 shadow-sm" placeholder="Search by Team or Captain Name..." onkeyup="searchTeams()">
        </div>

        <div class="table-responsive rounded shadow-sm">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th style="min-width: 150px;">Team Name</th>
                        <th style="min-width: 150px;">Captain (IGN)</th>
                        <th style="min-width: 120px;">Rank Tier</th>
                        <th style="min-width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="teamResults">
                </tbody>
            </table>
        </div>

    </div>

    <script>
        window.onload = function() {
            searchTeams();
        };

        function searchTeams() {
            let query = document.getElementById('searchInput').value;
            fetch('ajax_search.php?q=' + encodeURIComponent(query))
                .then(response => response.text())
                .then(data => {
                    document.getElementById('teamResults').innerHTML = data;
                })
                .catch(error => console.error('Error fetching data:', error));
        }
    </script>
</body>

</html>