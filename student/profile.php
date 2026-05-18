<?php
session_name('STUDENT_SESSION');
session_start();


if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login/login.php");
    exit;
}

include("../conn.php");

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT full_name, email, role, image FROM signup WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $role, $image);
$stmt->fetch();
$stmt->close();

$words = explode(" ", trim($full_name));
$initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <?php include('../assets/header.php'); ?>

    <div class="wrapper">

        <div class="sidebar">
            <h4><i class="bi bi-mortarboard"></i> Student Dashboard</h4>
            <a class="active" href="student/profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
            <a href="student/my_notes.php"><i class="bi bi-chat-left-text"></i> My Notes</a>

            <a href="student/Purchased.php"><i class="bi bi-people"></i> My courses</a>
            <a href="student/Meetings.php"><i class="bi bi-calendar-event"></i> Scheduled Meetings</a>
            <a href="student/Request.php"><i class="bi bi-chat-left-text"></i> Requests</a>
        </div>

        <div class="main-content">

            <div class="profile-card">

                <div class="text-end mb-3">
                    <a href="edit_profile.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil-square"></i> Edit Profile
                    </a>
                </div>

                <div class="text-center mb-4">
                    <div class="avatar-circle">
                        <?php
                        $upload_path = "../uploads/profile_pics/";
                        if (!empty($image) && file_exists($upload_path . $image)): ?>
                            <img src="<?php echo htmlspecialchars($upload_path . $image); ?>"
                                style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                        <?php else: ?>
                            <?php echo $initials; ?>
                        <?php endif; ?>
                    </div>
                    <h3 class="mt-3 fw-bold"><?php echo htmlspecialchars($full_name); ?></h3>
                    <p class="text-secondary mb-0">Welcome to your learning space</p>
                </div>

                <div class="info-row">
                    <i class="bi bi-person"></i>
                    <div>
                        <div class="value"><?php echo htmlspecialchars($full_name); ?></div>
                    </div>
                </div>

                <div class="info-row">
                    <i class="bi bi-envelope"></i>
                    <div>
                        <div class="value"><?php echo htmlspecialchars($email); ?></div>
                    </div>
                </div>

                <div class="info-row">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        <div class="value <?php
                                            echo strtolower($role) == 'admin'
                                                ? 'role-admin'
                                                : (strtolower($role) == 'teacher' ? 'role-teacher' : 'role-student');
                                            ?>">
                            <?php echo htmlspecialchars(ucfirst($role)); ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include('../assets/half-footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        <?php if (isset($_SESSION['update_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Profile Updated!',
                text: 'Your changes have been saved successfully.',
                timer: 2500,
                showConfirmButton: false
            });
            <?php unset($_SESSION['update_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['update_error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: 'There was an error updating your profile. Please try again.',
                confirmButtonColor: '#d33'
            });
            <?php unset($_SESSION['update_error']); ?>
        <?php endif; ?>
    </script>

</body>

</html>