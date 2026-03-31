<?php
require_once("config/app_config.php");
$sqlPeranan = "SELECT * FROM roles";
$HasilSQLPeranan = mysqli_query($conn, $sqlPeranan);

$mesej = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $namapenguna = $_POST["username"];
    $katalaluan = $_POST["password"];
    $peranan = $_POST["peranan_id"];

    $arahanSQL = mysqli_prepare($conn, "INSERT INTO users (username, password, role_id) VALUE (?, ?, ?)");
    mysqli_stmt_bind_param($arahanSQL, "ssi", $namapenguna, $katalaluan, $peranan);
    if (mysqli_stmt_execute($arahanSQL)) {
        $mesej = "<p style='color:green;'>Berjaya masuk data</p>";
    } else {
        $mesej = "<p style='color:red;'>Gagal... Tidak berjaya masuk data</p" . mysqli_stmt_error($sqlPeranan);
    }
    $HasilSQLPeranan = mysqli_query($conn, $sqlPeranan);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>spmp | Pengguna Baru</title>
</head>

<body>
    <div class="m-5">
        <?php echo $mesej; ?>
        <h2>Enter New User</h2>
        <form method="POST" action="">
            username <input type="text" name="username"><br>
            password <input type="password" name="password"><br>
            peranan: <select name="peranan_id">
                <option value="">-- Sila Pilih Peranan --</option>
                <?php while ($row = mysqli_fetch_assoc($HasilSQLPeranan)): ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo $row['name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="submit" value="Masuk Data">
        </form>
    </div>
</body>

</html>