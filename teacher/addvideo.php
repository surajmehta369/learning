<?php
include("../conn.php");

// ---------------- CREATE VIDEOS TABLE IF NOT EXISTS ----------------
$conn->query("
CREATE TABLE IF NOT EXISTS course_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_path VARCHAR(255) NOT NULL,
    pdf_path VARCHAR(255) DEFAULT NULL,
    type ENUM('upload', 'link') DEFAULT 'upload',
    uploader_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES baseline_courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;
");

// ---------------- HANDLE FORM SUBMISSION ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $video_type = $_POST['video_type'];
    $video_path = '';
    $pdf_path = null;

    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === 0) {
        $pdfDir = "../uploads/pdfs/";
        if (!file_exists($pdfDir)) mkdir($pdfDir, 0777, true);

        $pdfName = time() . "_notes_" . basename($_FILES["pdf_file"]["name"]);
        $pdf_path = $pdfDir . $pdfName;
        move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $pdf_path);
    }

    if ($video_type === 'upload' && isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
        $targetDir = "../uploads/videos/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["video_file"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $targetFilePath)) {
            $video_path = $targetFilePath;
        }
    }

    if ($video_type === 'link' && !empty($_POST['video_link'])) {
        $raw_url = $_POST['video_link'];

        $video_path = str_replace("watch?v=", "embed/", $raw_url);

        if (strpos($video_path, 'youtu.be/') !== false) {
            $video_path = str_replace("youtu.be/", "youtube.com/embed/", $video_path);
        }

        if (strpos($video_path, '&') !== false) {
            $video_path = explode('&', $video_path)[0];
        }
    }

    if (!empty($video_path)) {
        $uploader_id = isset($_COOKIE['user_id']) ? intval($_COOKIE['user_id']) : null;

        $stmt = $conn->prepare("INSERT INTO course_videos (course_id, title, description, video_path, pdf_path, type, uploader_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $course_id, $title, $description, $video_path, $pdf_path, $video_type, $uploader_id);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>🎉 Video and PDF added successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Error: " . $stmt->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>⚠️ Please provide a video file or a valid YouTube link.</div>";
    }
    exit;
}

// ---------------- FETCH COURSES ----------------
$courses = $conn->query("SELECT id, name FROM baseline_courses ORDER BY name ASC");
?>

<div class="card shadow p-4">
    <h4 class="mb-3">🎥 Add New Course Video</h4>

    <form id="addVideoForm" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Select Course</label>
            <select name="course_id" class="form-select" required>
                <option value="">-- Select Course --</option>
                <?php while ($row = $courses->fetch_assoc()): ?>
                    <option value="<?= $row['id']; ?>"><?= htmlspecialchars($row['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Video Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Video Type</label>
            <select name="video_type" id="videoType" class="form-select" required>
                <option value="">-- Select Type --</option>
                <option value="upload">Upload File</option>
                <option value="link">YouTube Link</option>
            </select>
        </div>

        <div class="mb-3 d-none" id="uploadDiv">
            <label class="form-label">Upload Video</label>
            <input type="file" name="video_file" class="form-control" accept="video/*">
        </div>

        <div class="mb-3 d-none" id="linkDiv">
            <label class="form-label">Video Link (YouTube URL)</label>
            <input type="url" name="video_link" class="form-control" placeholder="https://youtube.com/watch?v=...">
        </div>

        <div class="mb-3 border-top pt-3">
            <label class="form-label text-danger fw-bold">
                <i class="fas fa-file-pdf"></i> Chapter Notes (PDF)
            </label>
            <input type="file" name="pdf_file" class="form-control" accept=".pdf">
        </div>

        <button type="submit" class="btn btn-primary">Add Video</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $(document).off('change', '#videoType').on('change', '#videoType', function() {
            const type = $(this).val();
            $("#uploadDiv, #linkDiv").addClass("d-none");
            if (type === "upload") $("#uploadDiv").removeClass("d-none");
            if (type === "link") $("#linkDiv").removeClass("d-none");
        });

        $(document).off('submit', '#addVideoForm').on('submit', '#addVideoForm', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Uploading...',
                text: 'Please wait while we process the files.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "addvideo.php",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.indexOf('successfully') !== -1) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Done!',
                            text: 'Video and PDF added successfully!',
                            timer: 3000
                        }).then(() => {
                            $.get("addvideo.php", function(data) {
                                $("#content-area").html(data);
                            });
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            html: response
                        });
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server upload failed. Check your file size limits.', 'error');
                }
            });
        });
    });
</script>