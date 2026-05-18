<?php
session_name('STUDENT_SESSION');
session_start();
include("../conn.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login/login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT full_name, email, role, image FROM signup WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $role, $image);
$stmt->fetch();
$stmt->close();

$initials = strtoupper(substr($full_name, 0, 1));
$upload_path = "../uploads/profile_pics/";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>

    </style>
</head>

<body>
    <?php include('../assets/header.php'); ?>
    <div class="wrapper">
        <div class="sidebar">
            <h4><i class="bi bi-mortarboard"></i> Dashboard</h4>
            <a class="active" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
            <a href="Purchased.php"><i class="bi bi-people"></i> Purchased courses</a>
            <a href="Meetings.php"><i class="bi bi-calendar-event"></i> Scheduled Meetings</a>
            <a href="Request.php"><i class="bi bi-chat-left-text"></i> Requests</a>
        </div>

        <div class="main-content">
            <div class="profile-card">
                <h3 class="text-center mb-4">Edit My Details</h3>

                <form action="update_profile.php" method="POST" enctype="multipart/form-data">

                    <div class="text-center mb-4">
                        <div class="avatar-circle">
                            <?php if (!empty($image) && file_exists($upload_path . $image)): ?>
                                <img src="<?php echo $upload_path . htmlspecialchars($image); ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo $initials; ?>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mt-2">Current Profile Picture</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Upload New Profile Picture</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <div class="form-text">Accepted formats: JPG, PNG. Max size 2MB.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($full_name); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                        <a href="profile.php" class="btn btn-secondary w-100">Cancel</a>
                    </div>
                </form>
                <hr class="my-4">
                <div class="text-center">
                    <button type="button" onclick="confirmDelete()" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash"></i> Delete Account Permanently
                    </button>
                </div>

                <form id="deleteForm" action="delete_profile.php" method="POST" style="display:none;"></form>

                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                    function confirmDelete() {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "You won't be able to revert this! Your account will be permanently removed.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes, delete it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('deleteForm').submit();
                            }
                        })
                    }
                </script>


                <script>
                    document.querySelector('input[name="image"]').addEventListener('change', function() {
                        const file = this.files[0];
                        const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                        const fileName = file.name.split('.').pop().toLowerCase();

                        if (file) {
                            // Check Extension
                            if (!allowedExtensions.includes(fileName)) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Invalid File Type',
                                    text: 'Please upload an image (JPG, JPEG, PNG, or WEBP).',
                                    confirmButtonColor: '#6f42c1'
                                });
                                this.value = ''; // Reset the input
                                return;
                            }

                            // Check Size (2MB limit)
                            if (file.size > 2 * 1024 * 1024) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'File Too Large',
                                    text: 'The image must be less than 2MB.',
                                    confirmButtonColor: '#6f42c1'
                                });
                                this.value = ''; // Reset the input
                            }
                        }
                    });
                </script>
            </div>
        </div>
    </div>
    <?php include('../assets/footer.php'); ?>

    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>