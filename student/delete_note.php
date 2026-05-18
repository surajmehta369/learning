<?php
session_name('STUDENT_SESSION');
session_start();
include("../conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $note_id = intval($_POST['id']);
    $user_id = intval($_SESSION['user_id']);

    // Ensure the note belongs to the logged-in user before deleting
    $stmt = $conn->prepare("DELETE FROM video_notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $note_id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Note deleted successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete note."]);
    }
    exit;
}