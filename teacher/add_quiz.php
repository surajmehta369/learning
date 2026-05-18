<?php
include("conn.php");

// 1. Capture the preselected video ID from the "Quiz" button in managevideos.php
$preselected_id = isset($_GET['video_id']) ? intval($_GET['video_id']) : 0;

// 2. Fetch videos with course names (matching your managevideos.php logic)
$videos_result = $conn->query("SELECT cv.id, cv.title, cv.course_id, bc.name as course_name 
                        FROM course_videos cv 
                        LEFT JOIN baseline_courses bc ON cv.course_id = bc.id 
                        ORDER BY bc.name ASC, cv.created_at DESC");

$videos = [];
if ($videos_result) {
    while ($row = $videos_result->fetch_assoc()) {
        $videos[] = $row;
    }
}

$courses = $conn->query("SELECT id, name FROM baseline_courses ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $video_id = intval($_POST['video_id']);
    $db_video_id = ($video_id === 0) ? null : $video_id;

    $course_id = intval($_POST['course_id']);
    $quiz_title = mysqli_real_escape_string($conn, $_POST['quiz_title']);

    mysqli_begin_transaction($conn);
    try {
        $stmt = $conn->prepare("INSERT INTO quizzes (course_id, video_id, quiz_title) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $course_id, $db_video_id, $quiz_title);
        $stmt->execute();
        $quiz_id = $conn->insert_id;

        if (isset($_POST['q_text'])) {
            $stmt_q = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");

            foreach ($_POST['q_text'] as $key => $text) {
                $q_text = $text;
                $a = $_POST['opt_a'][$key];
                $b = $_POST['opt_b'][$key];
                $c = $_POST['opt_c'][$key];
                $d = $_POST['opt_d'][$key];
                $correct = $_POST['correct'][$key]; 

                $stmt_q->bind_param("issssss", $quiz_id, $q_text, $a, $b, $c, $d, $correct);
                $stmt_q->execute();
            }
        }

        mysqli_commit($conn);
        $success = "Quiz created successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Failed to save quiz: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teacher - Create Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .question-block { position: relative; }
        .remove-btn { position: absolute; top: 10px; right: 10px; }
    </style>
</head>

<body class="bg-light p-4">

    <div class="container bg-white p-4 shadow-sm rounded" style="max-width: 850px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-patch-question"></i> Create New Quiz</h2>
            <a href="teacherpage.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Videos
            </a>
        </div>

        <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Where should this quiz appear?</label>
                    <select name="link_data" class="form-select" id="quiz_selector" required onchange="updateIds()">
                        <option value="">-- Select Location --</option>
                        <optgroup label="Final Exam">
                            <?php $courses->data_seek(0); while ($c = $courses->fetch_assoc()): ?>
                                <option value="final-<?= $c['id'] ?>">Final Quiz for: <?= htmlspecialchars($c['name']) ?></option>
                            <?php endwhile; ?>
                        </optgroup>
                        <optgroup label="Chapter Quizzes (Linked to Videos)">
                            <?php foreach ($videos as $v): ?>
                                <option value="video-<?= $v['id'] ?>-<?= $v['course_id'] ?>" <?= ($preselected_id == $v['id']) ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($v['course_name']) ?>] <?= htmlspecialchars($v['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>

                    <input type="hidden" name="video_id" id="hidden_video_id" value="0">
                    <input type="hidden" name="course_id" id="hidden_course_id" value="0">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Quiz Display Title</label>
                    <input type="text" name="quiz_title" class="form-control" placeholder="e.g. Chapter 1 Quiz" required>
                </div>
            </div>

            <hr>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="text-primary mb-0">Questions</h4>
                <button type="button" class="btn btn-sm btn-info text-white shadow-sm" id="ai-gen-btn" onclick="generateAIQuiz()">
                    <i class="bi bi-robot"></i> Generate with AI
                </button>
            </div>

            <div id="questions-area">
                <div class="card p-3 mb-3 question-block border-start border-primary border-4">
                    <h5>Question 1</h5>
                    <input type="text" name="q_text[]" class="form-control mb-3" placeholder="Type your question here..." required>
                    <div class="row g-2">
                        <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text">A</span><input type="text" name="opt_a[]" class="form-control" required></div></div>
                        <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text">B</span><input type="text" name="opt_b[]" class="form-control" required></div></div>
                        <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text">C</span><input type="text" name="opt_c[]" class="form-control" required></div></div>
                        <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text">D</span><input type="text" name="opt_d[]" class="form-control" required></div></div>
                    </div>
                    <div class="mt-2 row align-items-center">
                        <div class="col-auto"><label class="fw-bold text-success small">Correct Answer:</label></div>
                        <div class="col-md-3">
                            <select name="correct[]" class="form-select form-select-sm">
                                <option value="a">Option A</option><option value="b">Option B</option><option value="c">Option C</option><option value="d">Option D</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-primary" onclick="addManualQuestion()">
                    <i class="bi bi-plus-circle"></i> Add Question
                </button>
                <button type="submit" class="btn btn-success px-5 fw-bold">Save Quiz</button>
            </div>
        </form>
    </div>

    <script>
        // Run updateIds on load to catch preselected video_id from URL
        window.onload = updateIds;

        function updateIds() {
            const selector = document.getElementById('quiz_selector').value;
            const videoInput = document.getElementById('hidden_video_id');
            const courseInput = document.getElementById('hidden_course_id');

            if (!selector) return;

            if (selector.startsWith('final-')) {
                videoInput.value = "0";
                courseInput.value = selector.split('-')[1];
            } else if (selector.startsWith('video-')) {
                const parts = selector.split('-');
                videoInput.value = parts[1];
                courseInput.value = parts[2];
            }
        }

        async function generateAIQuiz() {
            const videoSelect = document.getElementById('quiz_selector');
            if (!videoSelect.value) return alert("Please select a video/location first!");
            
            const selectedTitle = videoSelect.options[videoSelect.selectedIndex].text;
            const btn = document.getElementById('ai-gen-btn');
            const originalContent = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> AI is thinking...';

            try {
                const response = await fetch('generate_ai_quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ title: selectedTitle })
                });

                const data = await response.json();

                if (data.error) {
                    alert("AI Error: " + (data.message || data.error));
                } else {
                    // Clear the manual first question and replace with AI questions
                    document.getElementById('questions-area').innerHTML = '';
                    data.forEach((q, index) => {
                        renderQuestionCard(q, index + 1, true);
                    });
                }
            } catch (error) {
                console.error("Fetch Error:", error);
                alert("Could not connect to AI script.");
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }

        function renderQuestionCard(data, count, isAI = false) {
            const area = document.getElementById('questions-area');
            const borderColor = isAI ? 'border-info' : 'border-primary';
            const textColor = isAI ? 'text-info' : '';

            const html = `
                <div class="card p-3 mb-3 question-block border-start ${borderColor} border-4 shadow-sm">
                    <button type="button" class="btn-close remove-btn btn-sm" onclick="this.parentElement.remove()"></button>
                    <h5 class="${textColor}">Question ${count} ${isAI ? '(AI Generated)' : ''}</h5>
                    <input type="text" name="q_text[]" class="form-control mb-3" value="${data.question || ''}" placeholder="Question text" required>
                    <div class="row g-2">
                        <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text">A</span><input type="text" name="opt_a[]" class="form-control" value="${data.a || ''}" required></div></div>
                        <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text">B</span><input type="text" name="opt_b[]" class="form-control" value="${data.b || ''}" required></div></div>
                        <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text">C</span><input type="text" name="opt_c[]" class="form-control" value="${data.c || ''}" required></div></div>
                        <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text">D</span><input type="text" name="opt_d[]" class="form-control" value="${data.d || ''}" required></div></div>
                    </div>
                    <div class="mt-2 row align-items-center">
                        <div class="col-auto"><label class="fw-bold text-success small">Correct Answer:</label></div>
                        <div class="col-md-3">
                            <select name="correct[]" class="form-select form-select-sm">
                                <option value="a" ${data.correct == 'a' ? 'selected' : ''}>Option A</option>
                                <option value="b" ${data.correct == 'b' ? 'selected' : ''}>Option B</option>
                                <option value="c" ${data.correct == 'c' ? 'selected' : ''}>Option C</option>
                                <option value="d" ${data.correct == 'd' ? 'selected' : ''}>Option D</option>
                            </select>
                        </div>
                    </div>
                </div>`;
            area.insertAdjacentHTML('beforeend', html);
        }

        function addManualQuestion() {
            const count = document.querySelectorAll('.question-block').length + 1;
            renderQuestionCard({}, count, false);
        }
    </script>
</body>
</html>