<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="number" name="kelvinVal">
        <label for="">
            K
        </label>
        <input type="submit" value="Calculate">
    </form>
</body>
<?php
if($_SERVER['REQUEST_METHOD'] == "POST") {
    $kelvin = $_POST['kelvinVal'];
    $calculate = $kelvin - 273.15;
}
?>
<?php
echo $calculate;
?>
</html>