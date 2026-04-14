<?php
include 'db.php';

$sql = "SELECT COUNT(*) AS total FROM roles";
$result = mysqli_query($conn,$sql);
$row = mysqli_fetch_assoc($result);
echo "Bilangan roles di dalam sistem ".$row['total'];
?>