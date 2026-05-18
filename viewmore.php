<?php
session_name('STUDENT_SESSION');
session_start();

include("conn.php");

$needs_login = false;
$user_id = 0;
$u_id = 0;

if (!isset($_SESSION['user_id'])) {
    $needs_login = true;
} else {
    $user_id = intval($_SESSION['user_id']);
    $u_id = $user_id;
}

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$courseId = $course_id;

if ($course_id === 0) {
    die("Invalid course ID.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please login first']);
        exit;
    }

    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'add') {
        $c_id = intval($_POST['course_id']);
        $title = mysqli_real_escape_string($conn, $_POST['course_title']);
        $price = floatval($_POST['course_price']);
        $img = mysqli_real_escape_string($conn, $_POST['course_image'] ?? '');

        $sql = "INSERT IGNORE INTO baseline_User_Cart (user_id, course_id, course_title, course_price, course_image)
                VALUES ($user_id, $c_id, '$title', $price, '$img')";
        mysqli_query($conn, $sql);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'remove') {
        $cart_id = intval($_POST['cart_id']);
        $sql = "DELETE FROM baseline_User_Cart WHERE id=$cart_id AND user_id=$user_id";
        mysqli_query($conn, $sql);
        echo json_encode(['success' => true]);
        exit;
    }
}


$percentage = 0;
$completed_videos = [];
$videos = [];
$similar_courses = [];
$course = null;
$has_paid = false;
$already_in_cart = false;
$payment_status = '';
$rating = '';
$review_count = '';
$enrolled_count = '';
$duration_hours = '';
$lecture_count = '';

