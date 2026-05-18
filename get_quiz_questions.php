<?php
include("conn.php");
header('Content-Type: application/json');

if (isset($_GET['quiz_id'])) {
    $quiz_id = intval($_GET['quiz_id']);
    // Fetch all questions for the specific quiz
    $stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($questions);
}
?>