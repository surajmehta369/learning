`<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('STUDENT_SESSION');
    session_start();
}

include("conn.php");

// Authentication Check
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'parent'
) {
    header("Location: login/login.php");
    exit;
}

$parentEmail = $_SESSION['user_email'] ?? '';

// Fetch Parent Info
$stmt = $conn->prepare("SELECT full_name FROM signup WHERE email=? LIMIT 1");
$stmt->bind_param("s", $parentEmail);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();
$parentName = $parent['full_name'] ?? 'Parent';

// Fetch Quiz Results
$sql = "
SELECT 
    s.id,
    s.full_name,
    s.parent_email,
    qr.score,
    qr.total_questions,
    qr.percentage,
    qr.completed_at
FROM signup s
INNER JOIN quiz_results qr ON s.id = qr.user_id
WHERE s.role='student'
ORDER BY qr.percentage DESC, qr.completed_at ASC
";

$result = mysqli_query($conn, $sql);

$students = [];
$rank = 1;

while ($row = mysqli_fetch_assoc($result)) {

    $isMine = ($row['parent_email'] == $parentEmail);

    $students[] = [
        'rank'       => $rank,
        'name'       => $row['full_name'],
        'score'      => $row['score'],
        'total'      => $row['total_questions'],
        'percentage' => round($row['percentage']),
        'date'       => date("M d, Y", strtotime($row['completed_at'])),
        'mine'       => $isMine
    ];

    $rank++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Baseline Learning | Quiz Leaderboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            min-height: calc(100vh - 220px);
        }

        .topbar {
            background: #fff;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .page-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: none;
        }

        /* Rank Badges */
        .rank-badge {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            background: #e9ecef;
            color: #495057;
        }

        .top1 {
            background: #FFD700;
            color: #000;
            box-shadow: 0 0 10px rgba(255,215,0,0.5);
        }

        .top2 {
            background: #C0C0C0;
            color: #000;
        }

        .top3 {
            background: #CD7F32;
            color: #fff;
        }

        /* Highlight Student */
        .highlight-row {
            background-color: #f8fbff !important;
            border-left: 5px solid #0d6efd;
        }

        .child-label {
            background: #0d6efd;
            color: #fff;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
            text-transform: uppercase;
        }

        .progress {
            height: 8px;
            border-radius: 10px;
            background: #eee;
        }

        .table thead th {
            border-top: none;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
        }

        @media (max-width: 768px) {

            .topbar {
                padding: 15px;
            }

            .page-card {
                padding: 20px;
            }

            .table {
                min-width: 700px;
            }
        }

    </style>

</head>

<body>

    <!-- Topbar -->
    <div class="topbar d-flex justify-content-between align-items-center">

        <div>
            <h4 class="mb-0 text-primary">📊 Quiz Performance</h4>

            <p class="text-muted mb-0 small">
                Viewing stats for all participants
            </p>
        </div>

        <a href="parent_dashboard.php" class="btn btn-sm btn-outline-secondary">
            Back to Dashboard
        </a>

    </div>

    <!-- Main Content -->
    <div class="main-content">

        <div class="container py-5">

            <div class="page-card">

                <div class="row mb-4 align-items-center">

                    <div class="col-md-6">
                        <h5 class="fw-bold mb-0">
                            Class Leaderboard
                        </h5>
                    </div>

                    <div class="col-md-6 text-end">
                        <span class="badge bg-light text-dark border p-2">
                            Total Attempts: <?php echo count($students); ?>
                        </span>
                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead>
                            <tr>
                                <th width="10%">Rank</th>
                                <th width="30%">Student Name</th>
                                <th width="15%">Raw Score</th>
                                <th width="30%">Accuracy</th>
                                <th width="15%">Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (empty($students)): ?>

                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No quiz results found.
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach($students as $s): ?>

                                    <tr class="<?php echo $s['mine'] ? 'highlight-row' : ''; ?>">

                                        <td>

                                            <?php 
                                                $rCls = '';

                                                if($s['rank'] == 1) {
                                                    $rCls = 'top1';
                                                } elseif($s['rank'] == 2) {
                                                    $rCls = 'top2';
                                                } elseif($s['rank'] == 3) {
                                                    $rCls = 'top3';
                                                }
                                            ?>

                                            <div class="rank-badge <?php echo $rCls; ?>">
                                                <?php echo $s['rank']; ?>
                                            </div>

                                        </td>

                                        <td>

                                            <span class="fw-bold">
                                                <?php echo htmlspecialchars($s['name']); ?>
                                            </span>

                                            <?php if($s['mine']): ?>
                                                <span class="child-label ms-2">
                                                    Your Child
                                                </span>
                                            <?php endif; ?>

                                            <br>

                                            <small class="text-muted">
                                                Completed: <?php echo $s['date']; ?>
                                            </small>

                                        </td>

                                        <td>

                                            <span class="badge bg-light text-dark border">
                                                <?php echo $s['score']; ?> / <?php echo $s['total']; ?>
                                            </span>

                                        </td>

                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="progress flex-grow-1 me-2">

                                                    <div
                                                        class="progress-bar <?php echo ($s['percentage'] >= 70) ? 'bg-success' : 'bg-warning'; ?>"
                                                        role="progressbar"
                                                        style="width: <?php echo $s['percentage']; ?>%">
                                                    </div>

                                                </div>

                                                <span class="fw-bold small">
                                                    <?php echo $s['percentage']; ?>%
                                                </span>

                                            </div>

                                        </td>

                                        <td>

                                            <?php if($s['percentage'] >= 90): ?>

                                                <span class="text-success small fw-bold">
                                                    Mastery
                                                </span>

                                            <?php elseif($s['percentage'] >= 70): ?>

                                                <span class="text-primary small fw-bold">
                                                    Proficient
                                                </span>

                                            <?php else: ?>

                                                <span class="text-muted small fw-bold">
                                                    Developing
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <!-- Footer -->
    <?php include('assets/footer.php'); ?>

</body>
</html>