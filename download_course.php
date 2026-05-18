<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("conn.php");

// Check Login
// if (!isset($_COOKIE['user_id'])) {
//     header("Location: https://avengers.topscripts.in/edu/baselinelearning/login/login.php");
//     exit();
// }
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'student') {
    header("Location: login/login.php");
    exit;
}
$user_id = intval($_SESSION['user_id']);

// Fetch Purchased Courses where payment is successful
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
<title>Purchased Courses</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background: #eef1f7;
    font-family: 'Segoe UI', sans-serif;
}

.wrapper {
    display: flex;
    padding-top: 15px;
}

.sidebar {
    width: 240px;
    background: #6f42c1;
    color: #fff;
    padding: 20px;
    border-radius: 0 10px 10px 0;
    position: sticky;
    top: 95px;
}

.sidebar h4 {
    margin-bottom: 25px;
    display: flex;
    gap: 8px;
    align-items: center;
    font-size: 18px;
    font-weight: bold;
}

.sidebar a {
    display: flex;
    gap: 10px;
    padding: 12px;
    border-radius: 7px;
    font-size: 15px;
    color: #fff;
    text-decoration: none;
}
.sidebar a:hover, .sidebar a.active {
    background: rgba(255,255,255,0.2);
}

.main-content {
    flex: 1;
    padding: 25px;
}

.course-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: 0.3s;
}
.course-card:hover {
    transform: translateY(-4px);
}
.course-img {
    height: 170px;
    object-fit: cover;
    width: 100%;
}
.price-tag {
    font-size: 17px;
    font-weight: 600;
    color: #6f42c1;
}
</style>
</head>

<body>

<?php include('header.php'); ?>

<div class="wrapper">

    <!-- Sidebar -->
    <div class="sidebar">
        <h4><i class="bi bi-mortarboard"></i> User Dashboard</h4>
        <a href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
        <a class="active" href="Purchased.php"><i class="bi bi-people"></i> Purchased Courses</a>
        <a href="Meetings.php"><i class="bi bi-calendar-event"></i> Scheduled Meetings</a>
        <a href="Request.php"><i class="bi bi-chat-left-text"></i> Requests</a>
        <a href="download_course.php"><i class="bi bi-book"></i> Download courses</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <h3 class="mb-4 fw-bold"><i class="bi bi-bag-check"></i> My Download Courses</h3>

        <div class="row g-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="course-card">
                        <img src="<?php echo htmlspecialchars($row['course_image']); ?>" class="course-img" alt="Course Image">

                        <div class="p-3">
                            <h5 class="fw-bold text-dark">
                                <?php echo htmlspecialchars($row['course_title']); ?>
                            </h5>

                            <div class="price-tag mb-2">₹<?php echo htmlspecialchars($row['course_price']); ?></div>

                            <a href="viewmore.php?id=<?php echo $row['course_id']; ?>" class="btn btn-primary w-100">
                                <i class="bi bi-play-circle"></i> Download Course Videos
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php else: ?>
            <div class="text-center p-5 bg-white shadow-sm rounded">
                <i class="bi bi-bag-x" style="font-size:45px;color:#6f42c1"></i>
                <h4 class="mt-3">No Courses Purchased Yet</h4>
                <p class="text-muted">Explore our courses and start learning.</p>
                <a href="ourcourses.php" class="btn btn-success">Browse Courses</a>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

</body>
</html>
