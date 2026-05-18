<?php
require_once __DIR__ . '../../conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');

  $email = trim($_POST['email'] ?? '');
  $password = trim($_POST['password'] ?? '');

  if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
  }

  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['show_welcome'] = true;

    // --- CRITICAL CHANGE START ---
    // This creates the 'parent' session key your dashboard expects.
    if (strtolower($user['role']) === 'parent') {
      $_SESSION['parent'] = $user['email'];
    }
    // --- CRITICAL CHANGE END ---

    echo json_encode([
      'success' => true,
      'message' => 'Login successful!',
      'role' => $user['role']
    ]);
    exit;
  } else {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <title>Login to start learning</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light dark" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="stylesheet" href="../css/styles.css">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --bg: #ffffff;
      --bg-soft: #f7f8fa;
      --text: #1f2328;
      --muted: #6b7280;
      --brand: #5624d0;
      --brand-contrast: #ffffff;
      --border: #e5e7eb;
      --focus: #3b82f6;
      --btn-secondary-bg: #f3f4f6;
      --btn-secondary-text: #111827;
    }

    @media (prefers-color-scheme: light) {
      :root {
        --bg: #ffffff;
        --bg-soft: #f7f9fc;
        --text: #0b0d12;
        --muted: #6b7280;
        --brand: #7c5cff;
        --brand-contrast: #ffffff;
        --border: #d1d5db;
        --btn-secondary-bg: #e5e7eb;
        --btn-secondary-text: #0b0d12;
      }
    }

    * {
      box-sizing: border-box
    }

    html,
    body {
      height: 100%
    }

    body {
      margin: 0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
      color: var(--text);
      background: var(--bg);
      line-height: 1.45;
    }


    .ud-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 24px 16px 48px;
    }

    .auth-layout--auth-grid-layout--E7OfM {
      display: grid;
      grid-template-columns: 1fr;
      gap: 32px;
      align-items: center;
    }

    @media (min-width:980px) {
      .auth-layout--auth-grid-layout--E7OfM {
        grid-template-columns: 1.1fr 1fr;
        min-height: calc(100vh - 140px);
      }
    }

    .auth-layout--auth-form-image-col--gE0hR {
      display: block
    }

    .auth-layout--auth-form-image-wrapper--OwRnP {
      background: radial-gradient(1100px 600px at -10% -10%, var(--bg-soft) 0, transparent 60%),
        radial-gradient(800px 500px at 120% 120%, var(--bg-soft) 0, transparent 60%);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 24px;
      min-height: 280px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .auth-layout--auth-form-image--aISx9 img {
      width: 100%;
      height: auto;
      max-width: 680px;
      display: block
    }

    .auth-layout--auth-form-col--LnbX2 {
      display: block
    }

    .auth-form-row--large--tUoO2 {
      margin-bottom: 8px
    }

    .ud-heading-xxl {
      font-size: clamp(22px, 2.2vw, 32px);
      margin: 0 0 6px
    }

    .auth-form-row--small--Byo8R {
      margin: 12px 0
    }

    .auth-form-row--medium--T7wIs {
      margin: 16px 0
    }

    .auth-panel {
      border: 1px solid var(--border);
      background: var(--bg-soft);
      border-radius: 16px;
      padding: 28px;
    }

    .ud-compact-form-group.ud-form-group {
      display: block
    }

    .ud-compact-form-control-container {
      display: flex;
      flex-direction: column-reverse;
      gap: 6px
    }

    .ud-form-label.ud-heading-sm {
      font-size: 14px;
      font-weight: 600
    }

    .ud-text-input {
      border: 1px solid var(--border);
      background: #fff;
      color: #111827;
      border-radius: 10px;
      padding: 12px 14px;
      font-size: 16px;
      transition: border-color .15s, box-shadow .15s;
      outline: none;
      width: 100%;
    }

    .ud-text-input:focus {
      border-color: var(--focus);
      box-shadow: 0 0 0 4px color-mix(in oklab, var(--focus) 20%, transparent);
    }

    @media (prefers-color-scheme: dark) {
      :root {
        --bg: #ffffff;

      }
    }

    .ud-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      border: 0;
      border-radius: 10px;
      padding: 12px 16px;
      font-weight: 700;
      cursor: pointer;
      transition: transform .02s ease-in, box-shadow .15s ease;
    }

    .ud-btn:active {
      transform: translateY(1px)
    }

    .ud-btn-large {
      width: 100%
    }

    .ud-btn-brand {
      background: var(--brand);
      color: var(--brand-contrast);
      box-shadow: 0 6px 16px rgba(86, 36, 208, .25)
    }

    .ud-btn-brand:hover {
      filter: brightness(1.05)
    }

    .ud-btn-secondary {
      background: var(--btn-secondary-bg);
      color: var(--btn-secondary-text);
      border: 1px solid var(--border)
    }

    .ud-btn-icon {
      padding: 10px 14px
    }

    .ud-btn-icon-large {
      width: auto
    }

    .separator-module--separator--qtyh7 {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 20px 0 6px
    }

    .auth-option-separator--separator-text--ZP1wD {
      margin: 0;
      font-size: 13px;
      color: var(--muted);
      font-weight: 600;
      letter-spacing: .02em;
      text-transform: uppercase
    }

    .social-icon-row--social-icons-list--3de3w {
      list-style: none;
      padding: 0;
      margin: 12px 0 0;
      display: flex;
      gap: 12px;
      flex-wrap: wrap
    }

    .social-icon-row--social-icons-list--3de3w li {
      display: block
    }

    .other-options-button-module--other-options-button--ZXQ1m {
      padding: 10px 0
    }

    .ud-text-bold {
      font-weight: 700
    }

    .ud-link-underline {
      color: inherit;
      text-decoration: none;
      border-bottom: 1px solid transparent
    }

    .ud-link-underline:hover {
      border-color: currentColor
    }

    .small {
      font-size: 12px;
      color: var(--muted);
      margin-top: 8px
    }

    .google-signin-btn {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 6px 42px;
      background-color: white;
      border: #d1d1d3;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1rem;
      color: #4a4a4a;
      cursor: pointer;
      transition: background-color 0.2s ease, box-shadow 0.2s ease;
      width: 100%;
      max-width: 400px;
      box-sizing: border-box;
    }

    .google-signin-btn:hover {
      background-color: #f3e8ff;
      box-shadow: 0 0 10px rgba(107, 70, 193, 0.3);
    }

    .google-icon {
      width: 24px;
      height: 24px;
      display: block;
    }


    .toast-message {
      min-width: 250px;
      margin-bottom: 10px;
      padding: 12px 20px;
      border-radius: 8px;
      color: #fff;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      opacity: 0.95;
      cursor: default;
      transition: opacity 0.4s ease, transform 0.4s ease;
    }

    .toast-success {
      background-color: #28a745;
    }

    .toast-error {
      background-color: #dc3545;
    }

    .google-login-button {
      background-color: #b73232;
      color: white;
      border: none;
      border-radius: 10px;
      padding: 12px 20px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%
    }

    .google-login-button:hover {
      background-color: #8d1212ff
    }

    .google-login-button img {
      width: 20px;
      height: 20px
    }

    #toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 99999 !important;
    }

    .password-wrapper {
      position: relative;
    }

    .toggle-password {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #6b7280;
      font-size: 16px;
    }
  </style>
