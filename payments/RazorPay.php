<?php

session_name('STUDENT_SESSION');
session_start();

include("conn.php");

$keySecret = "9BXXrmYcmxJjEmeZAi8ovfPR";

$payment_id = $_POST['razorpay_payment_id'] ?? '';
$order_id = $_POST['razorpay_order_id'] ?? '';
$signature = $_POST['razorpay_signature'] ?? '';

if (empty($payment_id) || empty($order_id) || empty($signature)) {

    header("Location: razorpay_cancel.php");
    exit;
}

$generated_signature = hash_hmac(
    "sha256",
    $order_id . "|" . $payment_id,
    $keySecret
);

if ($generated_signature === $signature) {

    $_SESSION['razorpay_payment_id'] = $payment_id;
    $_SESSION['razorpay_order_id'] = $order_id;

    header("Location: razorpay_success.php");
    exit;

} else {

    header("Location: razorpay_cancel.php");
    exit;
}
?>