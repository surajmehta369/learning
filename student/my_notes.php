<?php
session_name('STUDENT_SESSION');
session_start();

if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    $_SESSION['user_role'] !== 'student'
) {
    header("Location: ../login/login.php");
    exit;
}

include("../conn.php");

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("
    SELECT 
        vn.id,             -- Add this line
        vn.note_content,
        vn.updated_at,
        bc.name AS course_name,
        cv.title AS video_title
    FROM video_notes vn
    JOIN baseline_courses bc ON bc.id = vn.course_id
    JOIN course_videos cv ON cv.id = vn.video_id
    WHERE vn.user_id = ?
    ORDER BY vn.updated_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Notes</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <?php include('../assets/header.php'); ?>

    <div class="wrapper">


        <div class="sidebar">
            <h4><i class="bi bi-mortarboard"></i> Student Dashboard</h4>
            <a href="student/profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
            <a href="student/Purchased.php"><i class="bi bi-people"></i> My Courses</a>
            <a class="active" href="student/my_notes.php"><i class="bi bi-chat-left-text"></i> My Notes</a>
            <a href="student/Meetings.php"><i class="bi bi-calendar-event"></i> Scheduled Meetings</a>
            <a href="student/Request.php"><i class="bi bi-chat-left-text"></i> Requests</a>
        </div>

        <div class="main-content">

            <h3 class="mb-4 fw-bold">📝 My Video Notes</h3>

            <?php if ($result->num_rows === 0): ?>
                <div class="alert alert-info">
                    You haven’t saved any notes yet.
                </div>
            <?php else: ?>

                <div class="row g-4">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">

                                    <h6 class="fw-bold mb-1">
                                        📘 <?= htmlspecialchars($row['course_name']); ?>
                                    </h6>

                                    <p class="text-muted small mb-2">
                                        🎥 <?= htmlspecialchars($row['video_title']); ?>
                                    </p>

                                    <div class="border rounded p-3 bg-light">
                                        <?= nl2br(htmlspecialchars($row['note_content'])); ?>
                                    </div>



                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <button class="btn btn-sm btn-outline-danger delete-note" data-id="<?= $row['id']; ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                        <small class="text-muted">
                                            Last updated: <?= date("d M Y, h:i A", strtotime($row['updated_at'])); ?>
                                        </small>
                                    </div>




                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <?php include('../assets/half-footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).on("click", ".delete-note", function() {
            var noteId = $(this).data("id");
            var noteCard = $(this).closest(".col-md-6");

            Swal.fire({
                title: "Delete Note?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "student/delete_note.php",
                        method: "POST",
                        data: {
                            id: noteId
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            if (data.status === "success") {
                                noteCard.fadeOut(500, function() {
                                    $(this).remove();
                                });
                                Swal.fire("Deleted!", data.message, "success");
                            } else {
                                Swal.fire("Error", data.message, "error");
                            }
                        },
                        error: function() {
                            Swal.fire("Error", "Server error occurred.", "error");
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>