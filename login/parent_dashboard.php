<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('STUDENT_SESSION');
    session_start();
}

include("conn.php");
include('assets/header.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header("Location: login/login.php");
    exit;
}

$parentEmail = $_SESSION['user_email'];

$stmtUser = $conn->prepare("SELECT full_name FROM signup WHERE email = ? LIMIT 1");
$stmtUser->bind_param("s", $parentEmail);
$stmtUser->execute();
$parentData = $stmtUser->get_result()->fetch_assoc();

$parentName = $parentData['full_name'] ?? 'Parent';
$parentInitials = strtoupper(substr($parentName, 0, 1));

$stmtKids = $conn->prepare("
SELECT 
    id,
    full_name,
    status
FROM signup
WHERE role='student'
AND parent_email=?
ORDER BY full_name ASC
");

$stmtKids->bind_param("s", $parentEmail);
$stmtKids->execute();

$kidsResult = $stmtKids->get_result();

$children = [];

while($row = $kidsResult->fetch_assoc()){
    $children[] = $row;
}

$activeChild = !empty($children) ? $children[0] : null;

$courseCount = 0;
$avgProgress = 0;
$pendingTotal = 0;

$progressCourses = [];
$paymentRows = [];

if($activeChild){

    $sid = $activeChild['id'];

    $qC = $conn->prepare("
    SELECT COUNT(*) total
    FROM baseline_User_Cart
    WHERE user_id=?
    AND payment_mode='success'
    ");

    $qC->bind_param("i",$sid);
    $qC->execute();

    $courseCount = $qC->get_result()->fetch_assoc()['total'] ?? 0;

    $qP = $conn->prepare("
    SELECT IFNULL(SUM(course_price),0) total_due
    FROM baseline_User_Cart
    WHERE user_id=?
    AND payment_mode='pending'
    ");

    $qP->bind_param("i",$sid);
    $qP->execute();

    $pendingTotal = $qP->get_result()->fetch_assoc()['total_due'] ?? 0;

    $progressQuery = "
    SELECT 
        c.id,
        c.course_name,
        COUNT(cv.id) total_videos,
        COUNT(vp.video_id) completed_videos
    FROM baseline_User_Cart uc
    LEFT JOIN baseline_courses c ON c.id = uc.course_id
    LEFT JOIN course_videos cv ON cv.course_id = c.id
    LEFT JOIN video_progress vp 
        ON vp.video_id = cv.id 
        AND vp.user_id = uc.user_id
    WHERE uc.user_id=?
    AND uc.payment_mode='success'
    GROUP BY c.id
    ";

    $stmtProgress = $conn->prepare($progressQuery);
    $stmtProgress->bind_param("i",$sid);
    $stmtProgress->execute();

    $progressResult = $stmtProgress->get_result();

    $totalPercent = 0;
    $courseCounter = 0;

    while($p = $progressResult->fetch_assoc()){

        $totalVideos = (int)$p['total_videos'];
        $completedVideos = (int)$p['completed_videos'];

        $percent = 0;

        if($totalVideos > 0){
            $percent = round(($completedVideos / $totalVideos) * 100);
        }

        $p['progress_percent'] = $percent;

        $progressCourses[] = $p;

        $totalPercent += $percent;
        $courseCounter++;
    }

    if($courseCounter > 0){
        $avgProgress = round($totalPercent / $courseCounter);
    }

    $payQuery = "
    SELECT 
        course_title,
        course_price,
        payment_mode
    FROM baseline_User_Cart
    WHERE user_id=?
    ORDER BY id DESC
    LIMIT 5
    ";

    $stmtPay = $conn->prepare($payQuery);
    $stmtPay->bind_param("i",$sid);
    $stmtPay->execute();

    $payResult = $stmtPay->get_result();

    while($pay = $payResult->fetch_assoc()){
        $paymentRows[] = $pay;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Parent Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#f4f7fb;
    font-family:'Inter',sans-serif;
    color:#111827;
}

.dashboardWrapper{
    display:flex;
    min-height:100vh;
}

.sidebar{
    width:270px;
    background:#ffffff;
    border-right:1px solid #e5e7eb;
    padding:24px 18px;
    position:sticky;
    top:0;
    height:100vh;
}

.brand{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:35px;
}

.brandLogo{
    width:45px;
    height:45px;
    border-radius:14px;
    background:linear-gradient(135deg,#7c3aed,#2563eb);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
}

.brandText{
    font-size:22px;
    font-weight:800;
}

.brandText span{
    color:#4f46e5;
}

.parentCard{
    background:#f8fafc;
    border-radius:18px;
    padding:16px;
    display:flex;
    gap:14px;
    align-items:center;
    margin-bottom:30px;
}

.parentAvatar{
    width:50px;
    height:50px;
    border-radius:14px;
    background:linear-gradient(135deg,#7c3aed,#2563eb);
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-weight:700;
    font-size:18px;
}

.parentName{
    font-size:15px;
    font-weight:700;
}

.parentRole{
    font-size:13px;
    color:#6b7280;
}

.navMenu{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.navMenu a{
    text-decoration:none;
    color:#374151;
    padding:14px 16px;
    border-radius:14px;
    font-size:15px;
    font-weight:600;
    transition:0.25s;
    display:flex;
    align-items:center;
    gap:12px;
}

.navMenu a:hover,
.navMenu a.active{
    background:linear-gradient(135deg,#7c3aed,#2563eb);
    color:white;
}

.logoutBtn{
    margin-top:30px;
    background:#fee2e2 !important;
    color:#dc2626 !important;
}

.mainContent{
    flex:1;
    padding:30px;
}

.topBar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:28px;
    flex-wrap:wrap;
    gap:20px;
}

.topHeading h1{
    font-size:34px;
    font-weight:800;
    margin-bottom:5px;
}

.topHeading p{
    color:#6b7280;
}

.topActions{
    display:flex;
    gap:12px;
    align-items:center;
}

.searchBox{
    background:white;
    border-radius:14px;
    padding:12px 18px;
    width:260px;
    border:1px solid #e5e7eb;
}

.searchBox input{
    border:none;
    width:100%;
    outline:none;
    background:none;
}

.heroCard{
    background:linear-gradient(135deg,#7c3aed,#2563eb);
    border-radius:30px;
    padding:40px;
    color:white;
    position:relative;
    overflow:hidden;
    margin-bottom:28px;
}

.heroCard::before{
    content:'';
    position:absolute;
    width:250px;
    height:250px;
    border-radius:50%;
    background:rgba(255,255,255,0.08);
    right:-60px;
    top:-80px;
}

.heroTitle{
    font-size:42px;
    font-weight:800;
    margin-bottom:10px;
}

.heroSub{
    max-width:650px;
    line-height:1.7;
    opacity:0.95;
}

.childSection{
    margin-bottom:25px;
}

.sectionTitle{
    font-size:18px;
    font-weight:700;
    margin-bottom:16px;
}

.childTabs{
    display:flex;
    gap:16px;
    flex-wrap:wrap;
}

.childTab{
    background:white;
    border-radius:18px;
    padding:18px;
    border:2px solid transparent;
    min-width:230px;
    display:flex;
    align-items:center;
    gap:15px;
    transition:0.25s;
}

.childTab.active{
    border-color:#7c3aed;
    box-shadow:0 10px 25px rgba(124,58,237,0.12);
}

.childAvatar{
    width:55px;
    height:55px;
    border-radius:16px;
    background:linear-gradient(135deg,#7c3aed,#2563eb);
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:18px;
    font-weight:700;
}

.childName{
    font-size:16px;
    font-weight:700;
}

.childMeta{
    color:#6b7280;
    font-size:13px;
    margin-top:4px;
}

.statsGrid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:22px;
    margin-bottom:28px;
}

.statCard{
    background:white;
    border-radius:24px;
    padding:26px;
    box-shadow:0 8px 24px rgba(0,0,0,0.05);
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.statInfo h5{
    color:#6b7280;
    font-size:14px;
    margin-bottom:10px;
}

.statInfo h2{
    font-size:34px;
    font-weight:800;
}

.statIcon{
    width:68px;
    height:68px;
    border-radius:20px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
    color:white;
}

.purple{
    background:linear-gradient(135deg,#7c3aed,#8b5cf6);
}

.blue{
    background:linear-gradient(135deg,#2563eb,#3b82f6);
}

.orange{
    background:linear-gradient(135deg,#f59e0b,#fb923c);
}

.contentGrid{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:24px;
    align-items:start;
}

.cardBox{
    background:white;
    border-radius:26px;
    padding:28px;
    box-shadow:0 8px 24px rgba(0,0,0,0.05);
    margin-bottom:24px;
}

.cardHeader{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:22px;
}

.cardHeader h3{
    font-size:20px;
    font-weight:700;
}

.progressItem{
    margin-bottom:24px;
}

.progressTop{
    display:flex;
    justify-content:space-between;
    margin-bottom:10px;
}

.progressTop span{
    font-size:15px;
    font-weight:600;
}

.progress{
    height:10px;
    border-radius:50px;
    background:#e5e7eb;
}

.progress-bar{
    background:linear-gradient(135deg,#7c3aed,#2563eb);
}

.table{
    margin-bottom:0;
}

.table thead{
    background:#f9fafb;
}

.table th{
    color:#6b7280;
    font-size:13px;
    font-weight:700;
    border:none;
    padding:16px;
}

.table td{
    padding:18px 16px;
    vertical-align:middle;
}

.statusBadge{
    padding:8px 14px;
    border-radius:30px;
    font-size:12px;
    font-weight:700;
}

.statusSuccess{
    background:#dcfce7;
    color:#15803d;
}

.statusPending{
    background:#fef3c7;
    color:#b45309;
}

.profileCard{
    text-align:center;
}

.profileAvatar{
    width:95px;
    height:95px;
    border-radius:28px;
    background:linear-gradient(135deg,#7c3aed,#2563eb);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:36px;
    font-weight:800;
    margin:auto;
    margin-bottom:20px;
}

.profileName{
    font-size:24px;
    font-weight:800;
    margin-bottom:8px;
}

.profileId{
    color:#6b7280;
    margin-bottom:25px;
}

.profileMeta{
    display:flex;
    justify-content:space-between;
    padding:14px 0;
    border-bottom:1px solid #e5e7eb;
    font-size:15px;
}

.profileBtn{
    margin-top:24px;
    width:100%;
    border:none;
    background:linear-gradient(135deg,#7c3aed,#2563eb);
    color:white;
    padding:16px;
    border-radius:16px;
    font-size:15px;
    font-weight:700;
}

.footerSpace{
    margin-top:40px;
}

@media(max-width:1100px){

    .contentGrid{
        grid-template-columns:1fr;
    }

}

@media(max-width:900px){

    .dashboardWrapper{
        flex-direction:column;
    }

    .sidebar{
        width:100%;
        height:auto;
        position:relative;
    }

    .mainContent{
        padding:20px;
    }

    .heroTitle{
        font-size:30px;
    }

}

</style>
</head>

<body>

<div class="dashboardWrapper">

    <aside class="sidebar">

        <div class="brand">

            <div class="brandLogo">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>

            <div class="brandText">
                Baseline<span>Learning</span>
            </div>

        </div>

        <div class="parentCard">

            <div class="parentAvatar">
                <?php echo $parentInitials; ?>
            </div>

            <div>
                <div class="parentName">
                    <?php echo htmlspecialchars($parentName); ?>
                </div>

                <div class="parentRole">
                    Parent Account
                </div>
            </div>

        </div>

        <div class="navMenu">

            <a href="#" class="active">
                <i class="fa-solid fa-house"></i>
                Dashboard
            </a>

            <a href="children.php">
                <i class="fa-solid fa-children"></i>
                My Children
            </a>

            <a href="pleaderboard.php">
                <i class="fa-solid fa-chart-line"></i>
                Progress Reports
            </a>

            <a href="payments.php">
                <i class="fa-solid fa-credit-card"></i>
                Payments
            </a>

            <a href="settings.php">
                <i class="fa-solid fa-gear"></i>
                Settings
            </a>

            <a href="logout.php" class="logoutBtn">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>

        </div>

    </aside>

    <main class="mainContent">

        <div class="topBar">

            <div class="topHeading">

                <h1>Parent Dashboard</h1>

                <p>
                    Monitor your children's learning progress and payments
                </p>

            </div>

            <div class="topActions">

                <div class="searchBox">
                    <input type="text" placeholder="Search...">
                </div>

            </div>

        </div>

        <div class="heroCard">

            <div class="heroTitle">
                Hello, <?php echo htmlspecialchars($parentName); ?>
            </div>

            <div class="heroSub">
                Track your children's course progress, payment status and overall academic activity in one professional dashboard.
            </div>

        </div>

        <div class="childSection">

            <div class="sectionTitle">
                Linked Students
            </div>

            <div class="childTabs">

                <?php foreach($children as $index => $child): ?>

                    <div class="childTab <?php echo $index == 0 ? 'active' : ''; ?>">

                        <div class="childAvatar">
                            <?php echo strtoupper(substr($child['full_name'],0,1)); ?>
                        </div>

                        <div>

                            <div class="childName">
                                <?php echo htmlspecialchars($child['full_name']); ?>
                            </div>

                            <div class="childMeta">
                                Student ID #<?php echo $child['id']; ?>
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

        <div class="statsGrid">

            <div class="statCard">

                <div class="statInfo">

                    <h5>Total Courses</h5>

                    <h2><?php echo $courseCount; ?></h2>

                </div>

                <div class="statIcon purple">
                    <i class="fa-solid fa-book-open"></i>
                </div>

            </div>

            <div class="statCard">

                <div class="statInfo">

                    <h5>Average Progress</h5>

                    <h2><?php echo $avgProgress; ?>%</h2>

                </div>

                <div class="statIcon blue">
                    <i class="fa-solid fa-chart-line"></i>
                </div>

            </div>

            <div class="statCard">

                <div class="statInfo">

                    <h5>Pending Payments</h5>

                    <h2>₹<?php echo number_format($pendingTotal); ?></h2>

                </div>

                <div class="statIcon orange">
                    <i class="fa-solid fa-wallet"></i>
                </div>

            </div>

        </div>

        <div class="contentGrid">

            <div>

                <div class="cardBox">

                    <div class="cardHeader">

                        <h3>
                            <i class="fa-solid fa-chart-simple"></i>
                            Course Progress
                        </h3>

                    </div>

                    <?php if(count($progressCourses) > 0): ?>

                        <?php foreach($progressCourses as $course): ?>

                            <div class="progressItem">

                                <div class="progressTop">

                                    <span>
                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                    </span>

                                    <span>
                                        <?php echo $course['progress_percent']; ?>%
                                    </span>

                                </div>

                                <div class="progress">

                                    <div class="progress-bar"
                                    style="width:<?php echo $course['progress_percent']; ?>%">
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p>No progress available.</p>

                    <?php endif; ?>

                </div>

                <div class="cardBox">

                    <div class="cardHeader">

                        <h3>
                            <i class="fa-solid fa-credit-card"></i>
                            Recent Payments
                        </h3>

                    </div>

                    <div class="table-responsive">

                        <table class="table">

                            <thead>

                                <tr>
                                    <th>Course</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>

                            </thead>

                            <tbody>

                            <?php if(count($paymentRows) > 0): ?>

                                <?php foreach($paymentRows as $pay): ?>

                                    <tr>

                                        <td>
                                            <?php echo htmlspecialchars($pay['course_title']); ?>
                                        </td>

                                        <td>
                                            ₹<?php echo number_format($pay['course_price']); ?>
                                        </td>

                                        <td>

                                            <?php if($pay['payment_mode']=='success'): ?>

                                                <span class="statusBadge statusSuccess">
                                                    Success
                                                </span>

                                            <?php else: ?>

                                                <span class="statusBadge statusPending">
                                                    Pending
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="3">
                                        No payment records found.
                                    </td>

                                </tr>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            <div>

                <?php if($activeChild): ?>

                <div class="cardBox profileCard">

                    <div class="profileAvatar">
                        <?php echo strtoupper(substr($activeChild['full_name'],0,1)); ?>
                    </div>

                    <div class="profileName">
                        <?php echo htmlspecialchars($activeChild['full_name']); ?>
                    </div>

                    <div class="profileId">
                        Student ID #<?php echo $activeChild['id']; ?>
                    </div>

                    <div class="profileMeta">

                        <span>Status</span>

                        <strong>
                            <?php echo ucfirst($activeChild['status']); ?>
                        </strong>

                    </div>

                    <div class="profileMeta">

                        <span>Progress</span>

                        <strong>
                            <?php echo $avgProgress; ?>%
                        </strong>

                    </div>

                    <div class="profileMeta">

                        <span>Courses</span>

                        <strong>
                            <?php echo $courseCount; ?>
                        </strong>

                    </div>

                    <button class="profileBtn">
                        <i class="fa-solid fa-eye"></i>
                        View Full Profile
                    </button>

                </div>

                <?php endif; ?>

            </div>

        </div>

        <div class="footerSpace">
            <?php include('assets/footer.php'); ?>
        </div>

    </main>

</div>

</body>
</html>