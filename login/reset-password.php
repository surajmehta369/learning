<?php
// Show errors (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';

// Get token from URL
$token = $_GET['token'] ?? '';
$token = trim($token);

if ($token === '') {
    die("<div class='error-container'><h2>Invalid or missing token.</h2><p>Please request a new password reset link.</p></div>");
}

// Check token in database
$stmt = $conn->prepare("
    SELECT email, expires_at 
    FROM password_resets 
    WHERE token = ? 
    LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<div class='error-container'><h2>Invalid or expired reset link.</h2><p>The reset link you used is no longer valid. Please request a new one.</p></div>");
}

$row = $result->fetch_assoc();

// Check token expiry
if (strtotime($row['expires_at']) < time()) {
    die("<div class='error-container'><h2>Reset link has expired.</h2><p>Password reset links expire after 1 hour for security. Please request a new one.</p></div>");
}

$email = $row['email'];
$stmt->close();

// Handle form submission
$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Hash new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Update user password
        $stmt = $conn->prepare("UPDATE signup SET password_hash = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        $stmt->execute();
        $stmt->close();

        // Delete used token
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();

        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password | Account Recovery</title>
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
            max-width: 500px;
        }

        .card {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .header {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            padding: 35px 30px;
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
            font-size: 60px;
            margin-bottom: 20px;
            display: block;
        }

        .form-container {
            padding: 40px;
        }

        .input-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-group label {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 15px;
        }

        .input-group label i {
            margin-right: 8px;
            color: #6a11cb;
        }

        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e5ee;
            border-radius: 12px;
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

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 43px;
            color: #777;
            font-size: 18px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: #6a11cb;
        }

        .password-strength {
            margin-top: 10px;
        }

        .strength-meter {
            height: 6px;
            background-color: #eee;
            border-radius: 3px;
            margin-bottom: 5px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: all 0.3s;
        }

        .strength-text {
            font-size: 13px;
            color: #777;
        }

        .strength-requirements {
            margin-top: 15px;
            font-size: 13px;
            color: #666;
        }

        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .requirement i {
            margin-right: 8px;
            font-size: 12px;
        }

        .requirement.met {
            color: #28a745;
        }

        .requirement.not-met {
            color: #777;
        }

        .submit-btn {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            border: none;
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }

        .submit-btn:hover:not(:disabled) {
            background: linear-gradient(to right, #5a0cb9, #1c68f0);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .submit-btn:active:not(:disabled) {
            transform: scale(0.98);
        }

        .message {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .error {
            background-color: #fdf0f0;
            color: #d93025;
            border: 1px solid #f5c6cb;
        }

        .success {
            background-color: #f0f9f0;
            color: #28a745;
            border: 1px solid #c3e6cb;
        }

        .success-container {
            text-align: center;
            padding: 40px;
        }

        .success-container i {
            font-size: 70px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .success-container h2 {
            color: #333;
            margin-bottom: 15px;
        }

        .success-container p {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .login-btn {
            display: inline-block;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .login-btn:hover {
            background: linear-gradient(to right, #5a0cb9, #1c68f0);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
            transform: translateY(-2px);
        }

        .error-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .error-container i {
            font-size: 70px;
            color: #d93025;
            margin-bottom: 20px;
        }

        .error-container h2 {
            color: #333;
            margin-bottom: 15px;
        }

        .error-container p {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        @media (max-width: 550px) {
            .container {
                padding: 10px;
            }
            
            .form-container {
                padding: 30px 20px;
            }
            
            .header {
                padding: 25px 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="card">
                <div class="success-container">
                    <i class="fas fa-check-circle"></i>
                    <h2>Password Reset Successful!</h2>
                    <p>Your password has been successfully reset. You can now log in to your account using your new password.</p>
                    <a href="login.php" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="header">
                    <i class="fas fa-lock"></i>
                    <h1>Create New Password</h1>
                    <p>Enter a new password for your account</p>
                </div>
                
                <div class="form-container">
                    <?php if (!empty($error)): ?>
                        <div class="message error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="resetPasswordForm">
                        <div class="input-group">
                            <label for="password">
                                <i class="fas fa-key"></i> New Password
                            </label>
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Enter your new password" minlength="8">
                            <div class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </div>
                            
                            <div class="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-text" id="strengthText">Password strength</div>
                            </div>
                            
                            <div class="strength-requirements">
                                <div class="requirement not-met" id="reqLength">
                                    <i class="fas fa-circle"></i> At least 8 characters
                                </div>
                                <div class="requirement not-met" id="reqLowercase">
                                    <i class="fas fa-circle"></i> Contains lowercase letter
                                </div>
                                <div class="requirement not-met" id="reqUppercase">
                                    <i class="fas fa-circle"></i> Contains uppercase letter
                                </div>
                                <div class="requirement not-met" id="reqNumber">
                                    <i class="fas fa-circle"></i> Contains number
                                </div>
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <label for="confirm_password">
                                <i class="fas fa-key"></i> Confirm Password
                            </label>
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm your new password" minlength="8">
                            <div class="toggle-password" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div id="passwordMatch" style="margin-top: 5px; font-size: 13px; display: none;">
                                <i class="fas fa-check-circle" style="color: #28a745; margin-right: 5px;"></i>
                                <span>Passwords match</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="submit-btn" id="submitBtn">
                            <i class="fas fa-redo"></i> Reset Password
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
        
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Password strength checker
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check password requirements
            const hasLength = password.length >= 8;
            const hasLowercase = /[a-z]/.test(password);
            const hasUppercase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            // Update requirement indicators
            document.getElementById('reqLength').className = hasLength ? 'requirement met' : 'requirement not-met';
            document.getElementById('reqLength').innerHTML = hasLength ? 
                '<i class="fas fa-check-circle"></i> At least 8 characters' : 
                '<i class="fas fa-circle"></i> At least 8 characters';
                
            document.getElementById('reqLowercase').className = hasLowercase ? 'requirement met' : 'requirement not-met';
            document.getElementById('reqLowercase').innerHTML = hasLowercase ? 
                '<i class="fas fa-check-circle"></i> Contains lowercase letter' : 
                '<i class="fas fa-circle"></i> Contains lowercase letter';
                
            document.getElementById('reqUppercase').className = hasUppercase ? 'requirement met' : 'requirement not-met';
            document.getElementById('reqUppercase').innerHTML = hasUppercase ? 
                '<i class="fas fa-check-circle"></i> Contains uppercase letter' : 
                '<i class="fas fa-circle"></i> Contains uppercase letter';
                
            document.getElementById('reqNumber').className = hasNumber ? 'requirement met' : 'requirement not-met';
            document.getElementById('reqNumber').innerHTML = hasNumber ? 
                '<i class="fas fa-check-circle"></i> Contains number' : 
                '<i class="fas fa-circle"></i> Contains number';
            
            // Calculate strength score
            if (hasLength) strength += 25;
            if (hasLowercase) strength += 25;
            if (hasUppercase) strength += 25;
            if (hasNumber) strength += 25;
            
            // Update strength meter
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            strengthFill.style.width = strength + '%';
            
            if (strength === 0) {
                strengthFill.style.backgroundColor = '#eee';
                strengthText.textContent = 'Enter a password';
                strengthText.style.color = '#777';
            } else if (strength <= 50) {
                strengthFill.style.backgroundColor = '#ff4d4d';
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#ff4d4d';
            } else if (strength <= 75) {
                strengthFill.style.backgroundColor = '#ffa500';
                strengthText.textContent = 'Fair password';
                strengthText.style.color = '#ffa500';
            } else {
                strengthFill.style.backgroundColor = '#28a745';
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#28a745';
            }
            
            // Check password match
            checkPasswordMatch();
        });
        
        // Password match checker
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const matchIndicator = document.getElementById('passwordMatch');
            const submitBtn = document.getElementById('submitBtn');
            
            if (confirmPassword.length === 0) {
                matchIndicator.style.display = 'none';
                submitBtn.disabled = true;
                return;
            }
            
            if (password === confirmPassword && password.length > 0) {
                matchIndicator.style.display = 'flex';
                matchIndicator.innerHTML = '<i class="fas fa-check-circle" style="color: #28a745; margin-right: 5px;"></i><span>Passwords match</span>';
                submitBtn.disabled = false;
            } else if (confirmPassword.length > 0) {
                matchIndicator.style.display = 'flex';
                matchIndicator.innerHTML = '<i class="fas fa-times-circle" style="color: #d93025; margin-right: 5px;"></i><span>Passwords do not match</span>';
                submitBtn.disabled = true;
            }
        }
        
        // Form submission with loading state
        document.getElementById('resetPasswordForm').addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Final validation
            if (password.length < 8) {
                alert('Password must be at least 8 characters long.');
                event.preventDefault();
                return false;
            }
            
            if (password !== confirmPassword) {
                alert('Passwords do not match.');
                event.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting Password...';
            submitBtn.disabled = true;
            
            // Form will submit normally
            return true;
        });
    </script>
</body>
</html>