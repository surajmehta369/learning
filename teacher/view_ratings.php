<?php
session_name('TEACHER_SESSION');
session_start();
include("../conn.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'teacher') {
    exit("Unauthorized");
}

$summaryQuery = "
    SELECT 
        c.id,
        c.name AS course_name,
        COUNT(r.id) AS total_ratings,
        ROUND(AVG(r.rating),2) AS avg_rating
    FROM baseline_courses c
    LEFT JOIN course_ratings r ON c.id = r.course_id
    GROUP BY c.id
    ORDER BY avg_rating DESC, total_ratings DESC
";

$summaryResult = $conn->query($summaryQuery);

$topCourseQuery = "
    SELECT c.name, ROUND(AVG(r.rating),2) as top_avg
    FROM baseline_courses c
    JOIN course_ratings r ON c.id = r.course_id
    GROUP BY c.id
    ORDER BY top_avg DESC LIMIT 1
";
$topCourse = $conn->query($topCourseQuery)->fetch_assoc();

$feedbackQuery = "
    SELECT 
        r.rating,
        r.feedback,
        r.created_at,
        c.name AS course_name,
        s.full_name AS student_name
    FROM course_ratings r
    JOIN baseline_courses c ON r.course_id = c.id
    JOIN signup s ON r.user_id = s.id
    ORDER BY r.created_at DESC
";

$feedbackResult = $conn->query($feedbackQuery);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Course Rating Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
            font-family: 'Inter', sans-serif;
        }

        .course-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            padding: 20px;
            transition: 0.3s ease;
            border-left: 6px solid;
            height: 100%;
        }

        .course-card:hover {
            transform: translateY(-4px);
        }

        .poor {
            border-color: #dc3545;
        }

        .average {
            border-color: #ffc107;
        }

        .good {
            border-color: #0d6efd;
        }

        .excellent {
            border-color: #198754;
        }

        .norating {
            border-color: #6c757d;
        }

        .rating-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .badge-poor {
            background: #dc3545;
        }

        .badge-average {
            background: #ffc107;
            color: black;
        }

        .badge-good {
            background: #0d6efd;
        }

        .badge-excellent {
            background: #198754;
        }

        .badge-norating {
            background: #6c757d;
        }

        .feedback-card {
            background: white;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #dee2e6;
        }

        .star {
            color: #f39c12;
            font-size: 18px;
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>📊 Course Rating Analysis</h2>

            <?php if ($topCourse): ?>
                <div class="card shadow-sm border-0" style="border-left: 5px solid #ffc107 !important; min-width: 300px;">
                    <div class="card-body py-2 px-3 d-flex align-items-center">
                        <div class="me-3 text-warning"><i class="fas fa-crown fa-2x"></i></div>
                        <div>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Highest Rated Course</small>
                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($topCourse['name']) ?></h6>
                            <small class="text-success fw-bold"><?= $topCourse['top_avg'] ?> / 5.0</small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <?php while ($row = $summaryResult->fetch_assoc()):
                $avg = $row['avg_rating'];
                $category = "No Ratings";
                $class = "norating";
                $badge = "badge-norating";

                if ($avg > 0 && $avg <= 2) {
                    $category = "Poor";
                    $class = "poor";
                    $badge = "badge-poor";
                } elseif ($avg > 2 && $avg <= 3.5) {
                    $category = "Average";
                    $class = "average";
                    $badge = "badge-average";
                } elseif ($avg > 3.5 && $avg <= 4.5) {
                    $category = "Good";
                    $class = "good";
                    $badge = "badge-good";
                } elseif ($avg > 4.5) {
                    $category = "Excellent";
                    $class = "excellent";
                    $badge = "badge-excellent";
                }
            ?>

                <div class="col-md-4 mb-4">
                    <div class="course-card <?= $class ?>">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($row['course_name']) ?></h5>
                            <?php if ($avg >= 4.5): ?>
                                <i class="fas fa-award text-success"></i>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <p class="mb-1 text-muted small">Total Reviews: <span class="text-dark fw-bold"><?= $row['total_ratings'] ?></span></p>
                            <p class="mb-1 text-muted small">Avg Rating: <span class="text-dark fw-bold"><?= $avg ? $avg : '0' ?> / 5.0</span></p>
                        </div>
                        <span class="rating-badge <?= $badge ?>">
                            <?= $category ?>
                        </span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <hr class="my-5">

        <h3 class="mb-4 text-dark fw-bold">📝 Recent Student Reviews</h3>

        <?php if ($feedbackResult->num_rows > 0): ?>
            <div class="row">
                <div class="col-lg-10">
                    <?php while ($fb = $feedbackResult->fetch_assoc()): ?>
                        <div class="feedback-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="fw-bold text-primary"><?= htmlspecialchars($fb['student_name']) ?></span>
                                    <span class="text-muted mx-1">•</span>
                                    <span class="badge bg-light text-dark border">Course: <?= htmlspecialchars($fb['course_name']) ?></span>
                                </div>
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt me-1"></i><?= date("d M Y", strtotime($fb['created_at'])) ?>
                                </small>
                            </div>

                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star small"><?= ($i <= $fb['rating']) ? '★' : '<span style="color:#ccc;">★</span>' ?></span>
                                <?php endfor; ?>
                            </div>

                            <p class="text-secondary mb-0" style="font-style: italic;">"<?= nl2br(htmlspecialchars($fb['feedback'])) ?>"</p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5 bg-white rounded shadow-sm">
                <i class="fas fa-comment-slash fa-3x text-light mb-3"></i>
                <p class="text-muted">No reviews submitted yet.</p>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>