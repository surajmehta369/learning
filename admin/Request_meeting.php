<?php
include("../conn.php");

// Handle AJAX POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // UPDATE REQUEST STATUS WITH LINK OR COMMENT
    if ($_POST['action'] == 'update_status') {
        $response = ['status' => 'error'];
        $id = intval($_POST['id']);
        $status = trim($_POST['status']);
        $meeting_link = trim($_POST['meeting_link'] ?? '');
        $admin_comment = trim($_POST['admin_comment'] ?? '');

        // Validate required fields based on status
        if ($status === 'rejected' && empty($admin_comment)) {
            $response['message'] = 'Comment is required when rejecting a request.';
            echo json_encode($response);
            exit;
        }

        if ($status === 'approved' && empty($meeting_link)) {
            $response['message'] = 'Meeting link is required when approving a request.';
            echo json_encode($response);
            exit;
        }

        $stmt = $conn->prepare("UPDATE baseline_request SET status = ?, meeting_link = ?, admin_comment = ? WHERE id = ?");
        $stmt->bind_param("sssi", $status, $meeting_link, $admin_comment, $id);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Request updated successfully!';
        } else {
            $response['message'] = 'Error updating request: ' . $conn->error;
        }

        echo json_encode($response);
        exit;
    }

    // DELETE REQUEST
    if ($_POST['action'] == 'delete_request') {
        $response = ['status' => 'error'];
        $id = intval($_POST['id']);

        $stmt = $conn->prepare("DELETE FROM baseline_request WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Request deleted successfully!';
        } else {
            $response['message'] = 'Error deleting request: ' . $conn->error;
        }

        echo json_encode($response);
        exit;
    }
}

