<?php
session_name('TEACHER_SESSION');
session_start();
include("conn.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'teacher') {
    exit("Unauthorized");
}

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM chapter_doubts WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Database Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "No ID provided.";
}
?>