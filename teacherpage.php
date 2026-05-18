<?php
session_name('TEACHER_SESSION');
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: login/login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Panel - Baseline Learning</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="images/favicon.png">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100%;
            background-color: #1a1a2e;
            color: #fff;
            padding-top: 20px;
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar.active {
            transform: translateX(-250px);
        }

        .sidebar-header h3 {
            margin-left: 20px;
        }

        .sidebar nav .nav-link {
            color: #cfd8dc;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: 0.2s;
        }

        .sidebar nav .nav-link i {
            margin-right: 10px;
        }

        .sidebar nav .nav-link.active,
        .sidebar nav .nav-link:hover {
            background-color: #0f3460;
            color: #fff;
            border-radius: 8px;
        }

        .header {
            margin-left: 250px;
            padding: 20px;
            background-color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info .user-name {
            font-weight: 600;
        }

        .user-info .user-role {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .logout-btn {
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 9px;
            background-color: #d82100;
            padding: 12px;
            width: 107px;
            font-weight: 800;
        }

        .hamburger-menu {
            position: fixed;
            top: 15px;
            left: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #1a1a2e;
            z-index: 1100;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            transition: all 0.3s;
        }

        .welcome-section h1 {
            font-weight: 700;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #0f3460;
        }

        .stat-label {
            font-size: 1rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px;
            color: #6c757d;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0f3460;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .header {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <button class="hamburger-menu d-lg-none">
        <i class="bi bi-list"></i>
    </button>

    <div class="sidebar">
        <div class="sidebar-header">
            <h3>📚 Teacher Panel</h3>
            <p>Manage Your Content</p>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link load-page active" href="#" data-page="teacher/dashboard_content.php">

                <i class="bi bi-speedometer2 nav-icon"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link load-page" href="#" data-page="teacher/addvideo.php">
                <i class="bi bi-camera-reels nav-icon"></i>
                <span>Add Video</span>
            </a>
            <a class="nav-link load-page" href="#" data-page="teacher/managevideos.php">
                <i class="bi bi-gear nav-icon"></i>
                <span>Manage Videos</span>
            </a>
            <a class="nav-link load-page" href="#" data-page="teacher/temeetingslot.php">
                <i class="bi bi-calendar-check nav-icon"></i>
                <span>Meeting Slots</span>
            </a>
            <a class="nav-link load-page" href="#" data-page="teacher/teacher_request.php">
                <i class="bi bi-inbox nav-icon"></i>
                <span>Manage Requests</span>
            </a>
            <a class="nav-link load-page" href="#" data-page="teacher/manage_doubts.php">
                <i class="bi bi-question-circle nav-icon"></i>
                <span>Student Doubts</span>
                <span class="badge bg-danger ms-auto" id="unanswered-count">0</span>
            </a>

            <a class="nav-link load-page" href="#" data-page="teacher/teacher_analytics.php">
                <i class="bi bi-question-circle nav-icon"></i>
                <span>📊 Student Analyticss</span>
                <span class="badge bg-danger ms-auto" id="unanswered-count">0</span>
            </a>


            <a class="nav-link load-page" href="#" data-page="teacher/review_submissions.php">
                <i class="bi bi-clipboard-check nav-icon"></i>
                <span>Student's Quiz Performance</span>
            </a>

            <a class="nav-link load-page" href="#" data-page="teacher/add_weekly_update.php">
                <i class="fas fa-edit"></i> <span>Manage Updates</span>
            </a>

            <a class="nav-link load-page" href="#" data-page="teacher/view_ratings.php">
                <i class="fas fa-edit"></i> <span>Rating analytics</span>
            </a>
        </nav>
    </div>

    <div class="header">
        <h1>Welcome: <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
        <div class="user-profile">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                <div class="user-role">Teacher Account</div>
            </div>
            <a href="logout.php?role=teacher" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>


    <div class="main-content">
        <div id="content-area"></div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <script>
        $(document).ready(function() {


            let lastPage = localStorage.getItem("teacher_last_page") || "teacher/dashboard_content.php";
            $("#content-area").load(lastPage);

            $(".nav-link").removeClass("active");
            $('.nav-link[data-page="' + lastPage + '"]').addClass("active");

            $('.hamburger-menu').click(function(e) {
                e.stopPropagation();
                $('.sidebar').toggleClass('active');
            });

            $(document).click(function(e) {
                if (!$(e.target).closest('.sidebar, .hamburger-menu').length && $(window).width() < 768) {
                    $('.sidebar').removeClass('active');
                }
            });

            $(".load-page").click(function(e) {
                e.preventDefault();
                const page = $(this).data("page");

                localStorage.setItem("teacher_last_page", page);

                $(".nav-link").removeClass("active");
                $(this).addClass("active");

                $("#content-area").html(`
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <h4 class="text-muted">Loading content...</h4>
                    <p class="text-muted">Please wait while we fetch your data</p>
                </div>
            `);

                $.ajax({
                    url: page,
                    type: "GET",
                    success: function(data) {
                        $("#content-area").html(data);
                        if ($(window).width() < 768) {
                            $('.sidebar').removeClass('active');
                        }
                    },
                    error: function() {
                        $("#content-area").html(`
                        <div class="alert alert-danger text-center p-5">
                            <i class="bi bi-exclamation-triangle-fill display-4 text-danger mb-3"></i>
                            <h4>Oops! Something went wrong</h4>
                            <p class="mb-4">We couldn't load the requested page.</p>
                            <button class="btn btn-primary load-page" data-page="dashboard_content.php">
                                <i class="bi bi-arrow-clockwise"></i> Return to Dashboard
                            </button>
                        </div>
                    `);
                    }
                });
            });

        });

        function loadAnalytics() {
            $.get('teacher/teacher_analytics.php', function(data) {
                $('#analytics-container').html(data);
            }).fail(function(xhr) {
                console.error("Error loading analytics: ", xhr.responseText);
                $('#analytics-container').html('<div class="alert alert-danger">Error loading data. Check console.</div>');
            });
        }

        $(document).ready(function() {
            loadAnalytics();
        });
    </script>

</body>

</html>