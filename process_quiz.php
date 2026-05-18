<?php
session_name('STUDENT_SESSION');
session_start();
include("conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Session expired. Please log in again."
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$quiz_id = 59; 
if (!isset($_POST['q_ids']) || empty($_POST['q_ids'])) {
    echo json_encode([
        "success" => false,
        "message" => "No questions were submitted."
    ]);
    exit;
}

$q_ids = $_POST['q_ids'];
$score = 0;
$total_questions = count($q_ids);

$stmt = $conn->prepare("SELECT correct_option FROM quiz_questions WHERE id = ?");

foreach ($q_ids as $qid) {
    if (!isset($_POST['ans_'.$qid])) {
        continue;
    }

    $user_answer = $_POST['ans_'.$qid];

    $stmt->bind_param("i", $qid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (intval($user_answer) === intval($row['correct_option'])) {
            $score++;
        }
    }
}
$stmt->close();

$percentage = ($total_questions > 0) ? ($score / $total_questions) * 100 : 0;

$insert = $conn->prepare("
    INSERT INTO quiz_results (user_id, quiz_id, score, total_questions, percentage, completed_at)
    VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ON DUPLICATE KEY UPDATE
    score = VALUES(score),
    total_questions = VALUES(total_questions),
    percentage = VALUES(percentage),
    completed_at = CURRENT_TIMESTAMP
");

$insert->bind_param("iiiid", $user_id, $quiz_id, $score, $total_questions, $percentage);

if (!$insert->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Database save failed",
        "error" => $conn->error
    ]);
    $insert->close();
    exit;
}

$insert->close();

echo json_encode([
    "success" => true,
    "score" => $score,
    "total" => $total_questions,
    "percentage" => round($percentage, 2)
]);
exit;