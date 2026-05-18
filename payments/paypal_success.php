<?php
session_name('STUDENT_SESSION');
session_start();
include("../conn.php");
require 'payments/paypal_config.php';

if (!isset($_GET['token'])) {
    die("No token provided. Payment failed.");
}

$token = $_GET['token'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paypal_url . "/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $_ENV['PAYPAL_CLIENT_ID'] . ":" . $_ENV['PAYPAL_CLIENT_SECRET']);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
$result = curl_exec($ch);
$json = json_decode($result);
$access_token = $json->access_token;
curl_close($ch);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paypal_url . "/v2/checkout/orders/" . $token . "/capture");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $access_token
]);
curl_setopt($ch, CURLOPT_POST, true);
$response = curl_exec($ch);
$order_capture = json_decode($response);
curl_close($ch);

if (isset($order_capture->status) && $order_capture->status === 'COMPLETED') {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE baseline_User_Cart SET payment_mode='paypal_success' WHERE user_id=? AND payment_mode='pending'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    echo "Payment successful! Your courses are now active.";
} else {
    echo "Payment could not be captured. Please contact support.";
}
