<?php
include("conn.php");
session_start();

// Background logic to save progress
if(isset($_POST['video_id']) && isset($_SESSION['user_id'])) {
    $student_id = $_SESSION['user_id'];
    $video_id = intval($_POST['video_id']);
    $status = $_POST['status'];

    $sql = "REPLACE INTO video_progress (student_id, video_id, status, updated_at) 
            VALUES (?, ?, ?, NOW())";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $student_id, $video_id, $status);
    
    if($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    exit; // Stop execution here so no other text is sent
}
?>