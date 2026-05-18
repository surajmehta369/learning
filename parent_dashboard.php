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

$parentEmail = $_SESSION['user_email'] ?? '';

$stmtUser = $conn->prepare("
    SELECT full_name
    FROM signup
    WHERE email = ?
    LIMIT 1
");

$stmtUser->bind_param("s", $parentEmail);
$stmtUser->execute();

$userResult = $stmtUser->get_result();
$parentData = $userResult->fetch_assoc();

$parentName = $parentData['full_name'] ?? 'Parent';
$parentInitial = strtoupper(substr($parentName, 0, 1));

$children = [];

$sql = "
SELECT
    s.id,
    s.full_name,
    s.status,

    (
        SELECT COUNT(DISTINCT course_title)
        FROM baseline_User_Cart
        WHERE user_id = s.id
        AND payment_mode='success'
    ) AS purchased_courses,

    (
        SELECT IFNULL(SUM(course_price),0)
        FROM baseline_User_Cart
        WHERE user_id = s.id
        AND payment_mode='pending'
    ) AS pending_amount,

    (
        SELECT IFNULL(AVG(completed_percent),0)
        FROM student_progress
        WHERE student_id = s.id
    ) AS progress_avg,

    (
        SELECT IFNULL(AVG(percentage),0)
        FROM quiz_results
        WHERE user_id = s.id
    ) AS quiz_avg

FROM signup s
WHERE s.role='student'
AND s.parent_email=?
ORDER BY s.full_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $parentEmail);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $courseNames = [];

    $courseStmt = $conn->prepare("
        SELECT DISTINCT course_title
        FROM baseline_User_Cart
        WHERE user_id=?
        AND payment_mode='success'
        ORDER BY id DESC
        LIMIT 3
    ");

    $courseStmt->bind_param("i", $row['id']);
    $courseStmt->execute();

    $courseRes = $courseStmt->get_result();

    while($course = $courseRes->fetch_assoc()){
        $courseNames[] = $course['course_title'];
    }

    $row['courses_list'] = $courseNames;

    $children[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Children | Parent Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

:root{
    --primary:#6d4aff;
    --primary2:#3b82f6;
    --dark:#0f172a;
    --muted:#64748b;
    --border:#e9edf5;
    --bg:#f5f7fb;
    --card:#ffffff;
    --shadow:0 10px 30px rgba(15,23,42,.06);
    --gradient:linear-gradient(135deg,#6d4aff 0%,#5d5eff 45%,#3b82f6 100%);
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:var(--bg);
    font-family:'Inter',sans-serif;
    color:var(--dark);
}

.topbar{
    height:72px;
    background:#fff;
    border-bottom:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 26px;
    position:sticky;
    top:0;
    z-index:999;
}

.brand{
    display:flex;
    align-items:center;
    gap:12px;
    font-size:24px;
    font-weight:800;
}

.brand-icon{
    width:42px;
    height:42px;
    border-radius:14px;
    background:var(--gradient);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
}

.profile-menu{
    position:relative;
}

.profile-trigger{
    display:flex;
    align-items:center;
    gap:12px;
    cursor:pointer;
    padding:10px 14px;
    border-radius:16px;
    transition:.2s;
}

.profile-trigger:hover{
    background:#f8fafc;
}

.avatar{
    width:40px;
    height:40px;
    border-radius:12px;
    background:var(--gradient);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
}

.profile-info{
    line-height:1.2;
}

.profile-info strong{
    font-size:14px;
}

.profile-info span{
    font-size:12px;
    color:var(--muted);
}

.profile-dropdown{
    position:absolute;
    right:0;
    top:65px;
    width:220px;
    background:#fff;
    border-radius:18px;
    box-shadow:0 20px 40px rgba(15,23,42,.12);
    border:1px solid var(--border);
    overflow:hidden;
    display:none;
}

.profile-dropdown a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:15px 18px;
    text-decoration:none;
    color:#334155;
    font-weight:600;
    transition:.2s;
}

.profile-dropdown a:hover{
    background:#f8fafc;
    color:var(--primary);
}

.profile-dropdown.show{
    display:block;
}

.layout{
    display:flex;
    min-height:calc(100vh - 72px);
}

.sidebar{
    width:250px;
    background:#fff;
    border-right:1px solid var(--border);
    padding:20px 16px;
    position:sticky;
    top:72px;
    height:calc(100vh - 72px);
    display:flex;
    flex-direction:column;
    justify-content:space-between;
}

.sidebar-top{
    width:100%;
}

.parent-box{
    background:linear-gradient(135deg,#eef2ff,#f5f3ff);
    padding:18px;
    border-radius:20px;
    margin-bottom:22px;
    display:flex;
    align-items:center;
    gap:14px;
}

.parent-avatar{
    width:48px;
    height:48px;
    border-radius:16px;
    background:var(--gradient);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
}

.parent-box h4{
    margin:0;
    font-size:15px;
    font-weight:700;
}

.parent-box span{
    font-size:12px;
    color:var(--muted);
}

.nav-links{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.nav-links a{
    text-decoration:none;
    color:#475569;
    padding:14px 16px;
    border-radius:14px;
    display:flex;
    align-items:center;
    gap:12px;
    font-weight:600;
    transition:.2s;
}

.nav-links a:hover{
    background:#f5f3ff;
    color:var(--primary);
}

.nav-links a.active{
    background:var(--gradient);
    color:#fff;
}

.logout-btn{
    margin-top:20px;
}

.logout-btn a{
    display:flex;
    align-items:center;
    gap:12px;
    text-decoration:none;
    padding:14px 16px;
    border-radius:14px;
    background:#fff1f2;
    color:#e11d48;
    font-weight:700;
    transition:.2s;
}

.logout-btn a:hover{
    background:#ffe4e6;
}

.main{
    flex:1;
    padding:28px;
}

.hero{
    background:var(--gradient);
    border-radius:30px;
    padding:42px;
    color:#fff;
    margin-bottom:28px;
    position:relative;
    overflow:hidden;
}

.hero::before{
    content:'';
    position:absolute;
    width:320px;
    height:320px;
    border-radius:50%;
    background:rgba(255,255,255,.08);
    right:-100px;
    top:-100px;
}

.hero h1{
    font-size:42px;
    font-weight:800;
    margin-bottom:10px;
}

.hero p{
    max-width:650px;
    line-height:1.7;
    opacity:.95;
}

.hero-badge{
    display:inline-block;
    background:rgba(255,255,255,.15);
    padding:8px 16px;
    border-radius:999px;
    margin-bottom:18px;
    font-size:13px;
    font-weight:600;
}

.summary-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px;
    margin-bottom:28px;
}

.summary-card{
    background:#fff;
    border-radius:22px;
    padding:24px;
    border:1px solid var(--border);
    box-shadow:var(--shadow);
}

.summary-card .icon{
    width:54px;
    height:54px;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    margin-bottom:16px;
}

.bg-purple{
    background:#ede9fe;
    color:#6d28d9;
}

.bg-blue{
    background:#dbeafe;
    color:#2563eb;
}

.bg-green{
    background:#dcfce7;
    color:#16a34a;
}

.summary-card small{
    color:var(--muted);
    font-weight:600;
}

.summary-card h2{
    font-size:34px;
    margin-top:8px;
    font-weight:800;
}

.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(420px,1fr));
    gap:24px;
}

.child-card{
    background:#fff;
    border-radius:28px;
    border:1px solid var(--border);
    box-shadow:var(--shadow);
    overflow:hidden;
    transition:.25s;
}

.child-card:hover{
    transform:translateY(-4px);
}

.child-top{
    padding:26px;
    border-bottom:1px solid var(--border);
}

.child-header{
    display:flex;
    align-items:center;
    gap:16px;
}

.child-avatar{
    width:72px;
    height:72px;
    border-radius:22px;
    background:var(--gradient);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:26px;
    font-weight:800;
}

.child-name{
    font-size:24px;
    font-weight:800;
}

.child-id{
    color:var(--muted);
    margin-top:4px;
}

.status-badge{
    display:inline-block;
    margin-top:12px;
    padding:7px 14px;
    border-radius:999px;
    background:#dcfce7;
    color:#15803d;
    font-size:12px;
    font-weight:700;
}

.child-body{
    padding:26px;
}

.info-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    margin-bottom:24px;
}

