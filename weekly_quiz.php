<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('STUDENT_SESSION');
    session_start();
}
include("conn.php");

$query = "SELECT * FROM quiz_questions ORDER BY RAND() LIMIT 5";
$res = $conn->query($query);
$questions = [];
while($row = $res->fetch_assoc()) {
    $questions[] = $row;
}
?>

<div class="modal-header border-0 pb-0 px-4 pt-4">
    <h5 class="modal-title fw-bold text-dark">Weekly Challenge</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="px-4 mt-3">
    <div class="progress" style="height: 6px; border-radius: 10px; background-color: #f0f0f0;">
        <div id="quizProgress" class="progress-bar bg-primary" style="width: 0%; transition: 0.4s;"></div>
    </div>
</div>

<div class="modal-body p-4">
    <form id="quizForm">
        <?php foreach($questions as $index => $q): ?>
            <div class="question-step" id="step-<?= $index + 1 ?>" style="<?= $index === 0 ? '' : 'display:none;' ?>">
                
                <p class="text-primary fw-bold mb-1 small text-uppercase">Question <?= $index + 1 ?> of 5</p>
                <h4 class="fw-bold mb-4 text-dark"><?= htmlspecialchars($q['question_text']) ?></h4>
                
                <input type="hidden" name="q_ids[]" value="<?= $q['id'] ?>">

                <div class="quiz-options d-grid gap-3">
                    <?php 
                    $opts = ['option_a', 'option_b', 'option_c', 'option_d'];
                    foreach($opts as $i => $key): 
                        if(!empty($q[$key])):
                    ?>
                    <label class="option-container">
                        <input type="radio" name="ans_<?= $q['id'] ?>" value="<?= $i + 1 ?>" class="d-none quiz-radio">
                        <div class="option-card py-3 px-4 border-2 d-flex align-items-center">
                            <span class="option-letter fw-bold me-3 text-muted"><?= chr(65 + $i) ?>.</span>
                            <span class="option-text"><?= htmlspecialchars($q[$key]) ?></span>
                            <i class="fas fa-check-circle ms-auto check-icon" style="color: #0d6efd; opacity: 0;"></i>
                        </div>
                    </label>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </form>
</div>

<div class="modal-footer border-0 bg-light d-flex justify-content-between p-3" style="border-radius: 0 0 15px 15px;">
    <button type="button" class="btn btn-link text-muted text-decoration-none fw-bold" id="prevBtn" style="visibility: hidden;">
        <i class="fas fa-chevron-left me-1"></i> Back
    </button>
    
    <button type="button" class="btn btn-primary px-4 fw-bold shadow-sm" id="nextStepBtn">
        Next Question <i class="fas fa-chevron-right ms-1"></i>
    </button>
</div>

<style>
    .option-container { cursor: pointer; display: block; }
    .option-card {
        background: #fff;
        border: 2px solid #f0f0f0;
        border-radius: 12px;
        transition: all 0.2s ease;
    }
    .option-container:hover .option-card {
        border-color: #6c63ff;
        background: #fbfaff;
    }
    .quiz-radio:checked + .option-card {
        border-color: #0d6efd;
        background: #f0f7ff;
        color: #0d6efd;
    }
    .quiz-radio:checked + .option-card .check-icon { opacity: 1 !important; }
</style>

<script>
$(document).ready(function() {
    let currentStep = 1;
    const totalSteps = 5;

    function updateProgress() {
        let percent = ((currentStep - 1) / totalSteps) * 100;
        $('#quizProgress').css('width', percent + '%');
    }

    $('#nextStepBtn').on('click', function() {
        if (!$(`#step-${currentStep} input:checked`).length) {
            Swal.fire({ icon: 'warning', title: 'Wait!', text: 'Please select an answer first.', timer: 2000, showConfirmButton: false });
            return;
        }

        if (currentStep < totalSteps) {
            $(`#step-${currentStep}`).hide();
            currentStep++;
            $(`#step-${currentStep}`).fadeIn();
            $('#prevBtn').css('visibility', 'visible');
            if (currentStep === totalSteps) $(this).text('Finish Quiz');
            updateProgress();
        } else {
            alert("Quiz Completed!");
        }
    });

    $('#prevBtn').on('click', function() {
        if (currentStep > 1) {
            $(`#step-${currentStep}`).hide();
            currentStep--;
            $(`#step-${currentStep}`).fadeIn();
            if (currentStep === 1) $(this).css('visibility', 'hidden');
            $('#nextStepBtn').text('Next Question');
            updateProgress();
        }
    });
});
</script>