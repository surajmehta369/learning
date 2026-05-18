<?php
session_name('STUDENT_SESSION');
session_start();
include("conn.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

$user_id   = intval($_SESSION['user_id']);
$full_name = trim($_POST['full_name']);
$email     = trim($_POST['email']);

$upload_dir = "uploads/profile_pics/";
$new_image_name = null;

/* =====================
   VALIDATION
===================== */
if (empty($full_name) || strlen($full_name) < 3) {
    $_SESSION['validation_error'] = "Full name must be at least 3 characters long.";
    header("Location: profile.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['validation_error'] = "Please enter a valid email address.";
    header("Location: profile.php");
    exit;
}

/* =====================
   IMAGE UPLOAD
===================== */
if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_tmp  = $_FILES['image']['tmp_name'];
    $file_ext  = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed   = ['jpg', 'jpeg', 'png', 'webp'];

    $mime = mime_content_type($file_tmp);
    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($file_ext, $allowed) || !in_array($mime, $allowed_mime)) {
        $_SESSION['validation_error'] = "Invalid image file type.";
        header("Location: profile.php");
        exit;
    }

    if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
        $_SESSION['validation_error'] = "Image size must be under 2MB.";
        header("Location: profile.php");
        exit;
    }

    $new_image_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
    $target_file = $upload_dir . $new_image_name;

    move_uploaded_file($file_tmp, $target_file);
}

/* =====================
   DATABASE UPDATE
===================== */
$conn->begin_transaction();

try {

    // Fetch old image
    $old_image = null;
    $old = $conn->prepare("SELECT image FROM signup WHERE id = ?");
    $old->bind_param("i", $user_id);
    $old->execute();
    $old->bind_result($old_image);
    $old->fetch();
    $old->close();

    if ($new_image_name) {
        $stmt = $conn->prepare("UPDATE signup SET full_name=?, email=?, image=? WHERE id=?");
        $stmt->bind_param("sssi", $full_name, $email, $new_image_name, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE signup SET full_name=?, email=? WHERE id=?");
        $stmt->bind_param("ssi", $full_name, $email, $user_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Update failed");
    }

    $stmt->close();

    // Delete old image AFTER successful update
    if ($new_image_name && $old_image && file_exists($upload_dir . $old_image)) {
        unlink($upload_dir . $old_image);
    }

    $conn->commit();
    $_SESSION['update_success'] = true;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['update_error'] = true;
}

header("Location: profile.php");
exit;
