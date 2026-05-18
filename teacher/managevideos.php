<?php
include("../conn.php");

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $video_query = $conn->prepare("SELECT video_path, type FROM course_videos WHERE id = ?");
    $video_query->bind_param("i", $delete_id);
    $video_query->execute();
    $video_result = $video_query->get_result();

    if ($video_row = $video_result->fetch_assoc()) {
        if ($video_row['type'] === 'upload' && file_exists($video_row['video_path'])) {
            unlink($video_row['video_path']);
        }

        $delete_stmt = $conn->prepare("DELETE FROM course_videos WHERE id = ?");
        $delete_stmt->bind_param("i", $delete_id);

        if ($delete_stmt->execute()) {
            $success_message = "<div class='alert alert-success'>Video deleted successfully!</div>";
        } else {
            $error_message = "<div class='alert alert-danger'>Error deleting video.</div>";
        }
    }
}


// Handle video update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_video'])) {
    $video_id = intval($_POST['video_id']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    $course_id = $_POST['course_id'];
    $video_type = $_POST['video_type'];
    $video_path = $_POST['current_video_path'];

    if ($video_type === 'upload' && isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
        $targetDir = "../uploads/videos/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (file_exists($video_path)) {
            unlink($video_path);
        }

        $fileName = time() . "_" . basename($_FILES["video_file"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $targetFilePath)) {
            $video_path = $targetFilePath;
        }
    }

    if ($video_type === 'link' && !empty($_POST['video_link'])) {
        $video_path = $_POST['video_link'];
    }

    $update_stmt = $conn->prepare("UPDATE course_videos SET title = ?, description = ?, course_id = ?, video_path = ?, type = ? WHERE id = ?");
    $update_stmt->bind_param("ssissi", $title, $description, $course_id, $video_path, $video_type, $video_id);

    if ($update_stmt->execute()) {
        $success_message = "<div class='alert alert-success'>Video updated successfully!</div>";
    } else {
        $error_message = "<div class='alert alert-danger'>Error updating video.</div>";
    }
}

$videos_query = "
    SELECT cv.*, bc.name as course_name 
    FROM course_videos cv 
    LEFT JOIN baseline_courses bc ON cv.course_id = bc.id 
    ORDER BY cv.created_at DESC
";
$videos_result = $conn->query($videos_query);

$courses = $conn->query("SELECT id, name FROM baseline_courses ORDER BY name ASC");
?>

<div class="card shadow p-4">
    <h4 class="mb-4">🎬 Manage Videos</h4>

    <?php
    if (isset($success_message)) echo $success_message;
    if (isset($error_message)) echo $error_message;
    ?>

    <?php if ($videos_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Title</th>
                        <th>Course</th>
                        <th>Type</th>
                        <th>Video</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($video = $videos_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($video['title']); ?></strong>
                                <?php if (!empty($video['description'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($video['description'], 0, 100)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($video['course_name']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $video['type'] === 'upload' ? 'primary' : 'success'; ?>">
                                    <?php echo ucfirst($video['type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($video['type'] === 'upload'): ?>
                                    <span class="text-muted">File: <?php echo basename($video['video_path']); ?></span>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($video['video_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-play-circle"></i> View Link
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($video['created_at'])); ?></td>
                            <td>

                                <a href="add_quiz.php?video_id=<?= $video['id'] ?>" class="btn btn-sm btn-info">Quiz</a>

                                <button class="btn btn-sm btn-outline-primary mb-1" data-bs-toggle="modal" data-bs-target="#editVideoModal"
                                    onclick="editVideo(
                                            <?php echo $video['id']; ?>, 
                                            '<?php echo addslashes($video['title']); ?>', 
                                            '<?php echo addslashes($video['description']); ?>', 
                                            <?php echo $video['course_id']; ?>,
                                            '<?php echo $video['type']; ?>',
                                            '<?php echo addslashes($video['video_path']); ?>'
                                        )">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>

                                <button class="btn btn-sm btn-outline-danger delete-video" data-video-id="<?php echo $video['id']; ?>">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No videos found. <a href="#" class="alert-link load-page" data-page="addvideo.php">Add your first video</a>.
        </div>
    <?php endif; ?>
</div>

<!-- Edit Video Modal -->
<div class="modal fade" id="editVideoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="editVideoForm">
                <div class="modal-body">
                    <input type="hidden" name="video_id" id="edit_video_id">
                    <input type="hidden" name="update_video" value="1">
                    <input type="hidden" name="current_video_path" id="edit_current_video_path">

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <select name="course_id" id="edit_course_id" class="form-select" required>
                            <option value="">-- Select Course --</option>
                            <?php
                            // Reset courses pointer and fetch again
                            $courses->data_seek(0);
                            while ($course = $courses->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Video Type</label>
                        <select name="video_type" id="edit_video_type" class="form-select" required onchange="toggleEditVideoFields()">
                            <option value="">-- Select Type --</option>
                            <option value="upload">Upload File</option>
                            <option value="link">YouTube Link</option>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="edit_uploadDiv">
                        <label class="form-label">Upload New Video (Leave empty to keep current)</label>
                        <input type="file" name="video_file" class="form-control" accept="video/*">
                        <small class="text-muted" id="current_file_info"></small>
                    </div>

                    <div class="mb-3 d-none" id="edit_linkDiv">
                        <label class="form-label">Video Link (YouTube URL)</label>
                        <input type="url" name="video_link" id="edit_video_link" class="form-control" placeholder="https://youtube.com/...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Video</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function editVideo(id, title, description, courseId, type, videoPath) {
        document.getElementById('edit_video_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_course_id').value = courseId;
        document.getElementById('edit_video_type').value = type;
        document.getElementById('edit_current_video_path').value = videoPath;

        if (type === 'upload') {
            document.getElementById('current_file_info').textContent = 'Current file: ' + videoPath.split('/').pop();
        } else {
            document.getElementById('edit_video_link').value = videoPath;
        }
        toggleEditVideoFields();
    }

    function toggleEditVideoFields() {
        const type = document.getElementById('edit_video_type').value;
        const uploadDiv = document.getElementById('edit_uploadDiv');
        const linkDiv = document.getElementById('edit_linkDiv');

        if (uploadDiv && linkDiv) {
            uploadDiv.classList.add('d-none');
            linkDiv.classList.add('d-none');
            if (type === "upload") uploadDiv.classList.remove('d-none');
            else if (type === "link") linkDiv.classList.remove('d-none');
        }
    }

    $(document).ready(function() {
        $(document).off('submit', '#editVideoForm').on('submit', '#editVideoForm', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            $.ajax({
                url: 'managevideos.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#editVideoModal').modal('hide');
                    $('.modal-backdrop').remove();

                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        timer: 1000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#content-area').html(response);
                    });
                }
            });
        });

        $(document).off('click', '.delete-video').on('click', '.delete-video', function(e) {
            e.preventDefault();
            const videoId = $(this).data('video-id');

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'managevideos.php?delete_id=' + videoId,
                        type: 'GET',
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                timer: 1000,
                                showConfirmButton: false
                            }).then(() => {
                                $('#content-area').html(response);
                            });
                        },
                        error: function() {
                            Swal.fire('Error', 'Something went wrong!', 'error');
                        }
                    });
                }
            });
        });
    });
</script>