<?php

session_name('STUDENT_SESSION');
session_start();

include(__DIR__ . "/../conn.php");
if (isset($_SESSION['user_id'])) {

    $user_id = intval($_SESSION['user_id']);

    $stmt = $conn->prepare("
        UPDATE baseline_User_Cart 
        SET payment_mode='failed' 
        WHERE user_id=? 
        AND payment_mode='pending'
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payment Failed</title>

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body style="background:#fff5f5;">

    <div class="container mt-5">

        <div class="card shadow p-5 text-center">

            <i class="fa-solid fa-circle-xmark text-danger"
                style="font-size:80px;"></i>

            <h2 class="mt-4 text-danger">
                Payment Failed / Cancelled
            </h2>

            <p class="lead">
                Your payment was cancelled or failed.
            </p>

            <div class="mt-4">

                <a href="checkout.php" class="btn btn-danger">
                    Retry Payment
                </a>

            </div>

        </div>

    </div>

</body>

</html>