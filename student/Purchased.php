<?php
session_name('STUDENT_SESSION');
session_start();

include("../conn.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login/login.php");
    exit;
}
$user_id = intval($_SESSION['user_id']);

$hide_nav = true;

$query = "SELECT course_id, course_title, course_price, course_image 
          FROM baseline_User_Cart 
          WHERE user_id = ? AND payment_mode = 'success'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchased Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .course-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #eee;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .course-img {
            height: 180px;
            width: 100%;
            object-fit: cover;
        }

        .price-tag {
            color: #6f42c1;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .btn-submit-review {
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 700;
            border-radius: 50px;
            background-color: #ffc107;
            color: #000;
            border: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-submit-review:hover {
            background-color: #e0a800;
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            color: #000;
        }

        .star-rating .bi-star-fill {
            color: #ffc107;
        }
    </style>
</head>

<body>

    <?php include('../assets/header.php'); ?>

    <div class="wrapper">
        <div class="sidebar">
            <h4><i class="bi bi-mortarboard"></i> Student Dashboard</h4>
            <a href="student/profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
            <a class="active" href="student/Purchased.php"><i class="bi bi-people"></i> My Courses</a>
            <a href="student/my_notes.php"><i class="bi bi-chat-left-text"></i> My Notes</a>
            <a href="student/Meetings.php"><i class="bi bi-calendar-event"></i> Scheduled Meetings</a>
            <a href="student/Request.php"><i class="bi bi-chat-left-text"></i> Requests</a>
        </div>

        <div class="main-content">
            <h3 class="mb-4 fw-bold"><i class="bi bi-bag-check"></i> My Purchased Courses</h3>

            <div class="row g-4">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="course-card h-100">
                                <img src="<?= htmlspecialchars($row['course_image']) ?>" class="course-img" alt="Course Image">

                                <div class="p-4">
                                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($row['course_title']) ?></h5>

                                    <div class="price-tag mb-3">₹<?= number_format((float)$row['course_price'], 2) ?></div>

                                    <div class="d-grid gap-2">
                                        <a href="student/viewmore.php?id=<?= $row['course_id'] ?>" class="btn btn-primary py-2 fw-bold">
                                            <i class="bi bi-play-circle me-1"></i> Start Learning
                                        </a>

                                        <button class="btn btn-submit-review open-rating-modal"
                                            data-course-id="<?= $row['course_id'] ?>"
                                            data-course-name="<?= htmlspecialchars($row['course_title']) ?>">
                                            <i class="bi bi-star-fill"></i> Submit Your Review
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center p-5 bg-white shadow-sm rounded">
                        <i class="bi bi-bag-x text-secondary" style="font-size:45px;"></i>
                        <h4 class="mt-3">No Courses Purchased Yet</h4>
                        <p class="text-muted">Explore our courses and start your learning journey.</p>
                        <a href="ourcourses.php" class="btn btn-success px-4">Browse Courses</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ratingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">Submit Your Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="ratingForm">
                    <div class="modal-body text-center p-4">
                        <p class="text-muted">How would you rate <strong id="modalCourseName" class="text-dark"></strong>?</p>
                        <div class="mb-3">
                            <div class="star-rating fs-1 text-warning">
                                <i class="bi bi-star rating-star" data-value="1" style="cursor:pointer;"></i>
                                <i class="bi bi-star rating-star" data-value="2" style="cursor:pointer;"></i>
                                <i class="bi bi-star rating-star" data-value="3" style="cursor:pointer;"></i>
                                <i class="bi bi-star rating-star" data-value="4" style="cursor:pointer;"></i>
                                <i class="bi bi-star rating-star" data-value="5" style="cursor:pointer;"></i>
                            </div>
                            <input type="hidden" name="rating_value" id="rating_value" value="0">
                            <input type="hidden" name="course_id" id="modalCourseId">
                        </div>
                        <textarea class="form-control" name="feedback" rows="3" placeholder="Share your experience with this course..."></textarea>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" class="btn btn-warning w-100 fw-bold py-2">Post Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('../assets/half-footer.php'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('.open-rating-modal').on('click', function() {
                const id = $(this).data('course-id');
                const name = $(this).data('course-name');
                $('#modalCourseId').val(id);
                $('#modalCourseName').text(name);

                $('.rating-star').removeClass('bi-star-fill').addClass('bi-star');
                $('#rating_value').val(0);
                $('#ratingForm textarea').val('');

                var myModal = new bootstrap.Modal(document.getElementById('ratingModal'));
                myModal.show();
            });

            $('.rating-star').on('click', function() {
                const val = $(this).data('value');
                $('#rating_value').val(val);
                $('.rating-star').each(function() {
                    if ($(this).data('value') <= val) {
                        $(this).removeClass('bi-star').addClass('bi-star-fill');
                    } else {
                        $(this).removeClass('bi-star-fill').addClass('bi-star');
                    }
                });
            });

            $('#ratingForm').on('submit', function(e) {
                e.preventDefault();
                if ($('#rating_value').val() == 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Oops!',
                        text: 'Please select a star rating.'
                    });
                    return;
                }

                $.ajax({
                    url: 'student/save_rating.php',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#ratingModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Thank You!',
                            text: 'Your review has been submitted.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong. Please try again.'
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>