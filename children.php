<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name('STUDENT_SESSION');
    session_start();
}

include("conn.php");

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'parent'
) {
    header("Location: login/login.php");
    exit;
}

$parent_email = $_SESSION['user_email'] ?? '';

$children = [];

$sql = "
SELECT 
    s.id,
    s.full_name,
    s.image,

    (
        SELECT IFNULL(MAX(percentage),0)
        FROM quiz_results
        WHERE user_id = s.id
    ) AS top_quiz_score,

    (
        SELECT COUNT(*)
        FROM baseline_User_Cart
        WHERE user_id = s.id
    ) AS total_courses,

    (
        SELECT COUNT(*)
        FROM video_progress
        WHERE user_id = s.id
    ) AS completed_videos,

    (
        SELECT COUNT(*)
        FROM course_videos
        WHERE course_id IN (
            SELECT course_id
            FROM baseline_User_Cart
            WHERE user_id = s.id
            AND payment_mode='success'
        )
    ) AS total_videos,

    (
        SELECT IFNULL(SUM(course_price),0)
        FROM baseline_User_Cart
        WHERE user_id = s.id
        AND payment_mode='pending'
    ) AS pending_amount,

    (
        SELECT course_title
        FROM baseline_User_Cart
        WHERE user_id = s.id
        AND payment_mode='pending'
        ORDER BY id DESC
        LIMIT 1
    ) AS pending_course,

    (
        SELECT GROUP_CONCAT(course_title SEPARATOR ', ')
        FROM baseline_User_Cart
        WHERE user_id = s.id
        AND payment_mode='success'
    ) AS purchased_courses

FROM signup s

WHERE s.role='student'
AND s.parent_email=?

ORDER BY s.full_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $parent_email);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $totalVideos = (int)$row['total_videos'];
    $completedVideos = (int)$row['completed_videos'];

    $progress = 0;

    if($totalVideos > 0){
        $progress = round(($completedVideos / $totalVideos) * 100);
    }

    $row['progress'] = $progress;

    $children[] = $row;
}

$total_students = count($children);

$total_pending = 0;
$total_courses = 0;

foreach($children as $child){
    $total_pending += $child['pending_amount'];
    $total_courses += $child['total_courses'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Children</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Inter,Arial,sans-serif;
    background:#f5f7fb;
    color:#111827;
}

.parentPage{
    padding:40px;
    max-width:1600px;
    margin:auto;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:35px;
    flex-wrap:wrap;
    gap:20px;
}

.heading{
    font-size:42px;
    font-weight:800;
    color:#111827;
    margin-bottom:8px;
}

.subheading{
    color:#64748b;
    font-size:15px;
}

.topButtons{
    display:flex;
    gap:14px;
}

.topBtn{
    text-decoration:none;
    padding:14px 22px;
    border-radius:14px;
    font-size:14px;
    font-weight:700;
    transition:0.3s;
}

.homeBtn{
    background:white;
    border:1px solid #dbe3ee;
    color:#111827;
}

.dashboardBtn{
    background:#4f46e5;
    color:white;
    box-shadow:0 10px 25px rgba(79,70,229,0.25);
}

.topBtn:hover{
    transform:translateY(-2px);
}

.summaryGrid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:22px;
    margin-bottom:35px;
}

.summaryCard{
    background:white;
    border-radius:24px;
    padding:28px;
    position:relative;
    border:1px solid #edf2f7;
    box-shadow:0 10px 30px rgba(15,23,42,0.05);
}

.summaryIcon{
    position:absolute;
    top:24px;
    right:24px;
    width:58px;
    height:58px;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:22px;
}

