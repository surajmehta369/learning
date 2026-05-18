<?php
session_name('STUDENT_SESSION');
session_start();
include("conn.php");

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id === 0) {
    die("Unauthorized access");
}

$course_id = $_POST['course_id'] ?? 0;
$video_id  = $_POST['video_id'] ?? 0;
$note_text = $_POST['note_text'] ?? '';

if ($course_id == 0 || $video_id == 0 || trim($note_text) === '') {
    die("Missing required data");
}

$stmt = $conn->prepare("
    INSERT INTO video_notes (user_id, course_id, video_id, note_content)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        note_content = VALUES(note_content),
        updated_at = CURRENT_TIMESTAMP
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("iiis", $user_id, $course_id, $video_id, $note_text);

if ($stmt->execute()) {
    echo "Success";
} else {
    echo "Execute failed: " . $stmt->error;
}


