<?php

session_name('TEACHER_SESSION');
session_start();

include("../conn.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'teacher') {
    exit("Unauthorized");
}

// ✅ MANUAL PHPMailer INCLUDES (NO 500 ERROR)
require_once __DIR__ . '../../phpmailer/PHPMailer.php';
require_once __DIR__ . '../../phpmailer/SMTP.php';
require_once __DIR__ . '../../phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    if ($_POST['action'] == 'update_status') {
        $response = ['status' => 'error'];
        $id = intval($_POST['id']);
        $status = trim($_POST['status']);
        $meeting_link = trim($_POST['meeting_link'] ?? '');
        $admin_comment = trim($_POST['admin_comment'] ?? '');

        // Validation for rejection
        if ($status === 'rejected' && empty($admin_comment)) {
            $response['message'] = 'Comment is required when rejecting a request.';
            echo json_encode($response);
            exit;
        }

        // Updated Query: Includes is_active to ensure meetings can be reopened if moved back to approved
        $is_active = ($status === 'approved') ? 1 : 0;

        $stmt = $conn->prepare("UPDATE baseline_request SET status = ?, meeting_link = ?, admin_comment = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sssii", $status, $meeting_link, $admin_comment, $is_active, $id);

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

    // ===============================
    // APPROVE REQUEST + INSTANT EMAIL (ROBUST)
    // ===============================
    if ($_POST['action'] === 'approve_request') {

        $duration = intval($_POST['link_duration'] ?? 0);

        if ($duration <= 0) {
            $response['message'] = 'Meeting duration is required.';
            echo json_encode($response);
            exit;
        }


        $response = ['status' => 'error'];
        $id = intval($_POST['id']);
        $meeting_link = trim($_POST['meeting_link'] ?? '');

        if (empty($meeting_link)) {
            $response['message'] = 'Meeting link is required.';
            echo json_encode($response);
            exit;
        }

        $timeStmt = $conn->prepare("
            SELECT request_date, request_time 
            FROM baseline_request 
            WHERE id = ?
        ");
        $timeStmt->bind_param("i", $id);
        $timeStmt->execute();
        $timeData = $timeStmt->get_result()->fetch_assoc();
        $timeStmt->close();

        $start_time = date(
            'Y-m-d H:i:s',
            strtotime($timeData['request_date'] . ' ' . $timeData['request_time'])
        );

        $expiry_time = date(
            'Y-m-d H:i:s',
            strtotime("+{$duration} minutes", strtotime($start_time))
        );

        // -----------------------------
        // UPDATE MEETING STATUS
        // -----------------------------
        $stmt = $conn->prepare("
            UPDATE baseline_request 
            SET 
                    status = 'approved',
                    meeting_link = ?,
                    link_duration = ?,
                    link_start_time = ?,
                    link_expiry_time = ?,
                    reminder_sent = 0
                    WHERE id = ?
            ");
        $stmt->bind_param(
            "sissi",
            $meeting_link,
            $duration,
            $start_time,
            $expiry_time,
            $id
        );


        if (!$stmt->execute()) {
            $response['message'] = 'Failed to approve meeting: ' . $conn->error;
            echo json_encode($response);
            exit;
        }
        $stmt->close();

        // -----------------------------
        // FETCH STUDENT + TEACHER DATA
        // -----------------------------
        // --- Corrected SELECT in approve_request block ---
        $q = $conn->prepare("
            SELECT 
                br.user_id,
                br.link_start_time,
                br.meeting_link,
                t.full_name AS teacher_name,
                t.email AS teacher_email,
                s.full_name AS student_name,
                s.email AS student_email
            FROM baseline_request br
            JOIN signup t ON t.id = br.teacher_id
            JOIN signup s ON s.id = br.user_id
            WHERE br.id = ?
        ");
        $q->bind_param("i", $id);
        $q->execute();
        $data = $q->get_result()->fetch_assoc();
        $q->close();

        // ✅ CLEANER NOTIFICATION CALL (using the function in conn.php)
        $notif_msg = "Meeting Approved! Time: " . date('d M, h:i A', strtotime($data['link_start_time']));
        // -----------------------------
        // SEND EMAIL TO STUDENT + TEACHER
        // -----------------------------
        $sent_to_student = false;
        $sent_to_teacher = false;
        $mail_errors = [];

        try {
            $mail = new PHPMailer(true);
            $mail->SMTPDebug = 3;
            $mail->Debugoutput = 'error_log';

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'surajmehta369@gmail.com';
            $mail->Password   = 'jnxuesncsbfzpdyy';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('surajmehta369@gmail.com', 'School Management System');
            $mail->isHTML(true);
            $mail->Subject = "📅 Meeting Approved";

            $startTime = date('d M Y, h:i A', strtotime($data['link_start_time']));

            $body = "
            <p>Hello <b>{$data['student_name']}</b>,</p>
            <p>Your meeting has been <b>approved</b>.</p>
            <p><b>Meeting Time:</b> {$startTime}</p>
            <p>
                <b>Meeting Link:</b><br>
                <a href='{$data['meeting_link']}' target='_blank'>
                    {$data['meeting_link']}
                </a>
            </p>
            <br>
            <p>— School Management System</p>
        ";

            // --- Send to student ---
            $mail->clearAddresses();
            $mail->addAddress($data['student_email'], $data['student_name']);
            $mail->Body = $body;

            try {
                $mail->send();
                $sent_to_student = true;
            } catch (Exception $e) {
                $mail_errors[] = "Student email failed: " . $mail->ErrorInfo;
            }

            // --- Send to teacher ---
            $mail->clearAddresses();
            $mail->addAddress($data['teacher_email'], $data['teacher_name']);
            $mail->Body = "
            <p>Hello <b>{$data['teacher_name']}</b>,</p>
            <p>The meeting with <b>{$data['student_name']}</b> has been <b>approved</b>.</p>
            <p><b>Meeting Time:</b> {$startTime}</p>
            <p>
                <b>Meeting Link:</b><br>
                <a href='{$data['meeting_link']}' target='_blank'>
                    {$data['meeting_link']}
                </a>
            </p>
            <br>
            <p>— School Management System</p>
        ";

            try {
                $mail->send();
                $sent_to_teacher = true;
            } catch (Exception $e) {
                $mail_errors[] = "Teacher email failed: " . $mail->ErrorInfo;
            }
        } catch (Exception $e) {
            $mail_errors[] = "PHPMailer setup failed: " . $e->getMessage();
        }

        // -----------------------------
        // RESPONSE
        // -----------------------------
        if ($sent_to_student && $sent_to_teacher) {
            $response['status'] = 'success';
            $response['message'] = 'Meeting approved & emails sent successfully!';
        } else {
            $response['status'] = 'partial';
            $response['message'] = 'Meeting approved but some emails failed.';
            $response['errors'] = $mail_errors;
        }

        echo json_encode($response);
        exit;
    }



    // REJECT REQUEST
    if ($_POST['action'] == 'reject_request') {
        $response = ['status' => 'error'];
        $id = intval($_POST['id']);
        $admin_comment = trim($_POST['admin_comment']);

        if (empty($admin_comment)) {
            $response['message'] = 'Comment is required to reject a request.';
            echo json_encode($response);
            exit;
        }

        $stmt = $conn->prepare("UPDATE baseline_request SET status = 'rejected', admin_comment = ? WHERE id = ?");
        $stmt->bind_param("si", $admin_comment, $id);

        if ($stmt->execute()) {


            $get_user = $conn->query("SELECT user_id FROM baseline_request WHERE id = $id");
            $user_data = $get_user->fetch_assoc();
            $student_id = $user_data['user_id'];

            $rej_msg = "Meeting Rejected: " . $admin_comment;
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notif_stmt->bind_param("is", $student_id, $rej_msg);
            $notif_stmt->execute();

            $response['status'] = 'success';
            $response['message'] = 'Request rejected successfully!';
            $response['status'] = 'success';
            $response['message'] = 'Request rejected successfully!';
        } else {
            $response['message'] = 'Error rejecting request: ' . $conn->error;
        }

        echo json_encode($response);
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'end_meeting') {

    $response = ['status' => 'error'];
    $id = intval($_POST['id']);
    $end_reason = trim($_POST['end_reason'] ?? '');

    if (empty($end_reason)) {
        $response['message'] = 'Reason is required to end the meeting.';
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE baseline_request 
         SET is_active = 0, end_reason = ? 
         WHERE id = ?"
    );
    $stmt->bind_param("si", $end_reason, $id);

    if ($stmt->execute()) {

        $get_user = $conn->query("SELECT user_id FROM baseline_request WHERE id = $id");
        $user_data = $get_user->fetch_assoc();

        $end_msg = "Your meeting session has been closed. Reason: " . $end_reason;
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notif_stmt->bind_param("is", $user_data['user_id'], $end_msg);
        $notif_stmt->execute();

        $response['status'] = 'success';
        $response['message'] = 'Meeting ended successfully.';
        $response['status'] = 'success';
        $response['message'] = 'Meeting ended successfully.';
    } else {
        $response['message'] = 'Error ending meeting.';
    }

    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$teacher_id = intval($_SESSION['user_id']);


$requests = [];
$stmt = $conn->prepare("
    SELECT br.*, s.full_name as user_name, s.email
    FROM baseline_request br
    LEFT JOIN signup s ON br.user_id = s.id
    WHERE br.teacher_id = ?
    ORDER BY 
        CASE 
            WHEN br.status = 'pending' THEN 1
            WHEN br.status = 'approved' THEN 2
            ELSE 3
        END,
        br.created_at DESC
");

$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();


if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

$conn->close();

?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-2"><i class="fas fa-calendar-check me-2"></i>Manage Meeting Requests - Teacher</h4>
            <p class="text-muted">Approve or reject meeting requests and manage Google Meet links</p>
        </div>
    </div>

    <!-- Status Filter Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="filter-buttons">
                <button class="btn btn-outline-primary filter-btn active" data-filter="all">All Requests</button>
                <button class="btn btn-outline-warning filter-btn" data-filter="pending">Pending</button>
                <button class="btn btn-outline-success filter-btn" data-filter="approved">Approved</button>
                <button class="btn btn-outline-danger filter-btn" data-filter="rejected">Rejected</button>
            </div>
        </div>
    </div>

    <!-- Request Cards -->
    <div class="row">
        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <i class="fas fa-exclamation-circle"></i>
                <h4>No requests found</h4>
                <p>There are no meeting requests to show at this time.</p>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $r): ?>
                <div class="col-12 col-md-6 col-lg-4 request-item <?= strtolower($r['status']) ?>">
                    <div class="card animate-fade-in">
                        <div class="card-body">
                            <h5 class="card-title"><?= $r['user_name'] ?></h5>
                            <p><strong>Status:</strong> <span class="badge bg-<?= strtolower($r['status']) ?>"><?= ucfirst($r['status']) ?></span></p>
                            <p><strong>Request Date:</strong> <?= $r['request_date'] ?> at <?= $r['request_time'] ?></p>
                            <p><strong>Message:</strong> <?= nl2br($r['message']) ?></p>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between mt-3">
                                <?php if ($r['status'] == 'pending'): ?>
                                    <button class="btn btn-success update-request" data-id="<?= $r['id'] ?>" data-status="approved" data-meetinglink="<?= $r['meeting_link'] ?>" data-bs-toggle="modal" data-bs-target="#approveModal">Approve</button>
                                    <button class="btn btn-danger update-request" data-id="<?= $r['id'] ?>" data-status="rejected" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                                <?php endif; ?>

                                <?php if ($r['status'] == 'approved'): ?>
                                    <?php if ($r['is_active'] == 1): ?>
                                        <button class="btn btn-warning end-meeting" data-id="<?= $r['id'] ?>">End Meeting</button>
                                    <?php else: ?>
                                        <div>
                                            <span class="badge bg-secondary">Ended</span>

                                            <?php if (!empty($r['end_reason'])): ?>
                                                <p class="text-danger mt-2 mb-0">
                                                    <strong>End Reason:</strong><br>
                                                    <?= nl2br(htmlspecialchars($r['end_reason'])) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>


                                <button class="btn btn-info update-request" data-id="<?= $r['id'] ?>" data-status="<?= $r['status'] ?>" data-meetinglink="<?= $r['meeting_link'] ?>" data-admincomment="<?= $r['admin_comment'] ?>" data-bs-toggle="modal" data-bs-target="#updateModal">Update</button>
                                <button class="btn btn-outline-danger delete-request" data-id="<?= $r['id'] ?>">Delete</button>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

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
                        <label class="form-label">Meeting Duration *</label>
                        <select class="form-select" name="link_duration" required>
                            <option value="">Select duration</option>
                            <option value="2">2 minutes</option>
                            <option value="30">30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">60 minutes</option>
                        </select>
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


<!-- End Meeting Modal -->
<div class="modal fade" id="endMeetingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title text-warning">
                    <i class="fas fa-stop-circle me-2"></i>End Meeting
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="endMeetingForm">
                    <input type="hidden" name="id" id="endMeetingId">

                    <div class="mb-3">
                        <label class="form-label">Reason *</label>
                        <textarea class="form-control" id="end_reason" name="end_reason"
                            rows="4" required
                            placeholder="Why are you ending this meeting?"></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning">
                            End Meeting
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>


<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
    $(document).ready(function() {
        // Handle filter buttons
        $(".filter-btn").click(function() {
            var filter = $(this).data("filter");
            $(".filter-btn").removeClass("active");
            $(this).addClass("active");

            if (filter === "all") {
                $(".request-item").show();
            } else {
                $(".request-item").hide();
                $("." + filter).show();
            }
        });

        // Approve Request
        // Update Request Pop-up Population
        $(".update-request").click(function() {
            var requestId = $(this).data("id");
            var status = $(this).data("status");
            var meetingLink = $(this).data("meetinglink");
            var adminComment = $(this).data("admincomment");

            // Populate the specific modals
            if (status === 'approved') {
                $("#approveId").val(requestId);
                $("#meeting_link").val(meetingLink);
            } else if (status === 'rejected') {
                $("#rejectId").val(requestId);
                $("#reject_comment").val(adminComment);
            }

            // Populate the General Update Modal
            $("#updateId").val(requestId);
            $("#update_status").val(status);
            $("#update_meeting_link").val(meetingLink);
            $("#update_comment").val(adminComment);
        });

        // Approve Request Form Submission
        $("#approveForm").submit(function(e) {
            e.preventDefault();

            var formData = $(this).serialize() + "&action=approve_request";

            $.ajax({
                url: "teacher/teacher_request.php",
                method: "POST",
                data: formData,
                success: function(response) {
                    var data = JSON.parse(response);

                    if (data.status === "success") {
                        Swal.fire("Approved", data.message, "success").then(() => location.reload());
                    } else {
                        Swal.fire("Error", data.message, "error");
                    }
                },
                error: function() {
                    Swal.fire("Error", "There was an issue with the request.", "error");
                }
            });
        });

        // Reject Request Form Submission
        $("#rejectForm").submit(function(e) {
            e.preventDefault();

            var formData = $(this).serialize() + "&action=reject_request";

            $.ajax({
                url: "teacher/teacher_request.php",
                method: "POST",
                data: formData,
                success: function(response) {
                    var data = JSON.parse(response);

                    if (data.status === "success") {
                        Swal.fire("Rejected", data.message, "success").then(() => location.reload());
                    } else {
                        Swal.fire("Error", data.message, "error");
                    }
                },
                error: function() {
                    Swal.fire("Error", "There was an issue with the request.", "error");
                }
            });
        });

        // Update Request Form Submission
        $("#updateForm").submit(function(e) {
            e.preventDefault();

            var formData = $(this).serialize() + "&action=update_status";

            $.ajax({
                url: "teacher/teacher_request.php",
                method: "POST",
                data: formData,
                success: function(response) {
                    var data = JSON.parse(response);

                    if (data.status === "success") {
                        Swal.fire("Updated", data.message, "success").then(() => location.reload());
                    } else {
                        Swal.fire("Error", data.message, "error");
                    }
                },
                error: function() {
                    Swal.fire("Error", "There was an issue with the request.", "error");
                }
            });
        });

        // Delete Request
        $(".delete-request").click(function() {
            var requestId = $(this).data("id");

            Swal.fire({
                title: "Are you sure?",
                text: "This will permanently delete the request.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "teacher/teacher_request.php",
                        method: "POST",
                        data: {
                            action: "delete_request",
                            id: requestId
                        },
                        success: function(response) {
                            var data = JSON.parse(response);

                            if (data.status === "success") {
                                Swal.fire("Deleted!", data.message, "success").then(() => location.reload());
                            } else {
                                Swal.fire("Error", data.message, "error");
                            }
                        },
                        error: function() {
                            Swal.fire("Error", "There was an issue deleting the request.", "error");
                        }
                    });
                }
            });
        });
    });




    // END MEETING BUTTON (FIXED)
    $(document).on("click", ".end-meeting", function() {
        var requestId = $(this).data("id");
        $("#endMeetingId").val(requestId);
        $("#end_reason").val("");
        $("#endMeetingModal").modal("show");
    });

    // END MEETING SUBMIT
    $(document).on("submit", "#endMeetingForm", function(e) {
        e.preventDefault();

        var formData = $(this).serialize() + "&action=end_meeting";

        $.ajax({
            url: "teacher/teacher_request.php",
            type: "POST",
            data: formData,
            success: function(response) {
                var data = JSON.parse(response);

                if (data.status === "success") {
                    Swal.fire("Ended", data.message, "success")
                        .then(() => location.reload());
                } else {
                    Swal.fire("Error", data.message, "error");
                }
            },
            error: function() {
                Swal.fire("Error", "Server error occurred", "error");
            }
        });
    });
</script>
</body>

</html>