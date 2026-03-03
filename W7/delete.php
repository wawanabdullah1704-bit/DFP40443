<?php
require_once("config/app_config.php");
$maklumat = mysqli_query($conn,"SELECT users.id, username as pengguna, email, password, name as peranan FROM spmp.users join roles on users.role_id = roles.id;") or die(mysqli_error($conn));  
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
    <div class="container-fluid text-center">
        <h2 class="mb-3 mt-5">Delete User</h2>
        <table class="table table-dark table-striped">
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
                <td><button class="btn btn-outline-primary" type="submit">Padam</button></td>
            </tr>
        <?php endwhile; ?>
        </table>
    </div>
</body>
</html>