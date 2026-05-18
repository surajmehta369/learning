<?php
session_name('STUDENT_SESSION');
session_start();
include("conn.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$upload_path = "uploads/profile_pics/";

$conn->begin_transaction();

try {

    // 🔹 Fetch image
    $stmt_img = $conn->prepare("SELECT image FROM signup WHERE id = ?");
    $stmt_img->bind_param("i", $user_id);
    $stmt_img->execute();
    $stmt_img->bind_result($image_filename);
    $stmt_img->fetch();
    $stmt_img->close();

    // 🔹 Delete user record
    $stmt = $conn->prepare("DELETE FROM signup WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if (!$stmt->execute()) {
        throw new Exception("User delete failed");
    }

    $stmt->close();

    // 🔹 Delete image AFTER DB delete
    if (!empty($image_filename)) {
        $file_to_delete = $upload_path . $image_filename;
        if (file_exists($file_to_delete)) {
            unlink($file_to_delete);
        }
    }

    $conn->commit();

    session_unset();
    session_destroy();

    header("Location: login/login.php?msg=account_deleted");
    exit;

} catch (Exception $e) {

    $conn->rollback();
    header("Location: profile.php?msg=error_deleting");
    exit;
}
