<?php
require_once __DIR__ . '../conn.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ===========================
   PHPMailer includes
   =========================== */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

/* ===========================
   Get & validate email
   =========================== */
$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: forgot-password.php?message=Invalid email address&success=0');
    exit;
}
$email_safe = mysqli_real_escape_string($conn, $email);

/* ===========================
   Check if user exists
   =========================== */
$checkQuery = "SELECT id, password_hash, type FROM signup WHERE email = '$email_safe' LIMIT 1";
$result = mysqli_query($conn, $checkQuery);

if ($result && mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);

    // Prevent reset link for Google login or users without password
    if ($user['type'] === 'google' || empty($user['password_hash'])) {
        header('Location: forgot-password.php?message=This email was created using Google Sign-In or has no password. Please login with Google directly.&success=0');
        exit;
    }

    /* ===========================
   Generate token
   =========================== */
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    /* ===========================
   Remove any old tokens first
   =========================== */
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->bind_param("s", $email_safe);
    $stmt->execute();
    $stmt->close();

    /* ===========================
   Store new token
   =========================== */
    $stmt = $conn->prepare("
    INSERT INTO password_resets (email, token, expires_at, created_at)
    VALUES (?, ?, ?, NOW())
");
    $stmt->bind_param("sss", $email_safe, $token, $expires);
    $stmt->execute();
    $stmt->close();

    // mysqli_query($conn, $insertQuery);

    /* ===========================
       Reset link
       =========================== */
    $resetLink = "https://avengers.topscripts.in/edu/baselinelearning/login/reset-password.php?token=$token";

    /* ===========================
       Send Email (SMTP)
       =========================== */
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'forapinew@gmail.com';
        $mail->Password   = 'nnhfvtldwpkzflew'; // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('forapinew@gmail.com', 'School Onboarding');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password - School Onboarding';
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(to right, #6a11cb, #2575fc); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; }
                    .button { display: inline-block; background: linear-gradient(to right, #6a11cb, #2575fc); color: white; text-decoration: none; padding: 12px 30px; border-radius: 8px; font-weight: bold; margin: 20px 0; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; text-align: center; }
                    .expiry-note { background: #fff8e1; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Password Reset Request</h2>
                    </div>
                    <div class='content'>
                        <p>Hello,</p>
                        <p>We received a request to reset the password for your account. Click the button below to set a new password:</p>
                        
                        <div style='text-align: center;'>
                            <a href='$resetLink' class='button'>Reset Your Password</a>
                        </div>
                        
                        <p>Or copy and paste this link into your browser:</p>
                        <p style='word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 5px; font-size: 14px;'>
                            $resetLink
                        </p>
                        
                        <div class='expiry-note'>
                            <strong>⚠️ This link will expire in 1 hour for security reasons.</strong>
                        </div>
                        
                        <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>
                        
                        <div class='footer'>
                            <p>This is an automated message, please do not reply to this email.</p>
                            <p>© " . date('Y') . " School Onboarding. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Password Reset Link: $resetLink\nThis link expires in 1 hour.\nIf you didn't request this, please ignore this email.";

        $mail->send();
        $showSuccessMessage = true;
        $emailSent = true;
    } catch (Exception $e) {
        $showSuccessMessage = true;
        $emailError = true;
        error_log("Email error: " . $mail->ErrorInfo);
    }
} else {
    header('Location: forgot-password.php?message=This email is not registered. Please register first.&success=0');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Email Sent | School Onboarding</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 550px;
        }

        .card {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .header i {
            font-size: 70px;
            margin-bottom: 20px;
            display: block;
            animation: bounce 1s ease infinite alternate;
        }

        @keyframes bounce {
            from {
                transform: translateY(0);
            }

            to {
                transform: translateY(-10px);
            }
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .content {
            padding: 40px;
            text-align: center;
        }

        .success-icon {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .success-message {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .email-display {
            background: #f8f9fa;
            padding: 12px 20px;
            border-radius: 10px;
            display: inline-block;
            margin: 20px 0;
            font-weight: 500;
            color: #6a11cb;
            border: 2px dashed #dee2e6;
        }

        .email-display i {
            margin-right: 8px;
            color: #6a11cb;
        }

        .instructions {
            background: #f0f9ff;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            text-align: left;
            border-left: 4px solid #2575fc;
        }

        .instructions h3 {
            color: #2575fc;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .instructions ul {
            list-style: none;
            padding-left: 5px;
        }

        .instructions li {
            margin-bottom: 12px;
            padding-left: 25px;
            position: relative;
        }

        .instructions li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }

        .timer {
            background: #fff8e1;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-weight: 500;
        }

        .timer i {
            color: #ff9800;
            font-size: 20px;
        }

        .timer span {
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            padding: 14px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            min-width: 180px;
        }

        .btn-primary {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #dee2e6;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #5a0cb9, #1c68f0);
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        .warning-note {
            margin-top: 25px;
            padding: 15px;
            background: #fdf0f0;
            border-radius: 10px;
            border: 1px solid #f5c6cb;
            color: #721c24;
            font-size: 14px;
            text-align: left;
        }

        .warning-note i {
            margin-right: 8px;
            color: #d93025;
        }

        @media (max-width: 600px) {
            .container {
                padding: 10px;
            }

            .content {
                padding: 30px 20px;
            }

            .header {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 26px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        /* Check animation */
        .checkmark {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #28a745;
            stroke-miterlimit: 10;
            margin: 0 auto 20px;
            box-shadow: inset 0px 0px 0px #28a745;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #28a745;
            fill: none;
            animation: stroke .6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke .3s cubic-bezier(0.65, 0, 0.45, 1) .8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {

            0%,
            100% {
                transform: none;
            }

            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 50px rgba(40, 167, 69, 0.1);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <i class="fas fa-paper-plane"></i>
                <h1>Check Your Email</h1>
                <p>We've sent you a password reset link</p>
            </div>

            <div class="content">
                <?php if (isset($showSuccessMessage) && $showSuccessMessage): ?>
                    <!-- Animated Checkmark -->
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                        <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                    </svg>

                    <h2 class="success-message">Reset Email Sent Successfully!</h2>

                    <div class="email-display">
                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($email) ?>
                    </div>

                    <p style="color: #666; line-height: 1.6; margin-bottom: 25px;">
                        We've sent a password reset link to your email address.
                        Please check your inbox and follow the instructions to create a new password.
                    </p>

                    <div class="timer">
                        <i class="fas fa-clock"></i>
                        <span>Link expires in: <strong>1 hour</strong></span>
                    </div>

                    <div class="instructions">
                        <h3><i class="fas fa-lightbulb"></i> What to do next:</h3>
                        <ul>
                            <li>Open the email from "School Onboarding"</li>
                            <li>Click the "Reset Password" button in the email</li>
                            <li>Create a new strong password for your account</li>
                            <li>Login with your new password</li>
                        </ul>
                    </div>

                    <?php if (isset($emailError)): ?>
                        <div class="warning-note">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Note:</strong> There was a temporary issue sending the email, but if an account exists with this email, the reset link has been generated. Please try again in a few minutes if you don't receive the email.
                        </div>
                    <?php endif; ?>

                    <div class="action-buttons">
                        <a href="https://mail.google.com" target="_blank" class="btn btn-primary">
                            <i class="fab fa-google"></i> Open Gmail
                        </a>
                        <a href="login.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                    </div>

                    <div style="margin-top: 25px; color: #777; font-size: 14px;">
                        <p><i class="fas fa-question-circle"></i> Didn't receive the email?</p>
                        <p style="margin-top: 5px;">
                            Check your spam folder or
                            <a href="forgot-password.php" style="color: #6a11cb; text-decoration: none; font-weight: 500;">
                                try again with a different email
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate the email display
            const emailDisplay = document.querySelector('.email-display');
            if (emailDisplay) {
                setTimeout(() => {
                    emailDisplay.style.transform = 'scale(1.05)';
                    emailDisplay.style.transition = 'transform 0.3s';
                    setTimeout(() => {
                        emailDisplay.style.transform = 'scale(1)';
                    }, 300);
                }, 800);
            }

            // Countdown timer for demo purposes
            const timerElement = document.querySelector('.timer strong');
            if (timerElement) {
                let minutes = 60;
                const updateTimer = () => {
                    minutes--;
                    if (minutes <= 0) {
                        timerElement.textContent = 'Expired!';
                        timerElement.style.color = '#d93025';
                        return;
                    }
                    const hrs = Math.floor(minutes / 60);
                    const mins = minutes % 60;
                    timerElement.textContent = `${hrs > 0 ? hrs + 'h ' : ''}${mins}m`;
                    setTimeout(updateTimer, 60000); // Update every minute
                };
                setTimeout(updateTimer, 60000);
            }

            // Button hover effects
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                });
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>

</html>