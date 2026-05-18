<?php
include("../conn.php");

$analytics_sql = "SELECT s.id, s.full_name, COUNT(DISTINCT cv.id) AS total_videos, COUNT(DISTINCT vp.video_id) AS completed_videos FROM signup s 
                INNER JOIN baseline_User_Cart c ON s.id = c.user_id AND c.payment_mode = 'success' 
                LEFT JOIN course_videos cv ON cv.course_id = c.course_id 
                LEFT JOIN video_progress vp ON vp.user_id = s.id AND vp.video_id = cv.id WHERE s.role = 'student' 
                GROUP BY s.id, s.full_name ORDER BY completed_videos DESC";

$result = mysqli_query($conn, $analytics_sql);

$total_students = 0;
$total_progress_sum = 0;

$performance_counts = [
    "Poor" => 0,
    "Below Average" => 0,
    "Average" => 0,
    "Good" => 0,
    "Great" => 0,
    "Excellent" => 0
];

$students = [];

while ($row = mysqli_fetch_assoc($result)) {
    $total_students++;

    $percentage = ($row['total_videos'] > 0)
        ? round(($row['completed_videos'] / $row['total_videos']) * 100)
        : 0;

    $total_progress_sum += $percentage;

    if ($percentage < 20) {
        $label = "Poor";
        $color = "#dc3545";
    } elseif ($percentage < 40) {
        $label = "Below Average";
        $color = "#fd7e14";
    } elseif ($percentage < 60) {
        $label = "Average";
        $color = "#0dcaf0";
    } elseif ($percentage < 75) {
        $label = "Good";
        $color = "#0d6efd";
    } elseif ($percentage < 90) {
        $label = "Great";
        $color = "#198754";
    } else {
        $label = "Excellent";
        $color = "#6f42c1";
    }

    $performance_counts[$label]++;

    $students[] = [
        "name" => $row['full_name'],
        "completed" => $row['completed_videos'],
        "total" => $row['total_videos'],
        "percentage" => $percentage,
        "label" => $label,
        "color" => $color
    ];
}

$avg_progress = ($total_students > 0)
    ? round($total_progress_sum / $total_students)
    : 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Student Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chart-bar {
            height: 22px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
        }

        .chart-fill {
            height: 100%;
            text-align: right;
            padding-right: 8px;
            color: white;
            font-size: 12px;
            line-height: 22px;
            transition: width 1s ease-in-out;
        }

        .card-stat {
            border-left: 5px solid #0d6efd;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-4">

        <h2 class="mb-4">📊 Student Performance Dashboard</h2>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card card-stat shadow-sm">
                    <div class="card-body">
                        <h6>Total Active Students</h6>
                        <h3><?= $total_students ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-stat shadow-sm">
                    <div class="card-body">
                        <h6>Average Progress</h6>
                        <h3><?= $avg_progress ?>%</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-stat shadow-sm">
                    <div class="card-body">
                        <h6>Excellent Performers</h6>
                        <h3><?= $performance_counts['Excellent'] ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-middle">
                <span>Student Detailed Progress</span>
                <span class="badge bg-secondary"><?= count($students) ?> Students Listed</span>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student Name</th>
                                <th>Completed Videos</th>
                                <th>Progress</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No student progress data available.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['name']) ?></td>
                                        <td><?= $s['completed'] ?> / <?= $s['total'] ?></td>
                                        <td style="width:40%;">
                                            <div class="chart-bar">
                                                <div class="chart-fill"
                                                    style="width: <?= $s['percentage'] ?>%; 
                                                        background: <?= $s['color'] ?>;">
                                                    <?= $s['percentage'] ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: <?= $s['color'] ?>;">
                                                <?= $s['label'] ?>
                                            </span>
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

</body>

</html>