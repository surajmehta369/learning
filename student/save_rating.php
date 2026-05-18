<?php
session_name('STUDENT_SESSION');
session_start();
include("../conn.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $course_id = $_POST['course_id'];
    $rating = $_POST['rating_value'];
    $feedback = $_POST['feedback'];

    $stmt = $conn->prepare("INSERT INTO course_ratings (course_id, user_id, rating, feedback) 
                            VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE rating = VALUES(rating), feedback = VALUES(feedback)");
    $stmt->bind_param("iiis", $course_id, $user_id, $rating, $feedback);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save rating.']);
    }
}
