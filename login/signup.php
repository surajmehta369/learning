<?php
require_once __DIR__ . '../../conn.php';

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $conn = new mysqli($servername, $username, $password, $database);

  if ($conn->connect_error) {
    $errorMessage = "Connection failed: " . $conn->connect_error;
  } else {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';
    $subscribeToEmails = isset($_POST['subscribeToEmails']) ? 1 : 0;
    $role = trim($_POST['role'] ?? '');

    $parent_name = trim($_POST['parent_name'] ?? '');
    $parent_email = trim($_POST['parent_email'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');

    if (!$full_name) {
      $errorMessage = "Please enter your full name.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errorMessage = "Please enter a valid email address.";
    } elseif (strlen($password_input) < 6) {
      $errorMessage = "Password must be at least 6 characters.";
    } elseif (!$role) {
      $errorMessage = "Please select a role.";
    } elseif ($role === 'student' && !$parent_name) {
      $errorMessage = "Please enter parent / guardian name.";
    } elseif ($role === 'student' && !filter_var($parent_email, FILTER_VALIDATE_EMAIL)) {
      $errorMessage = "Please enter a valid parent email.";
    } else {
      $stmt = $conn->prepare("SELECT id FROM signup WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
        $errorMessage = "An account with this email already exists.";
      } else {
        $password_hash = password_hash($password_input, PASSWORD_DEFAULT);
        $type = 'Register';

        $stmt = $conn->prepare(
          "INSERT INTO signup 
                      (full_name, email, password_hash, subscribe_to_emails, role, type, parent_name, parent_email, parent_phone) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
          "sssisssss",
          $full_name,
          $email,
          $password_hash,
          $subscribeToEmails,
          $role,
          $type,
          $parent_name,
          $parent_email,
          $parent_phone
        );

        if ($stmt->execute()) {
          $successMessage = "Account created successfully! Redirecting...";
        } else {
          $errorMessage = "Error creating account: " . $stmt->error;
        }
      }

      $stmt->close();
    }
    $conn->close();
  }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign in to start learning</title>

  <!-- Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css">

</head>

<body style="padding:10px;">

  <main class="auth-wrap" role="main">
    <div class="auth-grid">

      <div class="auth-illustration">
        <picture>
          <source media="(max-width: 980px)" srcset="https://baseavangers.topscripts.in/gagan/sms/baselinelearning/login/signin_logo.png">
          <source media="(min-width: 980px)" srcset="https://baseavangers.topscripts.in/gagan/sms/baselinelearning/login/signin_logo.png">
          <img alt="Baseline Learning" src="https://baseavangers.topscripts.in/gagan/sms/baselinelearning/login/signin_logo.png" loading="lazy">
        </picture>
      </div>


      <section class="auth-card" aria-labelledby="auth-form-heading">
        <h1 id="auth-form-heading" class="auth-title">Sign up with email</h1>
        <p class="auth-sub">Create your account to start learning.</p>

        <form id="signup-form" method="POST" novalidate>

          <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
          <?php elseif ($errorMessage): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
          <?php endif; ?>

          <!-- Full name -->
          <div class="form-row">
            <div class="field">
              <input id="full-name" name="full_name" type="text" required placeholder=" " aria-required="true" autocomplete="name" value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" />
              <label class="label" for="full-name">Full name</label>
            </div>
            <div class="help" id="name-help" style="display:none"></div>
          </div>

          <!-- Email -->
          <div class="form-row">
            <div class="field">
              <input id="email" name="email" type="email" minlength="7" maxlength="77" placeholder=" " required autocomplete="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" />
              <label class="label" for="email">Email</label>
            </div>
            <div class="help" id="email-help" style="display:none"></div>
          </div>

          <!-- Password -->
          <div class="form-row">
            <div class="field">
              <input id="password" name="password" type="password" minlength="6" maxlength="100" placeholder=" " required autocomplete="new-password" />
              <label class="label" for="password">Password</label>
            </div>
            <div class="help" id="password-help" style="display:none"></div>
          </div>

          <div class="form-row">
            <div class="field">
              <select id="role" name="role" required>
                <option value="" disabled <?php echo empty($role) ? 'selected' : ''; ?>>Select Role</option>

                <option value="admin" style="display:none"
                  <?php echo ($role == 'admin') ? 'selected' : ''; ?>>
                  Admin
                </option>

                <option value="student"
                  <?php echo ($role == 'student') ? 'selected' : ''; ?>>
                  Student
                </option>

                <option value="teacher"
                  <?php echo ($role == 'teacher') ? 'selected' : ''; ?>>
                  Teacher
                </option>

                <option value="parent"
                  <?php echo ($role == 'parent') ? 'selected' : ''; ?>>
                  Parent
                </option>
              </select>
            </div>
            <div class="help" id="role-help" style="display:none"></div>
          </div>

          <div id="parent-fields" style="display:none;">

            <div class="form-row">
              <div class="field">
                <input id="parent-name" name="parent_name" type="text" placeholder=" "
                  value="<?php echo htmlspecialchars($parent_name ?? ''); ?>" />
                <label class="label" for="parent-name">Parent / Guardian Name</label>
              </div>
            </div>

            <div class="form-row">
              <div class="field">
                <input id="parent-email" name="parent_email" type="email" placeholder=" "
                  value="<?php echo htmlspecialchars($parent_email ?? ''); ?>" />
                <label class="label" for="parent-email">Parent Email</label>
              </div>
            </div>

            <div class="form-row">
              <div class="field">
                <input id="parent-phone" name="parent_phone" type="text" placeholder=" "
                  value="<?php echo htmlspecialchars($parent_phone ?? ''); ?>" />
                <label class="label" for="parent-phone">Parent Phone</label>
              </div>
            </div>

          </div>
          <!-- Marketing opt-in -->
          <div class="form-row">
            <label class="toggle" for="subscribe">
              <input type="checkbox" id="subscribe" name="subscribeToEmails" <?php echo (isset($_POST['subscribeToEmails']) || !isset($_POST['subscribeToEmails'])) ? 'checked' : ''; ?> />
              <span>Send me special offers, personalized recommendations, and learning tips.</span>
            </label>
          </div>

          <!-- Submit -->
          <div class="form-row">
            <button type="submit" class="submit-btn">
              Continue
            </button>
          </div>

          <!-- Separator -->
          <div class="sep">
            <hr>
            <p>Other sign up options</p>
            <hr>
          </div>

          <!-- Social buttons -->
          <div class="socials" aria-label="Other sign up options">

            <button type="button" onclick="window.location='google-login.php';" class="google-login-button">
              <i class="fab fa-google"></i>
              Sign in with Google
            </button>

          </div>


          <p class="legal">
            By signing up, you agree to our
            <a href=" " target="_blank" rel="noopener noreferrer">Terms of Use</a>
            and
            <a href=" " target="_blank" rel="noopener noreferrer">Privacy Policy</a>.
          </p>

          <div class="auth-footer">
            <span style="font-weight: bold; font-size: 1.1rem;">Already have an account?</span>
            <a href="login.php" style="font-weight: bold; color: #6f42c1; font-size: 1.1rem; margin-left: 5px;">Log in</a>
          </div>
        </form>
      </section>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const form = document.getElementById('signup-form');
    const nameEl = document.getElementById('full-name');
    const emailEl = document.getElementById('email');
    const passwordEl = document.getElementById('password');
    const roleEl = document.getElementById('role');
    const parentNameEl = document.getElementById('parent-name');
    const parentEmailEl = document.getElementById('parent-email');
    const parentFields = document.getElementById('parent-fields');

    const nameHelp = document.getElementById('name-help');
    const emailHelp = document.getElementById('email-help');
    const passwordHelp = document.getElementById('password-help');
    const roleHelp = document.getElementById('role-help');

    function setError(inputEl, helpEl, msg) {
      inputEl.closest('.form-row').classList.add('error');
      helpEl.textContent = msg;
      helpEl.style.display = 'block';
    }

    function clearError(inputEl, helpEl) {
      inputEl.closest('.form-row').classList.remove('error');
      helpEl.textContent = '';
      helpEl.style.display = 'none';
    }

    // Clear error on input
    nameEl.addEventListener('input', () => clearError(nameEl, nameHelp));
    emailEl.addEventListener('input', () => clearError(emailEl, emailHelp));
    passwordEl.addEventListener('input', () => clearError(passwordEl, passwordHelp));
    roleEl.addEventListener('change', () => clearError(roleEl, roleHelp));

    form.addEventListener('submit', (e) => {
      let ok = true;

      if (!nameEl.value.trim()) {
        setError(nameEl, nameHelp, 'Please enter your full name.');
        ok = false;
      }

      const emailVal = emailEl.value.trim();
      if (!emailVal) {
        setError(emailEl, emailHelp, 'Please enter your email address.');
        ok = false;
      } else {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i;
        if (!re.test(emailVal)) {
          setError(emailEl, emailHelp, 'Enter a valid email like name@example.com.');
          ok = false;
        }
      }

      const passwordVal = passwordEl.value;
      if (!passwordVal) {
        setError(passwordEl, passwordHelp, 'Please enter a password.');
        ok = false;
      } else if (passwordVal.length < 6) {
        setError(passwordEl, passwordHelp, 'Password must be at least 6 characters.');
        ok = false;
      }

      const roleVal = roleEl.value;
      if (!roleVal) {
        setError(roleEl, roleHelp, 'Please select a role.');
        ok = false;
      }

      if (roleVal === 'student') {

        if (!parentNameEl.value.trim()) {
          alert('Please enter parent / guardian name.');
          ok = false;
        }

        const pe = parentEmailEl.value.trim();
        const re2 = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i;

        if (!re2.test(pe)) {
          alert('Please enter valid parent email.');
          ok = false;
        }
      }

      if (!ok) {
        e.preventDefault();
      }
    });

    window.addEventListener('DOMContentLoaded', () => {
      const successAlert = document.querySelector('.alert.alert-success');
      const errorAlert = document.querySelector('.alert.alert-danger');

      [successAlert, errorAlert].forEach(alert => {
        if (alert) {
          setTimeout(() => {
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
          }, 2000);
        }
      });


      if (successAlert) {
        setTimeout(() => {
          window.location.href = "https://baseavangers.topscripts.in/gagan/sms/baselinelearning/login/login.php";
        }, 2500);
      }
    });

    function toggleParentFields() {
      if (roleEl.value === 'student') {
        parentFields.style.display = 'block';
      } else {
        parentFields.style.display = 'none';

        parentNameEl.value = '';
        parentEmailEl.value = '';
        document.getElementById('parent-phone').value = '';
      }
    }
    roleEl.addEventListener('change', toggleParentFields);
    window.addEventListener('DOMContentLoaded', toggleParentFields);
  </script>

</body>

</html>