// Fetch all meeting requests from baseline_request table with user details
$requests = [];
$result = $conn->query("
    SELECT br.*, s.full_name as user_name, s.email 
    FROM baseline_request br 
    LEFT JOIN signup s ON br.user_id = s.id 
    ORDER BY 
        CASE 
            WHEN br.status = 'pending' THEN 1
            WHEN br.status = 'approved' THEN 2
            ELSE 3
        END,
        br.created_at DESC
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Meeting Requests - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        :root {
            --primary-color: #6f42c1;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --secondary-color: #6c757d;
        }

        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-py-4 {
            padding: 2rem 0;
        }

        .card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card-body {
            padding: 1.5rem;
        }

        .badge {
            font-size: 0.75em;
            padding: 0.5em 0.75em;
            border-radius: 50px;
            font-weight: 600;
        }

        .bg-pending {
            background-color: var(--warning-color);
            color: #000;
        }

        .bg-approved {
            background-color: var(--success-color);
            color: #fff;
        }

        .bg-resolved {
            background-color: var(--info-color);
            color: #fff;
        }

        .bg-rejected {
            background-color: var(--danger-color);
            color: #fff;
        }

        .btn {
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #8b5ceb);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #34ce57);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #e4606d);
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info-color), #39c0d6);
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .request-message {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            border-radius: 0 8px 8px 0;
            margin: 1rem 0;
            font-size: 0.9rem;
        }

        .admin-comment {
            background: #fff3cd;
            border-left: 4px solid var(--warning-color);
            padding: 1rem;
            border-radius: 0 8px 8px 0;
            margin: 1rem 0;
            font-size: 0.9rem;
        }

        .meeting-link {
            background: #d1ecf1;
            border-left: 4px solid var(--info-color);
            padding: 1rem;
            border-radius: 0 8px 8px 0;
            margin: 1rem 0;
            font-size: 0.9rem;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-3px);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .teacher-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.8em;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        .datetime-badge {
            background: #e9ecef;
            color: var(--secondary-color);
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75em;
            display: inline-block;
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        .action-buttons {
            margin-top: auto;
        }

        .request-header {
            border-bottom: 2px solid #f1f3f4;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .meeting-link-btn {
            background: linear-gradient(135deg, #4285f4, #34a853);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .meeting-link-btn:hover {
            color: white;
            transform: translateY(-2px);
            text-decoration: none;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .filter-buttons {
            margin-bottom: 2rem;
        }

        .filter-btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>

<body>

    <div class="container container-py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-2"><i class="fas fa-calendar-check me-2"></i>Manage Meeting Requests - Admin</h4>
                <p class="text-muted">Approve or reject meeting requests and manage Google Meet links</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php
            $total_requests = count($requests);
            $pending_count = 0;
            $approved_count = 0;
            $resolved_count = 0;
            $rejected_count = 0;

            foreach ($requests as $r) {
                switch (strtolower($r['status'])) {
                    case 'pending':
                        $pending_count++;
                        break;
                    case 'approved':
                        $approved_count++;
                        break;
                    case 'resolved':
                        $resolved_count++;
                        break;
                    case 'rejected':
                        $rejected_count++;
                        break;
                }
            }
            ?>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-primary"><?= $total_requests ?></div>
                    <div class="stats-label">Total Requests</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-warning"><?= $pending_count ?></div>
                    <div class="stats-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-success"><?= $approved_count ?></div>
                    <div class="stats-label">Approved</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-danger"><?= $rejected_count ?></div>
                    <div class="stats-label">Rejected</div>
                </div>
            </div>
        </div>

        <!-- Status Filter Buttons -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="filter-buttons">
                    <button class="btn btn-outline-primary filter-btn active" data-filter="all">All (<?= $total_requests ?>)</button>
                    <button class="btn btn-outline-warning filter-btn" data-filter="pending">Pending (<?= $pending_count ?>)</button>
                    <button class="btn btn-outline-success filter-btn" data-filter="approved">Approved (<?= $approved_count ?>)</button>
                    <button class="btn btn-outline-danger filter-btn" data-filter="rejected">Rejected (<?= $rejected_count ?>)</button>
                </div>
            </div>
        </div>

        <!-- Request List -->
        <div class="row">
            <div class="col-12">
                <?php if (empty($requests)): ?>
                    <div class="empty-state animate-fade-in">
                        <i class="fas fa-inbox"></i>
                        <h4>No Requests Found</h4>
                        <p>There are no meeting requests at the moment.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4" id="requestList">
                        <?php foreach ($requests as $r): ?>
                            <?php
                            $status_lower = strtolower($r['status']);
                            switch ($status_lower) {
                                case 'pending':
                                    $badgeClass = 'bg-pending';
                                    break;
                                case 'approved':
                                    $badgeClass = 'bg-approved';
                                    break;
                                case 'resolved':
                                    $badgeClass = 'bg-resolved';
                                    break;
                                case 'rejected':
                                    $badgeClass = 'bg-rejected';
                                    break;
                                default:
                                    $badgeClass = 'bg-secondary';
                                    break;
                            }

                            $date = date("d M Y", strtotime($r['request_date']));
                            $time = date("h:i A", strtotime($r['request_time']));
                            ?>
                            <div class="col-md-6 col-lg-4 animate-fade-in request-item" data-status="<?= strtolower($r['status']) ?>" data-id="<?= $r['id'] ?>">
                                <div class="card">
                                    <div class="card-body d-flex flex-column">
                                        <!-- Request Header -->
                                        <div class="request-header">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-user me-1 text-primary"></i>
                                                    <?= htmlspecialchars($r['user_name']) ?>
                                                </h6>
                                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span>
                                            </div>
                                            <p class="small mb-1 text-muted">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?= htmlspecialchars($r['email']) ?>
                                            </p>
                                        </div>

                                        <!-- Teacher Information -->
                                        <div class="mb-3">
                                            <span class="teacher-badge">
                                                <i class="fas fa-chalkboard-teacher me-1"></i>
                                                <?= htmlspecialchars($r['teacher_name']) ?>
                                            </span>
                                        </div>

                                        <!-- Date & Time -->
                                        <div class="mb-3">
                                            <div class="datetime-badge">
                                                <i class="fas fa-calendar me-1"></i><?= $date ?>
                                            </div>
                                            <div class="datetime-badge ms-2">
                                                <i class="fas fa-clock me-1"></i><?= $time ?>
                                            </div>
                                        </div>

                                        <!-- Message -->
                                        <div class="request-message flex-grow-1">
                                            <strong><i class="fas fa-comment me-1"></i>User Message:</strong>
                                            <p class="mb-0 mt-1"><?= htmlspecialchars($r['message']) ?></p>
                                        </div>

                                        <!-- Meeting Link (if approved) -->
                                        <?php if (!empty($r['meeting_link']) && $r['status'] === 'approved'): ?>
                                            <div class="meeting-link">
                                                <strong><i class="fas fa-video me-1"></i>Meeting Link:</strong>
                                                <div class="mt-2">
                                                    <a href="<?= htmlspecialchars($r['meeting_link']) ?>" target="_blank" class="meeting-link-btn">
                                                        <i class="fab fa-google"></i>
                                                        Join Google Meet
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Admin Comment (if rejected) -->
                                        <?php if (!empty($r['admin_comment']) && $r['status'] === 'rejected'): ?>
                                            <div class="admin-comment">
                                                <strong><i class="fas fa-comment-dots me-1"></i>Admin Comment:</strong>
                                                <p class="mb-0 mt-1"><?= htmlspecialchars($r['admin_comment']) ?></p>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Created Date -->
                                        <p class="small text-muted mb-3">
                                            <i class="fas fa-calendar-plus me-1"></i>
                                            Requested: <?= date('d M Y, h:i A', strtotime($r['created_at'])) ?>
                                        </p>

                                        <!-- Action Buttons -->
                                        <div class="action-buttons">
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <div class="d-grid gap-2">
                                                    <button class="btn btn-success approve-request" data-id="<?= $r['id'] ?>">
                                                        <i class="fas fa-check me-1"></i> Approve
                                                    </button>
                                                    <button class="btn btn-danger reject-request" data-id="<?= $r['id'] ?>">
                                                        <i class="fas fa-times me-1"></i> Reject
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-grid gap-2">
                                                    <button class="btn btn-info update-request" data-id="<?= $r['id'] ?>" data-status="<?= $r['status'] ?>">
                                                        <i class="fas fa-edit me-1"></i> Update Status
                                                    </button>
                                                    <button class="btn btn-danger delete-request" data-id="<?= $r['id'] ?>">
                                                        <i class="fas fa-trash me-1"></i> Delete Request
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-check text-success me-2"></i>Approve Meeting Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="approveForm">
                        <input type="hidden" name="id" id="approveId">
                        <input type="hidden" name="status" value="approved">
                        <div class="mb-3">
                            <label for="meeting_link" class="form-label">Google Meet Link *</label>
                            <input type="url" class="form-control" id="meeting_link" name="meeting_link"
                                placeholder="https://meet.google.com/xxx-xxxx-xxx" required>
                            <div class="form-text">Enter the Google Meet link for this meeting.</div>
                        </div>
                        <div class="mb-3">
                            <label for="approve_comment" class="form-label">Optional Comment</label>
                            <textarea class="form-control" id="approve_comment" name="admin_comment"
                                placeholder="Any additional comments for the user..." rows="3"></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Approve Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-times text-danger me-2"></i>Reject Meeting Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="rejectForm">
                        <input type="hidden" name="id" id="rejectId">
                        <input type="hidden" name="status" value="rejected">
                        <div class="mb-3">
                            <label for="reject_comment" class="form-label">Reason for Rejection *</label>
                            <textarea class="form-control" id="reject_comment" name="admin_comment"
                                placeholder="Please provide a reason for rejecting this request..."
                                rows="4" required></textarea>
                            <div class="form-text">This comment will be visible to the user.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger">Reject Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit text-info me-2"></i>Update Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateForm">
                        <input type="hidden" name="id" id="updateId">
                        <div class="mb-3">
                            <label for="update_status" class="form-label">Status</label>
                            <select class="form-select" id="update_status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                        <div class="mb-3" id="meetingLinkField">
                            <label for="update_meeting_link" class="form-label">Google Meet Link</label>
                            <input type="url" class="form-control" id="update_meeting_link" name="meeting_link"
                                placeholder="https://meet.google.com/xxx-xxxx-xxx">
                        </div>
                        <div class="mb-3">
                            <label for="update_comment" class="form-label">Admin Comment</label>
                            <textarea class="form-control" id="update_comment" name="admin_comment"
                                placeholder="Enter any comments..." rows="3"></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Filter functionality
            $('.filter-btn').on('click', function() {
                const filter = $(this).data('filter');
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');

                if (filter === 'all') {
                    $('.request-item').show();
                } else {
                    $('.request-item').hide();
                    $(`.request-item[data-status="${filter}"]`).show();
                }
            });

            // Approve Request
            $(document).on('click', '.approve-request', function() {
                const id = $(this).data('id');
                $('#approveId').val(id);
                $('#approveModal').modal('show');
            });

            // Reject Request
            $(document).on('click', '.reject-request', function() {
                const id = $(this).data('id');
                $('#rejectId').val(id);
                $('#rejectModal').modal('show');
            });

            // Update Request
            $(document).on('click', '.update-request', function() {
                const id = $(this).data('id');
                const status = $(this).data('status');
                $('#updateId').val(id);
                $('#update_status').val(status);

                // Show/hide meeting link field based on current status
                if (status === 'approved') {
                    $('#meetingLinkField').show();
                    $('#update_meeting_link').prop('required', true);
                } else {
                    $('#meetingLinkField').hide();
                    $('#update_meeting_link').prop('required', false);
                }

                $('#updateModal').modal('show');
            });

            // Toggle meeting link field based on status
            $('#update_status').on('change', function() {
                if ($(this).val() === 'approved') {
                    $('#meetingLinkField').show();
                    $('#update_meeting_link').prop('required', true);
                } else {
                    $('#meetingLinkField').hide();
                    $('#update_meeting_link').prop('required', false);
                }
            });

            // Form submissions
            $('#approveForm').on('submit', function(e) {
                e.preventDefault();
                submitRequest($(this).serialize());
            });

            $('#rejectForm').on('submit', function(e) {
                e.preventDefault();
                submitRequest($(this).serialize());
            });

            $('#updateForm').on('submit', function(e) {
                e.preventDefault();
                submitRequest($(this).serialize());
            });

            // Delete Request
            $(document).on('click', '.delete-request', function() {
                const id = $(this).data('id');
                const card = $(this).closest('.request-item');
                const userName = card.find('h6').text().trim();

                Swal.fire({
                    title: 'Delete Request?',
                    html: `Are you sure you want to delete the meeting request from <strong>${userName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteRequest(id, card);
                    }
                });
            });

            function submitRequest(formData) {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: "Request_meeting.php", // This file handles the request
                    type: "POST",
                    data: formData + '&action=update_status',
                    success: function(res) {
                        try {
                            const r = JSON.parse(res);
                            if (r.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    text: r.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: r.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        } catch (e) {
                            console.error('Parse error:', e);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Unexpected error occurred.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Network Error!',
                            text: 'Please check your connection and try again.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }

            function deleteRequest(id, card) {
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: "Request_meeting.php", // This file handles the request
                    type: "POST",
                    data: {
                        action: 'delete_request',
                        id: id
                    },
                    success: function(res) {
                        try {
                            const r = JSON.parse(res);
                            if (r.status === 'success') {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: r.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    card.remove();
                                    // Update statistics after deletion
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: r.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        } catch (e) {
                            console.error('Parse error:', e);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Unexpected error occurred.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Network Error!',
                            text: 'Please check your connection and try again.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    </script>
</body>

</html>