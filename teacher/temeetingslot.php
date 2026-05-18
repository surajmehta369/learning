<?php
session_name('TEACHER_SESSION');
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'teacher') {
    exit("Unauthorized");
}

$teacher_id = intval($_SESSION['user_id']);

include("../conn.php");


/**$conn->query("
    UPDATE meeting_slots
    SET status = 'expired'
    WHERE link_expiry_time IS NOT NULL
      AND link_expiry_time < NOW()
      AND status != 'expired'
");*/


$teacher_query = $conn->prepare("
    SELECT full_name 
    FROM signup 
    WHERE id = ? AND role = 'teacher'
");
$teacher_query->bind_param("i", $teacher_id);
$teacher_query->execute();
$teacher_result = $teacher_query->get_result();

if ($teacher_result->num_rows === 0) {
    header("Location: ../index.php");
    exit;
}

$teacher = $teacher_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['action'] === 'add_meeting') {
        $response = ['status' => 'error'];

        $teacher_id = intval($_POST['teacher_id']);
        $note = trim($_POST['note']);
        $date = $_POST['date'];
        $time = $_POST['time'];

        if (!$teacher_id || !$date || !$time) {
            $response['message'] = 'Missing required fields.';
            echo json_encode($response);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO meeting_slots
            (teacher_id, name, note, meeting_date, meeting_time, status)
            VALUES (?, ?, ?, ?, ?, 'upcoming')
        ");
        $stmt->bind_param(
            "issss",
            $teacher_id,
            $teacher['full_name'],
            $note,
            $date,
            $time
        );
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Meeting added successfully';
            $response['new_id'] = $conn->insert_id;
        } else {
            $response['message'] = 'Failed to add meeting';
        }

        echo json_encode($response);
        exit;
    }


    if ($_POST['action'] === 'end_meeting') {

        $slot_id = intval($_POST['slot_id']);

        if (!$slot_id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid meeting']);
            exit;
        }

        $stmt = $conn->prepare("
        UPDATE meeting_slots SET
            link_expiry_time = NOW(),
            status = 'expired'
        WHERE id = ?
          AND teacher_id = ?
    ");

        $stmt->bind_param("ii", $slot_id, $teacher_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Meeting ended successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to end meeting']);
        }
        exit;
    }


    if ($_POST['action'] === 'delete_meeting') {
        $id = intval($_POST['id']);
        $response = ['status' => 'error'];

        if ($id <= 0) {
            $response['message'] = 'Invalid meeting ID';
            echo json_encode($response);
            exit;
        }

        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM meeting_requests WHERE slot_id = $id");
            $conn->query("DELETE FROM meeting_slots WHERE id = $id");
            $conn->commit();

            $response['status'] = 'success';
            $response['message'] = 'Meeting deleted successfully';
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Delete failed';
        }

        echo json_encode($response);
        exit;
    }

    if ($_POST['action'] === 'add_link') {
        $slot_id  = intval($_POST['slot_id']);
        $link     = trim($_POST['meeting_link']);
        $duration = intval($_POST['link_duration']);

        if (!$slot_id || !$link || !$duration) {
            echo json_encode(['status' => 'error', 'message' => 'Missing data']);
            exit;
        }

        // Fetch meeting date and time
        $stmt = $conn->prepare("SELECT meeting_date, meeting_time FROM meeting_slots WHERE id = ?");
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
        $meeting = $stmt->get_result()->fetch_assoc();

        if (!$meeting) {
            echo json_encode(['status' => 'error', 'message' => 'Meeting not found']);
            exit;
        }

        // Start time of the meeting
        $start_time = date('Y-m-d H:i:s', strtotime($meeting['meeting_date'] . ' ' . $meeting['meeting_time']));

        // Calculate expiry time based on the meeting duration
        $expiry_time = date('Y-m-d H:i:s', strtotime("+{$duration} minutes", strtotime($start_time)));

        // Update meeting slot with the link and expiry time
        $update = $conn->prepare("
                    UPDATE meeting_slots SET
                        meeting_link = ?, 
                        link_duration = ?, 
                        link_start_time = ?, 
                        link_expiry_time = ?, 
                        status = 'upcoming'
                    WHERE id = ?
                ");
        $update->bind_param(
            "sissi",
            $link,
            $duration,
            $start_time,
            $expiry_time,
            $slot_id
        );

        if ($update->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Link saved successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save link']);
        }
        exit;
    }
}

