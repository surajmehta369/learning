<?php

$keySecret = "9BXXrmYcmxJjEmeZAi8ovfPR";

$payment_id = $_POST['razorpay_payment_id'];
$order_id = $_POST['razorpay_order_id'];
$signature = $_POST['razorpay_signature'];

$generated_signature = hash_hmac(
    "sha256",
    $order_id . "|" . $payment_id,
    $keySecret
);

if ($generated_signature === $signature) {
?>
    <a href="index.php" class="btn btn-custom btn-download mr-2">
        <i class="fa-solid fa-home"></i> Back to Home
    </a>
<?
} else {

    echo "❌ Payment Failed";
}
