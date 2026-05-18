<?php
require_once __DIR__ . '/db.php';

/* ===== Message handling (PUT THIS HERE) ===== */
$message = '';
$message_type = '';

if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $success = $_GET['success'] ?? 0;

    $message_type = ($success == 1) ? 'success' : 'error';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | Account Recovery</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
      max-width: 450px;
    }

    .card {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      overflow: hidden;
      transition: transform 0.3s ease;
    }

    .card:hover {
      transform: translateY(-5px);
    }

    .header {
      background: linear-gradient(to right, #6a11cb, #2575fc);
      color: white;
      padding: 30px 20px;
      text-align: center;
    }

    .header h1 {
      font-size: 28px;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .header p {
      opacity: 0.9;
      font-size: 15px;
    }

    .header i {
      font-size: 50px;
      margin-bottom: 15px;
      display: block;
    }

    .form-container {
      padding: 35px;
    }

    .input-group {
      margin-bottom: 25px;
      position: relative;
    }

    .input-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #333;
    }

    .input-group input {
      width: 100%;
      padding: 15px 15px 15px 45px;
      border: 2px solid #e1e5ee;
      border-radius: 10px;
      font-size: 16px;
      transition: all 0.3s;
    }

    .input-group input:focus {
      border-color: #6a11cb;
      outline: none;
      box-shadow: 0 0 0 3px rgba(106, 17, 203, 0.1);
    }

    .input-icon {
      position: absolute;
      left: 15px;
      top: 43px;
      color: #6a11cb;
      font-size: 18px;
    }

    .submit-btn {
      background: linear-gradient(to right, #6a11cb, #2575fc);
      color: white;
      border: none;
      width: 100%;
      padding: 16px;
      border-radius: 10px;
      font-size: 18px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
    }

    .submit-btn:hover {
      background: linear-gradient(to right, #5a0cb9, #1c68f0);
      box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
    }

    .submit-btn:active {
      transform: scale(0.98);
    }

    .message {
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 25px;
      text-align: center;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .login-link {
      text-align: center;
      margin-top: 25px;
      color: #666;
    }

    .login-link a {
      color: #6a11cb;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }

    .login-link a:hover {
      text-decoration: underline;
      color: #2575fc;
    }

    .instructions {
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 10px;
      margin-top: 20px;
      font-size: 14px;
      color: #555;
      border-left: 4px solid #6a11cb;
    }

    .instructions h3 {
      margin-bottom: 8px;
      color: #333;
      font-size: 16px;
    }

    .instructions ul {
      padding-left: 20px;
    }

    .instructions li {
      margin-bottom: 5px;
    }

    @media (max-width: 480px) {
      .container {
        padding: 10px;
      }

      .form-container {
        padding: 25px;
      }

      .header {
        padding: 25px 15px;
      }

      .header h1 {
        font-size: 24px;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="card">
      <div class="header">
        <i class="fas fa-key"></i>
        <h1>Forgot Password?</h1>
        <p>Enter your email address to reset your password</p>
      </div>

      <div class="form-container">
        <?php if ($message): ?>
          <div class="message <?php echo $message_type; ?>">
            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span><?php echo $message; ?></span>
          </div>
        <?php endif; ?>

        <form method="post" action="handle-forgot-password.php" id="forgotPasswordForm">
          <div class="input-group">
            <label for="email">Email Address</label>
            <div class="input-icon">
              <i class="fas fa-envelope"></i>
            </div>
            <input type="email" id="email" name="email" required placeholder="Enter your registered email address">
          </div>

          <button type="submit" class="submit-btn">
            <i class="fas fa-paper-plane"></i> Send Reset Link
          </button>
        </form>

        <div class="instructions">
          <h3><i class="fas fa-info-circle"></i> What happens next?</h3>
          <ul>
            <li>We'll send a password reset link to your email</li>
            <li>Click the link in the email to create a new password</li>
            <li>The link will expire in 1 hour for security reasons</li>
            <li>If you don't see the email, check your spam folder</li>
          </ul>
        </div>

        <div class="login-link">
          Remember your password? <a href="login.php">Back to Login</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Form validation
    document.getElementById('forgotPasswordForm').addEventListener('submit', function(event) {
      const emailInput = document.getElementById('email');
      const emailValue = emailInput.value.trim();

      // Simple email validation
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!emailPattern.test(emailValue)) {
        alert('Please enter a valid email address.');
        emailInput.focus();
        event.preventDefault();
        return false;
      }

      // Show loading state
      const submitBtn = this.querySelector('.submit-btn');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
      submitBtn.disabled = true;

      // In a real application, the form would be submitted here
      // For demo purposes, we'll revert after 2 seconds
      setTimeout(function() {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }, 2000);
    });

    // Add focus effect to input
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('focus', function() {
      this.parentElement.querySelector('.input-icon').style.color = '#2575fc';
    });

    emailInput.addEventListener('blur', function() {
      this.parentElement.querySelector('.input-icon').style.color = '#6a11cb';
    });
  </script>
</body>

</html>