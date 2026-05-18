<?php
include("conn.php");

// Handle delete request (AJAX)
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM course_videos WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["status" => "success"]);
    exit;
}
?>



<div class="container mt-3">
    <div class="d-flex justify-content-between mb-3">
        <h2>Manage Course Videos</h2>
    </div>

    <table class="table table-bordered table-striped" id="videosTable">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Course</th>
                <th>Video Title</th>
                <th>Description</th>
                <th>Type</th>
                <th>Uploaded By</th>
                <th>Uploaded On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Fetch videos along with uploader and course name
        $sql = "
            SELECT 
                cv.*,
                s.full_name AS teacher_name,
                c.name AS course_name
            FROM course_videos cv
            LEFT JOIN signup s ON cv.uploader_id = s.id
            LEFT JOIN courses c ON cv.course_id = c.id
            ORDER BY cv.created_at DESC
        ";

        $result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $videoType = htmlspecialchars($row['type']);
        $teacherName = htmlspecialchars($row['teacher_name'] ?? 'Unknown');
        $courseName = htmlspecialchars($row['course_name'] ?? 'N/A');
        $title = htmlspecialchars($row['title']);
        $desc = htmlspecialchars($row['description']);
        $created = htmlspecialchars($row['created_at']);
        $videoUrl = htmlspecialchars($row['video_path']);

        // Detect if it's a YouTube link
        $isYouTube = (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false);

        echo "<tr data-id='{$row['id']}'>
            <td>{$row['id']}</td>
            <td>{$courseName}</td>
            <td>{$title}</td>
            <td>{$desc}</td>
            <td>{$videoType}</td>
            <td>{$teacherName}</td>
            <td>{$created}</td>
            <td>";

        if ($isYouTube) {
            echo "<a href='{$videoUrl}' target='_blank' class='btn btn-sm btn-primary'>View</a>";
        } else {
            echo "<button class='btn btn-sm btn-primary view-video' data-url='{$videoUrl}'>View</button>";
        }

        echo " <button class='btn btn-sm btn-danger delete-video'>Delete</button>
            </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='8' class='text-center text-muted'>No videos found.</td></tr>";
}

        ?>
        </tbody>
    </table>
</div>

<!-- Video Preview Modal -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <video id="previewVideo" width="100%" height="400" controls>
                    <source src="" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){

    // Initialize DataTable
    if ($.fn.DataTable.isDataTable('#videosTable')) {
        $('#videosTable').DataTable().destroy();
    }
    $('#videosTable').DataTable({
        "order": [[0, "desc"]],
        "columnDefs": [{ "orderable": false, "targets": [7] }]
    });

    // View video modal
    $(document).off('click', '.view-video').on('click', '.view-video', function(){
        var url = $(this).data('url');
        $('#previewVideo source').attr('src', url);
        $('#previewVideo')[0].load();
        $('#videoModal').modal('show');
    });

    // Delete video
    $(document).off('click', '.delete-video').on('click', '.delete-video', function(){
        if(!confirm('Are you sure you want to delete this video?')) return;
        var row = $(this).closest('tr');
        var id = row.data('id');
        $.post('admin_videos.php', { delete_id: id }, function(res){
            var r = JSON.parse(res);
            if(r.status == 'success'){
                row.fadeOut(400, function(){ $(this).remove(); });
            } else {
                alert('Failed to delete video.');
            }
        }).fail(function(){
            alert('Error deleting video.');
        });
    });
});
</script>
