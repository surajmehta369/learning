<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../conn.php");
require 'stripe_config.php';

if(!isset($_GET['sid'])) {
    die("Invalid request. No session ID provided.");
}

$session_id = $_GET['sid'];

try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    $payment_status = $session->payment_status;
    $user_id = isset($session->metadata->user_id) ? intval($session->metadata->user_id) : 0;

    if($user_id <= 0) {
        die("Invalid user data.");
    }

    if($payment_status === 'paid') {
        // Update payment status to 'success'
        $stmt = $conn->prepare("UPDATE baseline_User_Cart SET payment_mode = 'success' WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if($stmt->execute()) {
            $updated_rows = $stmt->affected_rows;
            $stmt->close();
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Payment Success</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
                <style>
                    body { 
                        background: linear-gradient(135deg, #b7b7b7, #a8d98f); 
                        font-family: "Poppins", Arial, sans-serif; 
                        height: 100vh;
                        display: flex;
                        align-items: center;
                    }
                    .success-box { 
                        padding: 40px; 
                        background: #ffffff; 
                        border-radius: 12px; 
                        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15); 
                        text-align: center; 
                    }
                    .success-icon { 
                        font-size: 80px; 
                        color: #2ecc71; 
                        animation: pop 0.8s ease-out; 
                    }
                    @keyframes pop { 
                        0% { transform: scale(0.3); opacity: 0; } 
                        80% { transform: scale(1.2); opacity: 1; } 
                        100% { transform: scale(1); } 
                    }
                    .btn-custom { 
                        padding: 12px 20px; 
                        font-size: 18px; 
                        border-radius: 8px; 
                        transition: 0.3s; 
                    }
                    .btn-download { 
                        background: #27ae60; 
                        color: white; 
                    }
                    .btn-download:hover { 
                        background: #1e874b; 
                    }
                    .btn-home { 
                        border: 2px solid #333; 
                        color: #333; 
                    }
                    .btn-home:hover { 
                        background: #333; 
                        color: white; 
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-6 success-box">
                            <div class="success-icon">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <h2 class="mt-3">Payment Successful</h2>
                            
                            <?php if($updated_rows > 0): ?>
                                <p class="lead text-success">
                                    <i class="fa-solid fa-check-circle"></i> 
                                    Success! Your payment has been processed successfully.
                                </p>
                                <p class="text-muted">
                                    <?= $updated_rows ?> course(s)  have been added to your account.
                                </p>
                            <?php else: ?>
                                <p class="lead text-warning">
                                    <i class="fa-solid fa-exclamation-triangle"></i>
                                    No courses were updated.
                                </p>
                            <?php endif; ?>
                            
                            <div class="mt-4">
                                <a href="index.php" class="btn btn-custom btn-download mr-2">
                                    <i class="fa-solid fa-home"></i> Back to Home 
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            <?php
        } else {
            throw new Exception("Database update failed");
        }
    } else {
        // Payment failed - update status to 'failed'
        $stmt = $conn->prepare("UPDATE baseline_User_Cart SET payment_mode = 'failed' WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Payment Failed</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
            <style>
                body { 
                    background: linear-gradient(135deg, #ff6b6b, #ffa5a5); 
                    font-family: "Poppins", Arial, sans-serif; 
                    height: 100vh;
                    display: flex;
                    align-items: center;
                }
                .error-box { 
                    padding: 40px; 
                    background: #ffffff; 
                    border-radius: 12px; 
                    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15); 
                    text-align: center; 
                }
                .error-icon { 
                    font-size: 80px; 
                    color: #dc3545; 
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 error-box">
                        <div class="error-icon">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </div>
                        <h2 class="mt-3">Payment Failed</h2>
                        <p class="lead text-danger">
                            Your payment was not completed. Please try again.
                        </p>
                        <div class="mt-4">
                            <a href="cart.php" class="btn btn-primary">
                                <i class="fa-solid fa-shopping-cart"></i> Return to Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
} catch(Exception $e) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Error</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
        <style>
            body { 
                background: #f8f9fa; 
                font-family: Arial, sans-serif; 
                height: 100vh;
                display: flex;
                align-items: center;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <h2 class="text-danger">Payment Error</h2>
                    <p class="lead">There was an error processing your payment.</p>
                    <a href="cart.php" class="btn btn-primary">Return to Cart</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>