</head>

<body>

  <main class="ud-container auth-layout--auth-layout-container--OANZo" role="main" aria-labelledby="auth-form-heading">
    <div class="auth-layout--auth-grid-layout--E7OfM">


      <div class="auth-layout--auth-form-image-col--gE0hR">
        <div class="auth-layout--auth-form-image-wrapper--OwRnP">
          <picture class="auth-layout--auth-form-image--aISx9">

            <source media="(max-width: 980px)" srcset="https://baseavangers.topscripts.in/gagan/sms/baselinelearning/login/signin_logo.png">
            <source media="(min-width: 980px)" srcset="https://baseavangers.topscripts.in/gagan/sms/baselinelearning/login/signin_logo.png">
            <img alt="Baseline Learning" src="https://baseavangers.topscripts.in/gagan/sms/baselinelearning/login/signin_logo.png" loading="lazy">
          </picture>

        </div>
      </div>

      <div class="auth-layout--auth-form-col--LnbX2">
        <div class="auth-panel">
          <div>
            <div class="auth-form-row--large--tUoO2">
              <h1 id="auth-form-heading" class="ud-heading-xxl auth-form-heading--auth-form-heading--BNXbz">
                Log in to continue your learning journey
              </h1>
              <p class="small">Enter your email and password, or continue with a provider below.</p>
            </div>
          </div>

          <form id="login-form" method="post" novalidate autocomplete="email">
            <div class="auth-form-row--small--Byo8R">
              <div>
                <div class="ud-compact-form-group ud-form-group">
                  <div class="ud-compact-form-control-container">
                    <input aria-invalid="false" name="email" minlength="7" maxlength="77"
                      data-purpose="email-input" aria-label="Email" id="form-group--1" type="email"
                      class="ud-text-input ud-text-input-medium ud-text-sm ud-compact-form-control" value=""
                      placeholder="you@example.com" required>
                    <label for="form-group--1" class="ud-form-label ud-heading-sm">
                      <span class="ud-compact-form-label-content">
                        <span class="ud-compact-form-label-text">Email</span>
                      </span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="auth-form-row--small--Byo8R">
              <div class="ud-compact-form-group ud-form-group">
                <div class="ud-compact-form-control-container">

                  <div class="password-wrapper">
                    <input aria-invalid="false" name="password" minlength="6" maxlength="100"
                      aria-label="Password" id="form-group--2" type="password"
                      class="ud-text-input ud-text-input-medium ud-text-sm ud-compact-form-control"
                      placeholder="Enter your password" required autocomplete="current-password">

                    <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                  </div>

                  <label for="form-group--2" class="ud-form-label ud-heading-sm">
                    <span class="ud-compact-form-label-content">
                      <span class="ud-compact-form-label-text">Password</span>
                    </span>
                  </label>

                </div>
              </div>
            </div>

            <button type="submit" class="ud-btn ud-btn-large ud-btn-brand ud-btn-text-md">
              <span class="ud-btn-label">Continue</span>
            </button>
          </form>

          <div class="auth-form-row--medium--T7wIs">
            <div class="separator-module--separator--qtyh7">
            </div>
          </div>

          <div class="sep">
            <hr>
            <p style="text-align: center;">Other sign up options</p>
            <hr>
          </div>

          <div class="socials" aria-label="Other sign up options">

            <button type="button" onclick="window.location='google-login.php';" class="google-login-button">
              <i class="fab fa-google"></i>
              Sign in with Google
            </button>

          </div>
          <div class="text-end mt-2">
            <a href="forgot-password.php" class="ud-link-underline">
              Forgot password?
            </a>
          </div>

          <br>
          <p class="legal">
            By signing up, you agree to our
            <a href=" " target="_blank" rel="noopener noreferrer">Terms of Use</a>
            and
            <a href=" " target="_blank" rel="noopener noreferrer">Privacy Policy</a>.
          </p>

          <div>
            <div class="other-options-button-module--other-options-button--ZXQ1m other-options-button-module--other-options-bottom-border--JYBoJ">
              <span style="font-weight: bold; font-size: 1.1rem;">
                Don't have an account?
                <a class="ud-text-bold ud-link-underline" href="signup.php" aria-label="Sign up" style="font-weight: bold; color: #6f42c1; font-size: 1.1rem; margin-left: 5px;">
                  Sign up
                </a>
              </span>
            </div>
          </div>



        </div>
      </div>

    </div>
  </main>

  <div id="toast-container"
    style="position: fixed; top: 20px; right: 20px; z-index: 99999;">
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    $(document).ready(function() {
      $('#login-form').on('submit', function(e) {
        e.preventDefault();

        const email = $('#form-group--1').val().trim();
        const password = $('#form-group--2').val().trim();

        if (!email || !password) {
          Swal.fire({
            icon: 'warning',
            title: 'Required',
            text: 'Please fill in both email and password.',
            confirmButtonColor: '#7c5cff'
          });
          return;
        }

        $.ajax({
          url: 'handle-login.php',
          type: 'POST',
          dataType: 'json',
          data: {
            email: email,
            password: password
          },
          success: function(response) {
            if (response.success) {

              Swal.fire({
                icon: 'success',
                title: 'Welcome to Baseline Learning 🎉',
                text: 'Thank you for choosing us!',
                confirmButtonColor: '#7c5cff',
                timer: 3000,
                showConfirmButton: false
              });

              setTimeout(function() {
                // Normalize the role to lowercase to prevent case-mismatch bugs
                const userRole = response.role.toLowerCase();

                switch (userRole) {
                  case 'admin':
                    window.location.href = '../adminpage.php';
                    break;
                  case 'student':
                    window.location.href = '../student/profile.php';
                    break;
                  case 'teacher':
                    window.location.href = '../teacherpage.php';
                    break;
                  case 'parent':
                    window.location.href = '../parent_dashboard.php';
                    break;
                  default:
                    window.location.href = 'login.php';
                }
              }, 1500);

            } else {
              Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: response.message,
                confirmButtonColor: '#7c5cff'
              });
            }
          },
          error: function() {
            Swal.fire({
              icon: 'error',
              title: 'Oops...',
              text: 'An unexpected error occurred. Please try again.'
            });
          }
        });
      });
    });


    $('#togglePassword').click(function() {

      const password = $('#form-group--2');
      const icon = $(this);

      if (password.attr('type') === 'password') {
        password.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
      } else {
        password.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
      }

    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>