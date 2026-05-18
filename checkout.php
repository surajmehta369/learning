<?php
session_name('STUDENT_SESSION');
session_start();

include("conn.php");
error_reporting(E_ERROR | E_PARSE);

require 'payments/stripe_config.php';
require 'payments/paypal_config.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT id FROM signup WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_exists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$user_exists) {
    die("Invalid user");
}

$user_stmt = $conn->prepare("SELECT full_name, email FROM signup WHERE id=?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$user_name = $user_data['full_name'] ?? '';
$user_email = $user_data['email'] ?? '';
$stmt = $conn->prepare("SELECT * FROM baseline_User_Cart WHERE user_id=? AND (payment_mode IS NULL OR payment_mode = '' OR payment_mode = 'pending' OR payment_mode = 'stripe_failed')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($cart_items)) {
    die("No pending items in your cart. <a href='cart.php'>Return to Cart</a>");
}
$total = 0;
foreach ($cart_items as $item) {
    $total += floatval($item['course_price']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_gateway'])) {
    $gateway = $_POST['payment_gateway'];

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name) || empty($email) || empty($phone)) {
        die("Please fill all billing details.");
    }

    $update_stmt = $conn->prepare("UPDATE baseline_User_Cart SET payment_mode='pending' WHERE user_id=? AND (payment_mode IS NULL OR payment_mode = '' OR payment_mode = 'stripe_failed')");
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();

    if ($gateway === 'stripe') {
        $line_items = [];
        foreach ($cart_items as $item) {
            $line_items[] = [
                'price_data' => [
                    'currency' => 'inr',
                    'product_data' => ['name' => $item['course_title']],
                    'unit_amount' => intval(floatval($item['course_price']) * 100),
                ],
                'quantity' => 1,
            ];
        }

        try {
            $checkout_session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $line_items,
                'mode' => 'payment',
                'success_url' => '/payments/success.php?sid={CHECKOUT_SESSION_ID}',
                'cancel_url' => '/payments/cancel.php',
                'metadata' => [
                    'user_id' => $user_id,
                    'name' => $name,
                    'email' => $email
                ]
            ]);
            header("Location: " . $checkout_session->url);
            exit;
        } catch (Exception $e) {
            die("Stripe Error: " . $e->getMessage());
        }
    } elseif ($gateway === 'paypal') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $paypal_url . "/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $_ENV['PAYPAL_CLIENT_ID'] . ":" . $_ENV['PAYPAL_CLIENT_SECRET']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        $result = curl_exec($ch);
        $json = json_decode($result);

        if (!isset($json->access_token)) {
            die("PayPal Auth Error: Check your Client ID and Secret.");
        }
        $access_token = $json->access_token;

        $order_data = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "amount" => [
                    "currency_code" => "USD",
                    "value" => number_format($total, 2, '.', '')
                ]
            ]],
            "application_context" => [
                "shipping_preference" => "NO_SHIPPING",
                "user_action" => "PAY_NOW",
                "return_url" => "/payments/paypal_success.php",
                "cancel_url" => "/payments/paypal_cancel.php"
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $paypal_url . "/v2/checkout/orders");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $access_token
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
        $response = curl_exec($ch);
        $order = json_decode($response);

        if (isset($order->links)) {
            foreach ($order->links as $link) {
                if ($link->rel === 'approve') {
                    header("Location: " . $link->href);
                    exit;
                }
            }
        } else {
            echo "PayPal Error: " . ($order->message ?? "Unknown error occurred.");
            exit;
        }
    } elseif ($gateway === 'razorpay') {

        $keyId = $_ENV['RazorPayID'];
        $keySecret = $_ENV['RazorPaySECRET'];

        if (!isset($total) || empty($total)) {
            die("Invalid amount");
        }

        $cleanTotal = (float)filter_var($total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $amount = (int)round($cleanTotal * 100);

        $receiptId = "rcpt_" . time();

        $data = [
            "receipt" => $receiptId,
            "amount" => $amount,
            "currency" => "INR"
        ];

        $ch = curl_init("https://api.razorpay.com/v1/orders");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $keyId . ":" . $keySecret);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            die("Curl Error: " . curl_error($ch));
        }

        $result = json_decode($response, true);

        if (!isset($result['id'])) {
            echo "<pre>";
            print_r($result);
            exit;
        }

        $razorpayOrderId = $result['id']; ?>
        <!DOCTYPE html>
        <html>

        <head>
            <title>Redirecting to Razorpay...</title>
            <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        </head>

        <body>

            <script>
                var options = {

                    "key": "<?php echo $keyId; ?>",
                    "amount": "<?php echo $amount; ?>",
                    "currency": "INR",
                    "name": "Baseline E-Learning",
                    "description": "Course Payment",
                    "order_id": "<?php echo $razorpayOrderId; ?>",

                    "handler": function(response) {

                        var form = document.createElement("form");

                        form.method = "POST";
                        form.action = "payments/RazorPay.php";

                        form.innerHTML = `
                                            <input type="hidden" name="razorpay_payment_id" value="${response.razorpay_payment_id}">
                                            <input type="hidden" name="razorpay_order_id" value="${response.razorpay_order_id}">
                                            <input type="hidden" name="razorpay_signature" value="${response.razorpay_signature}">
                                        `;
                        document.body.appendChild(form);
                        form.submit();
                    },

                    "theme": {
                        "color": "#5624d0"
                    }
                };

                var rzp = new Razorpay(options);

                window.onload = function() {
                    rzp.open();
                };
            </script>

        </body>

        </html>

