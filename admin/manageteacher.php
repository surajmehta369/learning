<?php
include("../conn.php");

// Handle AJAX requests for approve.............
if (isset($_POST['action'])) {
    $response = ['status' => 'error'];


    if ($_POST['action'] === 'save_teacher') {
        $id = $_POST['teacher_id'] ?? '';
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $subscribe = isset($_POST['subscribe']) ? 1 : 0;
        $role = 'teacher';
        $status = ($id) ? $_POST['status'] : 'pending';

        if ($id) {
            // Updating the teacher
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE signup SET full_name=?, email=?, subscribe_to_emails=?, role=?, status=?, password_hash=?, updated_at=NOW() WHERE id=? AND role='teacher'");
                $stmt->bind_param("ssisssi", $full_name, $email, $subscribe, $role, $status, $password_hash, $id);
            } else {
                // Don't update the password if it's empty
                $stmt = $conn->prepare("UPDATE signup SET full_name=?, email=?, subscribe_to_emails=?, role=?, status=?, updated_at=NOW() WHERE id=? AND role='teacher'");
                $stmt->bind_param("ssissi", $full_name, $email, $subscribe, $role, $status, $id);
            }
            $stmt->execute();
        } else {
            // Creating a new teacher
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO signup (full_name,email,password_hash,subscribe_to_emails,role,status,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())");
            $stmt->bind_param("sssiss", $full_name, $email, $password_hash, $subscribe, $role, $status);
            $stmt->execute();
        }
        $response['status'] = 'success';
    }

    if ($_POST['action'] === 'delete_teacher') {
        $id = intval($_POST['teacher_id']);
        $stmt = $conn->prepare("DELETE FROM signup WHERE id=? AND role='teacher'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $response['status'] = 'success';
    }


    if ($_POST['action'] === 'approve_teacher') {
        $id = intval($_POST['teacher_id']);
        $stmt = $conn->prepare("UPDATE signup SET status='approved' WHERE id=? AND role='teacher'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $response['status'] = 'success';
    }

    echo json_encode($response);
    exit;
}
?>

<div class="container mt-3">
    <div class="d-flex justify-content-between mb-3">
        <h2>Manage Teachers</h2>
        <button class="btn btn-primary" id="addTeacherBtn">Add Teacher</button>
    </div>

    <table class="table table-bordered table-striped" id="teacherTable">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Subscribe</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = $conn->query("SELECT * FROM signup WHERE role='teacher'");
            while ($row = $res->fetch_assoc()) {
                $subscribeText = $row['subscribe_to_emails'] ? 'Yes' : 'No';
                echo "<tr data-id='{$row['id']}'>
                <td>{$row['id']}</td>
                <td>" . htmlspecialchars($row['full_name']) . "</td>
                <td>" . htmlspecialchars($row['email']) . "</td>
                <td>$subscribeText</td>
                <td class='status'>{$row['status']}</td>
                <td>{$row['created_at']}</td>
                <td>{$row['updated_at']}</td>
                <td>
                    <button class='btn btn-sm btn-success approve-btn' " . ($row['status'] == 'approved' ? 'disabled' : '') . ">Approve</button>
                    <button class='btn btn-sm btn-danger delete-btn'>Delete</button>
                    <button class='btn btn-sm btn-warning edit-btn'>Edit</button>
                </td>
            </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Teacher Modal -->
<div class="modal fade" id="teacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="teacherForm">
                <div class="modal-header">
                    <h5 class="modal-title">Add Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="teacher_id" id="teacher_id">
                    <input type="hidden" name="status" id="status">
                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" id="password" class="form-control">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="subscribe" id="subscribe" class="form-check-input">
                        <label class="form-check-label">Subscribe to Emails</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {

        // SAVE (Add / Edit Teacher)
        $('#teacherForm').on('submit', function(e) {
            e.preventDefault();

            let data = $(this).serialize();
            data += '&action=save_teacher';

            $.post('manageteacher.php', data, function() {
                location.reload();
            }, 'json');
        });

        // EDIT Teacher (open modal with data)
        $(document).on('click', '.edit-btn', function() {
            let row = $(this).closest('tr');

            $('#teacher_id').val(row.data('id'));
            $('#full_name').val(row.find('td:eq(1)').text());
            $('#email').val(row.find('td:eq(2)').text());
            $('#subscribe').prop('checked', row.find('td:eq(3)').text() === 'Yes');
            $('#status').val(row.find('.status').text());

            $('#password').val('');
            $('#teacherModal').modal('show');
        });

        // DELETE Teacher
        $(document).on('click', '.delete-btn', function() {
            if (!confirm('Delete this teacher?')) return;

            let id = $(this).closest('tr').data('id');

            $.post('manageteacher.php', {
                action: 'delete_teacher',
                teacher_id: id
            }, function() {
                location.reload();
            }, 'json');
        });

        // APPROVE Teacher
        $(document).on('click', '.approve-btn', function() {
            let id = $(this).closest('tr').data('id');

            $.post('manageteacher.php', {
                action: 'approve_teacher',
                teacher_id: id
            }, function() {
                location.reload();
            }, 'json');
        });

    });
</script>