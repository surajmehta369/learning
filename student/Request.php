<?php
include("../conn.php");
session_name('STUDENT_SESSION');
session_start();
// if (!isset($_COOKIE['user_email'])) {
//     header("Location: https://avengers.topscripts.in/edu/baselinelearning/login/login.php");
//     exit;
// }
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login/login.php");
    exit;
}
// print_r($_SESSION['user_id']);
// die();
$user_id = $_SESSION['user_id'] ?? 0;


$requests = [];
$result = $conn->prepare("
    SELECT br.*, s.full_name as teacher_name 
    FROM baseline_request br 
    LEFT JOIN signup s ON br.teacher_id = s.id 
    WHERE br.user_id = ? 
    ORDER BY br.created_at DESC
");
$result->bind_param("i", $user_id);
$result->execute();
$requests_result = $result->get_result();

if ($requests_result && $requests_result->num_rows > 0) {
    while ($row = $requests_result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Meeting Requests - User Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>

    </style>
</head>

<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php include("../assets/header.php"); ?>

    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <h4><i class="bi bi-mortarboard"></i> Student Dashboard</h4>
            <a href="student/profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
            <a href="student/Purchased.php"><i class="bi bi-people"></i> Purchased Courses</a>
            <a href="student/Meetings.php"><i class="bi bi-calendar-event"></i> Scheduled Meetings</a>
            <a class="active" href="student/Request.php"><i class="bi bi-chat-left-text"></i> My Requests</a>
            <!-- <a href="download_course.php"><i class="fas fa-download"></i> Download Courses</a> -->
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="main-card animate-fade-in">
                <!-- Header -->
                <div class="card-header-custom text-center">
                    <h1 class="mb-3"><i class="fas fa-calendar-check me-2"></i>My Meeting Requests</h1>
                    <p class="mb-0">Track the status of your meeting requests with teachers</p>
                </div>

                <div class="card-body-custom">
                    <!-- Statistics Cards -->
                    <div class="row mb-5">
                        <?php
                        $total_requests = count($requests);
                        $pending_count = 0;
                        $approved_count = 0;
                        $rejected_count = 0;

                        foreach ($requests as $r) {
                            switch (strtolower($r['status'])) {
                                case 'pending':
                                    $pending_count++;
                                    break;
                                case 'approved':
                                    $approved_count++;
                                    break;
                                case 'rejected':
                                    $rejected_count++;
                                    break;
                            }
                        }
                        ?>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-primary"><?= $total_requests ?></div>
                                <div class="stats-label">Total Requests</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-warning"><?= $pending_count ?></div>
                                <div class="stats-label">Pending</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-success"><?= $approved_count ?></div>
                                <div class="stats-label">Approved</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-danger"><?= $rejected_count ?></div>
                                <div class="stats-label">Rejected</div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter Buttons -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="filter-buttons">
                                <button class="btn btn-outline-primary filter-btn active" data-filter="all">All (<?= $total_requests ?>)</button>
                                <button class="btn btn-outline-warning filter-btn" data-filter="pending">Pending (<?= $pending_count ?>)</button>
                                <button class="btn btn-outline-success filter-btn" data-filter="approved">Approved (<?= $approved_count ?>)</button>
                                <button class="btn btn-outline-danger filter-btn" data-filter="rejected">Rejected (<?= $rejected_count ?>)</button>
                            </div>
                        </div>
                    </div>

                    <!-- Request List -->
                    <div class="row">
                        <div class="col-12">
                            <?php if (empty($requests)): ?>
                                <div class="empty-state animate-fade-in">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No Requests Found</h4>
                                    <p>You haven't made any meeting requests yet.</p>
                                    <a href="student/Meetings.php" class="btn btn-custom mt-3">
                                        <i class="fas fa-plus me-2"></i>Make Your First Request
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="row" id="requestList">
                                    <?php foreach ($requests as $r): ?>
                                        <?php
                                        $badgeClass = match (strtolower($r['status'])) {
                                            'pending' => 'bg-pending',
                                            'approved' => 'bg-approved',
                                            'rejected' => 'bg-rejected',
                                            default => 'bg-secondary'
                                        };

                                        $statusClass = match (strtolower($r['status'])) {
                                            'pending' => 'pending',
                                            'approved' => 'approved',
                                            'rejected' => 'rejected',
                                            default => ''
                                        };

                                        $date = date("F j, Y", strtotime($r['request_date']));
                                        $time = date("h:i A", strtotime($r['request_time']));
                                        $created = date("M j, Y g:i A", strtotime($r['created_at']));
                                        ?>
                                        <div class="col-12 mb-4 animate-fade-in request-item" data-status="<?= strtolower($r['status']) ?>">
                                            <div class="request-card <?= $statusClass ?>">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <h5 class="mb-0">
                                                                    <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>
                                                                    <?= htmlspecialchars($r['teacher_name']) ?>
                                                                </h5>
                                                                <span class="badge <?= $badgeClass ?>">
                                                                    <span class="status-indicator status-<?= $r['status'] ?>"></span>
                                                                    <?= ucfirst($r['status']) ?>
                                                                </span>
                                                            </div>

                                                            <div class="info-item">
                                                                <i class="fas fa-calendar"></i>
                                                                <strong>Requested Date:</strong> <?= $date ?>
                                                            </div>
                                                            <div class="info-item">
                                                                <i class="fas fa-clock"></i>
                                                                <strong>Requested Time:</strong> <?= $time ?>
                                                            </div>
                                                            <div class="info-item">
                                                                <i class="fas fa-comment"></i>
                                                                <strong>Your Message:</strong> "<?= htmlspecialchars($r['message']) ?>"
                                                            </div>
                                                            <div class="info-item">
                                                                <i class="fas fa-calendar-plus"></i>
                                                                <strong>Submitted:</strong> <?= $created ?>
                                                            </div>

                                                            <?php
                                                            date_default_timezone_set('Asia/Kolkata');
                                                            $now = new DateTime();

                                                            // 1. Setup Start Time (Use link_start_time OR fallback to request_date + time)
                                                            if (!empty($r['link_start_time'])) {
                                                                $startTime = new DateTime($r['link_start_time']);
                                                            } else {
                                                                // Combine the date and time columns from your DB
                                                                $startTime = new DateTime($r['request_date'] . ' ' . $r['request_time']);
                                                            }

                                                            // 2. Setup Expiry Time (Use link_expiry_time OR fallback to 40 mins after start)
                                                            if (!empty($r['link_expiry_time'])) {
                                                                $endTime = new DateTime($r['link_expiry_time']);
                                                            } else {
                                                                $endTime = clone $startTime;
                                                                $endTime->modify('+40 minutes'); // Default meeting duration
                                                            }

                                                            // 3. Comparisons
                                                            $isExpired    = $now > $endTime;
                                                            $isNotStarted = $now < $startTime;
                                                            $isOngoing    = ($now >= $startTime && $now <= $endTime);
                                                            ?>


                                                            <?php if ($r['status'] === 'approved'): ?>

                                                                <?php if ($isNotStarted): ?>
                                                                    <div class="alert alert-primary mb-0">
                                                                        <strong><i class="fas fa-clock me-2"></i>Meeting Not Started</strong>
                                                                        <div class="small">This link will activate at <?= $startTime->format('h:i A') ?>.</div>
                                                                    </div>

                                                                <?php elseif ($isOngoing && !empty($r['meeting_link']) && $r['is_active'] == 1): ?>
                                                                    <div class="meeting-link-box p-3" style="background: #e8f5e9; border: 2px solid #2e7d32; border-radius: 10px;">
                                                                        <div class="d-flex align-items-center justify-content-between">
                                                                            <div>
                                                                                <strong class="text-success"><i class="fas fa-video me-2"></i>Meeting is Live</strong>
                                                                                <p class="mb-0 small text-muted">Started at <?= $startTime->format('h:i A') ?></p>
                                                                            </div>
                                                                            <a href="<?= htmlspecialchars($r['meeting_link']) ?>"
                                                                                target="_blank"
                                                                                class="btn btn-success">
                                                                                Join Now
                                                                            </a>
                                                                        </div>
                                                                    </div>

                                                                <?php elseif ($isExpired || $r['is_active'] == 0): ?>
                                                                    <div class="alert alert-secondary mb-0">
                                                                        <strong><i class="fas fa-times-circle me-2"></i>Meeting Ended</strong>
                                                                        <div class="small">This session expired at <?= $endTime->format('h:i A') ?>.</div>
                                                                    </div>
                                                                <?php endif; ?>

                                                            <?php endif; ?>



                                                            <?php if (!empty($r['admin_comment']) && $r['status'] === 'rejected'): ?>
                                                                <div class="admin-comment-box">
                                                                    <strong><i class="fas fa-comment-dots me-2"></i>Admin Response:</strong>
                                                                    <p class="mb-0 mt-2"><?= htmlspecialchars($r['admin_comment']) ?></p>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="col-md-4 text-end">
                                                            <div class="d-grid gap-2">
                                                                <?php if ($r['status'] === 'pending'): ?>
                                                                    <span class="text-warning">
                                                                        <i class="fas fa-clock me-1"></i>Under Review
                                                                    </span>
                                                                    <small class="text-muted">Waiting for admin approval</small>
                                                                <?php elseif ($r['status'] === 'approved'): ?>
                                                                    <span class="text-success">
                                                                        <i class="fas fa-check-circle me-1"></i>Approved
                                                                    </span>
                                                                    <small class="text-muted">Click the link above to join</small>
                                                                <?php else: ?>
                                                                    <span class="text-danger">
                                                                        <i class="fas fa-times-circle me-1"></i>Declined
                                                                    </span>
                                                                    <small class="text-muted">See admin comment for details</small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../assets/half-footer.php"); ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Filter functionality
            $('.filter-btn').on('click', function() {
                const filter = $(this).data('filter');
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');

                if (filter === 'all') {
                    $('.request-item').show();
                } else {
                    $('.request-item').hide();
                    $(`.request-item[data-status="${filter}"]`).show();
                }
            });


            setInterval(function() {

                console.log('Checking for updates...');
            }, 30000);


            $('.meeting-link-btn').on('click', function() {
                $('html, body').animate({
                    scrollTop: 0
                }, 500);
            });


            $('.meeting-link-btn').on('click', function(e) {
                const href = $(this).attr('href');
                if (href && href.startsWith('http')) {
                    if (!confirm('You are about to leave this site and join the meeting. Continue?')) {
                        e.preventDefault();
                    }
                }
            });
        });

        // Function to refresh data 
        function refreshRequests() {
            Swal.fire({
                title: 'Refreshing...',
                text: 'Checking for updates',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    </script>
    <script>
        const searchBox = document.getElementById('searchBox');
        const subjectGrid = document.getElementById('subjectGrid');
        const interviewGrid = document.getElementById('interviewGrid');
        const subjects = subjectGrid.querySelectorAll('.col');
        const interviews = interviewGrid.querySelectorAll('.col');

        searchBox.addEventListener('input', () => {
            const query = searchBox.value.toLowerCase();

            // filter subjects
            subjects.forEach(col => {
                const subjectName = col.getAttribute('data-subject').toLowerCase();
                col.style.display = subjectName.includes(query) ? '' : 'none';
            });

            // filter interviews
            interviews.forEach(col => {
                const interviewName = col.getAttribute('data-interview').toLowerCase();
                col.style.display = interviewName.includes(query) ? '' : 'none';
            });
        });

        // Apply arrow hover to both subjects and interviews
        document.querySelectorAll(".subject-card, .interview-card").forEach(card => {
            const arrow = card.querySelector(".card-arrow");
            card.addEventListener("mouseenter", () => {
                arrow.innerHTML = "&#8593;"; // Up arrow
            });
            card.addEventListener("mouseleave", () => {
                arrow.innerHTML = "&#8595;"; // Down arrow
            });
        });
    </script>
</body>

</html>