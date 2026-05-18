<?php
session_name('STUDENT_SESSION');
session_start();
include("conn.php");

if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);

    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");

    $conn->query("INSERT INTO notification_views (user_id, last_viewed_at) 
                  VALUES ($user_id, NOW()) 
                  ON DUPLICATE KEY UPDATE last_viewed_at = NOW()");
    
    echo "success";
}
?>
