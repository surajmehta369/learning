<?php
include("../conn.php");

// ---------------- Delete if delete_id exists ----------------
// Delete User
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $conn->query("DELETE FROM signup WHERE id=$id");
    echo json_encode(['status'=>'success']);
    exit;
}

// Update User
if (isset($_POST['update_user'])) {
    $id = intval($_POST['user_id']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $subscribe = isset($_POST['subscribe']) ? 1 : 0;

    if ($password) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("UPDATE signup SET full_name='$full_name', email='$email', password_hash='$password_hash', subscribe_to_emails=$subscribe, updated_at=NOW() WHERE id=$id");
    } else {
        $conn->query("UPDATE signup SET full_name='$full_name', email='$email', subscribe_to_emails=$subscribe, updated_at=NOW() WHERE id=$id");
    }

    echo json_encode(['status'=>'success']);
    exit;
}

// Add User
if (isset($_POST['add_user'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $subscribe = isset($_POST['subscribe']) ? 1 : 0;

    if ($password) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("INSERT INTO signup (full_name, email, password_hash, subscribe_to_emails, role, created_at, updated_at) 
                     VALUES ('$full_name', '$email', '$password_hash', $subscribe, 'student', NOW(), NOW())");

        echo json_encode(['status'=>'success']);
        exit;
    } else {
        echo json_encode(['status'=>'error','message'=>'Password is required']);
        exit;
    }
}


// ---------------- Fetch users ----------------
$result = $conn->query("SELECT * FROM signup WHERE role='student' ORDER BY id DESC");
//$result = $conn->query("SELECT * FROM signup WHERE role IN ('customer','student') ORDER BY id DESC");

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">👥 Manage Users</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus"></i> Add User
        </button>
    </div>

    <table class="table table-striped table-bordered" id="usersTable">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subscribe</th>
                <th>Role</th>
                <th>Created Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <?= $row['subscribe_to_emails'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
                    </td>
                    <td><span class="badge bg-info"><?= $row['role'] ?></span></td>
                    <td><?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></td>
                    <td>
                    <button class="btn btn-sm btn-warning edit-user"
                        data-id="<?= $row['id'] ?>"
                        data-name="<?= htmlspecialchars($row['full_name']) ?>"
                        data-email="<?= htmlspecialchars($row['email']) ?>"
                        data-subscribe="<?= $row['subscribe_to_emails'] ?>"
                        data-bs-toggle="modal"
                        data-bs-target="#editUserModal">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                        <button class="btn btn-sm btn-danger delete-user" data-id="<?= $row['id'] ?>">
                            <i class="fas fa-trash"></i> Delete
                        </button>

                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Edit User Modal -->
<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="manageusers.php">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                        <div class="form-text">Leave blank if you don't want to change the password</div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="subscribe" id="edit_subscribe" class="form-check-input">
                        <label class="form-check-label">Subscribe to Emails</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add User Modal -->


<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="manageusers.php">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="form-text">Password is required for new users</div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="subscribe" class="form-check-input">
                        <label class="form-check-label">Subscribe to Emails</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    // Initialize DataTable
    if (!$.fn.DataTable.isDataTable('#usersTable')) {
        $('#usersTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "columnDefs": [{
                "orderable": false,
                "targets": [6]
            }]
        });
    }




    // Reset add modal when shown
    document.getElementById('addUserModal').addEventListener('show.bs.modal', function() {
        this.querySelector('form').reset();
    });
</script>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script>
    // Fill Edit User Modal with data
    $(document).on('click', '.edit-user', function () {
        $('#edit_user_id').val($(this).data('id'));
        $('#edit_full_name').val($(this).data('name'));
        $('#edit_email').val($(this).data('email'));

        if ($(this).data('subscribe') == 1) {
            $('#edit_subscribe').prop('checked', true);
        } else {
            $('#edit_subscribe').prop('checked', false);
        }
    });
</script>
