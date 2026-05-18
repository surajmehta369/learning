<?php
include("conn.php");
session_name('TEACHER_SESSION');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['voice_note'])) {
    $id = intval($_POST['id']);
    $uploadDir = '../uploads/voice_notes/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = "voice_" . $id . "_" . time() . ".webm";
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['voice_note']['tmp_name'], $targetPath)) {
        $sql = "UPDATE chapter_doubts SET answer = '$targetPath', status = 'resolved' WHERE id = $id";
        
        if (mysqli_query($conn, $sql)) {
            echo "success";
        } else {
            echo "Database Error: " . mysqli_error($conn);
        }
    } else {
        echo "Failed to save file.";
    }
}
?>