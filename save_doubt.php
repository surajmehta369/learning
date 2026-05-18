<?php
session_name('STUDENT_SESSION');
session_start();

include("conn.php");
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = $_POST['course_id'];
    $video_id  = $_POST['video_id'];
    $question  = mysqli_real_escape_string($conn, $_POST['question']);
    $student_id = $_SESSION['user_id']; 

    $query = "INSERT INTO chapter_doubts (course_id, chapter_id, student_id, question) 
              VALUES ('$course_id', '$video_id', '$student_id', '$question')";

    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>