<?php

session_name('STUDENT_SESSION');
session_start();

include("../conn.php");

$user_id = $_SESSION['student_id'] ?? 0;

if (!$user_id) {
    die("Payment session broken: user_id missing");
}

$payment_id = $_SESSION['razorpay_payment_id'] ?? '';
$order_id   = $_SESSION['razorpay_order_id'] ?? '';

$sql = "UPDATE baseline_user_cart
        SET payment_mode='success',
            razorpay_payment_id=?,
            razorpay_order_id=?,
            payment_date=NOW()
        WHERE user_id=?
        AND payment_mode='pending'";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssi",
    $payment_id,
    $order_id,
    $user_id
);

$stmt->execute();

$updated_rows = $stmt->affected_rows;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Success</title>

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

</head>

<body style="background:#f4fff4;">

    <div class="container mt-5">

        <div class="card shadow p-5 text-center">

            <h2 class="text-success">
                Payment Successful
            </h2>

            <?php if ($updated_rows > 0): ?>

                <p class="lead">
                    Your course access has been activated successfully.
                </p>

            <?php else: ?>

                <p class="lead text-warning">
                    Payment received, but no pending cart items were found.
                </p>

            <?php endif; ?>

            <a href="../index.php" class="btn btn-success mt-3">
                Back To Home
            </a>

        </div>

    </div>

</body>

</html>