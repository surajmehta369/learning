<?php
session_name('GOOGLE_AUTH'); // IMPORTANT
session_start();

if (!isset($_SESSION['google_temp'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Role</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .box {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }
        button {
            margin: 10px;
            padding: 10px 25px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Select Your Role</h2>
    <form method="post" action="save-role.php">
        <button type="submit" name="role" value="student">Student</button>
        <button type="submit" name="role" value="teacher">Teacher</button>
    </form>
</div>

</body>
</html>
