<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_name('STUDENT_SESSION');
session_start();
include("conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $doubt_id = $_POST['doubt_id'];
    $student_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        DELETE FROM chapter_doubts 
        WHERE id = ? AND student_id = ?
    ");

    $stmt->bind_param("ii", $doubt_id, $student_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
}
?>
