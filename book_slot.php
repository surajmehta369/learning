<?php
session_name('STUDENT_SESSION');  
session_start();
header('Content-Type: application/json');
include("conn.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$slot_id = intval($_POST['slot_id'] ?? 0);

if ($slot_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid slot ID.']);
    exit;
}

$stmt = $conn->prepare("SELECT meeting_date, meeting_time, status, teacher_id FROM meeting_slots WHERE id = ?");
$stmt->bind_param("i", $slot_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Slot not found.']);
    exit;
}

$slot = $res->fetch_assoc();
$meetingDT = new DateTime($slot['meeting_date'] . ' ' . $slot['meeting_time'], new DateTimeZone('Asia/Kolkata'));
$now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));

if ($slot['status'] !== 'upcoming' || $meetingDT < $now) {
    echo json_encode(['status' => 'error', 'message' => 'Slot expired or unavailable.']);
    exit;
}

$check = $conn->prepare("SELECT * FROM baseline_request WHERE user_id = ? AND teacher_id = ? AND request_date = ? AND request_time = ?");
$request_date = $slot['meeting_date'];
$request_time = $slot['meeting_time'];
$teacher_id = intval($slot['teacher_id']);
$check->bind_param("iiss", $user_id, $teacher_id, $request_date, $request_time);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'You already requested this slot.']);
    exit;
}


$teacher_stmt = $conn->prepare("SELECT full_name FROM signup WHERE id = ? AND role = 'teacher'");
$teacher_stmt->bind_param("i", $teacher_id);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();

if ($teacher_result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Teacher not found.']);
    exit;
}

$teacher = $teacher_result->fetch_assoc();
$teacher_name = $teacher['full_name'];

$message = "Booked slot on " . $request_date . " at " . date('h:i A', strtotime($request_time));
$insert = $conn->prepare("INSERT INTO baseline_request (user_id, teacher_id, teacher_name, request_date, request_time, message, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
$insert->bind_param("iissss", $user_id, $teacher_id, $teacher_name, $request_date, $request_time, $message);

if ($insert->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Meeting slot booked successfully and sent for approval.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to book slot: ' . $conn->error]);
}
