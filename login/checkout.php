<?php
include("conn.php");
require 'stripe_config.php';

if(!isset($_COOKIE['user_id'])) die("Please login first");
$user_id = intval($_COOKIE['user_id']);

// Get cart items for the logged-in user
$stmt = $conn->prepare("SELECT * FROM baseline_User_Cart WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if(empty($cart_items)){
    die("No items in your cart.");
}

$total = 0;
$line_items = [];

foreach($cart_items as $item){
    $price = floatval($item['course_price']);
    $total += $price;

    $line_items[] = [
        'price_data' => [
            'currency' => 'inr',
            'product_data' => [
                'name' => $item['course_title'],
            ],
            'unit_amount' => intval($price * 100), 
        ],
        'quantity' => 1,
    ];
}

try {
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
       'success_url' => $BASE_URL . "success.php?session_id={CHECKOUT_SESSION_ID}",
        'cancel_url' => 'https://avengers.topscripts.in/edu/baselinelearning/cancel.php',
        'metadata' => [
            'user_id' => $user_id
        ]
    ]);

    // Redirect to Strip
    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
    exit;
} catch(Exception $e) {
    echo "Stripe Error: " . $e->getMessage();
}
