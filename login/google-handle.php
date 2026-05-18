<?php
session_name('GOOGLE_AUTH');
session_start();

require_once __DIR__ . '../conn.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$client_id = $_ENV['ClientID'];
$client_secret = $_ENV['ClientSecret'];
$redirect_uri = 'https://avengers.topscripts.in/edu/baselinelearning/login/google-handle.php';

if (!isset($_GET['code'])) {
    exit("Authorization failed");
}

/* =========================
   GET ACCESS TOKEN
========================= */
$token_response = file_get_contents(
    'https://oauth2.googleapis.com/token',
    false,
    stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'code' => $_GET['code'],
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code'
            ])
        ]
    ])
);

$token = json_decode($token_response, true);
if (!isset($token['access_token'])) {
    exit("Token error");
}

/* =========================
   GET USER INFO
========================= */
$userinfo = json_decode(
    file_get_contents(
        "https://www.googleapis.com/oauth2/v2/userinfo?access_token=" . $token['access_token']
    ),
    true
);

$email = $userinfo['email'] ?? '';
$name  = $userinfo['name'] ?? '';

if (!$email) {
    exit("Failed to get email from Google");
}

/* =========================
   CHECK USER IN DB
========================= */
$stmt = $conn->prepare("SELECT id, full_name, role, status FROM signup WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();

/* =========================
   EXISTING USER
========================= */
if ($user) {

    // Teacher approval check
    if ($user['role'] === 'teacher' && strtolower($user['status']) !== 'approved') {
        exit("Your account is pending admin approval.");
    }

    $_SESSION['google_user'] = [
        'id'    => $user['id'],
        'name'  => $user['full_name'],
        'email' => $email,
        'role'  => $user['role']
    ];

    $_SESSION['google_logged_in'] = true;

    // Switch to role-based session SAFELY
    session_write_close();

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

    $_SESSION['logged_in']  = true;
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role']  = $user['role'];

    header("Location: " . ($user['role'] === 'teacher' ? "../teacherpage.php" : "../index.php"));
    exit;
}

/* =========================
   NEW USER → ROLE SELECTION
========================= */
$_SESSION['google_temp'] = [
    'name'  => $name,
    'email' => $email
];

header("Location: select-role.php");
exit;
