<?php
session_name('STUDENT_SESSION');
session_start();
include("conn.php"); 

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$video_id = isset($_POST['video_id']) ? intval($_POST['video_id']) : null;
$course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : null;

if (!$video_id || !$course_id) {
    //echo json_encode(['status' => 'error', 'message' => 'Missing ID data']);
    exit;
}

// CHANGE: Use video_progress table
$check = $conn->prepare("SELECT id FROM video_progress WHERE user_id = ? AND video_id = ?");
$check->bind_param("ii", $user_id, $video_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // CHANGE: Use video_progress table
    $delete = $conn->prepare("DELETE FROM video_progress WHERE user_id = ? AND video_id = ?");
    $delete->bind_param("ii", $user_id, $video_id);
    $delete->execute();
    echo json_encode(['status' => 'success', 'action' => 'uncompleted', 'success' => true]);
} else {
    // CHANGE: Use video_progress table
    $insert = $conn->prepare("INSERT INTO video_progress (user_id, course_id, video_id) VALUES (?, ?, ?)");
    $insert->bind_param("iii", $user_id, $course_id, $video_id);
    
    if ($insert->execute()) {
        echo json_encode(['status' => 'success', 'action' => 'completed', 'success' => true]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error']);
    }
}
?>