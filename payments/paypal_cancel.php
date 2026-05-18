<?php
session_name('STUDENT_SESSION');
session_start();
include("../conn.php");

if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $update_stmt = $conn->prepare("UPDATE baseline_User_Cart SET payment_mode = NULL WHERE user_id = ? AND payment_mode = 'pending'");
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .cancel-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-align: center; max-width: 400px; }
        .icon { font-size: 50px; color: #e74c3c; margin-bottom: 20px; }
        h1 { color: #333; margin-bottom: 10px; }
        p { color: #666; line-height: 1.5; margin-bottom: 30px; }
        .btn { display: inline-block; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; transition: 0.3s; }
        .btn-retry { background: #0070ba; color: white; margin-right: 10px; }
        .btn-retry:hover { background: #005ea6; }
        .btn-cart { background: #eee; color: #333; }
        .btn-cart:hover { background: #ddd; }
    </style>
</head>
<body>

<div class="cancel-card">
    <div class="icon">✕</div>
    <h1>Payment Cancelled</h1>
    <p>You have cancelled the PayPal transaction. No charges were made to your account.</p>
    
    <a href="checkout.php" class="btn btn-retry">Try Again</a>
    <a href="cart.php" class="btn btn-cart">Return to Cart</a>
</div>

</body>
</html>