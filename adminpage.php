<?php
session_name('ADMIN_SESSION');
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login/login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <link rel="icon" type="image/png" href="images/favicon.png">
    <style>
        body {
            background: #f8f9fa;
            font-family: Arial, sans-serif;
            overflow: hidden;
            /* full page scroll band */
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 56px;
            /* navbar height */
            left: 0;
            width: 16.6667%;
            /* col-md-2 */
            height: calc(100vh - 56px);
            background: #343a40;
            color: #fff;
            overflow: hidden;
            /* sidebar scroll band */
        }

        .sidebar a {
            color: #fff;
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #495057;
        }

        /* Content area */
        #contentArea {
            margin-left: 16.6667%;
            /* sidebar ki jagah chhodne ke liye */
            padding: 20px;
            background: #fff;
            height: calc(100vh - 56px);
            overflow-y: auto;
            /* sirf content scroll ho */
        }

        /* Loading animation */
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
    </style>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <ul class="navbar-nav flex-row">
                <li class="nav-item">
                    <span class="navbar-text text-light me-3">
                        Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
                    </span>
                </li>
                <li class="nav-item"><a class="nav-link px-3" href="logout.php?role=admin">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <a href="admin/managecourses.php" class="ajax-link active">
                    <i class="fas fa-book me-2"></i>Courses
                </a>
                <a href="admin/manageusers.php" class="ajax-link">
                    <i class="fas fa-users me-2"></i>Users
                </a>
                <a href="admin/manageteacher.php" class="ajax-link">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Teachers
                </a>
                <a href="admin/managequery.php" class="ajax-link">
                    <i class="fas fa-question-circle me-2"></i>Queries
                </a>
                <a href="admin/meeting_request.php" class="ajax-link">
                    <i class="fas fa-video me-2"></i>Meeting Slots
                </a>
                <a href="admin/Request_meeting.php" class="ajax-link">
                    <i class="fa-solid fa-clock me-2"></i>Requests
                </a>
            </div>

            <!-- Main content area -->
            <div class="col-md-10" id="contentArea">
                <h4>Welcome to Admin Dashboard</h4>
                <p>Select a menu item to manage data.</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        function loadPage(url, link) {
            // Loading animation
            document.getElementById('contentArea').innerHTML = `
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => {
                    document.getElementById('contentArea').innerHTML = html;
                    document.querySelectorAll('.ajax-link').forEach(a => a.classList.remove('active'));
                    if (link) link.classList.add('active');

                    // Initialize specific page functionality
                    initTeacherPage();
                    initUserPage();
                    initCoursesPage();

                    if (url.includes('admin/meeting_request.php')) {
                        initMeetingPage();
                    }

                    if (url.includes('admin/Request_meeting.php')) {
                        initRequestMeetingPage();
                    }

                    // Initialize DataTables for any tables
                    const table = document.querySelector('.table');
                    if (table && !$.fn.DataTable.isDataTable(table)) {
                        $(table).DataTable({
                            "columnDefs": [{
                                "orderable": false,
                                "targets": [5, 6]
                            }]
                        });
                    }
                })
                .catch(err => {
                    console.error('Error loading page:', err);
                    document.getElementById('contentArea').innerHTML = "<p class='text-danger'>Error loading page. Please try again.</p>";
                });
        }

        document.querySelectorAll('.ajax-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadPage(this.getAttribute('href'), this);
            });
        });

        // Load initial page
        loadPage("admin/managecourses.php", document.querySelector(".ajax-link.active"));

        function initTeacherPage() {
            if ($.fn.DataTable.isDataTable('#teacherTable')) {
                $('#teacherTable').DataTable().destroy();
            }
            $('#teacherTable').DataTable({
                "columnDefs": [{
                    "orderable": false,
                    "targets": [7]
                }]
            });

            $('#addTeacherBtn').off('click').on('click', function() {
                $('#teacherForm')[0].reset();
                $('#teacher_id').val('');
                $('#status').val('pending');
                $('#teacherModal .modal-title').text('Add Teacher');
                $('#teacherModal').modal('show');
            });

            $(document).off('click', '.edit-btn').on('click', '.edit-btn', function() {
                var row = $(this).closest('tr');
                $('#teacher_id').val(row.data('id'));
                $('#full_name').val(row.find('td:eq(1)').text());
                $('#email').val(row.find('td:eq(2)').text());
                $('#subscribe').prop('checked', row.find('td:eq(3)').text() == 'Yes');
                $('#status').val(row.find('.status').text());
                $('#teacherModal .modal-title').text('Edit Teacher');
                $('#teacherModal').modal('show');
            });

            $(document).off('click', '.approve-btn').on('click', '.approve-btn', function() {
                var row = $(this).closest('tr');
                var btn = $(this);
                $.post('admin/manageteacher.php', {
                    action: 'approve_teacher',
                    teacher_id: row.data('id')
                }, function(res) {
                    var r = JSON.parse(res);
                    if (r.status == 'success') {
                        row.find('.status').text('approved');
                        btn.prop('disabled', true);
                    }
                });
            });

            $(document).off('click', '.delete-btn').on('click', '.delete-btn', function() {
                if (!confirm('Are you sure to delete this teacher?')) return;
                var row = $(this).closest('tr');
                $.post('admin/manageteacher.php', {
                    action: 'delete_teacher',
                    teacher_id: row.data('id')
                }, function(res) {
                    var r = JSON.parse(res);
                    if (r.status == 'success') row.remove();
                });
            });

            $('#teacherForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                var data = $(this).serialize() + '&action=save_teacher';

                $.post('admin/manageteacher.php', data, function(res) {
                    var r = JSON.parse(res);
                    if (r.status == 'success') {
                        $('#teacherModal').modal('hide');
                        loadPage('admin/manageteacher.php');
                    } else {
                        alert('Error saving teacher. Please try again.');
                    }
                }).fail(function() {
                    alert('Email Already exists.');
                });
            });
        }

        function initCoursesPage() {

            $(document).off('click', '.delete-course').on('click', '.delete-course', function() {
                if (!confirm('Are you sure you want to delete this course?')) return;
                var id = $(this).data('id');

                $.post('admin/managecourses.php', {
                    delete_id: id
                }, function(res) {
                    loadPage('admin/managecourses.php', $('.ajax-link.active')[0]);
                });
            });

            $(document).off('submit', '#courseForm').on('submit', '#courseForm', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('save_course_ajax', 1);
                $.ajax({
                    url: 'admin/managecourses.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#courseModal').modal('hide');
                        loadPage('admin/managecourses.php', $('.ajax-link.active')[0]);
                    },
                    error: function() {
                        alert('Error saving course. Please try again.');
                    }
                });
            });

            $(document).off('click', '.btn-warning[data-bs-target="#courseModal"], .btn-primary[data-bs-target="#courseModal"]').on('click', '.btn-warning[data-bs-target="#courseModal"], .btn-primary[data-bs-target="#courseModal"]', function() {
                var button = $(this);
                var modal = $('#courseModal');
                var modalTitle = modal.find('.modal-title');
                var courseId = modal.find('#course_id');
                var name = modal.find('#name');
                var desc = modal.find('#description');
                var price = modal.find('#price');
                var category = modal.find('#category');

                if (button.hasClass('btn-warning')) {
                    modalTitle.text('Update Course');
                    courseId.val(button.data('id'));
                    name.val(button.data('name'));
                    desc.val(button.data('desc'));
                    price.val(button.data('price'));
                    category.val(button.data('category'));
                } else { // Add
                    modalTitle.text('Add Course');
                    courseId.val('');
                    name.val('');
                    desc.val('');
                    price.val('');
                    category.val('');
                }
            });
        }

        function initUserPage() {
            if ($.fn.DataTable.isDataTable('#usersTable')) {
                $('#usersTable').DataTable().destroy();
            }
            $('#usersTable').DataTable({
                "order": [
                    [0, "desc"]
                ],
                "columnDefs": [{
                    "orderable": false,
                    "targets": [6]
                }]
            });


            $(document).off('click', '.edit-user').on('click', '.edit-user', function() {
                var btn = $(this);
                $('#edit_user_id').val(btn.data('id'));
                $('#edit_full_name').val(btn.data('name'));
                $('#edit_email').val(btn.data('email'));
                $('#edit_subscribe').prop('checked', btn.data('subscribe') == 1);
                $('#editUserModal').modal('show');
            });


            $('#addUserModal').on('show.bs.modal', function() {
                $(this).find('form')[0].reset();
            });
        }

        // Add User
        $(document).off('submit', '#addUserModal form').on('submit', '#addUserModal form', function(e) {
            e.preventDefault();
            var formData = $(this).serialize() + '&add_user=1';
            $.post('admin/manageusers.php', formData, function(res) {
                var r = JSON.parse(res);
                if (r.status == 'success') {
                    $('#addUserModal').modal('hide');
                    const activeLink = document.querySelector('.ajax-link.active');
                    if (activeLink) {
                        loadPage(activeLink.getAttribute('href'), activeLink);
                    }
                } else {
                    alert(r.message || 'Failed to add user');
                }
            });
        });

        // Edit User
        $(document).off('submit', '#editUserModal form').on('submit', '#editUserModal form', function(e) {
            e.preventDefault();
            var formData = $(this).serialize() + '&update_user=1';
            $.post('admin/manageusers.php', formData, function(res) {
                var r = JSON.parse(res);
                if (r.status == 'success') {
                    $('#editUserModal').modal('hide');
                    const activeLink = document.querySelector('.ajax-link.active');
                    if (activeLink) {
                        loadPage(activeLink.getAttribute('href'), activeLink);
                    }
                } else {
                    alert(r.message || 'Failed to update user');
                }
            });
        });

        // Delete User
        $(document).off('click', '.delete-user').on('click', '.delete-user', function() {
            if (!confirm('Are you sure to delete this user?')) return;
            var id = $(this).data('id');
            $.post('admin/manageusers.php', {
                delete_id: id
            }, function(res) {
                var r = JSON.parse(res);
                if (r.status == 'success') {
                    const activeLink = document.querySelector('.ajax-link.active');
                    if (activeLink) {
                        loadPage(activeLink.getAttribute('href'), activeLink);
                    }
                } else {
                    alert('Failed to delete user');
                }
            });
        });

        function initMeetingPage() {
            console.log('Initializing Meeting Page...');

            // Add Meeting Form
            $('#meetingForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Adding Meeting...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                $.post('admin/meeting_request.php',
                    $(this).serialize() + '&action=add_meeting',
                    function(res) {
                        try {
                            var r = JSON.parse(res);
                            if (r.status == 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    text: r.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    const activeLink = document.querySelector('.ajax-link.active');
                                    if (activeLink) {
                                        loadPage(activeLink.getAttribute('href'), activeLink);
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: r.message || 'Error adding meeting.',
                                    icon: 'error'
                                });
                            }
                        } catch (e) {
                            console.error('Parse error:', e);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Invalid response from server',
                                icon: 'error'
                            });
                        }
                    }
                ).fail(function(xhr, status, error) {
                    Swal.fire({
                        title: 'Network Error!',
                        text: 'Status: ' + xhr.status + ' - ' + error,
                        icon: 'error'
                    });
                });
            });

            // Save Link Button
            $(document).off('click', '.save-link-btn').on('click', '.save-link-btn', function() {
                var slot_id = $(this).data('id');
                var link_input = $('#link_' + slot_id);
                var link = link_input.val().trim();

                if (!link) {
                    Swal.fire({
                        title: 'Warning!',
                        text: 'Please enter a meeting link',
                        icon: 'warning'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Saving Link...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: 'admin/meeting_request.php',
                    type: 'POST',
                    data: {
                        action: 'add_link',
                        slot_id: slot_id,
                        meeting_link: link
                    },
                    success: function(res) {
                        try {
                            var r = JSON.parse(res);
                            if (r.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    text: r.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: r.message || 'Error saving link',
                                    icon: 'error'
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Server error occurred',
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Network Error!',
                            text: 'Status: ' + xhr.status + ' - ' + error,
                            icon: 'error'
                        });
                    }
                });
            });

            // Delete Meeting 
            $(document).off('click', '.delete-meeting').on('click', '.delete-meeting', function() {
                var slot_id = $(this).data('id');
                var card = $(this).closest('.col-md-4');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This meeting and all related requests will be deleted!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('Deleting meeting with ID:', slot_id);

                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        $.ajax({
                            url: 'admin/meeting_request.php',
                            type: 'POST',
                            data: {
                                action: 'delete_meeting',
                                id: slot_id
                            },
                            success: function(res) {
                                try {
                                    var r = JSON.parse(res);
                                    console.log('Delete response:', r);

                                    if (r.status === 'success') {
                                        card.fadeOut(300, function() {
                                            $(this).remove();
                                        });

                                        Swal.fire({
                                            title: 'Deleted!',
                                            text: r.message,
                                            icon: 'success',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: r.message || 'Failed to delete meeting',
                                            icon: 'error'
                                        });
                                    }
                                } catch (e) {
                                    console.error('Parse error:', e, 'Response:', res);
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Invalid response from server',
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX error:', status, error);
                                Swal.fire({
                                    title: 'Server Error!',
                                    text: 'Status: ' + xhr.status + ' - ' + error,
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            });
        }

        function initRequestMeetingPage() {
            console.log('Initializing Request Meeting Page...');

            // Filter functionality
            $('.filter-btn').off('click').on('click', function() {
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
            $(document).off('click', '.approve-request').on('click', '.approve-request', function() {
                const id = $(this).data('id');
                $('#approveId').val(id);
                $('#approveModal').modal('show');
            });

            // Reject Request
            $(document).off('click', '.reject-request').on('click', '.reject-request', function() {
                const id = $(this).data('id');
                $('#rejectId').val(id);
                $('#rejectModal').modal('show');
            });

            // Update Request
            $(document).off('click', '.update-request').on('click', '.update-request', function() {
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
            $('#update_status').off('change').on('change', function() {
                if ($(this).val() === 'approved') {
                    $('#meetingLinkField').show();
                    $('#update_meeting_link').prop('required', true);
                } else {
                    $('#meetingLinkField').hide();
                    $('#update_meeting_link').prop('required', false);
                }
            });

            // Form submissions
            $('#approveForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                submitRequest($(this).serialize());
            });

            $('#rejectForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                submitRequest($(this).serialize());
            });

            $('#updateForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                submitRequest($(this).serialize());
            });

            // Delete Request
            $(document).off('click', '.delete-request').on('click', '.delete-request', function() {
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
                    url: "admin/Request_meeting.php",
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
                    url: "admin/Request_meeting.php",
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
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initTeacherPage();
        });
    </script>
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
</body>

</html>