.info-box{
    background:#f8fafc;
    border:1px solid #edf1f7;
    border-radius:18px;
    padding:18px;
}

.info-box h3{
    font-size:30px;
    font-weight:800;
    margin-bottom:6px;
}

.info-box span{
    color:var(--muted);
    font-size:14px;
}

.course-wrap h5{
    font-size:17px;
    font-weight:800;
    margin-bottom:16px;
}

.course-grid{
    display:grid;
    gap:14px;
}

.course-card{
    display:flex;
    align-items:center;
    gap:16px;
    padding:16px;
    border-radius:20px;
    background:#f8fafc;
    border:1px solid #edf1f7;
    transition:.2s;
}

.course-card:hover{
    background:#fff;
    border-color:#d9def0;
    transform:translateY(-2px);
}

.course-image{
    width:60px;
    height:60px;
    border-radius:16px;
    background:var(--gradient);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
    flex-shrink:0;
}

.course-content{
    flex:1;
}

.course-content h4{
    font-size:16px;
    font-weight:800;
    margin-bottom:4px;
}

.course-content p{
    margin:0;
    color:var(--muted);
    font-size:13px;
}

.course-arrow{
    width:42px;
    height:42px;
    border-radius:12px;
    background:#eef2ff;
    color:#4f46e5;
    display:flex;
    align-items:center;
    justify-content:center;
}

