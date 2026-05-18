<?php
session_start();
require_once __DIR__ . '/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$token = $_GET['token'] ?? '';

if (!$token) {
    exit('Invalid access.');
}

// Check if token exists and not expired
$stmt = $conn->prepare("SELECT email, expires_at FROM signup_tokens WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit("Token is invalid or expired.");
}

$row = $result->fetch_assoc();
$email = $row['email'];
$expires_at = $row['expires_at'];

if (strtotime($expires_at) < time()) {
    exit("Token expired.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';

    if (empty($role)) {
        echo "<script>alert('Select Role');</script>";
    } else {
        // Update role only
        $stmt = $conn->prepare("UPDATE signup SET role = ? WHERE email = ?");
        $stmt->bind_param("ss", $role, $email);
        $stmt->execute();

        // Fetch user data for cookies
        $stmt = $conn->prepare("SELECT id, full_name, email, role FROM signup WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Delete token after use
        $stmt = $conn->prepare("DELETE FROM signup_tokens WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        // Clear old cookies (optional but recommended)
        setcookie("user_id", "", time() - 3600, "/", "", true, true);
        setcookie("user_name", "", time() - 3600, "/", "", true, true);
        setcookie("user_email", "", time() - 3600, "/", "", true, true);
        setcookie("user_role", "", time() - 3600, "/", "", true, true);

        // Set new cookies for the logged-in user (valid for 7 days)
        setcookie("user_id", $user['id'], time() + (86400 * 7), "/", "", true, true);
        setcookie("user_name", $user['full_name'], time() + (86400 * 7), "/", "", true, true);
        setcookie("user_email", $user['email'], time() + (86400 * 7), "/", "", true, true);
        setcookie("user_role", $user['role'], time() + (86400 * 7), "/", "", true, true);

        // Redirect based on role
        if ($role === 'student') {
            header("Location: https://avengers.topscripts.in/edu/baselinelearning/index.php");
            exit;
        } elseif ($role === 'teacher') {
            header("Location: https://avengers.topscripts.in/edu/baselinelearning/teacherpage.php");
            exit;
        } else {
            header("Location: https://avengers.topscripts.in/edu/baselinelearning/login/login.php");
            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Set Role</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f3f3;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        select {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            font-size: 14px;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Select Your Role</h2>
    <form method="POST">
        <label for="role">Select Role</label>
        <select name="role" id="role" required>
            <option value="">-- Select Role --</option>
            <option value="student">Student</option>
            <option value="teacher">Teacher</option>
        </select>
        <button type="submit">Submit</button>
    </form>
</div>

</body>
</html>
