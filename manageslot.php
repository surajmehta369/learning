<?php
session_start();
header('Content-Type: application/json');
include("conn.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please login as a student.']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['slot_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$slot_id = intval($_POST['slot_id']);

$slot_check = $conn->prepare("SELECT meeting_date, meeting_time FROM meeting_slots WHERE id = ?");
$slot_check->bind_param("i", $slot_id);
$slot_check->execute();
$slot_result = $slot_check->get_result();

if ($slot_result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Meeting slot not found.']);
    exit;
}

$slot = $slot_result->fetch_assoc();
$meetingDT = new DateTime($slot['meeting_date'] . ' ' . $slot['meeting_time'], new DateTimeZone('Asia/Kolkata'));
$now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));

if ($now > $meetingDT) {
    echo json_encode(['status' => 'error', 'message' => 'This meeting slot has expired.']);
    exit;
}

$check_existing = $conn->prepare("SELECT * FROM meeting_requests WHERE slot_id = ? AND user_id = ?");
$check_existing->bind_param("ii", $slot_id, $user_id);
$check_existing->execute();
$existing_result = $check_existing->get_result();

if ($existing_result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'You have already requested this meeting slot.']);
    exit;
}

$insert_stmt = $conn->prepare("INSERT INTO meeting_requests (slot_id, user_id, status) VALUES (?, ?, 'pending')");
$insert_stmt->bind_param("ii", $slot_id, $user_id);

if ($insert_stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Meeting slot booked successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to book meeting slot: ' . $conn->error]);
}
