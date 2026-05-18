<?php
// FIXED: Use ONE session for Google OAuth
session_name('GOOGLE_AUTH');
session_start();

require __DIR__ . '../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$client_id = $_ENV['ClientID'];
$redirect_uri = 'https://avengers.topscripts.in/edu/baselinelearning/login/google-handle.php';

// Always force account selection (most reliable)
$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'     => $client_id,
    'redirect_uri'  => $redirect_uri,
    'response_type' => 'code',
    'scope'         => 'email profile',
    'prompt'        => 'select_account'
]);

header("Location: $auth_url");
exit;
