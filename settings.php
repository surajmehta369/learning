<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('STUDENT_SESSION');
    session_start();
}

include("conn.php");

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header("Location: login/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$success = "";

/* FETCH USER DATA */
$stmt = $conn->prepare("
    SELECT full_name,email,phone,address,subscribe_to_emails,image,password_hash
    FROM signup
    WHERE id=?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

/* AJAX PASSWORD CHECK */
if(isset($_POST['ajax_check_password'])){

    if(password_verify($_POST['current_password'], $user['password_hash'])){

        echo "valid";

    }else{

        echo "invalid";
    }

    exit;
}

/* UPDATE PROFILE */
if (isset($_POST['save_profile'])) {

    $name      = trim($_POST['full_name']);
    $phone     = trim($_POST['phone']);
    $address   = trim($_POST['address']);
    $subscribe = isset($_POST['subscribe']) ? 1 : 0;

    $up = $conn->prepare("
        UPDATE signup
        SET full_name=?, phone=?, address=?, subscribe_to_emails=?
        WHERE id=?
    ");

    $up->bind_param("sssii", $name, $phone, $address, $subscribe, $user_id);

    if ($up->execute()) {

        $success = "Profile updated successfully.";

        /* REFRESH USER DATA */
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    }
}

/* CHANGE PASSWORD */
if (isset($_POST['change_password'])) {

    $current_password = trim($_POST['current_password']);
    $new_password     = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    $errors = [];

    /* VALIDATE CURRENT PASSWORD */

    if (empty($current_password)) {

        $errors['current_password'] = "Current password is required.";

    } elseif (!password_verify($current_password, $user['password_hash'])) {

        $errors['current_password'] = "Current password is incorrect.";
    }

    /* VALIDATE NEW PASSWORD */

    if (empty($new_password)) {

        $errors['new_password'] = "New password is required.";

    } elseif (strlen($new_password) < 6) {

        $errors['new_password'] = "Password must be at least 6 characters.";
    }

    /* VALIDATE CONFIRM PASSWORD */

    if (empty($confirm_password)) {

        $errors['confirm_password'] = "Please confirm your password.";

    } elseif ($new_password !== $confirm_password) {

        $errors['confirm_password'] = "Passwords do not match.";
    }

    /* UPDATE PASSWORD */

    if (empty($errors)) {

        $hash = password_hash($new_password, PASSWORD_DEFAULT);

        $cp = $conn->prepare("
            UPDATE signup
            SET password_hash=?
            WHERE id=?
        ");

        $cp->bind_param("si", $hash, $user_id);

        if ($cp->execute()) {

            $success = "Password updated successfully.";

        } else {

            $errors['new_password'] = "Password update failed.";
        }
    }
}

$initial = strtoupper(substr($user['full_name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Parent Settings</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

:root{
--primary:#6d28d9;
--primary2:#4f46e5;
--bg:#f5f7fb;
--card:#ffffff;
--text:#0f172a;
--muted:#64748b;
--border:#e8ecf4;
--danger:#ef4444;
--success:#10b981;
--shadow:0 15px 35px rgba(20,20,50,.08);
--radius:22px;
}

body{
margin:0;
background:linear-gradient(135deg,#eef2ff,#f8fafc);
font-family:Inter,Segoe UI,Arial,sans-serif;
color:var(--text);
}

.topbar{
height:72px;
background:rgba(255,255,255,.85);
backdrop-filter:blur(10px);
border-bottom:1px solid var(--border);
display:flex;
align-items:center;
justify-content:space-between;
padding:0 28px;
position:sticky;
top:0;
z-index:999;
}

.brand{
display:flex;
align-items:center;
gap:12px;
font-weight:800;
font-size:20px;
}

.logo{
width:42px;
height:42px;
border-radius:14px;
background:linear-gradient(135deg,var(--primary),var(--primary2));
color:#fff;
display:flex;
align-items:center;
justify-content:center;
}

.user-mini{
display:flex;
align-items:center;
gap:12px;
}

.avatar{
width:42px;
height:42px;
border-radius:14px;
background:linear-gradient(135deg,var(--primary),var(--primary2));
color:#fff;
display:flex;
align-items:center;
justify-content:center;
font-weight:700;
}

.wrapper{
max-width:1300px;
margin:auto;
padding:28px;
}

.hero{
background:linear-gradient(135deg,#6d28d9,#4338ca,#2563eb);
padding:32px;
border-radius:26px;
color:#fff;
box-shadow:var(--shadow);
margin-bottom:28px;
}

.hero h1{
font-size:34px;
font-weight:800;
margin-bottom:8px;
}

.hero p{
opacity:.9;
margin:0;
}

.grid{
display:grid;
grid-template-columns:340px 1fr;
gap:24px;
}

.card-ui{
background:var(--card);
border-radius:var(--radius);
box-shadow:var(--shadow);
padding:26px;
border:1px solid var(--border);
}

.profile-box{
text-align:center;
}

.profile-avatar{
width:90px;
height:90px;
border-radius:24px;
margin:auto;
background:linear-gradient(135deg,var(--primary),var(--primary2));
display:flex;
align-items:center;
justify-content:center;
font-size:34px;
font-weight:800;
color:#fff;
margin-bottom:14px;
}

.name{
font-size:22px;
font-weight:800;
}

.email{
color:var(--muted);
font-size:14px;
margin-bottom:22px;
}

.quick-item{
display:flex;
justify-content:space-between;
padding:12px 0;
border-bottom:1px solid var(--border);
font-size:14px;
}

.quick-item:last-child{
border-bottom:none;
}

.section-title{
font-size:20px;
font-weight:800;
margin-bottom:20px;
}

label{
font-weight:600;
font-size:14px;
margin-bottom:7px;
}

.form-control{
border-radius:14px;
padding:12px 14px;
border:1px solid var(--border);
}

.form-control:focus{
border-color:var(--primary);
box-shadow:0 0 0 3px rgba(109,40,217,.12);
}

.btn-main{
background:linear-gradient(135deg,var(--primary),var(--primary2));
border:none;
color:#fff;
padding:12px 22px;
border-radius:14px;
font-weight:700;
}

.btn-main:hover{
opacity:.95;
color:#fff;
transform:translateY(-1px);
}

.btn-logout{
background:#fff0f0;
color:var(--danger);
border:none;
padding:12px 22px;
border-radius:14px;
font-weight:700;
width:100%;
}

.btn-logout:hover{
background:#ffe5e5;
color:var(--danger);
}

.two-col{
display:grid;
grid-template-columns:1fr 1fr;
gap:18px;
}

.switch-box{
padding:16px;
border:1px solid var(--border);
border-radius:16px;
background:#fafbff;
}

.small-muted{
font-size:13px;
color:var(--muted);
}

.validation-message{
font-size:13px;
margin-top:6px;
}

@media(max-width:991px){

.grid{
grid-template-columns:1fr;
}

.two-col{
grid-template-columns:1fr;
}

}

</style>

</head>

<body>

<div class="topbar">

    <div class="brand">

        <div class="logo">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>

        Baseline Learning

    </div>

    <div class="user-mini">

        <div class="avatar">
            <?php echo $initial; ?>
        </div>

        <div>

            <div style="font-weight:700">
                <?php echo htmlspecialchars($user['full_name']); ?>
            </div>

            <div class="small-muted">
                Parent Account
            </div>

        </div>

    </div>

</div>

<div class="wrapper">

<div class="hero">

    <h1>Account Settings</h1>

    <p>
        Manage your profile, security, notifications and account preferences.
    </p>

</div>

<?php if($success){ ?>

<div class="alert alert-success">
    <?php echo $success; ?>
</div>

<?php } ?>

<div class="grid">

<!-- LEFT -->

<div class="card-ui profile-box">

    <div class="profile-avatar">
        <?php echo $initial; ?>
    </div>

    <div class="name">
        <?php echo htmlspecialchars($user['full_name']); ?>
    </div>

    <div class="email">
        <?php echo htmlspecialchars($user['email']); ?>
    </div>

    <div class="quick-item">
        <span>Status</span>
        <strong style="color:#10b981;">Active</strong>
    </div>

    <div class="quick-item">
        <span>Role</span>
        <strong>Parent</strong>
    </div>

    <div class="quick-item">
        <span>Notifications</span>

        <strong>
            <?php echo ($user['subscribe_to_emails']==1)
            ? 'Enabled'
            : 'Disabled'; ?>
        </strong>
    </div>

    <div class="mt-4">

        <a href="logout.php" class="btn btn-logout">

            <i class="fa-solid fa-right-from-bracket me-2"></i>

            Logout

        </a>

    </div>

</div>

<!-- RIGHT -->

<div class="card-ui">

<form method="POST">

<div class="section-title">

    <i class="fa-solid fa-user-gear me-2 text-primary"></i>

    Profile Information

</div>

<div class="two-col">

    <div class="mb-3">

        <label>Full Name</label>

        <input
        type="text"
        name="full_name"
        class="form-control"
        value="<?php echo htmlspecialchars($user['full_name']); ?>"
        required>

    </div>

    <div class="mb-3">

        <label>Email Address</label>

        <input
        type="email"
        class="form-control"
        value="<?php echo htmlspecialchars($user['email']); ?>"
        readonly>

    </div>

    <div class="mb-3">

        <label>Phone Number</label>

        <input
        type="text"
        name="phone"
        class="form-control"
        value="<?php echo htmlspecialchars($user['phone']); ?>">

    </div>

    <div class="mb-3">

        <label>Address</label>

        <input
        type="text"
        name="address"
        class="form-control"
        value="<?php echo htmlspecialchars($user['address']); ?>">

    </div>

</div>

<div class="switch-box mb-4">

    <div class="form-check form-switch">

        <input
        class="form-check-input"
        type="checkbox"
        name="subscribe"
        <?php if($user['subscribe_to_emails']==1) echo "checked"; ?>>

        <label class="form-check-label fw-semibold ms-2">

            Receive Email Notifications

        </label>

    </div>

    <div class="small-muted mt-2">

        Get payment alerts, child progress updates and announcements.

    </div>

</div>

<button type="submit" name="save_profile" class="btn btn-main">

    <i class="fa-solid fa-floppy-disk me-2"></i>

    Save Changes

</button>

</form>

<hr class="my-5">

<form method="POST" id="passwordForm">

<div class="section-title">

    <i class="fa-solid fa-lock me-2 text-primary"></i>

    Security Settings

</div>

<!-- CURRENT PASSWORD -->

<div class="mb-3">

    <label>Current Password</label>

    <input
    type="password"
    id="current_password"
    name="current_password"
    class="form-control"
    required>

    <div id="currentPasswordMsg" class="validation-message"></div>

</div>

<!-- NEW PASSWORD -->

<div class="mb-3">

    <label>New Password</label>

    <input
    type="password"
    id="new_password"
    name="new_password"
    class="form-control"
    required>

    <div id="newPasswordMsg" class="validation-message"></div>

</div>

<!-- CONFIRM PASSWORD -->

<div class="mb-4">

    <label>Confirm New Password</label>

    <input
    type="password"
    id="confirm_password"
    name="confirm_password"
    class="form-control"
    required>

    <div id="confirmPasswordMsg" class="validation-message"></div>

</div>

<div class="d-flex gap-3 flex-wrap">

    <button type="submit"
    name="change_password"
    class="btn btn-main">

        <i class="fa-solid fa-key me-2"></i>

        Update Password

    </button>

    <a href="parent_dashboard.php"
    class="btn btn-outline-secondary px-4 py-2 rounded-4">

        <i class="fa-solid fa-house me-2"></i>

        Back to Home

    </a>

</div>

</form>

</div>

</div>

</div>

<script>

const currentPassword = document.getElementById('current_password');
const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');

const currentPasswordMsg = document.getElementById('currentPasswordMsg');
const newPasswordMsg = document.getElementById('newPasswordMsg');
const confirmPasswordMsg = document.getElementById('confirmPasswordMsg');


/* CURRENT PASSWORD VALIDATION */

currentPassword.addEventListener('keyup', function(){

    if(currentPassword.value.length < 1){

        currentPassword.classList.remove('is-valid');
        currentPassword.classList.remove('is-invalid');

        currentPasswordMsg.innerHTML = "";

        return;
    }

    fetch(window.location.href, {

        method: 'POST',

        headers: {
            'Content-Type':'application/x-www-form-urlencoded'
        },

        body:
        'ajax_check_password=1&current_password='
        + encodeURIComponent(currentPassword.value)

    })

    .then(response => response.text())

    .then(data => {

        if(data.trim() === 'valid'){

            currentPassword.classList.remove('is-invalid');
            currentPassword.classList.add('is-valid');

            currentPasswordMsg.innerHTML =
            "Current password is correct.";

            currentPasswordMsg.className =
            "text-success validation-message";

        }else{

            currentPassword.classList.remove('is-valid');
            currentPassword.classList.add('is-invalid');

            currentPasswordMsg.innerHTML =
            "Current password is incorrect.";

            currentPasswordMsg.className =
            "text-danger validation-message";
        }

    });

});


/* NEW PASSWORD VALIDATION */

newPassword.addEventListener('keyup', function(){

    if(newPassword.value.length < 6){

        newPassword.classList.remove('is-valid');
        newPassword.classList.add('is-invalid');

        newPasswordMsg.innerHTML =
        "Password must be at least 6 characters.";

        newPasswordMsg.className =
        "text-danger validation-message";

    }else{

        newPassword.classList.remove('is-invalid');
        newPassword.classList.add('is-valid');

        newPasswordMsg.innerHTML =
        "Password looks good.";

        newPasswordMsg.className =
        "text-success validation-message";
    }

});


/* CONFIRM PASSWORD VALIDATION */

confirmPassword.addEventListener('keyup', function(){

    if(confirmPassword.value !== newPassword.value){

        confirmPassword.classList.remove('is-valid');
        confirmPassword.classList.add('is-invalid');

        confirmPasswordMsg.innerHTML =
        "Passwords do not match.";

        confirmPasswordMsg.className =
        "text-danger validation-message";

    }else{

        confirmPassword.classList.remove('is-invalid');
        confirmPassword.classList.add('is-valid');

        confirmPasswordMsg.innerHTML =
        "Passwords match.";

        confirmPasswordMsg.className =
        "text-success validation-message";
    }

});

</script>

</body>
</html>

<?php include('assets/half-footer.php'); ?>