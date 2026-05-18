<?php
session_name('TEACHER_SESSION');
session_start();
include("../conn.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'teacher') {
    exit("Unauthorized");
}

// --- BACKEND AJAX HANDLERS ---
if (isset($_POST['ajax_action'])) {
    if (!isset($_SESSION['user_id'])) {
        exit("session_error");
    }
    $teacher_id = $_SESSION['user_id'];

    if ($_POST['ajax_action'] === 'save_update') {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $message = mysqli_real_escape_string($conn, $_POST['message']);
        $stmt = $conn->prepare("INSERT INTO weekly_updates (teacher_id, title, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $teacher_id, $title, $message);
        if ($stmt->execute()) {
            echo $stmt->insert_id;
        } else {
            echo "db_error";
        }
        $stmt->close();
        exit;
    }

    if ($_POST['ajax_action'] === 'delete_update') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM weekly_updates WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("ii", $id, $teacher_id);
        echo $stmt->execute() ? "success" : "error";
        $stmt->close();
        exit;
    }

    if ($_POST['ajax_action'] === 'edit_update') {
        $id = (int)$_POST['id'];
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $message = mysqli_real_escape_string($conn, $_POST['message']);
        $stmt = $conn->prepare("UPDATE weekly_updates SET title = ?, message = ? WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("ssii", $title, $message, $id, $teacher_id);
        echo $stmt->execute() ? "success" : "error";
        $stmt->close();
        exit;
    }
}

$teacher_id = $_SESSION['user_id'];
$query = "SELECT * FROM weekly_updates WHERE teacher_id = $teacher_id ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Weekly Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #f8f9fa;
        }

        .update-card {
            border: none;
            border-radius: 12px;
            transition: 0.3s;
        }

        .update-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1) !important;
        }

        .sticky-aside {
            position: sticky;
            top: 20px;
        }

        /* Restoring your original input look */
        .form-control {
            border: 1px solid #dee2e6;
            padding: 12px;
            border-radius: 8px;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: none;
        }

        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="row g-4 justify-content-center">

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 sticky-aside">
                    <div class="card-body p-4">
                        <h3 class="mb-4 text-primary"><i class="fas fa-bullhorn me-2"></i> <span id="formTitle">New Update</span></h3>
                        <form id="weeklyUpdateForm">
                            <input type="hidden" id="update_id" value="">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Update Title</label>
                                <input type="text" id="title" class="form-control" placeholder="e.g. Week 4 Motivation" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Message</label>
                                <textarea id="message" class="form-control" rows="5" placeholder="Share goals or tips..." required></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="submitBtn">Post Announcement</button>
                                <button type="button" class="btn btn-light d-none" id="cancelEditBtn">Cancel Edit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">Recent Announcements</h4>
                    <span class="badge bg-white text-primary border rounded-pill py-2 px-3" id="postCount">
                        <?= mysqli_num_rows($result) ?> Posts Total
                    </span>
                </div>

                <div class="row g-3" id="updatesGrid">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <div class="col-md-6 d-flex" id="card-<?= $row['id'] ?>">
                                <div class="card update-card shadow-sm w-100">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="fw-bold text-dark m-0 text-truncate title-display" style="max-width: 80%;"><?= htmlspecialchars($row['title']) ?></h5>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-outline-secondary border-0 edit-btn"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-title="<?= htmlspecialchars($row['title']) ?>"
                                                    data-msg="<?= htmlspecialchars($row['message']) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger border-0 delete-btn" data-id="<?= $row['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-3"><?= date('M d, Y', strtotime($row['created_at'])) ?></p>
                                        <p class="message-display text-secondary mb-0"><?= nl2br(htmlspecialchars($row['message'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5 bg-white rounded shadow-sm no-posts">
                            <p class="text-muted">No announcements posted yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        $(document).ready(function() {

            function resetForm() {
                $('#update_id').val('');
                $('#weeklyUpdateForm')[0].reset();
                $('#formTitle').text('New Update');
                $('#submitBtn').text('Post Announcement').removeClass('btn-success').addClass('btn-primary');
                $('#cancelEditBtn').addClass('d-none');
            }

            // CREATE OR UPDATE
            $('#weeklyUpdateForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#update_id').val();
                let action = id ? 'edit_update' : 'save_update';
                let titleVal = $('#title').val();
                let msgVal = $('#message').val();

                $('#submitBtn').prop('disabled', true).text('Processing...');

                $.ajax({
                    url: 'teacher/add_weekly_update.php',
                    type: 'POST',
                    data: {
                        ajax_action: action,
                        id: id,
                        title: titleVal,
                        message: msgVal
                    },
                    success: function(res) {
                        res = res.trim();
                        $('#submitBtn').prop('disabled', false).text('Post Announcement');

                        if (!isNaN(res) || res === "success") {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Update published.',
                                timer: 1500,
                                showConfirmButton: false
                            });

                            if (action === 'save_update') {
                                location.reload();
                            } else {
                                let card = $('#card-' + id);
                                card.find('.title-display').text(titleVal);
                                card.find('.message-display').html(msgVal.replace(/\n/g, "<br>"));
                                card.find('.edit-btn').data('title', titleVal).data('msg', msgVal);
                                resetForm();
                            }
                        } else {
                            Swal.fire('Error', res, 'error');
                        }
                    }
                });
            });

            // DELETE
            $(document).on('click', '.delete-btn', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This announcement will be removed!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('teacher/add_weekly_update.php', {
                            ajax_action: 'delete_update',
                            id: id
                        }, function(res) {
                            if (res.trim() === "success") {
                                $('#card-' + id).fadeOut(400, function() {
                                    $(this).remove();
                                });
                                Swal.fire('Deleted!', 'Post removed.', 'success');
                            }
                        });
                    }
                });
            });

            // EDIT
            $(document).on('click', '.edit-btn', function() {
                $('#update_id').val($(this).data('id'));
                $('#title').val($(this).data('title')).focus();
                $('#message').val($(this).data('msg'));
                $('#formTitle').text('Edit Update');
                $('#submitBtn').text('Save Changes').removeClass('btn-primary').addClass('btn-success');
                $('#cancelEditBtn').removeClass('d-none');
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            $('#cancelEditBtn').on('click', function() {
                resetForm();
            });
        });
    </script>
</body>

</html>