if (!$needs_login) {
    if (!isset($_GET['id'])) {
        die("Invalid course ID.");
    }
    $course_id = intval($_GET['id']);

    $course_result = mysqli_query($conn, "SELECT * FROM baseline_courses WHERE id=$course_id");
    if (mysqli_num_rows($course_result) == 0) {
        die("Course not found");
    }
    $course = mysqli_fetch_assoc($course_result);

    $res = $conn->query("SELECT video_id FROM video_progress WHERE user_id = $u_id");
    while ($row = $res->fetch_assoc()) {
        $completed_videos[] = $row['video_id'];
    }

    $stmt_total = $conn->prepare("SELECT COUNT(id) as total FROM course_videos WHERE course_id = ?");
    $stmt_total->bind_param("i", $course_id);
    $stmt_total->execute();
    $total_videos = $stmt_total->get_result()->fetch_assoc()['total'];

    $stmt_done = $conn->prepare("
        SELECT COUNT(vp.id) as completed 
        FROM video_progress vp
        JOIN course_videos cv ON vp.video_id = cv.id
        WHERE vp.user_id = ? AND cv.course_id = ?
    ");
    $stmt_done->bind_param("ii", $u_id, $course_id);
    $stmt_done->execute();
    $completed_count = $stmt_done->get_result()->fetch_assoc()['completed'];

    $percentage = ($total_videos > 0) ? round(($completed_count / $total_videos) * 100) : 0;

    $check_cart = mysqli_query($conn, "SELECT * FROM baseline_User_Cart WHERE user_id=$user_id AND course_id=$course_id");
    $cart_entry = mysqli_fetch_assoc($check_cart);
    $already_in_cart = $cart_entry ? true : false;
    $payment_status = $cart_entry['payment_mode'] ?? '';
    $has_paid = strtolower($payment_status) === 'success';

    $videos = [];
    $videos_result = mysqli_query($conn, "SELECT cv.*, s.full_name AS uploader_name, q.id AS quiz_id FROM course_videos cv 
                        LEFT JOIN signup s ON cv.uploader_id = s.id 
                        LEFT JOIN quizzes q ON cv.id = q.video_id WHERE cv.course_id=$course_id 
                        ORDER BY cv.id ASC");

    while ($row = mysqli_fetch_assoc($videos_result)) {
        $v_id = $row['id'];
        $check_res = mysqli_query($conn, "SELECT id FROM video_progress WHERE user_id = $user_id AND video_id = $v_id");

        $row['is_finished'] = (mysqli_num_rows($check_res) > 0);

        $videos[] = $row;
    }

    $final_quiz = null;
    if ($percentage >= 100) {
        $stmt_final = $conn->prepare("SELECT id, quiz_title FROM quizzes WHERE course_id = ? AND (video_id = 0 OR video_id IS NULL) ORDER BY id DESC LIMIT 1");
        $stmt_final->bind_param("i", $course_id);
        $stmt_final->execute();
        $final_quiz = $stmt_final->get_result()->fetch_assoc();
    }

    $cat = mysqli_real_escape_string($conn, $course['category']);
    $similar_courses_result = mysqli_query($conn, "
        SELECT * FROM baseline_courses 
        WHERE category = '$cat' 
        AND id != $course_id 
        LIMIT 3
    ");
    while ($row = mysqli_fetch_assoc($similar_courses_result)) {
        $similar_courses[] = $row;
    }

    $enrolled_count = rand(100, 5000);
    $rating = rand(35, 50) / 10;
    $review_count = rand(100, 5000);
    $duration_hours = rand(5, 40);
    $lecture_count = count($videos);


    $existing_note = '';

    $note_stmt = $conn->prepare("SELECT note_content FROM video_notes WHERE user_id = ? AND course_id = ? AND video_id = ? LIMIT 1");
    $note_stmt->bind_param("iii", $user_id, $course_id, $video_id);
    $note_stmt->execute();
    $note_stmt->bind_result($existing_note);
    $note_stmt->fetch();
    $note_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $needs_login ? 'Course Details' : htmlspecialchars($course['name']) . ' | Learn & Grow' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        :root {
            --primary-color: #5624d0;
            --primary-light: #f0ebff;
            --secondary-color: #1c1d1f;
            --accent-color: #ff6b6b;
            --success-color: #1e7b1e;
            --light-bg: #f7f9fa;
            --border-color: #d1d7dc;
            --text-dark: #1c1d1f;
            --text-gray: #6a6f73;
            --text-light: #fff;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            color: var(--text-dark);
            background-color: #fff;
            line-height: 1.4;
        }

        .course-hero {
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            color: white;
            padding: 40px 0 30px;
            position: relative;
            overflow: hidden;
        }

        .course-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><path d="M500,100 L0,0 L1000,0 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: 100% 100%;
        }

        .course-breadcrumb {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 15px;
        }

        .course-breadcrumb a {
            color: #fff;
            text-decoration: none;
            transition: opacity 0.3s;
        }

        .course-breadcrumb a:hover {
            opacity: 0.9;
            text-decoration: underline;
        }

        .course-title {
            font-weight: 800;
            font-size: 2.2rem;
            line-height: 1.2;
            margin-bottom: 15px;
        }

        .course-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 800px;
            margin-bottom: 20px;
        }

        .course-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .meta-item i {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .course-content-wrapper {
            margin-top: 40px;
        }

        .sidebar-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            position: sticky;
            top: 100px;
            overflow: hidden;
        }

        .course-image-side {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .pricing-section {
            padding: 25px;
            border-bottom: 1px solid var(--border-color);
        }

        .course-price {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .original-price {
            font-size: 1.1rem;
            color: var(--text-gray);
            text-decoration: line-through;
            margin-bottom: 10px;
        }

        .discount-badge {
            background: var(--accent-color);
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }

        .action-btn {
            width: 100%;
            padding: 14px;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 10px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), #7c4dff);
            color: white;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #4a1fb8, #6a3dff);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(86, 36, 208, 0.3);
        }

        .btn-outline-custom {
            background: white;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline-custom:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .btn-success-custom {
            background: var(--success-color);
            color: white;
        }

        .btn-success-custom:hover {
            background: #1a691a;
        }

        .includes-list {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .includes-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .includes-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .includes-list i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .content-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
        }

        .section-title {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary-color);
        }

        /* Video Cards */
        .video-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: white;
        }

        .video-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .video-card.unlocked {
            border-left: 4px solid var(--success-color);
        }

        .video-card.locked {
            opacity: 0.8;
            border-left: 4px solid #ccc;
        }

        .video-header {
            padding: 20px;
            background: var(--light-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .video-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
            color: var(--text-dark);
        }

        .video-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-unlocked {
            background: #e7f7e7;
            color: var(--success-color);
        }

        .badge-locked {
            background: #f5f5f5;
            color: #666;
        }

        .video-content {
            padding: 20px;
        }

        .video-description {
            color: var(--text-gray);
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .video-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--text-gray);
        }

        .video-player-wrapper {
            margin-top: 20px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }

        .video-player-wrapper.locked {
            filter: blur(8px);
            pointer-events: none;
            user-select: none;
        }

        .video-lock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 30px;
            border-radius: 8px;
        }

        .lock-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #fff;
        }

        .lock-text {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .lock-subtext {
            opacity: 0.9;
            max-width: 400px;
        }

        .similar-course-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            background: white;
        }

        .similar-course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .similar-course-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .similar-course-body {
            padding: 20px;
        }

        .similar-course-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 8px;
            color: var(--text-dark);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .similar-course-price {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1.1rem;
            margin-top: 10px;
        }

        .login-modal .modal-content {
            border-radius: 15px;
            overflow: hidden;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .login-modal-header {
            background: linear-gradient(135deg, var(--primary-color), #7c4dff);
            color: white;
            padding: 30px 30px 20px;
            text-align: center;
            border-bottom: none;
        }

        .login-modal-body {
            padding: 30px;
            text-align: center;
        }

        .login-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .login-btn {
            background: linear-gradient(135deg, var(--primary-color), #7c4dff);
            border: none;
            padding: 12px 40px;
            font-weight: 600;
            border-radius: 8px;
            color: white;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(86, 36, 208, 0.3);
            color: white;
        }

        .progress-section {
            background: linear-gradient(135deg, var(--primary-light), #e8f4ff);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .progress-title {
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), #7c4dff);
            border-radius: 4px;
            width:
                <?= $has_paid ? '100%' : ($already_in_cart ? '30%' : '0%') ?>;
            transition: width 1s ease;
        }

        @media (max-width: 992px) {
            .course-title {
                font-size: 1.8rem;
            }

            .sidebar-card {
                position: static;
                margin-bottom: 30px;
            }
        }

        @media (max-width: 768px) {
            .course-hero {
                padding: 30px 0 20px;
            }

            .course-title {
                font-size: 1.6rem;
            }

            .content-section {
                padding: 20px;
            }

            .course-meta {
                gap: 15px;
            }
        }

        .text-primary-custom {
            color: var(--primary-color);
        }

        .bg-light-custom {
            background: var(--light-bg);
        }

        .shadow-soft {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .hover-lift {
            transition: transform 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-3px);
        }

        .content-blur {
            filter: blur(8px);
            transition: filter 0.3s ease;
            pointer-events: none;
            user-select: none;
        }

        .modal-backdrop.show {
            opacity: 0.8;
        }
    </style>
</head>

<body>
    <?php include("assets/header.php"); ?>

    <?php if ($needs_login): ?>
        <div class="modal fade show" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-modal="true"
            role="dialog" style="display: block; background-color: rgba(0,0,0,0.9);">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content login-modal">
                    <div class="login-modal-header">
                        <i class="fas fa-graduation-cap fa-2x mb-3"></i>
                        <h4 class="modal-title">Welcome to Course Details</h4>
                    </div>
                    <div class="login-modal-body">
                        <div class="login-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3 class="mb-3">Login Required</h3>
                        <p class="text-muted mb-4">Please login to access course content, videos, and add courses to your
                            cart.</p>
                        <div class="d-flex flex-column gap-3">
                            <a href="login/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                                class="btn login-btn">
                                <i class="fas fa-sign-in-alt"></i> Login to Continue
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home"></i> Back to Home
                            </a>
                        </div>
                        <div class="mt-4 pt-3 border-top">
                            <p class="text-muted small mb-0">Don't have an account?
                                <a href="signup.php" class="text-primary-custom fw-bold">Sign up here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="filter: blur(8px); pointer-events: none; user-select: none; position: relative;">
            <div class="course-hero">
                <div class="container">
                </div>
            </div>
            <div class="container course-content-wrapper py-5">
            </div>
        </div>

        <script>
            document.getElementById('loginModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    e.stopPropagation();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    window.location.href = 'index.php';
                }
            });
        </script>

    <?php else: ?>
        <div id="main-content">
            <div class="course-hero">
                <div class="container">
                    <div class="course-breadcrumb">
                        <a href="ourcourses.php">Courses</a>
                        <span class="mx-2">/</span>
                        <a href="ourcourses.php?category=<?= urlencode($course['category']) ?>">
                            <?= htmlspecialchars($course['category']) ?>
                        </a>
                        <span class="mx-2">/</span>
                        <span><?= htmlspecialchars($course['name']) ?></span>
                    </div>

                    <h1 class="course-title"><?= htmlspecialchars($course['name']) ?></h1>
                    <p class="course-subtitle"><?= nl2br(htmlspecialchars(substr($course['description'], 0, 200))) . '...' ?>
                    </p>

                    <div class="course-meta">
                        <div class="meta-item">
                            <i class="fas fa-star text-warning"></i>
                            <span><?= number_format($rating, 1) ?> (<?= number_format($review_count) ?> reviews)</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <span><?= number_format($enrolled_count) ?> students enrolled</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?= $duration_hours ?> total hours</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-play-circle"></i>
                            <span><?= $lecture_count ?> lectures</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container course-content-wrapper">
                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <?php if ($already_in_cart || $has_paid): ?>
                            <div class="progress-section">
                                <div class="progress-title">
                                    <span>Your Progress</span>
                                    <span><?= $percentage ?>% Complete</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $percentage ?>%;"></div>
                                </div>
                                <div class="mt-3">
                                    <?php if ($has_paid): ?>
                                        <?php if ($percentage >= 100): ?>
                                            <span class="text-success d-block mb-3">
                                                <i class="fas fa-trophy"></i> Course Completed! You've mastered this content.
                                            </span>

                                            <?php if ($final_quiz): ?>
                                                <div class="alert alert-success text-center border-2 shadow-sm mt-3">
                                                    <h5 class="fw-bold text-success mb-2">🎉 Final Assessment Ready!</h5>
                                                    <p class="small mb-3">Complete the final quiz to validate your learning.</p>

                                                    <button type="button"
                                                        class="btn btn-success btn-lg px-4 fw-bold"
                                                        onclick="openQuizModal(<?= intval($final_quiz['id']) ?>)">
                                                        <i class="fas fa-clipboard-check"></i> Start Final Quiz: <?= htmlspecialchars($final_quiz['quiz_title']) ?>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-success"><i class="fas fa-check-circle"></i> Keep going!</span>
                                        <?php endif; ?>
                                    <?php elseif ($already_in_cart): ?>
                                        <span class="text-primary-custom"><i class="fas fa-shopping-cart"></i> Course in cart - Complete payment to unlock all videos</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="content-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i> Course Description
                            </h3>
                            <div class="course-description-content">
                                <?= nl2br(htmlspecialchars($course['description'])) ?>
                            </div>


                            <?php if (!empty($course['learning_objectives'])): ?>
                                <div class="mt-4">
                                    <h5 class="fw-bold mb-3">What you'll learn</h5>
                                    <div class="row">
                                        <?php
                                        $objectives = explode("\n", $course['learning_objectives']);
                                        $half = ceil(count($objectives) / 2);
                                        ?>
                                        <div class="col-md-6">
                                            <ul class="list-unstyled">
                                                <?php for ($i = 0; $i < $half; $i++): ?>
                                                    <?php if (!empty(trim($objectives[$i]))): ?>
                                                        <li class="mb-2">
                                                            <i class="fas fa-check text-success me-2"></i>
                                                            <?= htmlspecialchars(trim($objectives[$i])) ?>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="list-unstyled">
                                                <?php if (isset($objectives) && is_array($objectives)): ?>
                                                    <?php for ($i = $half; $i < count($objectives); $i++): ?>
                                                        <?php
                                                        $objective = trim($objectives[$i] ?? '');
                                                        if ($objective !== ''):
                                                        ?>
                                                            <li class="mb-2">
                                                                <i class="fas fa-check text-success me-2"></i>
                                                                <?= htmlspecialchars($objective) ?>
                                                            </li>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($videos)): ?>
                            <div class="content-section">
                                <h3 class="section-title">
                                    <i class="fas fa-play-circle"></i> Course Curriculum
                                </h3>
                                <p class="text-muted mb-4"><?= $lecture_count ?> lectures</p>

                                <div class="accordion" id="curriculumAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#curriculumCollapse">
                                                <strong>All Course Videos (<?= count($videos) ?>)</strong>
                                            </button>
                                        </h2>
                                        <div id="curriculumCollapse" class="accordion-collapse collapse show"
                                            data-bs-parent="#curriculumAccordion">
                                            <div class="accordion-body p-0">
                                                <?php foreach ($videos as $index => $video): ?>

                                                    <?php
                                                    $existing_doubts = [];

                                                    $stmt_doubts = $conn->prepare("
                                                        SELECT * FROM chapter_doubts 
                                                        WHERE student_id = ? 
                                                        AND chapter_id = ?
                                                        ORDER BY created_at DESC
                                                    ");

                                                    $stmt_doubts->bind_param("ii", $user_id, $video['id']);
                                                    $stmt_doubts->execute();
                                                    $result_doubts = $stmt_doubts->get_result();

                                                    while ($row_doubt = $result_doubts->fetch_assoc()) {
                                                        $existing_doubts[] = $row_doubt;
                                                    }

                                                    $stmt_doubts->close();


                                                    $existing_note = '';

                                                    $note_stmt = $conn->prepare("
                                                        SELECT note_content
                                                        FROM video_notes
                                                        WHERE user_id = ?
                                                        AND course_id = ?
                                                        AND video_id = ?
                                                    ");

                                                    $note_stmt->bind_param("iii", $user_id, $course_id, $video['id']);
                                                    $note_stmt->execute();
                                                    $note_stmt->bind_result($existing_note);
                                                    $note_stmt->fetch();
                                                    $note_stmt->close();

                                                    $video_index = $index + 1;
                                                    $is_first_video = $video_index === 1;
                                                    $is_unlocked = $has_paid || $is_first_video;
                                                    ?>
                                                    <div class="video-card <?= $is_unlocked ? 'unlocked' : 'locked' ?>">
                                                        <div class="video-header">
                                                            <h6 class="video-title">
                                                                <i class="fas fa-video me-2 text-primary-custom"></i>
                                                                <?= htmlspecialchars($video['title']) ?>
                                                            </h6>
                                                            <span
                                                                class="video-badge <?= $is_unlocked ? 'badge-unlocked' : 'badge-locked' ?>">
                                                                <?php if ($is_unlocked): ?>
                                                                    <i class="fas fa-unlock me-1"></i> Unlocked
                                                                <?php else: ?>
                                                                    <i class="fas fa-lock me-1"></i> Locked
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                        <div class="video-content">
                                                            <p class="video-description">
                                                                <?= nl2br(htmlspecialchars($video['description'])) ?>
                                                            </p>
                                                            <div class="video-meta">
                                                                <span>
                                                                    <i class="fas fa-user-circle me-1"></i>
                                                                    <?= htmlspecialchars($video['uploader_name'] ?? 'Instructor') ?>
                                                                </span>
                                                                <span>
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    <?= rand(5, 60) ?> mins
                                                                </span>
                                                            </div>

                                                            <?php if (!empty($video['video_path'])): ?>
                                                                <div class="video-player-wrapper <?= $is_unlocked ? '' : 'locked' ?> mt-3">
                                                                    <?php if ($video['type'] == 'upload'): ?>
                                                                        <video controls width="100%" class="rounded" style="max-height: 400px;">
                                                                            <source src="<?= htmlspecialchars($video['video_path']) ?>"
                                                                                type="video/mp4">
                                                                            Your browser does not support the video tag.
                                                                        </video>
                                                                    <?php else: ?>
                                                                        <iframe width="100%" height="400"
                                                                            src="<?= htmlspecialchars($video['video_path']) ?>" frameborder="0"
                                                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                                            allowfullscreen class="rounded">
                                                                        </iframe>
                                                                    <?php endif; ?>

                                                                    <?php if (!$is_unlocked): ?>
                                                                        <div class="video-lock-overlay">
                                                                            <div class="lock-icon">
                                                                                <i class="fas fa-lock"></i>
                                                                            </div>
                                                                            <div class="lock-text">This content is locked</div>
                                                                            <div class="lock-subtext">
                                                                                Complete your purchase to access all course videos and
                                                                                materials.
                                                                            </div>
                                                                            <?php if (!$already_in_cart): ?>
                                                                                <a href="#pricing" class="btn btn-primary mt-3">
                                                                                    <i class="fas fa-shopping-cart"></i> Purchase Course
                                                                                </a>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <?php
                                                                $is_done = in_array($video['id'], $completed_videos);
                                                                ?>
                                                                <div class="progress-section mt-3">
                                                                    <?php if ($is_unlocked): ?>
                                                                        <div class="d-flex align-items-center gap-3 flex-wrap">

                                                                            <button type="button"
                                                                                class="btn btn-sm mark-complete-btn <?= $is_done ? 'btn-success' : 'btn-outline-primary' ?>"
                                                                                data-video="<?= $video['id'] ?>"
                                                                                data-course="<?= $course_id ?>"
                                                                                onclick="toggleProgress(this)">
                                                                                <i class="fas <?= $is_done ? 'fa-check-circle' : 'fa-check' ?>"></i>
                                                                                <span class="btn-text"><?= $is_done ? 'Chapter Finished' : 'Mark as Completed' ?></span>
                                                                            </button>

                                                                            <?php if (!empty($video['pdf_path'])): ?>
                                                                                <?php if ($is_done): ?>
                                                                                    <a href="<?= htmlspecialchars($video['pdf_path']) ?>"
                                                                                        target="_blank"
                                                                                        class="btn btn-sm btn-danger animate__animated animate__fadeIn">
                                                                                        <i class="fas fa-file-pdf"></i> PDF
                                                                                    </a>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted small">
                                                                                        <i class="fas fa-lock"></i> PDF Locked
                                                                                    </span>
                                                                                <?php endif; ?>
                                                                            <?php endif; ?>

                                                                            <?php if (!empty($video['quiz_id'])): ?>
                                                                                <?php if ($is_done): ?>
                                                                                    <button class="btn btn-sm btn-primary mt-2" onclick="openQuizModal(<?= $video['quiz_id'] ?>)">
                                                                                        <i class="fas fa-question-circle"></i> Take Quiz
                                                                                    </button>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted small">
                                                                                        <i class="fas fa-lock"></i> Quiz Locked
                                                                                    </span>
                                                                                <?php endif; ?>
                                                                            <?php endif; ?>


                                                                            <div class="container my-4">
                                                                                <div class="card shadow-sm border-0">
                                                                                    <div class="card-body">
                                                                                        <h3 class="mb-3">📝 My Video Notes</h3>

                                                                                        <input type="hidden" class="note_course_id" value="<?= $course_id ?>">
                                                                                        <input type="hidden" class="note_video_id" value="<?= $video['id'] ?>">

                                                                                        <div class="note-view d-none">
                                                                                            <div class="border rounded p-3 bg-light note-content-text"></div>
                                                                                            <button class="btn btn-sm btn-warning mt-3 edit-note-btn">
                                                                                                ✏️ Edit Notes
                                                                                            </button>
                                                                                        </div>

                                                                                        <div class="note-edit">

                                                                                            <textarea class="form-control video-note-text"
                                                                                                rows="6"
                                                                                                data-video-id="<?= $video['id'] ?>">
                                                                                            <?= htmlspecialchars($existing_note ?? '') ?>
                                                                                            </textarea>


                                                                                            <button
                                                                                                class="btn btn-primary mt-3 save-note-btn"
                                                                                                data-course-id="<?= $course_id ?>"
                                                                                                data-video-id="<?= $video['id'] ?>">
                                                                                                💾 Save Notes
                                                                                            </button>
                                                                                        </div>

                                                                                        <span class="note_status fw-bold"></span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <div class="container my-4">
                                                                                <div class="card shadow-sm border-0">
                                                                                    <div class="card-body">
                                                                                        <h3 class="mb-3">❓ Ask a Doubt</h3>

                                                                                        <div class="mb-4">
                                                                                            <textarea class="form-control doubt-question-text"
                                                                                                placeholder="Type your question about this chapter here..."
                                                                                                rows="3"></textarea>
                                                                                            <button class="btn btn-primary mt-2 ask-doubt-btn"
                                                                                                data-course-id="<?= $course_id ?>"
                                                                                                data-video-id="<?= $video['id'] ?>">
                                                                                                🚀 Post Question
                                                                                            </button>
                                                                                            <div class="doubt-status mt-2"></div>
                                                                                        </div>

                                                                                        <hr>

                                                                                        <h5 class="mb-3">Recent Discussions</h5>
                                                                                        <div class="doubts-list">
                                                                                            <?php foreach ($existing_doubts as $doubt): ?>
                                                                                                <div class="d-flex justify-content-between align-items-start">

                                                                                                    <div>
                                                                                                        <span class="badge bg-info">Question</span>
                                                                                                    </div>

                                                                                                    <div class="text-end">
                                                                                                        <small class="text-muted">
                                                                                                            <?= date("Y-m-d H:i:s", strtotime($doubt['created_at'])) ?>
                                                                                                        </small>

                                                                                                        <button class="btn btn-sm btn-danger delete-doubt-btn mt-1"
                                                                                                            data-doubt-id="<?= $doubt['id'] ?>">
                                                                                                            🗑 Delete
                                                                                                        </button>
                                                                                                    </div>

                                                                                                </div>

                                                                                                <div class="border rounded p-3 mb-3 bg-light">
                                                                                                    <div class="d-flex justify-content-between">
                                                                                                        <span class="badge bg-info text-dark">Question</span>
                                                                                                        <small class="text-muted"><?= $doubt['created_at'] ?></small>
                                                                                                    </div>
                                                                                                    <p class="mt-2 fw-bold"><?= htmlspecialchars($doubt['question']) ?></p>

                                                                                                    <?php if ($doubt['answer']): ?>
                                                                                                        <div class="ms-4 p-2 border-start border-primary border-4 bg-white">
                                                                                                            <span class="badge bg-success">Teacher's Answer</span>

                                                                                                            <?php
                                                                                                            if (strpos($doubt['answer'], 'uploads/voice_notes/') !== false): ?>
                                                                                                                <div class="mt-2 p-2 bg-light rounded shadow-sm">
                                                                                                                    <label class="small text-muted d-block mb-1">
                                                                                                                        <i class="bi bi-headphones"></i> Voice Explanation:
                                                                                                                    </label>
                                                                                                                    <audio controls class="w-100" style="height: 35px;">
                                                                                                                        <source src="<?= $doubt['answer'] ?>" type="audio/webm">
                                                                                                                        Your browser does not support the audio element.
                                                                                                                    </audio>
                                                                                                                </div>
                                                                                                            <?php else: ?>
                                                                                                                <p class="mb-0 mt-1"><?= htmlspecialchars($doubt['answer']) ?></p>
                                                                                                            <?php endif; ?>
                                                                                                        </div>
                                                                                                    <?php else: ?>
                                                                                                        <span class="text-muted small italic">⏳ Waiting for teacher's response...</span>
                                                                                                    <?php endif; ?>
                                                                                                </div>
                                                                                            <?php endforeach; ?>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>


                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>

                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($similar_courses)): ?>
                            <div class="content-section">
                                <h3 class="section-title">
                                    <i class="fas fa-layer-group"></i> Similar Courses
                                </h3>
                                <p class="text-muted mb-4">Students also viewed these related courses</p>

                                <div class="row g-4">
                                    <?php foreach ($similar_courses as $similar): ?>
                                        <div class="col-md-4">
                                            <a href="viewmore.php?id=<?= $similar['id'] ?>" class="text-decoration-none">
                                                <div class="similar-course-card hover-lift">
                                                    <img src="<?= $similar['image'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80' ?>"
                                                        alt="<?= htmlspecialchars($similar['name']) ?>" class="similar-course-img">
                                                    <div class="similar-course-body">
                                                        <h6 class="similar-course-title"><?= htmlspecialchars($similar['name']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($similar['category']) ?></small>
                                                        <div class="similar-course-price">
                                                            ₹<?= number_format($similar['price'], 2) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-4">
                        <div class="sidebar-card">
                            <img src="<?= $course['image'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80' ?>"
                                alt="<?= htmlspecialchars($course['name']) ?>" class="course-image-side">

                            <div class="pricing-section">
                                <div class="course-price">₹<?= number_format($course['price'], 2) ?></div>
                                <div class="original-price">₹<?= number_format($course['price'] * 1.3, 2) ?></div>
                                <div class="discount-badge">Save 30% today</div>

                                <?php if ($has_paid): ?>
                                    <button class="action-btn btn-success-custom" disabled>
                                        <i class="fas fa-check-circle"></i> Purchased
                                    </button>
                                    <a href="Purchased.php" class="action-btn btn-outline-custom text-decoration-none">
                                        <i class="fas fa-play-circle"></i> Go to My Courses
                                    </a>
                                <?php elseif ($already_in_cart): ?>
                                    <button class="action-btn btn-success-custom" disabled>
                                        <i class="fas fa-shopping-cart"></i> Added to Cart
                                    </button>
                                    <a href="cart.php" class="action-btn btn-primary-custom text-decoration-none">
                                        <i class="fas fa-credit-card"></i> Complete Purchase
                                    </a>
                                <?php else: ?>
                                    <button id="addToCartBtn" class="action-btn btn-primary-custom">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                    <button class="action-btn btn-outline-custom" style="display: none" ;>
                                        <i class="fas fa-heart"></i> Add to Wishlist
                                    </button>
                                <?php endif; ?>

                                <div class="text-center mt-3">
                                    <small class="text-muted">30-Day Money-Back Guarantee</small>
                                </div>
                            </div>

                            <div class="includes-list">
                                <h6 class="fw-bold mb-3">This course includes:</h6>
                                <ul>
                                    <li>
                                        <i class="fas fa-play-circle"></i>
                                        <span><?= count($videos) ?> hours on-demand video</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-file-download"></i>
                                        <span>Downloadable resources</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-mobile-alt"></i>
                                        <span>Access on mobile and TV</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-infinity"></i>
                                        <span>Full lifetime access</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-certificate"></i>
                                        <span>Certificate of completion</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="p-3 text-center border-top">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Secure payment • 100% satisfaction guaranteed
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="quizModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold">Course Quiz</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="progress mb-4" style="height: 8px;">
                            <div id="m-progress" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                        </div>

                        <div id="m-question-box">
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <small class="text-muted" id="m-counter"></small>
                            <button id="m-next-btn" class="btn btn-primary px-4 fw-bold" onclick="handleModalNext()" disabled>Next Question</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            function escapeHTML(str) {
                if (!str) return "";
                return str.replace(/[&<>"']/g, m => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [m]));
            }

            function updateProgressBar(change) {
                const progressBar = document.getElementById('courseProgressBar');
                const progressText = document.getElementById('progressText');

                if (!progressBar) return;

                const totalVideos = parseInt("<?= isset($total_videos) ? $total_videos : 0 ?>") || 0;
                if (totalVideos === 0) return;

                let currentPercent = parseFloat(progressBar.getAttribute('aria-valuenow')) || 0;
                let currentCompletedCount = Math.round((currentPercent / 100) * totalVideos);

                let newCompletedCount = currentCompletedCount + change;
                newCompletedCount = Math.max(0, Math.min(newCompletedCount, totalVideos));

                let newPercent = Math.round((newCompletedCount / totalVideos) * 100);

                progressBar.style.width = newPercent + '%';
                progressBar.setAttribute('aria-valuenow', newPercent);

                if (progressText) {
                    progressText.innerText = newPercent + '% Completed';
                }
            }

            window.toggleProgress = function(btnElement) {
                const vID = btnElement.getAttribute('data-video');
                const cID = btnElement.getAttribute('data-course');

                const formData = new FormData();
                formData.append('video_id', vID);
                formData.append('course_id', cID);

                btnElement.disabled = true;

                fetch('update_progress.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success || data.status === 'success') {
                            location.reload();
                        } else {
                            btnElement.disabled = false;
                            console.error("Database error:", data.message);
                        }
                    })
                    .catch(error => {
                        btnElement.disabled = false;
                        console.error("Network error:", error);
                    });
            };



            let mQuestions = [];
            let mCurrentIdx = 0;
            let mScore = 0;
            let mActiveQuizId = null;
            let myQuizModal;

            function openQuizModal(quizId) {
                mActiveQuizId = quizId;
                mCurrentIdx = 0;
                mScore = 0;

                fetch(`get_quiz_questions.php?quiz_id=${quizId}`)
                    .then(res => res.json())
                    .then(data => {
                        mQuestions = data;
                        if (mQuestions.length > 0) {
                            renderModalQuestion();

                            const main = document.getElementById('main-content');
                            if (main) main.classList.add('content-blur');

                            if (!myQuizModal) {
                                myQuizModal = new bootstrap.Modal(document.getElementById('quizModal'));
                            }
                            myQuizModal.show();
                        } else {
                            alert("This quiz has no questions yet.");
                        }
                    })
                    .catch(err => console.error("Fetch error:", err));
            }

            function renderModalQuestion() {
                const q = mQuestions[mCurrentIdx];
                const nextBtn = document.getElementById('m-next-btn');

                nextBtn.disabled = true;
                nextBtn.style.display = 'block';
                nextBtn.innerText = (mCurrentIdx === mQuestions.length - 1) ? "Finish Quiz" : "Next Question";

                document.getElementById('m-progress').style.width = ((mCurrentIdx / mQuestions.length) * 100) + "%";
                document.getElementById('m-counter').style.display = 'block';
                document.getElementById('m-counter').innerText = `Question ${mCurrentIdx + 1} of ${mQuestions.length}`;

                let html = `<h4 class="mb-4">${escapeHTML(q.question_text)}</h4>`;
                const opts = {
                    'a': q.option_a,
                    'b': q.option_b,
                    'c': q.option_c,
                    'd': q.option_d
                };

                for (let key in opts) {
                    html += `
            <button class="btn btn-outline-secondary w-100 mb-2 p-3 text-start m-opt" 
                    onclick="selOpt(this, '${key}', '${q.correct_option}')" data-key="${key}">
                <span class="me-2 fw-bold text-uppercase">${key}.</span> ${escapeHTML(opts[key])}
            </button>`;
                }
                document.getElementById('m-question-box').innerHTML = html;
            }

            function selOpt(el, selectedKey, correctKey) {
                const allOpts = document.querySelectorAll('.m-opt');

                allOpts.forEach(btn => {
                    btn.onclick = null;
                    btn.style.cursor = 'default';
                });

                if (selectedKey === correctKey) {
                    el.classList.replace('btn-outline-secondary', 'btn-success');
                    el.classList.add('text-white');
                    mScore++;
                } else {
                    el.classList.replace('btn-outline-secondary', 'btn-danger');
                    el.classList.add('text-white');

                    allOpts.forEach(btn => {
                        if (btn.getAttribute('data-key') === correctKey) {
                            btn.classList.replace('btn-outline-secondary', 'btn-success');
                            btn.classList.add('text-white');
                        }
                    });
                }

                document.getElementById('m-next-btn').disabled = false;
            }

            function handleModalNext() {
                if (mCurrentIdx < mQuestions.length - 1) {
                    mCurrentIdx++;
                    renderModalQuestion();
                } else {
                    showResults();
                }
            }

            function showResults() {
                const total = mQuestions.length;
                const percentage = Math.round((mScore / total) * 100);
                const resultBox = document.getElementById('m-question-box');

                document.getElementById('m-next-btn').style.display = 'none';
                document.getElementById('m-counter').style.display = 'none';
                document.getElementById('m-progress').style.width = "100%";

                let certificateUI = "";
                if (percentage >= 75) {
                    let courseTitle = "Laravel";

                    certificateUI = `
    <div class="mt-4">
        <a href="generate_certificate.php?score=${percentage}&course=${encodeURIComponent(courseTitle)}" target="_blank" class="btn btn-primary btn-lg w-100 shadow-sm mb-2">
            <i class="fas fa-award me-2"></i>Download Certificate
        </a>
    </div>`;
                } else {
                    certificateUI = `
    <div class="alert alert-warning mt-4 py-2" style="font-size: 0.9rem;">
        Score at least 75% to earn a certificate.
    </div>`;
                }

                resultBox.innerHTML = `
        <div class="text-center py-3">
            <div class="mb-3">
                <i class="fas fa-trophy text-warning" style="font-size: 4rem;"></i>
            </div>
            <h2 class="display-4 fw-bold text-primary mb-1">${percentage}%</h2>
            <p class="lead text-muted">You got ${mScore} out of ${total} correct!</p>
            
            ${certificateUI}
            
            <button type="button" 
                    class="btn btn-success btn-lg w-100 mt-2 fw-bold"
                    onclick="saveAndFinish(${percentage})">
                Save & Exit
            </button>
        </div>
    `;
            }

            function saveAndFinish(pct) {
                console.log("Saving quiz...");


                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

                const params = `quiz_id=${mActiveQuizId}&score=${mScore}&total=${mQuestions.length}&percentage=${pct}`;

                fetch('save_score.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: params
                    })
                    .then(response => {

                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error(text)
                            });
                        }
                        return response.text();
                    })
                    .then(data => {
                        console.log("Server response:", data);
                        if (data.trim() === "success") {

                            if (myQuizModal) myQuizModal.hide();


                            window.location.reload();
                        } else {
                            alert("Unexpected response: " + data);
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        }
                    })
                    .catch(err => {
                        console.error("Fetch error:", err);
                        alert("Save failed: " + err.message);
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
            }
            document.addEventListener('DOMContentLoaded', function() {
                const modalElement = document.getElementById('quizModal');
                if (modalElement) {
                    modalElement.addEventListener('hidden.bs.modal', function() {
                        document.getElementById('m-question-box').innerHTML = '';
                        const main = document.getElementById('main-content');
                        if (main) main.classList.remove('content-blur');
                    });
                }

                const addBtn = document.getElementById('addToCartBtn');
                if (addBtn) {
                    addBtn.addEventListener('click', function() {
                        const originalText = addBtn.innerHTML;
                        addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                        addBtn.disabled = true;

                        fetch('cart.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'action=add&course_id=<?= $course['id'] ?>&course_title=<?= urlencode($course['name']) ?>&course_price=<?= $course['price'] ?>&course_image=<?= urlencode($course['image']) ?>'
                        }).then(res => res.json()).then(data => {
                            if (data.success) {
                                addBtn.innerHTML = '<i class="fas fa-check-circle"></i> Added to Cart';
                                addBtn.className = 'action-btn btn-success-custom';

                                const successAlert = document.createElement('div');
                                successAlert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
                                successAlert.style.zIndex = '1050';
                                successAlert.innerHTML = `
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Success!</strong> Course added to your cart.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                `;
                                document.body.appendChild(successAlert);

                                setTimeout(() => {
                                    successAlert.remove();
                                }, 3000);

                                const userEmail = '<?= isset($_SESSION['user_email']) ? $_SESSION['user_email'] : "guest"; ?>';
                                const cartKey = 'cart_' + userEmail;
                                let cart = JSON.parse(localStorage.getItem(cartKey)) || [];

                                const courseId = <?= $course['id'] ?>;
                                if (!cart.some(item => item.id === courseId)) {
                                    cart.push({
                                        id: courseId,
                                        title: '<?= addslashes($course['name']) ?>',
                                        price: <?= $course['price'] ?>,
                                        image: '<?= addslashes($course['image']) ?>'
                                    });
                                    localStorage.setItem(cartKey, JSON.stringify(cart));
                                }

                                window.dispatchEvent(new Event('cartUpdated'));

                                setTimeout(() => {
                                    window.location.href = 'cart.php';
                                }, 1000);

                            } else {
                                addBtn.innerHTML = originalText;
                                addBtn.disabled = false;

                                const errorAlert = document.createElement('div');
                                errorAlert.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3';
                                errorAlert.style.zIndex = '1050';
                                errorAlert.innerHTML = `
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>Error!</strong> ${data.message || 'Failed to add course to cart.'}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                `;
                                document.body.appendChild(errorAlert);

                                setTimeout(() => {
                                    errorAlert.remove();
                                }, 3000);
                            }
                        }).catch(error => {
                            addBtn.innerHTML = originalText;
                            addBtn.disabled = false;
                            console.error('Error:', error);
                        });
                    });
                }

                document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                    anchor.addEventListener('click', function(e) {
                        const targetId = this.getAttribute('href');
                        if (targetId.startsWith('#')) {
                            e.preventDefault();
                            const targetElement = document.querySelector(targetId);
                            if (targetElement) {
                                window.scrollTo({
                                    top: targetElement.offsetTop - 100,
                                    behavior: 'smooth'
                                });
                            }
                        }
                    });
                });

                document.querySelectorAll('.video-card').forEach(card => {
                    card.addEventListener('mouseenter', function() {
                        if (!this.classList.contains('locked')) {
                            this.style.transform = 'translateY(-5px)';
                        }
                    });

                    card.addEventListener('mouseleave', function() {
                        if (!this.classList.contains('locked')) {
                            this.style.transform = 'translateY(0)';
                        }
                    });
                });
            });

            document.querySelectorAll('.mark-complete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const videoId = this.getAttribute('data-video-id');
                    const courseId = "<?= $course_id ?>";

                    fetch('update_progress.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `video_id=${videoId}&course_id=${courseId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert(data.message || 'Error updating progress');
                            }
                        });
                });
            });

            $(document).on('click', '.save-note-btn', function() {

                const wrapper = $(this).closest('.card-body');

                const noteText = wrapper.find('.video-note-text').val();
                const courseId = $(this).data('course-id');
                const videoId = $(this).data('video-id');

                if (!noteText.trim()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Empty Note',
                        text: 'Please write something before saving'
                    });
                    return;
                }

                $.ajax({
                    url: 'save_note.php',
                    type: 'POST',
                    data: {
                        course_id: courseId,
                        video_id: videoId,
                        note_text: noteText
                    },
                    success: function(response) {

                        if (response.toLowerCase().includes('success')) {

                            wrapper.find('.note-content-text').text(noteText);
                            wrapper.find('.note-edit').addClass('d-none');
                            wrapper.find('.note-view').removeClass('d-none');

                            Swal.fire({
                                icon: 'success',
                                title: 'Saved!',
                                text: 'Your note has been saved',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response
                            });
                        }
                    }
                });
            });


            $(document).on('click', '.edit-note-btn', function() {

                const wrapper = $(this).closest('.card-body');

                const existingText = wrapper.find('.note-content-text').text();

                wrapper.find('.video-note-text').val(existingText);
                wrapper.find('.note-view').addClass('d-none');
                wrapper.find('.note-edit').removeClass('d-none');
            });

            $(document).on('click', '.ask-doubt-btn', function() {

                var btn = $(this);
                var videoId = btn.data('video-id');
                var courseId = btn.data('course-id');

                var questionTextarea = btn.closest('.card-body')
                    .find('.doubt-question-text');

                var question = questionTextarea.val().trim();

                if (question === '') {
                    alert('Please enter your question');
                    return;
                }

                $.ajax({
                    url: 'save_doubt.php',
                    method: 'POST',
                    data: {
                        course_id: courseId,
                        video_id: videoId,
                        question: question
                    },
                    success: function(response) {
                        console.log(response);
                        location.reload();
                    }
                });

            });

            $(document).on('click', '.delete-doubt-btn', function() {

                var btn = $(this);
                var doubtId = btn.data('doubt-id');

                Swal.fire({
                    title: 'Delete this question?',
                    text: "You won't be able to undo this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it'
                }).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({
                            url: 'delete_doubt.php',
                            type: 'POST',
                            data: {
                                doubt_id: doubtId
                            },
                            success: function(response) {

                                response = response.trim();

                                if (response === "success") {

                                    $('#doubt-' + doubtId).fadeOut(300, function() {
                                        $(this).remove();
                                    });

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: 'Your question has been deleted.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                } else {
                                    Swal.fire(
                                        'Error',
                                        'Something went wrong.',
                                        'error'
                                    );
                                }
                            }
                        });

                    }

                });

            });
        </script>
    <?php endif; ?>

    <?php include("assets/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>