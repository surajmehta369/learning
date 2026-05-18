<?php
require_once __DIR__ . '../../conn.php';
header('Content-Type: application/json');

function send_response($success, $message, $role = null)
{
    echo json_encode(['success' => $success, 'message' => $message, 'role' => $role]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    if (!$email) {
        send_response(false, 'Please enter a valid email address.');
    }
    if (strlen($password) < 6) {
        send_response(false, 'Password must be at least 6 characters.');
    }

    // Fetch user by email
    $stmt = $conn->prepare("SELECT id, full_name, email, password_hash, role, status FROM signup WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);

    if (!$stmt->execute()) {
        send_response(false, 'Database query failed.');
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        send_response(false, 'Invalid email or password.');
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password_hash'])) {
        send_response(false, 'Invalid email or password.');
    }

    if ($user['role'] === 'teacher' && strtolower($user['status']) !== 'approved') {
        send_response(false, 'Your account approval is pending by the admin.');
    }

    // 🔥 Use different session name for each role
    switch ($user['role']) {
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

    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_status'] = $user['status'];

    send_response(true, 'Login successful! Redirecting...', $user['role']);
} else {
    send_response(false, 'Invalid request method.');
}
