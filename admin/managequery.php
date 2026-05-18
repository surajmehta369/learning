<?php
include("../conn.php");

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM Baseline_contactus WHERE id = $id");
    echo "<script>
        alert('Record deleted successfully!');
        window.location.href = '../adminpage.php';
    </script>";
    exit;
}

$result = $conn->query("SELECT * FROM Baseline_contactus ORDER BY created_at DESC");
?>

<div class="container mt-4">
    <h3 class="mb-3">📩 Manage Queries & Complaints</h3>

    <table class="table table-striped table-bordered" id="queryTable">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Type</th>
                <th>Message</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <?= !empty($row['query']) ? '<span class="badge bg-info">Query</span>' : '<span class="badge bg-danger">Complaint</span>' ?>
                    </td>
                    <td><?= htmlspecialchars($row['query'] ?: $row['complaint']) ?></td>
                    <td><?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></td>
                    <td>
                        <a href="admin/managequery.php?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this record?');">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
    if (!$.fn.DataTable.isDataTable('#queryTable')) {
        $('#queryTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "columnDefs": [{
                "orderable": false,
                "targets": [6]
            }]
        });
    }
</script>