.purple{
    background:linear-gradient(135deg,#7c3aed,#8b5cf6);
}

.blue{
    background:linear-gradient(135deg,#2563eb,#3b82f6);
}

.orange{
    background:linear-gradient(135deg,#f97316,#fb923c);
}

.summaryLabel{
    color:#64748b;
    font-size:14px;
    margin-bottom:10px;
}

.summaryValue{
    font-size:42px;
    font-weight:800;
    color:#111827;
}

.childrenGrid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(420px,1fr));
    gap:28px;
}

.childCard{
    background:white;
    border-radius:30px;
    padding:28px;
    border:1px solid #e8edf5;
    box-shadow:0 12px 35px rgba(15,23,42,0.06);
    transition:0.35s;
}

.childCard:hover{
    transform:translateY(-5px);
}

.childHeader{
    display:flex;
    align-items:center;
    gap:18px;
    margin-bottom:28px;
}

.childAvatar{
    width:80px;
    height:80px;
    border-radius:24px;
    overflow:hidden;
    background:linear-gradient(135deg,#4f46e5,#2563eb);
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:30px;
    font-weight:800;
    flex-shrink:0;
}

.childAvatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.childName{
    font-size:24px;
    font-weight:800;
    margin-bottom:5px;
}

.childId{
    font-size:14px;
    color:#64748b;
}

.statsGrid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
    margin-bottom:22px;
}

.statBox{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:20px;
    padding:22px;
}

.statValue{
    font-size:30px;
    font-weight:800;
    color:#111827;
    margin-bottom:8px;
}

.statLabel{
    font-size:13px;
    color:#64748b;
}

.progressCard{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:22px;
    padding:22px;
    margin-bottom:22px;
}

.progressTop{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:14px;
}

.progressTitle{
    font-weight:700;
    font-size:15px;
}

.progressPercent{
    color:#4f46e5;
    font-weight:800;
    font-size:15px;
}

.progressBar{
    width:100%;
    height:12px;
    background:#dbe4ee;
    border-radius:50px;
    overflow:hidden;
}

.progressFill{
    height:100%;
    border-radius:50px;
    background:linear-gradient(90deg,#4f46e5,#2563eb);
}

.progressText{
    margin-top:10px;
    color:#64748b;
    font-size:13px;
}

.courseBox{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:22px;
    padding:20px;
    margin-bottom:18px;
}

.courseLabel{
    color:#64748b;
    font-size:13px;
    margin-bottom:10px;
}

.courseText{
    font-size:14px;
    line-height:1.8;
    color:#111827;
    font-weight:600;
}

.pendingText{
    color:#ea580c;
}

.dashboardButton{
    width:100%;
    border:none;
    background:linear-gradient(135deg,#4f46e5,#2563eb);
    color:white;
    padding:16px;
    border-radius:18px;
    font-size:15px;
    font-weight:700;
    cursor:pointer;
    transition:0.3s;
    box-shadow:0 10px 25px rgba(37,99,235,0.25);
}

.dashboardButton:hover{
    transform:translateY(-2px);
}

.emptyState{
    background:white;
    border-radius:30px;
    padding:80px 20px;
    text-align:center;
    border:1px solid #e5e7eb;
}

.emptyState i{
    font-size:65px;
    color:#cbd5e1;
    margin-bottom:18px;
}

.emptyState h2{
    margin-bottom:10px;
}

.emptyState p{
    color:#64748b;
}

.footerWrapper{
    margin-top:70px;
}

footer,
.footer,
footer *{
    box-sizing:border-box;
}

footer .row{
    display:flex !important;
    flex-wrap:wrap !important;
}

footer .col,
footer [class*="col-"]{
    width:auto;
}

footer ul{
    padding-left:0;
}

footer a{
    text-decoration:none;
}

@media(max-width:768px){

    .parentPage{
        padding:20px;
    }

    .heading{
        font-size:32px;
    }

    .childrenGrid{
        grid-template-columns:1fr;
    }

    .statsGrid{
        grid-template-columns:1fr;
    }

    .topButtons{
        width:100%;
    }

    .topBtn{
        flex:1;
        text-align:center;
    }

}

</style>

</head>

<body>

<div class="parentPage">

    <div class="topbar">

        <div>

            <div class="heading">
                My Children
            </div>

            <div class="subheading">
                Students linked to <?php echo htmlspecialchars($parent_email); ?>
            </div>

        </div>

        <div class="topButtons">

            <a href="parent_dashboard.php" class="topBtn homeBtn">
                <i class="fa fa-home"></i>
                Back To Home
            </a>

            

        </div>

    </div>

    <div class="summaryGrid">

        <div class="summaryCard">

            <div class="summaryIcon purple">
                <i class="fa fa-users"></i>
            </div>

            <div class="summaryLabel">
                Total Students
            </div>

            <div class="summaryValue">
                <?php echo $total_students; ?>
            </div>

        </div>

        <div class="summaryCard">

            <div class="summaryIcon blue">
                <i class="fa fa-book-open"></i>
            </div>

            <div class="summaryLabel">
                Total Courses
            </div>

            <div class="summaryValue">
                <?php echo $total_courses; ?>
            </div>

        </div>

        <div class="summaryCard">

            <div class="summaryIcon orange">
                <i class="fa fa-wallet"></i>
            </div>

            <div class="summaryLabel">
                Pending Payments
            </div>

            <div class="summaryValue">
                ₹<?php echo number_format($total_pending); ?>
            </div>

        </div>

    </div>

    <div class="childrenGrid">

    <?php if(count($children) > 0): ?>

    <?php foreach($children as $child): ?>

        <div class="childCard">

            <div class="childHeader">

                <div class="childAvatar">

                    <?php if(!empty($child['image'])): ?>

                        <img src="uploads/<?php echo htmlspecialchars($child['image']); ?>">

                    <?php else: ?>

                        <?php echo strtoupper(substr($child['full_name'],0,1)); ?>

                    <?php endif; ?>

                </div>

                <div>

                    <div class="childName">
                        <?php echo htmlspecialchars($child['full_name']); ?>
                    </div>

                    <div class="childId">
                        Student ID #<?php echo $child['id']; ?>
                    </div>

                </div>

            </div>

            <div class="statsGrid">

                <div class="statBox">

                    <div class="statValue">
                        <?php echo $child['total_courses']; ?>
                    </div>

                    <div class="statLabel">
                        Total Courses
                    </div>

                </div>

                <div class="statBox">

                    <div class="statValue">
                        <?php echo round($child['top_quiz_score']); ?>%
                    </div>

                    <div class="statLabel">
                        Best Quiz Score
                    </div>

                </div>

            </div>

            <div class="progressCard">

                <div class="progressTop">

                    <div class="progressTitle">
                        Learning Progress
                    </div>

                    <div class="progressPercent">
                        <?php echo $child['progress']; ?>%
                    </div>

                </div>

                <div class="progressBar">

                    <div class="progressFill"
                    style="width:<?php echo $child['progress']; ?>%">
                    </div>

                </div>

                <div class="progressText">

                    <?php echo $child['completed_videos']; ?>
                    completed out of
                    <?php echo $child['total_videos']; ?>
                    videos

                </div>

            </div>

            <?php if($child['pending_amount'] > 0): ?>

            <div class="courseBox">

                <div class="courseLabel">
                    Pending Payment
                </div>

                <div class="courseText pendingText">

                    ₹<?php echo number_format($child['pending_amount']); ?>

                    <?php if(!empty($child['pending_course'])): ?>

                        • <?php echo htmlspecialchars($child['pending_course']); ?>

                    <?php endif; ?>

                </div>

            </div>

            <?php endif; ?>

            <div class="courseBox">

                <div class="courseLabel">
                    Purchased Courses
                </div>

                <div class="courseText">

                    <?php
                    echo !empty($child['purchased_courses'])
                        ? htmlspecialchars($child['purchased_courses'])
                        : 'No purchased courses yet';
                    ?>

                </div>

            </div>

            <button class="dashboardButton"
            onclick="switchStudent(<?php echo $child['id']; ?>)">

                <i class="fa fa-eye"></i>
                View Dashboard

            </button>

        </div>

    <?php endforeach; ?>

    <?php else: ?>

        <div class="emptyState">

            <i class="fa-solid fa-user-slash"></i>

            <h2>No Children Found</h2>

            <p>
                No student accounts linked with your email.
            </p>

        </div>

    <?php endif; ?>

    </div>

</div>

    <?php include('assets/half-footer.php'); ?>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<script>

function switchStudent(id)
{
    $.ajax({
        url:'handlers/set_session.php',
        type:'POST',
        data:{student_id:id},
        success:function()
        {
            window.location.href='parent_dashboard.php';
        }
    });
}

</script>

</body>
</html>