<?php
session_start();
include("conn.php");
if (
    !isset($_SESSION['user_email']) ||
    !isset($_SESSION['user_role']) ||
    strtolower($_SESSION['user_role']) != 'parent'
) {
    header("Location: login/login.php");
    exit;
}

$parentEmail = $_SESSION['user_email'];

$parentName = "Parent";

$qParent = mysqli_query($conn, "
    SELECT full_name 
    FROM signup 
    WHERE email='$parentEmail' 
    LIMIT 1
");

if ($qParent && mysqli_num_rows($qParent) > 0) {
    $rowParent = mysqli_fetch_assoc($qParent);
    $parentName = $rowParent['full_name'];
}
$sql = "
SELECT 
    s.id,
    s.full_name,
    s.status,

    (
        SELECT COUNT(*) 
        FROM baseline_User_Cart c
        WHERE c.user_id = s.id
        AND c.payment_mode='success'
    ) as total_courses,

    (
        SELECT IFNULL(AVG(sp.completed_percent),0)
        FROM student_progress sp
        WHERE sp.student_id = s.id
    ) as progress_avg,

    (
        SELECT IFNULL(AVG(q.percentage),0)
        FROM quiz_results q
        WHERE q.user_id = s.id
    ) as quiz_avg

FROM signup s
WHERE s.parent_email='$parentEmail'
AND s.role='student'
ORDER BY s.id DESC
";

$result = mysqli_query($conn, $sql);

$children = [];
$totalChildren = 0;
$totalProgress = 0;
$totalCourses = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $children[] = $row;
    $totalChildren++;
    $totalProgress += $row['progress_avg'];
    $totalCourses += $row['total_courses'];
}

$avgProgress = ($totalChildren > 0) ? round($totalProgress / $totalChildren) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Children Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
    background:#f5f7fb;
    font-family:Segoe UI, sans-serif;
}

.topbar{
    background:#ffffff;
    padding:15px 25px;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
}

.brand{
    font-size:24px;
    font-weight:700;
    color:#6c4df6;
}

.brand span{
    color:#111;
}

.page-title{
    font-size:14px;
    color:#777;
}

.card-box{
    background:#fff;
    border-radius:18px;
    padding:22px;
    box-shadow:0 8px 24px rgba(0,0,0,.05);
    transition:0.3s;
    height:100%;
}

.card-box:hover{
    transform:translateY(-5px);
}

.stat-num{
    font-size:30px;
    font-weight:700;
    color:#6c4df6;
}

.stat-text{
    color:#777;
    font-size:14px;
}

.section-title{
    font-size:24px;
    font-weight:700;
    margin-bottom:20px;
}

.child-card{
    background:#fff;
    border-radius:18px;
    padding:25px;
    box-shadow:0 10px 28px rgba(0,0,0,.05);
    transition:0.3s;
    height:100%;
}

.child-card:hover{
    transform:translateY(-6px);
}

.avatar{
    width:70px;
    height:70px;
    border-radius:50%;
    background:linear-gradient(135deg,#6c4df6,#3b82f6);
    color:#fff;
    font-size:28px;
    font-weight:700;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:auto;
}

.progress{
    height:8px;
    border-radius:20px;
}

.btn-main{
    background:linear-gradient(135deg,#6c4df6,#3b82f6);
    color:#fff;
    border:none;
    border-radius:10px;
    padding:10px;
    width:100%;
    font-weight:600;
}

.btn-main:hover{
    color:#fff;
    opacity:.95;
}

.chart-box{
    background:#fff;
    border-radius:18px;
    padding:25px;
    box-shadow:0 8px 24px rgba(0,0,0,.05);
}
</style>
</head>

<body>

<div class="topbar d-flex justify-content-between align-items-center">
    <div>
        <div class="brand">Baseline<span>Learning</span></div>
        <div class="page-title">Parent Dashboard / My Children</div>
    </div>

    <div>
        <a href="parent_dashboard.php" class="btn btn-primary">← Dashboard</a>
    </div>
</div>

<div class="container py-4">

    <div class="mb-4">
        <h2 class="fw-bold">My Children</h2>
        <p class="text-muted mb-0">Welcome back, <?php echo $parentName; ?></p>
    </div>

    <div class="row g-4 mb-4">

        <div class="col-md-4">
            <div class="card-box text-center">
                <div class="stat-num"><?php echo $totalChildren; ?></div>
                <div class="stat-text">Total Children</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-box text-center">
                <div class="stat-num"><?php echo $avgProgress; ?>%</div>
                <div class="stat-text">Average Progress</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-box text-center">
                <div class="stat-num"><?php echo $totalCourses; ?></div>
                <div class="stat-text">Purchased Courses</div>
            </div>
        </div>

    </div>

    <div class="mb-4">
        <div class="section-title">Linked Students</div>

        <div class="row g-4">

        <?php if(count($children)>0){ ?>
            <?php foreach($children as $child){ ?>

            <div class="col-md-6 col-lg-4">
                <div class="child-card">

                    <div class="avatar mb-3">
                        <?php echo strtoupper(substr($child['full_name'],0,1)); ?>
                    </div>

                    <h5 class="text-center fw-bold mb-1">
                        <?php echo $child['full_name']; ?>
                    </h5>

                    <p class="text-center text-muted small mb-4">
                        Student ID #<?php echo $child['id']; ?>
                    </p>

                    <div class="mb-2 d-flex justify-content-between">
                        <small>Course Progress</small>
                        <small><?php echo round($child['progress_avg']); ?>%</small>
                    </div>

                    <div class="progress mb-3">
                        <div class="progress-bar bg-primary"
                             style="width:<?php echo round($child['progress_avg']); ?>%">
                        </div>
                    </div>

                    <div class="mb-2 d-flex justify-content-between">
                        <small>Quiz Average</small>
                        <small><?php echo round($child['quiz_avg']); ?>%</small>
                    </div>

                    <div class="progress mb-3">
                        <div class="progress-bar bg-success"
                             style="width:<?php echo round($child['quiz_avg']); ?>%">
                        </div>
                    </div>

                    <div class="row text-center mb-3">
                        <div class="col">
                            <strong><?php echo $child['total_courses']; ?></strong><br>
                            <small class="text-muted">Courses</small>
                        </div>

                        <div class="col">
                            <strong><?php echo ucfirst($child['status']); ?></strong><br>
                            <small class="text-muted">Status</small>
                        </div>
                    </div>

                    <a href="parent_dashboard.php?student=<?php echo $child['id']; ?>" class="btn btn-main">
                        View Dashboard
                    </a>

                </div>
            </div>

            <?php } ?>
        <?php } else { ?>

            <div class="col-12">
                <div class="alert alert-warning">
                    No linked students found.
                </div>
            </div>

        <?php } ?>

        </div>
    </div>

    <div class="chart-box">
        <h5 class="fw-bold mb-4">Children Progress Analytics</h5>
        <canvas id="progressChart" height="110"></canvas>
    </div>

</div>

<script>
const labels = [
<?php foreach($children as $child){ ?>
'<?php echo $child['full_name']; ?>',
<?php } ?>
];

const progressData = [
<?php foreach($children as $child){ ?>
<?php echo round($child['progress_avg']); ?>,
<?php } ?>
];

new Chart(document.getElementById('progressChart'), {
    type:'bar',
    data:{
        labels:labels,
        datasets:[{
            label:'Progress %',
            data:progressData,
            borderRadius:8
        }]
    },
    options:{
        responsive:true,
        plugins:{
            legend:{display:false}
        },
        scales:{
            y:{
                beginAtZero:true,
                max:100
            }
        }
    }
});
</script>

</body>
</html>