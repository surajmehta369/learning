<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// include("conn.php");
// require 'stripe_config.php';

// if(!isset($_GET['sid'])) {
//     die("Invalid request. No session ID provided.");
// }

// $session_id = $_GET['sid'];

// try {
//     $session = \Stripe\Checkout\Session::retrieve($session_id);

//     $user_id = isset($session->metadata->user_id) ? intval($session->metadata->user_id) : 0;

//     if($user_id <= 0) {
//         die("Invalid user data.");
//     }

  
//     $stmt = $conn->prepare("UPDATE baseline_User_Cart SET payment_mode = 'cancelled' WHERE user_id = ?");
//     $stmt->bind_param("i", $user_id);
//     $stmt->execute();
//     $stmt->close();

// } catch(Exception $e) {
    
// }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body { 
            background: linear-gradient(135deg, #e8f9e9, #d0f4cf);
            font-family: "Poppins", Arial, sans-serif; 
            height: 100vh;
            display: flex;
            align-items: center;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .cancel-box { 
            padding: 40px; 
            background: #ffffff; 
            border-radius: 16px; 
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.15); 
            text-align: center; 
            animation: scaleUp 0.6s ease-out;
        }

        @keyframes scaleUp {
            0% { transform: scale(0.6); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .cancel-icon { 
            font-size: 90px; 
            color: #ff5c5c; 
            animation: softShake 0.8s ease-in-out;
        }

        @keyframes softShake {
            0% { transform: translateX(0); }
            30% { transform: translateX(-5px); }
            60% { transform: translateX(5px); }
            100% { transform: translateX(0); }
        }

        h2 { 
            color: #2c3e50; 
            font-weight: 600; 
        }

        p.lead {
            font-size: 18px;
            color: #e74c3c;
        }

        .btn-custom { 
            padding: 12px 22px; 
            font-size: 17px; 
            border-radius: 8px; 
            transition: 0.3s; 
        }

        .btn-back {
            border: 2px solid #2e7d32;
            color: #2e7d32;
            background: none;
        }
        .btn-back:hover {
            background: #2e7d32;
            color: white;
        }

        .btn-retry {
            background: #66bb6a;
            color: white;
        }
        .btn-retry:hover {
            background: #57a05a;
        }

    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 cancel-box">

                <div class="cancel-icon">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>

                <h2 class="mt-3">Payment Cancelled</h2>

                <p class="lead">
                    You cancelled the payment. No charges were made.
                </p>

                <p class="text-muted">
                    You can continue shopping or try again anytime.
                </p>

                <div class="mt-4">
                    <a href="cart.php" class="btn btn-custom btn-back mr-2">
                        <i class="fa-solid fa-arrow-left"></i> Back to Cart
                    </a>

                    <a href="checkout.php" class="btn btn-custom btn-retry">
                        <i class="fa-solid fa-rotate-right"></i> Retry Payment
                    </a>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
