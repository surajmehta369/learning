<?php
include("conn.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $video_id = $_POST['video_id'];
    $quiz_title = $_POST['quiz_title'];
    $stmt = $conn->prepare("INSERT INTO quizzes (video_id, quiz_title) VALUES (?, ?)");
    $stmt->bind_param("is", $video_id, $quiz_title);
    $stmt->execute();
    $quiz_id = $conn->insert_id;

    $texts = $_POST['q_text'];
    for ($i = 0; $i < count($texts); $i++) {
        $q_text = $texts[$i];
        $a = $_POST['opt_a'][$i];
        $b = $_POST['opt_b'][$i];
        $c = $_POST['opt_c'][$i];
        $d = $_POST['opt_d'][$i];
        $correct = $_POST['correct'][$i];

        $stmtQ = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtQ->bind_param("issssss", $quiz_id, $q_text, $a, $b, $c, $d, $correct);
        $stmtQ->execute();
    }

    echo "<script>alert('Quiz Added Successfully!'); window.location.href='adminpage.php';</script>";
}
?>