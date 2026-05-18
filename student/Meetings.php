<?php
session_name('STUDENT_SESSION');
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login/login.php");
    exit;
}
$user_id = intval($_SESSION['user_id']);

require_once '../conn.php';

$conn->query("
    UPDATE meeting_slots
    SET status = 'expired'
    WHERE link_expiry_time IS NOT NULL
    AND link_expiry_time < NOW()
    AND status != 'expired'

");
$conn->query("
    UPDATE baseline_request
    SET status = 'expired'
    WHERE status = 'pending'
    AND TIMESTAMP(request_date, request_time) < NOW()
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'custom_request') {
    $response = ['status' => 'error'];

    $teacher_id = intval($_POST['teacher_id']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $message = trim($_POST['message']);

    if (!$teacher_id || !$date || !$time || !$message) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    $teacher_stmt = $conn->prepare("SELECT full_name FROM signup WHERE id = ? AND role = 'teacher'");
    $teacher_stmt->bind_param("i", $teacher_id);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();

    if ($teacher_result->num_rows === 0) {
        $response['message'] = 'Teacher not found.';
        echo json_encode($response);
        exit;
    }

    $teacher = $teacher_result->fetch_assoc();
    $teacher_name = $teacher['full_name'];

    $stmt = $conn->prepare("INSERT INTO baseline_request (user_id, teacher_id, teacher_name, request_date, request_time, message, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iissss", $user_id, $teacher_id, $teacher_name, $date, $time, $message);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Meeting request submitted successfully!';
    } else {
        $response['message'] = 'Failed to submit request. Database error: ' . $stmt->error;
    }

    echo json_encode($response);
    exit;
}

$teachers = $conn->query("SELECT id, full_name FROM signup WHERE role='teacher' AND status='approved' ORDER BY full_name ASC");
$teachers_modal = $teachers;

$approvedMeetings = $conn->prepare("
    SELECT 
        br.id,
        br.request_date,
        br.request_time,
        br.meeting_link,
        br.is_active,
        br.end_reason,
        br.link_start_time,
        br.link_expiry_time,
        s.full_name AS teacher_name

    FROM baseline_request br
    JOIN signup s ON s.id = br.teacher_id
    WHERE br.user_id = ?
    AND br.status = 'approved'
    AND (
            br.link_expiry_time IS NULL
            OR br.link_expiry_time >= NOW()
        )

    ORDER BY br.request_date ASC, br.request_time ASC
");


$approvedMeetings->bind_param("i", $user_id);
$approvedMeetings->execute();
$approvedMeetingsResult = $approvedMeetings->get_result();

$pendingMeetings = $conn->prepare("
    SELECT 
        br.id,
        br.request_date,
        br.request_time,
        s.full_name AS teacher_name
    FROM baseline_request br
    JOIN signup s ON s.id = br.teacher_id
    WHERE br.user_id = ?
      AND br.status = 'pending'
      -- This line ensures if the meeting time has passed, it disappears
      AND TIMESTAMP(br.request_date, br.request_time) > NOW() 
    ORDER BY br.request_date ASC, br.request_time ASC
");


$pendingMeetings->bind_param("i", $user_id);
$pendingMeetings->execute();
$pendingMeetingsResult = $pendingMeetings->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Scheduled Meetings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22https://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📅</text></svg>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <?php include("../assets/header.php"); ?>

    <div class="wrapper">
        <div class="sidebar">
            <h4><i class="bi bi-mortarboard"></i> Student Dashboard</h4>
            <a href="student/profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
            <a href="student/Purchased.php"><i class="bi bi-people"></i> Purchased Courses</a>
            <a class="active" href="student/Meetings.php"><i class="bi bi-calendar-event"></i> Scheduled Meetings</a>
            <a href="student/Request.php"><i class="bi bi-chat-left-text"></i> Requests</a>
        </div>

        <div class="main-content">
            <h3 class="fw-bold mb-4 animate__animated animate__fadeInDown">
                <i class="bi bi-calendar-check"></i> Schedule a Meeting
            </h3>

            <?php if ($approvedMeetingsResult->num_rows > 0): ?>
                <h4 class="fw-bold mb-3">
                    <i class="bi bi-camera-video"></i> Your Approved Meetings
                </h4>

                <div class="row g-4 mb-4">
                    <?php while ($m = $approvedMeetingsResult->fetch_assoc()):
                        $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));

                        $isExpired = false;
                        $isNotStarted = true;
                        $isOngoing = false;

                        if (!empty($m['link_start_time']) && !empty($m['link_expiry_time'])) {
                            $linkStart  = new DateTime($m['link_start_time'],  new DateTimeZone('Asia/Kolkata'));
                            $linkExpiry = new DateTime($m['link_expiry_time'], new DateTimeZone('Asia/Kolkata'));

                            $isExpired    = ($now > $linkExpiry);
                            $isNotStarted = ($now < $linkStart);
                            $isOngoing    = ($now >= $linkStart && $now <= $linkExpiry);
                        }
                    ?>
                        <div class="col-md-3">
                            <div class="slot-card text-center">
                                <i class="bi bi-calendar-check fs-3 text-success"></i>

                                <h6 class="mt-2">
                                    <?= date('d M Y', strtotime($m['request_date'])) ?>
                                </h6>
                                <p class="mb-1">
                                    <?= date('h:i A', strtotime($m['request_time'])) ?>
                                </p>
                                <p class="text-muted small">
                                    <i class="bi bi-person"></i>
                                    <?= htmlspecialchars($m['teacher_name']) ?>
                                </p>

                                <?php if ($isExpired): ?>
                                    <button class="btn btn-danger btn-book w-100" disabled>
                                        <i class="bi bi-x-circle"></i> Expired
                                    </button>

                                <?php elseif ($m['is_active'] == 0): ?>
                                    <button class="btn btn-secondary btn-book w-100" disabled>
                                        <i class="bi bi-slash-circle"></i> Meeting Ended
                                    </button>

                                    <?php if (!empty($m['end_reason'])): ?>
                                        <p class="text-danger small mt-2 mb-0">
                                            <strong>Reason:</strong><br>
                                            <?= nl2br(htmlspecialchars($m['end_reason'])) ?>
                                        </p>
                                    <?php endif; ?>



                                <?php elseif ($isOngoing && $m['is_active'] == 1): ?>
                                    <a href="<?= htmlspecialchars($m['meeting_link']) ?>"
                                        target="_blank"
                                        class="btn btn-success btn-book w-100">
                                        <i class="bi bi-camera-video"></i> Join Meeting
                                    </a>

                                <?php elseif ($isNotStarted): ?>
                                    <button class="btn btn-primary btn-book w-100" disabled>
                                        <i class="bi bi-clock"></i> Not Started
                                    </button>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>


            <h4 class="fw-bold mb-3">
                <i class="bi bi-hourglass-split"></i> Pending/Requested Meetings
            </h4>

            <div class="row g-4 mb-4" id="pendingMeetingsContainer">
                <?php if ($pendingMeetingsResult->num_rows > 0): ?>
                    <?php while ($r = $pendingMeetingsResult->fetch_assoc()):
                        $meetingDT = new DateTime($r['request_date'] . ' ' . $r['request_time'], new DateTimeZone('Asia/Kolkata'));
                        $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));

                        $meeting_expired = false;
                        if (!empty($r['link_expiry_time'])) {
                            $expiryDT = new DateTime($r['link_expiry_time'], new DateTimeZone('Asia/Kolkata'));
                            if ($now > $expiryDT) {
                                $meeting_expired = true;
                            }
                        }
                    ?>
                        <div class="col-md-3">
                            <div class="slot-card text-center">
                                <i class="bi bi-clock fs-3 text-warning"></i>
                                <h6 class="mt-2"><?= date('d M Y', strtotime($r['request_date'])) ?></h6>
                                <p class="mb-1"><?= date('h:i A', strtotime($r['request_time'])) ?></p>
                                <p class="text-muted small"><i class="bi bi-person"></i> <?= htmlspecialchars($r['teacher_name']) ?></p>

                                <?php if ($meeting_expired): ?>
                                    <button class="btn btn-danger btn-book w-100" disabled>
                                        <i class="bi bi-x-circle"></i> Expired
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-book w-100" disabled>
                                        <i class="bi bi-clock"></i> Pending
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div id="no-pending-msg" class="col-12 text-center text-muted">
                        <p>No pending requests found.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row g-4">
                <?php
                $user_id = intval($_SESSION['user_id']);

                $slotsResult = $conn->prepare("
                    SELECT ms.*, s.full_name as name 
                    FROM meeting_slots ms
                    JOIN signup s ON s.id = ms.teacher_id
                    WHERE ms.status IN ('upcoming', 'scheduled') 
                    AND (
                        ms.link_expiry_time IS NULL
                        OR ms.link_expiry_time >= NOW()
                    )
                    ORDER BY ms.meeting_date ASC, ms.meeting_time ASC
                ");
                $slotsResult->execute();
                $slotsResult = $slotsResult->get_result();


                $slotsStmt = $conn->prepare("
                    SELECT ms.*, s.full_name as name 
                    FROM meeting_slots ms
                    JOIN signup s ON s.id = ms.teacher_id
                    WHERE ms.status IN ('upcoming', 'scheduled') 
                    AND (ms.link_expiry_time IS NULL OR ms.link_expiry_time >= NOW())
                    ORDER BY ms.meeting_date ASC, ms.meeting_time ASC
                ");
                $slotsStmt->execute();
                $slotsResult = $slotsStmt->get_result();


                if ($slotsResult->num_rows > 0):
                    while ($row = $slotsResult->fetch_assoc()):
                        $teacher_name = htmlspecialchars($row['name']);
                        $meetingDT = new DateTime($row['meeting_date'] . ' ' . $row['meeting_time'], new DateTimeZone('Asia/Kolkata'));
                        $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));

                        $meeting_link = trim($row['meeting_link'] ?? '');

                        $expiryTime = !empty($row['link_expiry_time'])
                            ? new DateTime($row['link_expiry_time'], new DateTimeZone('Asia/Kolkata'))
                            : (clone $meetingDT)->modify('+1 hour');

                        $isExpired = ($now > $expiryTime);
                        $isTimeCurrent = ($now >= $meetingDT && $now <= $expiryTime);
                ?>
                        <div class="col-md-3">
                            <div class="slot-card text-center animate__animated animate__zoomIn">
                                <i class="bi bi-clock fs-3 text-primary"></i>
                                <h5 class="mt-2"><?= $meetingDT->format('d M Y') ?></h5>
                                <p class="mb-1"><?= $meetingDT->format('h:i A') ?></p>
                                <p class="text-muted small"><i class="bi bi-person"></i> <?= $teacher_name ?></p>

                                <?php if ($isExpired): ?>
                                    <button class="btn btn-danger btn-sm w-100" disabled>Expired</button>

                                <?php elseif (!empty($meeting_link)): ?>
                                    <?php if ($isTimeCurrent): ?>
                                        <a href="<?= htmlspecialchars($meeting_link) ?>" target="_blank" class="btn btn-success btn-sm w-100">
                                            <i class="bi bi-camera-video"></i> Join Now
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-warning btn-sm w-100" disabled>
                                            Starts at <?= $meetingDT->format('h:i A') ?>
                                        </button>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm w-100" disabled>Waiting for Link</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile;
                else: ?>
                    <div class="col-12 text-center text-muted">
                        <p>No available meeting slots found.</p>
                    </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <div class="slot-card text-center animate__animated animate__zoomIn" style="border:2px dashed #6c63ff;">
                        <i class="bi bi-plus-circle fs-3 text-primary"></i>
                        <h5 class="mt-2">Request Custom Meeting</h5>
                        <button class="btn btn-primary btn-book mt-2 w-100" data-bs-toggle="modal" data-bs-target="#requestModal">Request</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../assets/half-footer.php"); ?>

    <div class="modal fade" id="requestModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-chat-left-text"></i> Request a Meeting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="requestForm">
                        <div class="mb-3">
                            <label>Teacher *</label>
                            <?php $teachers_modal->data_seek(0);
                            ?>
                            <select name="teacher_id" class="form-control" required>
                                <option value="">Select Teacher</option>
                                <?php while ($t = $teachers_modal->fetch_assoc()): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Date *</label>
                            <input type="date" name="date" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="mb-3">
                            <label>Time *</label>
                            <input type="time" name="time" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Message *</label>
                            <textarea name="message" class="form-control" placeholder="Why do you want this meeting?" required rows="4"></textarea>
                        </div>

                        <button class="btn btn-primary w-100" type="submit" id="submitBtn">Send Request</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    <script>
        $('#requestForm').submit(function(e) {
            e.preventDefault();

            const $btn = $('#submitBtn');
            const $form = $(this);

            // 1. Capture the values for the immediate UI update
            const teacherName = $form.find('select[name="teacher_id"] option:selected').text();
            const rawDate = $form.find('input[name="date"]').val();
            const rawTime = $form.find('input[name="time"]').val();

            // Format Date (matches your PHP: d M Y)
            const dateObj = new Date(rawDate);
            const formattedDate = dateObj.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });

            // Format Time to 12-hour AM/PM (matches your PHP: h:i A)
            const [hours, minutes] = rawTime.split(':');
            const period = hours >= 12 ? 'PM' : 'AM';
            const formattedTime = ((hours % 12) || 12) + ':' + minutes + ' ' + period;

            $btn.attr('disabled', true).text('Sending...');

            $.post(window.location.href, $form.serialize() + '&action=custom_request', function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Request Sent!',
                        text: res.message,
                        confirmButtonColor: '#6c63ff'
                    });

                    // 2. Remove the "No pending" placeholder if it's there
                    $('#no-pending-msg').remove();

                    // 3. Construct the card HTML
                    const newCard = `
                                    <div class="col-md-3 animate__animated animate__fadeInDown">
                                        <div class="slot-card text-center">
                                            <i class="bi bi-clock fs-3 text-warning"></i>
                                            <h6 class="mt-2">${formattedDate}</h6>
                                            <p class="mb-1">${formattedTime}</p>
                                            <p class="text-muted small"><i class="bi bi-person"></i> ${teacherName}</p>
                                            <button class="btn btn-secondary btn-book w-100" disabled>
                                                <i class="bi bi-clock"></i> Pending
                                            </button>
                                        </div>
                                    </div>`;

                    // 4. Inject the card at the top of the container
                    $('#pendingMeetingsContainer').prepend(newCard);

                    // 5. Clean up: Reset form and close modal
                    $form[0].reset();
                    const modalEl = document.getElementById('requestModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: res.message
                    });
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'Something went wrong on the server.', 'error');
            }).always(() => {
                $btn.attr('disabled', false).text('Send Request');
            });
        });
    </script>
</body>

</html>