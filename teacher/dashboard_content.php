<?php
session_name('TEACHER_SESSION');
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'teacher') {
    exit("Unauthorized");
}

include "../conn.php";

$teacher_id = $_SESSION['user_id'];

/* =========================
   Videos Uploaded
   ========================= */
$totalVideos = 0;

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM course_videos"
);
$stmt->execute();
$stmt->bind_result($totalVideos);
$stmt->fetch();
$stmt->close();

// Ensure it’s an integer
$totalVideos = intval($totalVideos);


/* =========================
   Total Courses
   (No teacher_id or status column exists)
   ========================= */
$stmt = $conn->prepare(
    "SELECT COUNT(*) 
     FROM baseline_courses"
);
$stmt->execute();
$stmt->bind_result($totalCourses);
$stmt->fetch();
$stmt->close();

/* =========================
   Pending Requests
   ========================= */
$pendingRequests = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM baseline_request WHERE status = 'pending'");
$stmt->execute();
$stmt->bind_result($pendingRequests);
$stmt->fetch();
$stmt->close();
$pendingRequests = intval($pendingRequests);

/* =========================
   Upcoming Meetings
   ========================= */
$upcomingMeetings = 0;
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT mr.id)
    FROM meeting_requests mr
    INNER JOIN meeting_slots ms ON mr.slot_id = ms.id
    WHERE ms.meeting_date >= CURDATE()
");
$stmt->execute();
$stmt->bind_result($upcomingMeetings);
$stmt->fetch();
$stmt->close();
$upcomingMeetings = intval($upcomingMeetings);
?>

<!-- =========================
     DASHBOARD UI
     ========================= -->

<div class="welcome-section">
    <h1 class="welcome-title">Teacher Dashboard</h1>
    <p class="welcome-subtitle">
        Manage your courses, upload educational videos, and interact with students all from one place.
    </p>
</div>

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-number"><?= (int)$totalVideos ?></div>
        <div class="stat-label">Videos Uploaded</div>
    </div>

    <div class="stat-card">
        <div class="stat-number"><?= (int)$totalCourses ?></div>
        <div class="stat-label">Total Courses</div>
    </div>

    <div class="stat-card">
        <div class="stat-number"><?= (int)$pendingRequests ?></div>
        <div class="stat-label">Pending Requests</div>
    </div>

    <div class="stat-card">
        <div class="stat-number"><?= (int)$upcomingMeetings ?></div>
        <div class="stat-label">Upcoming Meetings</div>
    </div>

</div>