$meetings = $conn->query("
    SELECT * 
    FROM meeting_slots
    WHERE teacher_id = $teacher_id
    ORDER BY meeting_date ASC, meeting_time ASC
");

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Meeting Slots</title>

    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.4/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.4/dist/sweetalert2.min.js"></script>
</head>

<body>
    <div class="container py-4">

        <h4 class="mb-3">Schedule a Meeting</h4>

        <form id="meetingForm" class="row g-3 mb-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="teacher_id" value="<?= $teacher_id ?>">

            <div class="col-md-4">
                <label class="form-label">Teacher</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($teacher['full_name']) ?>" disabled>
            </div>

            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Time</label>
                <input type="time" name="time" class="form-control" required>
            </div>

            <div class="col-12">
                <label class="form-label">Note</label>
                <textarea name="note" class="form-control" rows="2"></textarea>
            </div>

            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary">Add Meeting</button>
            </div>
        </form>

        <hr>

        <h5 class="mb-3">Scheduled Meetings</h5>

        <div class="row g-3" id="meetingContainer">
            <?php if ($meetings->num_rows > 0): ?>
                <?php while ($row = $meetings->fetch_assoc()):

                    $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                    $expiry = !empty($row['link_expiry_time']) ? new DateTime($row['link_expiry_time'], new DateTimeZone('Asia/Kolkata')) : null;
                    $isExpired = $expiry && $now > $expiry;

                    if ($isExpired && $row['status'] === 'expired') continue;


                    $start = !empty($row['link_start_time'])
                        ? new DateTime($row['link_start_time'], new DateTimeZone('Asia/Kolkata'))
                        : null;

                    $isOngoing = $start && $expiry && $now >= $start && $now <= $expiry;

                ?>
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6><?= htmlspecialchars($row['name']) ?></h6>
                                <p><b><?= $row['meeting_date'] ?></b></p>
                                <p><?= date('h:i A', strtotime($row['meeting_time'])) ?></p>

                                <?php if ($row['note']): ?>
                                    <p class="small text-muted"><?= htmlspecialchars($row['note']) ?></p>
                                <?php endif; ?>

                                <div class="link-section">
                                    <?php if ($row['status'] === 'scheduled' && !$isExpired): ?>
                                        <button class="btn btn-sm btn-warning w-100 end-meeting" data-id="<?= $row['id'] ?>">
                                            End Meeting
                                        </button>
                                    <?php elseif ($row['status'] === 'expired' || $isExpired): ?>
                                        <span class="badge bg-secondary w-100 p-2">Meeting Expired</span>
                                    <?php else: ?>
                                        <input type="text" class="form-control form-control-sm mb-2" id="link_<?= $row['id'] ?>" placeholder="Add meeting link">
                                        <select class="form-select form-select-sm mb-2" id="duration_<?= $row['id'] ?>">
                                            <option value="">Select time</option>
                                            <option value="5">5 min</option>
                                            <option value="15">15 min</option>
                                            <option value="30">30 min</option>
                                            <option value="60">60 min</option>
                                        </select>
                                        <button class="btn btn-sm btn-success w-100 save-link-btn" data-id="<?= $row['id'] ?>">Save Link</button>
                                    <?php endif; ?>
                                </div>

                                <button class="btn btn-sm btn-danger w-100 mt-2 delete-meeting" data-id="<?= $row['id'] ?>">Delete</button>
                            </div>
                        </div>
                    </div>



                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        No meetings scheduled yet.
                    </div>
                </div>
            <?php endif; ?>
        </div>


    </div>

    <script>
        // --- 1. ADD MEETING ---// --- 1. ADD MEETING (Zero Reload) ---
        $('#meetingForm').submit(function(e) {
            e.preventDefault();
            let form = $(this);
            let formData = form.serialize() + '&action=add_meeting';

            $.post('teacher/temeetingslot.php', formData, function(res) {
                try {
                    let r = JSON.parse(res);
                    if (r.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Meeting Added',
                            timer: 1000,
                            showConfirmButton: false
                        });

                        // IMPORTANT: This removes the "No meetings scheduled yet" message
                        $('#meetingContainer').find('.alert-info').closest('.col-12').remove();

                        // Get values
                        let teacherName = "<?= htmlspecialchars($teacher['full_name']) ?>";
                        let date = form.find('input[name="date"]').val();
                        let time = form.find('input[name="time"]').val();
                        let note = form.find('textarea[name="note"]').val();
                        let newId = r.new_id;

                        // Create the HTML Card
                        // ... inside the $('#meetingForm').submit function ...
                        let newCard = `
                                <div class="col-md-4">
                                    <div class="card shadow-sm border-primary">
                                        <div class="card-body">
                                            <h6>${teacherName}</h6>
                                            <p><b>${date}</b></p>
                                            <p>${time}</p>
                                            ${note ? `<p class="small text-muted">${note}</p>` : ''}
                                            
                                            <div class="link-section"> <input type="text" class="form-control form-control-sm mb-2" id="link_${newId}" placeholder="Add meeting link">
                                                <select class="form-select form-select-sm mb-2" id="duration_${newId}">
                                                    <option value="">Select duration</option>
                                                    <option value="5">5 min</option>
                                                    <option value="15">15 min</option>
                                                    <option value="30">30 min</option>
                                                    <option value="60">60 min</option>
                                                </select>
                                                <button class="btn btn-sm btn-success w-100 save-link-btn" data-id="${newId}">Save Link</button>
                                            </div>
                                            <button class="btn btn-sm btn-danger w-100 mt-2 delete-meeting" data-id="${newId}">Delete</button>
                                        </div>
                                    </div>
                                </div>`;
                        $('#meetingContainer').prepend(newCard);

                        // Clear the form
                        form[0].reset();
                    } else {
                        Swal.fire('Error', r.message, 'error');
                    }
                } catch (e) {
                    console.error("JSON Error:", res);
                }
            });
        });
        // --- 2. SAVE LINK ---
        // --- SAVE LINK SCRIPT ---
        $(document).on('click', '.save-link-btn', function() {
            let btn = $(this);
            let id = btn.data('id'); // Gets the ID from data-id="${newId}"

            // Select the specific input and select box using the ID
            let linkValue = $('#link_' + id).val();
            let durationValue = $('#duration_' + id).val();

            if (!linkValue || !durationValue) {
                Swal.fire('Error', 'Please enter a link and select duration', 'error');
                return;
            }

            // Visual feedback
            btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: 'teacher/temeetingslot.php',
                type: 'POST',
                data: {
                    action: 'add_link',
                    slot_id: id,
                    meeting_link: linkValue,
                    link_duration: durationValue
                },
                success: function(res) {
                    try {
                        let r = JSON.parse(res);
                        if (r.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Link Saved',
                                timer: 1000,
                                showConfirmButton: false
                            });

                            // Replace the inputs with the End Meeting button
                            btn.closest('.link-section').html(`
                        <button class="btn btn-sm btn-warning w-100 end-meeting" data-id="${id}">
                            End Meeting
                        </button>
                    `);
                        } else {
                            Swal.fire('Error', r.message, 'error');
                            btn.prop('disabled', false).text('Save Link');
                        }
                    } catch (e) {
                        console.error("Server Response:", res); // Check F12 console if this triggers
                        Swal.fire('Error', 'Server error. Check console for details.', 'error');
                        btn.prop('disabled', false).text('Save Link');
                    }
                }
            });
        });

        // --- 3. END MEETING (The missing part) ---
        $(document).on('click', '.end-meeting', function() {
            let btn = $(this);
            let id = btn.data('id');
            let card = btn.closest('.card');

            Swal.fire({
                title: 'End this meeting?',
                text: "This will expire the link immediately.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, End it'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('teacher/temeetingslot.php', {
                        action: 'end_meeting',
                        slot_id: id
                    }, function(res) {
                        let r = JSON.parse(res);
                        if (r.status === 'success') {
                            Swal.fire('Ended', r.message, 'success');
                            // Visually disable the card
                            card.addClass('bg-light').css('opacity', '0.7');
                            btn.replaceWith('<span class="badge bg-secondary w-100 p-2">Meeting Expired</span>');
                            card.find('input, select, .save-link-btn').remove();
                        }
                    });
                }
            });
        });

        // --- 4. DELETE MEETING ---
        $(document).on('click', '.delete-meeting', function() {
            let btn = $(this);
            let id = btn.data('id');
            let container = btn.closest('.col-md-4');

            Swal.fire({
                title: 'Delete?',
                icon: 'warning',
                showCancelButton: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('teacher/temeetingslot.php', {
                        action: 'delete_meeting',
                        id: id
                    }, function(res) {
                        let r = JSON.parse(res);
                        if (r.status === 'success') {
                            container.fadeOut(400, function() {
                                $(this).remove();
                                if ($('#meetingContainer').children().length === 0) {
                                    $('#meetingContainer').html('<div class="col-12 text-center alert alert-info">No meetings scheduled yet.</div>');
                                }
                            });
                        }
                    });
                }
            });
        });
    </script>

</body>

</html>