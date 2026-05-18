<?php
// include_once('/db.php'); 

$servername = "localhost";
$username = "topscbtk_avengers_dev";
$password = "Baseline@123";
$database = "topscbtk_avengers";


// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'errors' => []];

// Get POST data safely
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Basic validation (you can extend)
if (!$full_name) {
    $response['errors']['full_name'] = 'Full name is required.';
}
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['errors']['email'] = 'Valid email is required.';
}
if (!$password || strlen($password) < 6) {
    $response['errors']['password'] = 'Password must be at least 6 characters.';
}

if (!empty($response['errors'])) {
    $response['message'] = 'Please correct the errors.';
    echo json_encode($response);
    exit;
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM sign_user WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    $response['message'] = 'Email is already registered.';
    $response['errors']['email'] = 'Email is already registered.';
    echo json_encode($response);
    exit;
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $pdo->prepare("INSERT INTO sign_user (full_name, email, password) VALUES (?, ?, ?)");
try {
    $stmt->execute([$full_name, $email, $hashedPassword]);
    $response['success'] = true;
    $response['message'] = 'Registration successful!';
} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
