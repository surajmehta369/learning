<?php
include("conn.php");
session_name('STUDENT_SESSION'); 
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo "Error: Session expired.";
        exit;
    }

    $user_id = intval($_SESSION['user_id']);
    $quiz_id = intval($_POST['quiz_id']);
    $score = intval($_POST['score']);
    $total = intval($_POST['total']);
    $percentage = floatval($_POST['percentage']);

    $checkUser = $conn->prepare("SELECT id FROM signup WHERE id = ?");
    $checkUser->bind_param("i", $user_id);
    $checkUser->execute();
    
    if ($checkUser->get_result()->num_rows === 0) {
        http_response_code(400);
        echo "Error: User ID $user_id not found in signup table.";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO quiz_results (user_id, quiz_id, score, total_questions, percentage, completed_at) 
                            VALUES (?, ?, ?, ?, ?, NOW()) 
                            ON DUPLICATE KEY UPDATE 
                            score = VALUES(score), 
                            percentage = VALUES(percentage), 
                            completed_at = NOW()");
    
    $stmt->bind_param("iiiid", $user_id, $quiz_id, $score, $total, $percentage);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Database Error: " . $stmt->error;
    }
}
?>