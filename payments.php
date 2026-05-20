<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name("STUDENT_SESSION");
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

$stmt = $conn->prepare("
SELECT full_name 
FROM signup 
WHERE email=? 
LIMIT 1
");

$stmt->bind_param("s", $parentEmail);
$stmt->execute();

$p = $stmt->get_result()->fetch_assoc();

$parentName = $p['full_name'] ?? 'Parent';

$kids = [];

$q = $conn->prepare("
SELECT id, full_name 
FROM signup 
WHERE role='student'
AND parent_email=?
");

$q->bind_param("s", $parentEmail);
$q->execute();

$res = $q->get_result();

while($r = $res->fetch_assoc()){
    $kids[] = $r;
}

$payments = [];
$pendingCourses = [];
$paidCourses = [];

$totalPaid = 0;
$totalPending = 0;
$totalCourses = 0;

if(!empty($kids)){

    $ids = array_column($kids,'id');
    $idList = implode(",", $ids);

    $sql = "
    SELECT 
        baseline_User_Cart.*,
        signup.full_name

    FROM baseline_User_Cart

    LEFT JOIN signup
    ON signup.id = baseline_User_Cart.user_id

    WHERE baseline_User_Cart.user_id IN ($idList)

    ORDER BY baseline_User_Cart.added_on DESC
    ";

    $result = mysqli_query($conn,$sql);

    while($row = mysqli_fetch_assoc($result)){

        $payments[] = $row;

        $totalCourses++;

        if($row['payment_mode']=="success"){

            $paidCourses[] = $row;

            $totalPaid += $row['course_price'];

        }else{

            $pendingCourses[] = $row;

            $totalPending += $row['course_price'];
        }
    }
}

$totalAmount = $totalPaid + $totalPending;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Payments Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

html,
body{
    margin:0;
    padding:0;
}

body{
    background:#f4f7fc;
    font-family:Arial, Helvetica, sans-serif;
}

.topbar{
    background:#fff;
    padding:18px 30px;
    box-shadow:0 2px 10px rgba(0,0,0,.06);

    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:15px;
}

.topbar h4{
    margin:0;
    font-weight:700;
}

.topbar small{
    color:#6b7280;
}

.card-box{
    background:#fff;
    border-radius:22px;
    padding:22px;
    box-shadow:0 8px 25px rgba(0,0,0,.05);
    height:100%;
}

.big{
    font-size:30px;
    font-weight:700;
    margin-top:8px;
}

.summary-icon{
    width:52px;
    height:52px;
    border-radius:16px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom:14px;
    font-size:20px;
}

.bg-purple{
    background:#ede9fe;
    color:#7c3aed;
}

.bg-green{
    background:#dcfce7;
    color:#16a34a;
}

.bg-red{
    background:#fee2e2;
    color:#dc2626;
}

.bg-blue{
    background:#dbeafe;
    color:#2563eb;
}

.section-title{
    font-size:24px;
    font-weight:700;
    margin-bottom:20px;
    color:#111827;
}

.payment-card{
    background:#fff;
    border-radius:24px;
    padding:18px;
    box-shadow:0 8px 25px rgba(0,0,0,.05);
    height:100%;
    transition:0.25s;
}

.payment-card:hover{
    transform:translateY(-4px);
}

.payment-image{
    width:100%;
    height:200px;
    object-fit:cover;
    border-radius:18px;
    margin-bottom:18px;
}

.student-badge{
    display:inline-block;
    background:#eef2ff;
    color:#4338ca;
    padding:6px 12px;
    border-radius:30px;
    font-size:12px;
    font-weight:600;
    margin-bottom:14px;
}

.course-title{
    font-size:22px;
    font-weight:700;
    margin-bottom:10px;
    color:#111827;
}

.price{
    font-size:28px;
    font-weight:700;
    margin-bottom:12px;
}

.course-date{
    color:#6b7280;
    font-size:14px;
    margin-bottom:15px;
}

.pending-badge{
    display:inline-block;
    background:#fee2e2;
    color:#dc2626;
    padding:7px 14px;
    border-radius:30px;
    font-size:12px;
    font-weight:700;
}

.success-badge{
    display:inline-block;
    background:#dcfce7;
    color:#16a34a;
    padding:7px 14px;
    border-radius:30px;
    font-size:12px;
    font-weight:700;
}

.pay-btn{
    width:100%;
    border:none;
    margin-top:18px;
    background:linear-gradient(135deg,#7c3aed,#2563eb);
    color:#fff;
    padding:14px;
    border-radius:16px;
    font-size:15px;
    font-weight:700;
    transition:0.25s;
}

.pay-btn:hover{
    opacity:.95;
    color:#fff;
}

.back-btn{
    text-decoration:none;
}

.empty-box{
    background:#fff;
    padding:60px 20px;
    text-align:center;
    border-radius:24px;
}

.empty-box i{
    font-size:52px;
    color:#cbd5e1;
    margin-bottom:14px;
}

@media(max-width:768px){

    .topbar{
        padding:18px;
    }

    .section-title{
        font-size:20px;
    }

    .course-title{
        font-size:20px;
    }

}

</style>

</head>

<body>

<div class="topbar">

    <div>

        <h4>
            💳 Payments Dashboard
        </h4>

        <small>
            Welcome <?php echo htmlspecialchars($parentName); ?>
        </small>

    </div>

    <a href="parent_dashboard.php"
    class="btn btn-outline-primary btn-sm back-btn">

        ← Back Dashboard

    </a>

</div>

<div class="container py-4">

<div class="row g-4 mb-5">

    <div class="col-lg-3 col-md-6">

        <div class="card-box">

            <div class="summary-icon bg-purple">
                <i class="fa-solid fa-book"></i>
            </div>

            <small class="text-muted">
                Total Courses
            </small>

            <div class="big">
                <?php echo $totalCourses; ?>
            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card-box">

            <div class="summary-icon bg-green">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <small class="text-muted">
                Total Paid
            </small>

            <div class="big text-success">
                ₹<?php echo number_format($totalPaid); ?>
            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card-box">

            <div class="summary-icon bg-red">
                <i class="fa-solid fa-clock"></i>
            </div>

            <small class="text-muted">
                Pending Amount
            </small>

            <div class="big text-danger">
                ₹<?php echo number_format($totalPending); ?>
            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card-box">

            <div class="summary-icon bg-blue">
                <i class="fa-solid fa-wallet"></i>
            </div>

            <small class="text-muted">
                Total Value
            </small>

            <div class="big text-primary">
                ₹<?php echo number_format($totalAmount); ?>
            </div>

        </div>

    </div>

</div>

<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="section-title">
        Pending Payments
    </div>

    <div class="text-muted">
        <?php echo count($pendingCourses); ?> Pending
    </div>

</div>

<?php if(empty($pendingCourses)): ?>

<div class="empty-box mb-5">

    <i class="fa-solid fa-circle-check"></i>

    <h4>No Pending Payments</h4>

    <p class="text-muted mt-2">
        All courses are already paid.
    </p>

</div>

<?php else: ?>

<div class="row g-4 mb-5">

<?php foreach($pendingCourses as $row): ?>

<div class="col-lg-4 col-md-6">

<div class="payment-card">

    <img
    src="<?php echo htmlspecialchars($row['course_image']); ?>"
    class="payment-image">

    <div class="student-badge">

        👨‍🎓 <?php echo htmlspecialchars($row['full_name']); ?>

    </div>

    <div class="course-title">

        <?php echo htmlspecialchars($row['course_title']); ?>

    </div>

    <div class="price text-danger">

        ₹<?php echo number_format($row['course_price']); ?>

    </div>

    <div class="course-date">

        Added on
        <?php echo date("d M Y", strtotime($row['added_on'])); ?>

    </div>

    <div class="pending-badge">

        Pending Payment

    </div>

    <a href="checkout.php?student_id=<?php echo $row['user_id']; ?>&parent=1"
   class="pay-btn d-flex align-items-center justify-content-center text-decoration-none">

        <i class="fa-solid fa-credit-card me-2"></i>
        Proceed To Checkout

    </a>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="section-title">
        Purchased Courses
    </div>

    <div class="text-muted">
        <?php echo count($paidCourses); ?> Purchased
    </div>

</div>

<?php if(empty($paidCourses)): ?>

<div class="empty-box">

    <i class="fa-solid fa-book"></i>

    <h4>No Purchased Courses</h4>

    <p class="text-muted mt-2">
        No successful payments found yet.
    </p>

</div>

<?php else: ?>

<div class="row g-4">

<?php foreach($paidCourses as $row): ?>

<div class="col-lg-4 col-md-6">

<div class="payment-card">

    <img
    src="<?php echo htmlspecialchars($row['course_image']); ?>"
    class="payment-image">

    <div class="student-badge">

        👨‍🎓 <?php echo htmlspecialchars($row['full_name']); ?>

    </div>

    <div class="course-title">

        <?php echo htmlspecialchars($row['course_title']); ?>

    </div>

    <div class="price text-success">

        ₹<?php echo number_format($row['course_price']); ?>

    </div>

    <div class="course-date">

        Purchased on
        <?php echo date("d M Y", strtotime($row['added_on'])); ?>

    </div>

    <div class="success-badge">

        Payment Successful

    </div>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

</div>

<?php include('assets/footer.php'); ?>

<?php if(isset($_GET['payment']) && $_GET['payment']=="success"): ?>

<script>

window.history.replaceState({}, document.title, window.location.pathname);

Swal.fire({
    icon: 'success',
    title: 'Payment Successful',
    text: 'Course has been activated successfully.',
    confirmButtonColor: '#7c3aed'
});

</script>

<?php endif; ?>

</body>
</html>