<?php
        exit;
    } else {
        die("Invalid payment gateway");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Baseline Learning</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, #6d3df5 0%, transparent 25%),
                radial-gradient(circle at bottom right, #8f6bff 0%, transparent 20%),
                #f5f7ff;
            min-height: 100vh;
            padding: 40px 20px;
            color: #1f2937;
        }

        .checkout-wrapper {
            max-width: 1200px;
            margin: auto;
            display: grid;
            grid-template-columns: 1.7fr 1fr;
            gap: 28px;
        }

        .checkout-left,
        .checkout-right {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 30px;
            box-shadow:
                0 10px 40px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .checkout-right {
            height: fit-content;
            position: sticky;
            top: 30px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .btn-back {
            text-decoration: none;
            color: #5624d0;
            font-weight: 600;
            background: #f3efff;
            padding: 10px 16px;
            border-radius: 12px;
            transition: 0.3s;
        }

        .btn-back:hover {
            background: #5624d0;
            color: #fff;
            transform: translateY(-2px);
        }

        .secure-badge {
            background: #e8fff2;
            color: #0d8a4f;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
        }

        h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 15px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid #dbe2ea;
            background: #fff;
            font-size: 15px;
            transition: 0.3s;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #6d3df5;
            box-shadow: 0 0 0 4px rgba(109, 61, 245, 0.12);
            outline: none;
            transform: translateY(-1px);
        }

        .section-title {
            margin: 30px 0 18px;
            font-size: 20px;
            font-weight: 700;
        }

        .payment-options {
            display: grid;
            gap: 14px;
        }

        .payment-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px;
            border-radius: 18px;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all .3s ease;
            background: #fff;
            position: relative;
            overflow: hidden;
        }

        .payment-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(109, 61, 245, 0.08), transparent);
            opacity: 0;
            transition: .3s;
        }

        .payment-card:hover {
            transform: translateY(-3px);
            border-color: #6d3df5;
            box-shadow: 0 10px 25px rgba(109, 61, 245, 0.12);
        }

        .payment-card:hover::before {
            opacity: 1;
        }

        .payment-card input {
            display: none;
        }

        .payment-card.active {
            border-color: #6d3df5;
            background: #f8f5ff;
        }

        .payment-left {
            display: flex;
            align-items: center;
            gap: 14px;
            z-index: 2;
        }

        .payment-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .payment-info h4 {
            font-size: 16px;
            margin-bottom: 4px;
        }

        .payment-info p {
            font-size: 13px;
            color: #6b7280;
        }

        .radio-check {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #c7cad1;
            position: relative;
            z-index: 2;
        }

        .payment-card.active .radio-check {
            border-color: #6d3df5;
        }

        .payment-card.active .radio-check::after {
            content: "";
            width: 12px;
            height: 12px;
            background: #6d3df5;
            border-radius: 50%;
            position: absolute;
            top: 4px;
            left: 4px;
        }

        .btn-pay {
            width: 100%;
            margin-top: 28px;
            padding: 18px;
            border: none;
            border-radius: 18px;
            background: linear-gradient(135deg, #6d3df5, #5624d0);
            color: #fff;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: .3s;
            box-shadow: 0 12px 24px rgba(109, 61, 245, 0.25);
        }

        .btn-pay:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 35px rgba(109, 61, 245, 0.35);
        }

        .btn-pay:disabled {
            opacity: .7;
            cursor: not-allowed;
        }

        .summary-header {
            margin-bottom: 24px;
        }

        .summary-header h3 {
            font-size: 24px;
            margin-bottom: 6px;
        }

        .summary-header p {
            color: #6b7280;
            font-size: 14px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 16px 0;
            border-bottom: 1px solid #edf0f4;
        }

        .course-info {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .course-img {
            width: 90px;
            height: 65px;
            border-radius: 14px;
            object-fit: cover;
        }

        .course-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }

        .course-tag {
            font-size: 12px;
            background: #f3efff;
            color: #6d3df5;
            padding: 4px 10px;
            border-radius: 30px;
            display: inline-block;
        }

        .price {
            font-weight: 700;
            color: #111827;
            white-space: nowrap;
        }

        .coupon-box {
            margin: 24px 0;
            display: flex;
            gap: 10px;
        }

        .coupon-box input {
            flex: 1;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #dbe2ea;
        }

        .coupon-box button {
            padding: 14px 18px;
            border: none;
            background: #111827;
            color: #fff;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 600;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            color: #4b5563;
        }

        .summary-total {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 2px dashed #d7dce3;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-total h2 {
            margin: 0;
            color: #6d3df5;
        }

        .trust-info {
            margin-top: 24px;
            padding: 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #f8f5ff, #ffffff);
            border: 1px solid #ece7ff;
        }

        .trust-info div {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 14px;
            color: #374151;
        }

        .trust-info div:last-child {
            margin-bottom: 0;
        }

        @media(max-width: 900px) {
            .checkout-wrapper {
                grid-template-columns: 1fr;
            }

            .checkout-right {
                position: relative;
                top: 0;
            }
        }

        @media(max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            body {
                padding: 20px 12px;
            }

            .checkout-left,
            .checkout-right {
                padding: 22px;
                border-radius: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="checkout-wrapper">

        <!-- LEFT -->
        <div class="checkout-left">

            <div class="top-bar">
                <a href="cart.php" class="btn-back">
                    ← Back to Cart
                </a>

                <div class="secure-badge">
                    🔒 Secure Checkout
                </div>
            </div>

            <h2>Complete Your Purchase</h2>
            <p class="subtitle">
                Fill in your details and choose your preferred payment method.
            </p>

            <form method="POST" id="checkoutForm">

                <div class="form-grid">

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required
                            value="<?php echo htmlspecialchars($user_name); ?>">
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required
                            value="<?php echo htmlspecialchars($user_email); ?>">
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" required>
                    </div>

                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" required>
                    </div>

                    <div class="form-group full">
                        <label>Address</label>
                        <textarea name="address" required></textarea>
                    </div>

                </div>

                <div class="section-title">
                    Payment Method
                </div>

                <div class="payment-options">

                    <label class="payment-card">
                        <input type="radio" name="payment_gateway" value="stripe">

                        <div class="payment-left">
                            <div class="payment-icon">💳</div>

                            <div class="payment-info">
                                <h4>Stripe</h4>
                                <p>Pay securely using debit or credit card</p>
                            </div>
                        </div>

                        <div class="radio-check"></div>
                    </label>

                    <label class="payment-card">
                        <input type="radio" name="payment_gateway" value="paypal">

                        <div class="payment-left">
                            <div class="payment-icon">🅿️</div>

                            <div class="payment-info">
                                <h4>PayPal</h4>
                                <p>Fast & trusted global payments</p>
                            </div>
                        </div>

                        <div class="radio-check"></div>
                    </label>

                    <label class="payment-card">
                        <input type="radio" name="payment_gateway" value="razorpay">

                        <div class="payment-left">
                            <div class="payment-icon">🇮🇳</div>

                            <div class="payment-info">
                                <h4>Razorpay</h4>
                                <p>UPI, Wallets, Net Banking & Cards</p>
                            </div>
                        </div>

                        <div class="radio-check"></div>
                    </label>

                </div>

                <button type="submit" class="btn-pay" id="payBtn">
                    Proceed to Secure Payment →
                </button>

            </form>

        </div>

        <!-- RIGHT -->
        <div class="checkout-right">

            <div class="summary-header">
                <h3>Order Summary</h3>
                <p><?php echo count($cart_items); ?> course(s) selected</p>
            </div>

            <?php foreach ($cart_items as $item): ?>

                <div class="summary-item">

                    <div class="course-info">

                        <img src="/<?php echo htmlspecialchars($item['course_image']); ?>"
                            class="course-img"
                            onerror="this.src='https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';">

                        <div>
                            <span class="course-title">
                                <?php echo htmlspecialchars($item['course_title']); ?>
                            </span>

                            <span class="course-tag">
                                Premium Course
                            </span>
                        </div>

                    </div>

                    <div class="price">
                        ₹<?php echo number_format($item['course_price'], 2); ?>
                    </div>

                </div>

            <?php endforeach; ?>

            <div class="coupon-box">
                <input type="text" placeholder="Enter coupon code">
                <button type="button">Apply</button>
            </div>

            <div class="summary-row">
                <span>Subtotal</span>
                <span>₹<?php echo number_format($total, 2); ?></span>
            </div>

            <div class="summary-row">
                <span>Discount</span>
                <span>₹0.00</span>
            </div>

            <div class="summary-row">
                <span>Taxes</span>
                <span>Included</span>
            </div>

            <div class="summary-total">
                <span>Total</span>

                <h2>
                    ₹<?php echo number_format($total, 2); ?>
                </h2>
            </div>

            <div class="trust-info">
                <div>✅ Instant course access after payment</div>
                <div>🔒 100% secure encrypted checkout</div>
                <div>⚡ Fast payment processing</div>
            </div>

        </div>

    </div>

    <script>
        const paymentCards = document.querySelectorAll(".payment-card");

        paymentCards.forEach(card => {
            card.addEventListener("click", () => {

                paymentCards.forEach(c => c.classList.remove("active"));

                card.classList.add("active");

                card.querySelector("input").checked = true;
            });
        });

        document.getElementById("checkoutForm").addEventListener("submit", function(e) {

            const gateway = document.querySelector('input[name="payment_gateway"]:checked');

            if (!gateway) {
                e.preventDefault();
                alert("Please select a payment method.");
                return;
            }

            const btn = document.getElementById("payBtn");

            btn.innerHTML = "Processing Payment...";
            btn.disabled = true;
        });
    </script>

</body>

</html>