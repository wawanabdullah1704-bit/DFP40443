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
            Height (cm)
        </label>
        <input name="height" type="number">
        <label for="">
            Weight (kg)
        </label>
        <input name="weight" type="number">
        <input type="submit" value="Calculate">
    </form>
</body>
</html>