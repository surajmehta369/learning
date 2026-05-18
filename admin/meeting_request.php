<?php

include("../conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- ADD NEW MEETING ---
    if ($_POST['action'] == 'add_meeting') {
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

        $stmt = $conn->prepare("INSERT INTO meeting_slots (teacher_id, name, note, meeting_date, meeting_time) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $teacher_id, $teacher['full_name'], $note, $date, $time);
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Meeting added successfully';
        } else {
            $response['message'] = 'Failed to add meeting: ' . $conn->error;
        }
        echo json_encode($response);
        exit;
    }

    // --- DELETE MEETING -
    if ($_POST['action'] == 'delete_meeting') {
        $response = ['status' => 'error', 'message' => 'Unknown error'];

        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $response['message'] = 'Missing meeting ID';
            echo json_encode($response);
            exit;
        }

        $id = intval($_POST['id']);

        if ($id <= 0) {
            $response['message'] = 'Invalid meeting ID';
            echo json_encode($response);
            exit;
        }


        $conn->begin_transaction();

        try {

            $delete1 = $conn->query("DELETE FROM meeting_requests WHERE slot_id = $id");
            if ($delete1 === FALSE) {
                throw new Exception("Failed to delete meeting requests: " . $conn->error);
            }

            $delete2 = $conn->query("DELETE FROM meeting_slots WHERE id = $id");
            if ($delete2 === FALSE) {
                throw new Exception("Failed to delete meeting slot: " . $conn->error);
            }


            $conn->commit();

            $response['status'] = 'success';
            $response['message'] = 'Meeting deleted successfully';
        } catch (Exception $e) {

            $conn->rollback();
            $response['message'] = $e->getMessage();
            error_log("Delete meeting error: " . $e->getMessage());
        }

        echo json_encode($response);
        exit;
    }

    // BOOK SLOT 
    if ($_POST['action'] == 'book_slot') {
        $slot_id = intval($_POST['slot_id']);
        $user_id = intval($_COOKIE['user_id'] ?? 0);

        if (!$user_id || !$slot_id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user or slot.']);
            exit;
        }

        $exists = $conn->query("SELECT id FROM meeting_requests WHERE slot_id=$slot_id AND user_id=$user_id");
        if ($exists->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Already requested.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO meeting_requests (slot_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $slot_id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to book slot']);
        }
        exit;
    }

    // ADD MEETING LINK
    if ($_POST['action'] == 'add_link') {
        $slot_id = intval($_POST['slot_id']);
        $link = trim($_POST['meeting_link']);

        if (empty($slot_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid slot ID']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE meeting_slots SET meeting_link = ? WHERE id = ?");
        $stmt->bind_param("si", $link, $slot_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Link saved successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save link']);
        }
        exit;
    }
}

$teachers = $conn->query("SELECT id, full_name FROM signup WHERE role='teacher' AND status='approved' ORDER BY full_name ASC");
$meetings = $conn->query("SELECT m.*, s.full_name as teacher_name FROM meeting_slots m LEFT JOIN signup s ON m.teacher_id=s.id ORDER BY m.meeting_date ASC, m.meeting_time ASC");
?>

<div class="container py-4">
    <h4 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Schedule a Meeting</h4>

    <form id="meetingForm" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Teacher *</label>
            <select name="teacher_id" class="form-control" required>
                <option value="">Select Teacher</option>
                <?php while ($t = $teachers->fetch_assoc()): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">Date *</label><input type="date" name="date" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Time *</label><input type="time" name="time" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Note (optional)</label><textarea name="note" class="form-control" rows="2"></textarea></div>
        <div class="col-12 text-end"><button type="submit" class="btn btn-primary">Add Meeting</button></div>
    </form>

    <hr>
    <h5 class="mb-3"><i class="fas fa-list-alt me-2"></i>Scheduled Meetings</h5>
    <div id="meetingList" class="row g-3">

        <?php while ($row = $meetings->fetch_assoc()): ?>
            <?php
            $slot_id = $row['id'];
            $requests = $conn->query("SELECT u.full_name FROM meeting_requests r JOIN signup u ON r.user_id=u.id WHERE r.slot_id=$slot_id");
            ?>
            <div class="col-md-4" id="meeting-<?= $slot_id ?>" data-id="<?= $slot_id ?>">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6><i class="fas fa-user-tie me-1"></i><?= htmlspecialchars($row['teacher_name']) ?></h6>
                        <p><i class="fas fa-calendar me-1"></i><b><?= $row['meeting_date'] ?></b></p>
                        <p><i class="fas fa-clock me-1"></i><?= date('h:i A', strtotime($row['meeting_time'])) ?></p>
                        <?php if ($row['note']): ?><p class="small text-muted"><?= htmlspecialchars($row['note']) ?></p><?php endif; ?>

                        <?php if ($requests->num_rows > 0): ?>
                            <p class="mb-2"><b>Requested by:</b></p>
                            <ul class="small">
                                <?php while ($r = $requests->fetch_assoc()): ?>
                                    <li><?= htmlspecialchars($r['full_name']) ?></li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted small">No requests yet.</p>
                        <?php endif; ?>

                        <div class="mt-2">
                            <input type="text" class="form-control form-control-sm mb-2" placeholder="Add meeting link" id="link_<?= $slot_id ?>" value="<?= htmlspecialchars($row['meeting_link'] ?? '') ?>">
                            <button class="btn btn-sm btn-success w-100 save-link-btn" data-id="<?= $slot_id ?>">Save Link</button>
                        </div>

                        <button class="btn btn-sm btn-danger mt-2 w-100 delete-meeting" data-id="<?= $slot_id ?>">Delete</button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>

    </div>
</div>