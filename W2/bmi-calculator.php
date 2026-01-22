<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label for="">
            Height (m)
        </label>
        <input name="heightVal" type="number">
        <label for="">
            Weight (kg)
        </label>
        <input name="weightVal" type="number">
        <input type="submit" value="Calculate">
    </form>
</body>
<?php
if($_SERVER['REQUEST_METHOD'] == "POST") {
    $height = $_POST['heightVal'];
    $weight = $_POST['weightVal'];

    $bmi = $weight/(($height/100)* ($height/100));
    }
?>
<?php
echo $bmi;
?>
</html>