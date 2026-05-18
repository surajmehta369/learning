<?php

session_name('STUDENT_SESSION');
session_start();

require_once __DIR__ . '/../conn.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("
    UPDATE baseline_User_Cart 
    SET payment_mode='success' 
    WHERE user_id=? 
    AND payment_mode='pending'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$updated_rows = $stmt->affected_rows;

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Razorpay Payment Success</title>

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body style="background:#f4fff4;">

    <div class="container mt-5">

        <div class="card shadow p-5 text-center">

            <i class="fa-solid fa-circle-check text-success"
                style="font-size:80px;"></i>

            <h2 class="mt-4 text-success">
                Payment Successful
            </h2>

            <?php if($updated_rows > 0): ?>

                <p class="lead">
                    Your courses have been activated successfully.
                </p>

            <?php else: ?>

                <p class="lead text-warning">
                    Payment received but no rows updated.
                </p>

            <?php endif; ?>

            <div class="mt-4">

                <a href="../index.php" class="btn btn-success">
                    Back To Home
                </a>

            </div>

        </div>

    </div>

</body>

</html>