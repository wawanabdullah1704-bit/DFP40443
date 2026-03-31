<?php
include("includes/header.php");
$result_array = '';
?>
<?Php
if($_SERVER["REQUEST_METHOD"])
if(isset($_GET["hantar_id"])){
    $edit_id = $_GET["hantar_id"];
    $stmt = mysqli_prepare($conn,"SELECT id,username,role_id FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt,"i", $edit_id);
    mysqli_stmt_execute($stmt);
    $result  = mysqli_stmt_get_result( $stmt );
    $result_array = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}
$roles_names = mysqli_query($conn,"SELECT id, name FROM roles");
$user_result = mysqli_query($conn,"SELECT users.id, username, roles.name as role_name FROM spmp.user JOIN roles ON users.role_id");
?>
<?php if($result_array): ?>
    <form style="background-color: yellow" action="" method="POST">
        <input type="text" name="user_id" value="<?php echo $result_array["id"] ?>">
        <input type="text" name="username" value="<?php echo $result_array["username"] ?>">
        <select name="role_id" id="">
            <?php while($row = mysqli_fetch_assoc($roles_names)): ?>
            <option value="<?php echo $row['id'] ?>">
                <?php if($row['id'] == $result_array[]) ?>
                <?php echo $row['name'] ?>
            </option>
            <?php endwhile; ?>
        </select>
    </form>
<?php endif; ?>
<h2>All Users</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Role</th>
        <th>Edit</th>
    </tr>
    <?php while ($row = mysqli_fetch_array($user_result)): ?>
    <tr>
        <td><?php echo $row["id"] ?></td>
        <td><?php echo $row["username"] ?></td>
        <td><?php echo $row["role_name"] ?></td>
        <td><a href="kemaskini.php?hantar_id=<?php echo $row["id"]; ?>">Edit</a></td>
    </tr>
    <?php endwhile; ?>
</table>
<?php
include("includes/footer.php");
?>