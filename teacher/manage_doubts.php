<?php
session_name('TEACHER_SESSION');
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'teacher') {
    exit("Unauthorized");
}

include("../conn.php");

$sql = "SELECT d.*, 
               s.full_name as student_name, 
               cv.title as video_title 
        FROM chapter_doubts d 
        LEFT JOIN signup s ON d.student_id = s.id 
        LEFT JOIN course_videos cv ON d.chapter_id = cv.id 
        ORDER BY d.created_at DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid p-4">
    <h2 class="mb-4">❓ Student Doubts</h2>

    <div class="row">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-12 mb-3 doubt-item">
                    <div class="card shadow-sm border-<?= $row['status'] == 'pending' ? 'warning' : 'success' ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>
                                <strong><?= htmlspecialchars($row['student_name'] ?? 'Unknown Student') ?></strong>
                                <span class="text-muted">on</span>
                                <span class="badge bg-dark"><?= htmlspecialchars($row['video_title'] ?? 'General') ?></span>
                            </span>
                            <div class="d-flex align-items-center">
                                <span class="badge <?= $row['status'] == 'pending' ? 'bg-warning text-dark' : 'bg-success' ?> me-2">
                                    <?= ucfirst($row['status']) ?>
                                </span>

                                <button class="btn btn-sm btn-outline-danger delete-doubt-btn" data-id="<?= $row['id'] ?>" title="Delete Doubt">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="card-text mb-1"><strong>Question:</strong></p>
                            <blockquote class="blockquote fs-6">
                                "<?= htmlspecialchars($row['question']) ?>"
                            </blockquote>

                            <div class="reply-section mt-3">
                                <?php if ($row['answer']): ?>
                                    <div class="p-2 bg-light border-start border-4 border-primary">
                                        <strong>Your Reply:</strong><br>
                                        <?= htmlspecialchars($row['answer']) ?>
                                    </div>
                                <?php else: ?>
                                    <textarea class="form-control reply-text mb-2" placeholder="Type your answer..."></textarea>
                                    <div class="d-flex align-items-center">
                                        <button class="btn btn-sm btn-primary me-2 submit-reply" data-id="<?= $row['id'] ?>">
                                            💾 Send Reply
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger start-record" data-id="<?= $row['id'] ?>">
                                            🎤 Record Voice
                                        </button>
                                        <span id="timer-<?= $row['id'] ?>" class="ms-2 text-danger small fw-bold"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">No doubts submitted by students yet.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    $(document).on('click', '.delete-doubt-btn', function(e) {
        e.preventDefault();
        let btn = $(this);
        let id = btn.data('id');
        let card = btn.closest('.doubt-item');

        Swal.fire({
            title: 'Are you sure?',
            text: "This doubt will be deleted forever!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: 'teacher/teacher_delete_doubt.php',
                    type: 'POST',
                    data: {
                        id: id
                    },
                    success: function(res) {
                        if (res.trim() === 'success') {
                            Swal.fire('Deleted!', 'Doubt has been removed.', 'success');
                            card.fadeOut(400, function() {
                                $(this).remove();
                            });
                        } else {
                            Swal.fire('Error!', res, 'error');
                            btn.prop('disabled', false).html('🗑️');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Could not connect to the server.', 'error');
                        btn.prop('disabled', false).html('🗑️');
                    }
                });
            }
        });
    });

    $(document).on('click', '.submit-reply', function() {
        let btn = $(this);
        let id = btn.data('id');
        let reply = btn.closest('.reply-section').find('.reply-text').val();

        if (!reply || !reply.trim()) {
            Swal.fire('Required', 'Please type a reply.', 'warning');
            return;
        }

        btn.prop('disabled', true).text('Sending...');
        $.post('teacher/update_doubt.php', {
            id: id,
            answer: reply
        }, function(res) {
            if (res.trim() === 'success') {
                location.reload();
            } else {
                Swal.fire('Error', res, 'error');
                btn.prop('disabled', false).text('💾 Send Reply');
            }
        });
    });

    $(document).ready(function() {
        let mediaRecorder;
        let audioChunks = [];
        let recordingTimer;

        $(document).on('click', '.start-record', function() {
            let btn = $(this);
            let id = btn.data('id');
            let timerSpan = $('#timer-' + id);

            if (btn.hasClass('is-recording')) {
                stopMedia();
                return;
            }

            navigator.mediaDevices.getUserMedia({
                audio: true
            }).then(stream => {
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.start();
                btn.removeClass('btn-outline-danger').addClass('btn-danger').html('🛑 Stop & Send').addClass('is-recording');

                let seconds = 0;
                timerSpan.text("🔴 0s / 60s");
                recordingTimer = setInterval(() => {
                    seconds++;
                    timerSpan.text(`🔴 ${seconds}s / 60s`);
                    if (seconds >= 60) {
                        Swal.fire('Limit Reached', 'Maximum 1-minute recording reached.', 'info');
                        stopMedia();
                    }
                }, 1000);

                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);

                mediaRecorder.onstop = () => {
                    clearInterval(recordingTimer);
                    const audioBlob = new Blob(audioChunks, {
                        type: 'audio/webm'
                    });
                    uploadVoiceReply(audioBlob, id, btn);
                };

            }).catch(err => Swal.fire('Mic Error', 'Could not access microphone.', 'error'));
        });

        function stopMedia() {
            if (mediaRecorder && mediaRecorder.state !== "inactive") {
                mediaRecorder.stop();
                $('.start-record').removeClass('is-recording').removeClass('btn-danger').addClass('btn-outline-danger').html('🎤 Record Voice');
            }
        }

        function uploadVoiceReply(blob, id, btn) {
            let formData = new FormData();
            formData.append('voice_note', blob, `reply_${id}.webm`);
            formData.append('id', id);

            Swal.fire({
                title: 'Sending...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'teacher/update_doubt_voice.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.trim() === 'success') {
                        Swal.fire('Sent!', 'Voice note sent successfully.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res, 'error');
                    }
                },
                error: () => Swal.fire('Error', 'Server connection failed.', 'error')
            });
        }
    });
</script>