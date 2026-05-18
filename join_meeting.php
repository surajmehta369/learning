<?php
session_name('STUDENT_SESSION');
session_start();
require_once 'conn.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login/login.php");
    exit;
}

$slot_id = intval($_GET['slot_id']);

$slot = $conn->prepare("
    SELECT * FROM meeting_slots WHERE id = ?
");
$slot->bind_param("i", $slot_id);
$slot->execute();
$result = $slot->get_result();

if ($result->num_rows === 0) {
    die('Meeting not found');
}

$slot = $result->fetch_assoc();

// ❌ No link
if (empty($slot['meeting_link'])) {
    die('Meeting link not available');
}

$now = date('Y-m-d H:i:s');

// ✅ START TIMER ONLY ON FIRST JOIN
if (empty($slot['link_start_time'])) {

    $start_time  = $now;
    $expiry_time = date(
        'Y-m-d H:i:s',
        strtotime("+{$slot['link_duration']} minutes")
    );

    $update = $conn->prepare("
        UPDATE meeting_slots SET
            link_start_time = ?,
            link_expiry_time = ?,
            status = 'ongoing'
        WHERE id = ?
    ");
    $update->bind_param("ssi", $start_time, $expiry_time, $slot_id);
    $update->execute();
}

// ❌ Expired
if (!empty($slot['link_expiry_time']) && strtotime($slot['link_expiry_time']) < time()) {
    die('Meeting expired');
}

// ✅ Redirect to actual meeting
header("Location: ".$slot['meeting_link']);
exit;
