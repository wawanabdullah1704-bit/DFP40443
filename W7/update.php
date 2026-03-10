<?php
require_once("config/app_config.php");
$maklumat = mysqli_query($conn, "SELECT users.id,username as pengguna,email,password,name as peranan FROM spmp.users join roles on users.role_id = roles.id;") or die(mysqli_error($conn));

$mesej ="";
if($_SERVER["REQUEST_METHOD"] == "POST" &&isset( $_POST["update_user"])) {
    $userid = $_POST["user_id"];

    $user_id = $_POST["user_id"];
    $username = $_POST["username"];
    $role_id = $_POST["role_id"];

    $stmt = mysqli_prepare($conn,"UPDATE users SET username=?,role_id=? WHERE id=?");
    
    mysqli_stmt_bind_param($stmt,"ssi",$user_id,$username,$user_id);

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
}

if(isset($_GET["edit_id"])) {
    $id = $_GET["edit_id"];
    $stmt = mysqli_prepare($conn,"SELECT * FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt,"i",$id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
}

$roles_result = mysqli_query($conn,"SELECT * FROM roles");
$user_roles_result = mysqli_query($conn,"SELECT *,upper(concat(firstname,' ',lastname)) as fullname FROM pmis.users inner join pmis.roles on users.role_id =roles.id");


?>

<?php 
require_once "includes/header.php";
?>

<h2>Edit Form</h2>
<form>
    Username <input type="text" name="username" value="<?php echo $result['username'] ?>"><br><br>
    Password <input type="password" name="password" value="<?php $result['password'] ?>"><br><br>
    Email <input type="email" name="email" value="<?php echo $result['email'] ?>"><br><br>
    <label for="peranan_id">Select role:</label>
</form>

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <title>Document</title>
</head>
<body>
    <?php echo $mesej ?>
    <div class="container-fluid text-center">
        <h2 class="mb-3 mt-5">Delete User</h2>
        <table class="table table-dark table-striped table-bordered">
            <tr>
                <th>Id</th>
                <th>Nama Pengguna</th>
                <th>Peranan</th>
                <th>Tindakan</th>
            </tr>
            <?php while ($pengguna = mysqli_fetch_assoc($maklumat)): ?>
            <tr>
                <td><?php echo $pengguna['id'] ?></td>
                <td><?php echo $pengguna['pengguna'] ?></td>
                <td><?php echo $pengguna['peranan'] ?></td>
                <td><input type="submit" value="Padam" class="btn btn-outline-primary"></td>
            </tr>
        <?php endwhile; ?>
        </table>
    </div>
</body>
</html>