.view-btn{
    width:100%;
    border:none;
    border-radius:16px;
    background:var(--dark);
    color:#fff;
    padding:15px;
    margin-top:22px;
    font-size:15px;
    font-weight:700;
    transition:.2s;
}

.view-btn:hover{
    background:#111827;
    transform:translateY(-2px);
}

.footer-wrap{
    margin-top:42px;
    border-radius:28px;
    overflow:hidden;
    background:#fff;
    border:1px solid var(--border);
    box-shadow:var(--shadow);
}

.empty{
    background:#fff;
    border-radius:28px;
    padding:70px;
    text-align:center;
    box-shadow:var(--shadow);
}

@media(max-width:991px){

    .sidebar{
        display:none;
    }

    .main{
        padding:20px;
    }

    .grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:600px){

    .hero{
        padding:28px;
    }

    .hero h1{
        font-size:30px;
    }

    .info-grid{
        grid-template-columns:1fr;
    }

    .profile-info{
        display:none;
    }
}

</style>

</head>

<body>

<div class="topbar">

    <div class="brand">

        <div class="brand-icon">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>

        BaselineLearning

    </div>

    <div class="profile-menu">

        <div class="profile-trigger" id="profileToggle">

            <div class="avatar">
                <?php echo $parentInitial; ?>
            </div>

            <div class="profile-info">
                <strong><?php echo htmlspecialchars($parentName); ?></strong><br>
                <span>Parent Account</span>
            </div>

            <i class="fa-solid fa-chevron-down"></i>

        </div>

        <div class="profile-dropdown" id="profileDropdown">

            <a href="parent_dashboard.php">
                <i class="fa-solid fa-house"></i>
                Dashboard
            </a>

            <a href="settings.php">
                <i class="fa-solid fa-gear"></i>
                Settings
            </a>

            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>

        </div>

    </div>

</div>

