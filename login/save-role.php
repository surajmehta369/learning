<?php
// Continue Google OAuth session
session_name('GOOGLE_AUTH');
session_start();

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['google_temp'], $_POST['role'])) {
    header("Location: login.php");
    exit;
}

$name  = $_SESSION['google_temp']['name'];
$email = $_SESSION['google_temp']['email'];
$role  = $_POST['role'];

$type = 'Google';
$password = ''; // Google users don't need password

// Teacher approval handling
$status = ($role === 'teacher') ? 'pending' : 'approved';

/* =========================
   INSERT USER
========================= */
$stmt = $conn->prepare(
    "INSERT INTO signup (full_name, email, password_hash, role, type, status)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("ssssss", $name, $email, $password, $role, $type, $status);
$stmt->execute();

$user_id = $stmt->insert_id;

/* =========================
   CLOSE GOOGLE SESSION
========================= */
unset($_SESSION['google_temp']);
session_write_close();

/* =========================
   START ROLE SESSION
========================= */
switch ($role) {
    case 'admin':
        session_name('ADMIN_SESSION');
        break;
    case 'teacher':
        session_name('TEACHER_SESSION');
        break;
    default:
        session_name('STUDENT_SESSION');
}

session_start();

/* =========================
   LOGIN USER
========================= */
$_SESSION['logged_in']  = true;
$_SESSION['user_id']    = $user_id;
$_SESSION['user_name']  = $name;
$_SESSION['user_email'] = $email;
$_SESSION['user_role']  = $role;

/* =========================
   REDIRECT
========================= */
if ($role === 'teacher') {
    // Teacher must wait for approval
    header("Location: ../login.php?pending=1");
} else {
    header("Location: ../index.php");
}
exit;
