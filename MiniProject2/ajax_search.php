<?php
require 'db.php';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchTerm = "%" . $search . "%";

$sql = "SELECT * FROM teams WHERE team_name LIKE ? OR captain_name LIKE ? ORDER BY id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $searchTerm);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td><img src='" . htmlspecialchars($row['logo_path']) . "' alt='Logo' style='width: 50px; height: 50px; object-fit: cover; border-radius: 5px;'></td>";
        echo "<td>" . htmlspecialchars($row['team_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['captain_name']) . "</td>";
        echo "<td><span class='badge bg-info text-dark'>" . htmlspecialchars($row['rank_tier']) . "</span></td>";
        echo "<td>
                <a href='edit_team.php?id=" . $row['id'] . "' class='btn btn-warning btn-sm'>Edit</a>
                <a href='delete_team.php?id=" . $row['id'] . "' class='btn btn-danger btn-sm' onclick=\"return confirm('Delete this team from the tournament?');\">Delete</a>
              </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center text-muted py-4'>No teams found.</td></tr>";
}
mysqli_stmt_close($stmt);
?>