<div class="layout">

    <aside class="sidebar">

        <div class="sidebar-top">

            <div class="parent-box">

                <div class="parent-avatar">
                    <?php echo $parentInitial; ?>
                </div>

                <div>
                    <h4><?php echo htmlspecialchars($parentName); ?></h4>
                    <span>Parent Dashboard</span>
                </div>

            </div>

            <div class="nav-links">

                <a href="parent_dashboard.php">
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>

                <a href="children.php" class="active">
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

            </div>

        </div>

        <div class="logout-btn">

            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>

        </div>

    </aside>

    <main class="main">

        <section class="hero">

            <div class="hero-badge">
                👋 Welcome Back
            </div>

            <h1>My Children</h1>

            <p>
                Monitor learning progress, purchased courses,
                performance insights and payment details in one premium dashboard.
            </p>

        </section>

        <div class="summary-grid">

            <div class="summary-card">

                <div class="icon bg-purple">
                    <i class="fa-solid fa-users"></i>
                </div>

                <small>Total Children</small>

                <h2><?php echo count($children); ?></h2>

            </div>

            <div class="summary-card">

                <div class="icon bg-blue">
                    <i class="fa-solid fa-book-open"></i>
                </div>

                <small>Total Purchased Courses</small>

                <h2>

                    <?php

                    $totalCourses = 0;

                    foreach($children as $c){
                        $totalCourses += $c['purchased_courses'];
                    }

                    echo $totalCourses;

                    ?>

                </h2>

            </div>

            <div class="summary-card">

                <div class="icon bg-green">
                    <i class="fa-solid fa-chart-line"></i>
                </div>

                <small>Average Quiz Score</small>

                <h2>

                    <?php

                    $avgQuiz = 0;

                    if(count($children) > 0){

                        $sum = 0;

                        foreach($children as $c){
                            $sum += $c['quiz_avg'];
                        }

                        $avgQuiz = round($sum / count($children));
                    }

                    echo $avgQuiz;

                    ?>%

                </h2>

            </div>

        </div>

        <div class="grid">

            <?php if(count($children) > 0): ?>

                <?php foreach($children as $child): ?>

                    <div class="child-card">

                        <div class="child-top">

                            <div class="child-header">

                                <div class="child-avatar">
                                    <?php echo strtoupper(substr($child['full_name'],0,1)); ?>
                                </div>

                                <div>

                                    <div class="child-name">
                                        <?php echo htmlspecialchars($child['full_name']); ?>
                                    </div>

                                    <div class="child-id">
                                        Student ID #<?php echo $child['id']; ?>
                                    </div>

                                    <div class="status-badge">
                                        <?php echo ucfirst($child['status']); ?>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="child-body">

                            <div class="info-grid">

                                <div class="info-box">

                                    <h3>
                                        <?php echo $child['purchased_courses']; ?>
                                    </h3>

                                    <span>Purchased Courses</span>

                                </div>

                                <div class="info-box">

                                    <h3>
                                        ₹<?php echo number_format($child['pending_amount']); ?>
                                    </h3>

                                    <span>Pending Amount</span>

                                </div>

                            </div>

                            <div class="course-wrap">

                                <h5>Purchased Courses</h5>

                                <div class="course-grid">

                                    <?php if(count($child['courses_list']) > 0): ?>

                                        <?php foreach($child['courses_list'] as $course): ?>

                                            <?php
                                            $icon = "fa-code";

                                            if(stripos($course, 'php') !== false){
                                                $icon = "fa-php";
                                            }

                                            if(stripos($course, 'javascript') !== false){
                                                $icon = "fa-js";
                                            }

                                            if(stripos($course, 'python') !== false){
                                                $icon = "fa-python";
                                            }

                                            if(stripos($course, 'react') !== false){
                                                $icon = "fa-react";
                                            }
                                            ?>

                                            <div class="course-card">

                                                <div class="course-image">
                                                    <i class="fa-brands <?php echo $icon; ?>"></i>
                                                </div>

                                                <div class="course-content">

                                                    <h4>
                                                        <?php echo htmlspecialchars($course); ?>
                                                    </h4>

                                                    <p>
                                                        Interactive Learning Course
                                                    </p>

                                                </div>

                                                <div class="course-arrow">
                                                    <i class="fa-solid fa-arrow-right"></i>
                                                </div>

                                            </div>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <div class="course-card">

                                            <div class="course-image">
                                                <i class="fa-solid fa-book"></i>
                                            </div>

                                            <div class="course-content">

                                                <h4>No Courses Yet</h4>

                                                <p>
                                                    No purchased courses found
                                                </p>

                                            </div>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <button
                                class="view-btn"
                                onclick="switchStudent(<?php echo $child['id']; ?>)"
                            >

                                <i class="fa-solid fa-chart-line"></i>
                                View Dashboard

                            </button>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty">

                    <i class="fa-solid fa-user-slash"></i>

                    <h2>No Children Found</h2>

                    <p style="margin-top:10px;color:#64748b;">
                        No student accounts are linked with your parent email.
                    </p>

                </div>

            <?php endif; ?>

        </div>

        <div class="footer-wrap">
            <?php include('assets/footer.php'); ?>
        </div>

    </main>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script>

function switchStudent(id)
{
    $.ajax({
        url:'handlers/set_session.php',
        type:'POST',
        data:{student_id:id},
        success:function(){
            window.location.href='parent_dashboard.php';
        }
    });
}

const profileToggle = document.getElementById('profileToggle');
const profileDropdown = document.getElementById('profileDropdown');

profileToggle.addEventListener('click', function(e){
    e.stopPropagation();
    profileDropdown.classList.toggle('show');
});

document.addEventListener('click', function(){
    profileDropdown.classList.remove('show');
});

</script>

</body>
</html>
