<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('STUDENT_SESSION');
    session_start();
}
include("conn.php");

/** * CUMULATIVE QUERY:
 * Instead of showing the best single attempt, we SUM everything.
 * This ensures that someone with multiple successful quizzes ranks higher 
 * based on their total knowledge gain.
 */
$lb_query = "SELECT 
                u.full_name, 
                u.image, 
                SUM(r.score) as total_earned_score,
                SUM(r.total_questions) as total_possible_questions,
                (SUM(r.score) / SUM(r.total_questions) * 100) as overall_percentage
             FROM quiz_results r 
             JOIN signup u ON r.user_id = u.id 
             GROUP BY r.user_id
             ORDER BY overall_percentage DESC, total_earned_score DESC 
             LIMIT 10";

$lb_res = $conn->query($lb_query);
?>

<div class="modal-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-uppercase small fw-bold text-muted">
                <tr>
                    <th class="ps-4 py-3" style="width: 20%">Rank</th>
                    <th class="py-3" style="width: 50%">Student</th>
                    <th class="text-center py-3" style="width: 30%">Overall Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                while($row = $lb_res->fetch_assoc()): 
                    // Highlight the current logged-in user
                    $is_me = (isset($_SESSION['full_name']) && $row['full_name'] === $_SESSION['full_name']) ? 'table-primary border-start border-4 border-primary' : '';
                    
                    $row_style = "";
                    if($rank == 1) $row_style = "background: rgba(255, 193, 7, 0.05);";
                ?>
                <tr class="<?= $is_me ?>" style="<?= $row_style ?>">
                    <td class="ps-4">
                        <?php if($rank == 1): ?>
                            <span class="fs-4">🥇</span>
                        <?php elseif($rank == 2): ?>
                            <span class="fs-4">🥈</span>
                        <?php elseif($rank == 3): ?>
                            <span class="fs-4">🥉</span>
                        <?php else: ?>
                            <span class="fw-bold text-muted ms-2">#<?= $rank ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['full_name']) ?>&background=random&color=fff&bold=true" 
                                 class="rounded-circle me-3 shadow-sm border border-2 border-white" width="42" height="42">
                            <div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['full_name']) ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <i class="fas fa-certificate text-info me-1"></i>Verified Learner
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center px-3 py-1 rounded-pill shadow-sm bg-white border">
                            <span class="fw-bold text-dark me-2 small">
                                <?= $row['total_earned_score'] ?>/<?= $row['total_possible_questions'] ?>
                            </span>
                            <span class="badge rounded-pill bg-purple text-white">
                                <?= round($row['overall_percentage']) ?>%
                            </span>
                        </div>
                    </td>
                </tr>
                <?php $rank++; endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-footer border-0 bg-light d-flex justify-content-between p-3">
    <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Close</button>
    <button type="button" class="btn btn-purple fw-bold px-4" onclick="openWeeklyQuiz()" data-bs-dismiss="modal">
        <i class="fas fa-bolt me-2"></i>Weekly Challenge
    </button>
</div>

<style>
    .bg-purple { background: #6f42c1; }
    .btn-purple { 
        background: #6f42c1; 
        color: white; 
        border: none; 
        transition: all 0.3s ease;
        border-radius: 10px;
        padding: 10px 20px;
    }
    .btn-purple:hover { 
        background: #5a35a3; 
        color: white; 
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
    }
    .table-hover tbody tr:hover {
        background-color: rgba(111, 66, 193, 0.03) !important;
    }
    .spinner-purple {
        color: #6f42c1 !important;
    }
</style>