<?php
include("../conn.php");

$sql = "SELECT r.*, s.full_name, q.quiz_title 
        FROM quiz_results r
        JOIN signup s ON r.user_id = s.id
        JOIN quizzes q ON r.quiz_id = q.id
        ORDER BY r.completed_at DESC";

$result = mysqli_query($conn, $sql);

$submissions = [];
$total_score = 0;
$count = 0;
$top_performer = null;

while ($row = mysqli_fetch_assoc($result)) {
    $submissions[] = $row;
    $total_score += $row['percentage'];
    $count++;
    
    // Logic to find the highest percentage holder
    if ($top_performer === null || $row['percentage'] > $top_performer['percentage']) {
        $top_performer = $row;
    }
}

$avg_score = ($count > 0) ? round($total_score / $count) : 0;
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<div class="container-fluid p-4">

    <h2 class="mb-4">📋 Student Quiz Results</h2>

    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100" style="border-left:5px solid #0d6efd !important;">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold text-uppercase">Total Submissions</h6>
                    <h3 class="fw-bold mb-0"><?= $count ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100" style="border-left:5px solid #198754 !important;">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold text-uppercase">Average Quiz Score</h6>
                    <h3 class="fw-bold mb-0"><?= $avg_score ?>%</h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100" style="border-left:5px solid #ffc107 !important; background: linear-gradient(to right, #ffffff, #fffdf5);">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted small fw-bold text-uppercase">Top Performer</h6>
                        <?php if ($top_performer): ?>
                            <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($top_performer['full_name']) ?></h5>
                            <small class="text-warning fw-bold">
                                <i class="fas fa-star me-1"></i><?= $top_performer['percentage'] ?>% Score
                            </small>
                        <?php else: ?>
                            <h5 class="text-muted mb-0">No Data</h5>
                        <?php endif; ?>
                    </div>
                    <div class="ms-3 text-warning opacity-50">
                        <i class="fas fa-trophy fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white fw-bold py-3">
            <i class="fas fa-list me-2"></i> Student Detailed Quiz Results
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Student Name</th>
                            <th>Quiz Name</th>
                            <th>Score</th>
                            <th style="width:30%;">Progress</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($submissions)): ?>
                            <?php foreach($submissions as $s): ?>
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <?= htmlspecialchars($s['full_name']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($s['quiz_title']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= $s['score'] ?> / <?= $s['total_questions'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $p = $s['percentage'];
                                            $color = ($p >= 75) ? '#198754' : (($p >= 40) ? '#0dcaf0' : '#dc3545');
                                        ?>
                                        <div style="height:22px;background:#f0f0f0;border-radius:20px;overflow:hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
                                            <div style="height:100%; width: <?= $p ?>%; background: <?= $color ?>; text-align:right; padding-right:8px; color:white; font-size:11px; line-height:22px; transition: width 0.5s ease;">
                                                <?= $p ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted small">
                                        <?= date('M d, Y', strtotime($s['completed_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="50" class="opacity-25 mb-3"><br>
                                    <span class="text-muted">No submissions found.</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>