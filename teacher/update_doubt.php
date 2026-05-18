<?php
session_name('TEACHER_SESSION');
session_start();
include("conn.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $answer = mysqli_real_escape_string($conn, $_POST['answer']);
    $teacher_id = $_SESSION['user_id'] ?? 0; // 

    $sql = "UPDATE chapter_doubts 
            SET answer = '$answer', 
                status = 'answered', 
                teacher_id = '$teacher_id' 
            WHERE id = '$id'";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo mysqli_error($conn);
    }
}
?>