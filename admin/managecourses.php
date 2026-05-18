<?php
include("../conn.php");

$conn->query("
CREATE TABLE IF NOT EXISTS baseline_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(100),
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
");

$uploadDir = __DIR__ . '../uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (isset($_POST['save_course_ajax'])) {
    $id = $_POST['course_id'] ?? '';
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['name'] != '') {
        $image = '../uploads/' . time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    }

    if ($id) {
        $query = "UPDATE baseline_courses SET name='$name', description='$desc', price='$price', category='$category'";
        if ($image) $query .= ", image='$image'";
        $query .= " WHERE id=$id";
    } else {
        $query = "INSERT INTO baseline_courses (name, description, price, category, image) 
                  VALUES ('$name','$desc','$price','$category','$image')";
    }

    $conn->query($query);
    echo json_encode(['status' => 'success']);
    exit;
}


if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $conn->query("DELETE FROM baseline_courses WHERE id=$id");
    echo json_encode(['status' => 'success']);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Manage Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <style>
        body {
            background-color: #f0f2f5;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #343a40 !important;
        }

        .navbar .navbar-brand,
        .navbar .nav-link {
            color: #fff !important;
        }

        .dataTables_wrapper {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0 5px;
            width: 100% !important;
        }

        table.dataTable thead th {
            background-color: #343a40;
            color: #fff;
            font-weight: 600;
            text-align: center;
            border: none;
            padding: 12px 15px;
        }

        table.dataTable tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            text-align: center;
        }

        table.dataTable tbody tr {
            background: #f9f9f9;
            border-radius: 5px;
        }

        table.dataTable tbody tr:hover {
            background-color: #e2e6ea;
        }

        button.dt-button,
        .dataTables_wrapper .btn {
            background-color: #6f42c1;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 5px;
        }

        button.dt-button:hover,
        .dataTables_wrapper .btn:hover {
            background-color: #5a32a3;
        }

        .dataTables_filter {
            float: right;
            text-align: right;
            margin-bottom: 15px;
        }

        .dataTables_filter label {
            font-weight: 500;
            color: #343a40;
        }

        .dataTables_filter input {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 6px 10px;
            width: 250px;
            transition: all 0.3s;
        }

        .dataTables_filter input:focus {
            outline: none;
            border-color: #6f42c1;
            box-shadow: 0 0 5px rgba(111, 66, 193, 0.5);
        }

        .dataTables_paginate {
            float: right;
            margin-top: 15px;
        }

        .dataTables_paginate a {
            color: #6f42c1 !important;
            padding: 6px 12px;
            border: 1px solid #6f42c1;
            border-radius: 5px;
            margin: 0 2px;
            text-decoration: none !important;
            transition: all 0.3s;
        }

        .dataTables_paginate a.current {
            background-color: #6f42c1 !important;
            color: #fff !important;
        }

        .dataTables_paginate a:hover {
            background-color: #5a32a3 !important;
            color: #fff !important;
        }

        .dataTables_length {
            float: left;
            margin-bottom: 15px;
        }

        .dataTables_length label {
            font-weight: 500;
            color: #343a40;
        }

        .dataTables_length select {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 6px 10px;
            margin-left: 5px;
        }

        table.dataTable tbody td a.btn-danger,
        table.dataTable tbody td button.btn-danger {
            background-color: #dc3545;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 6px 12px;
            transition: all 0.3s;
        }

        table.dataTable tbody td a.btn-danger:hover,
        table.dataTable tbody td button.btn-danger:hover {
            background-color: #b02a37;
            color: #fff;
        }

        table.dataTable tbody td a.btn-warning,
        table.dataTable tbody td button.btn-warning {
            background-color: #ffc107;
            color: #343a40;
            border: none;
            border-radius: 5px;
            padding: 6px 12px;
            transition: all 0.3s;
        }

        table.dataTable tbody td a.btn-warning:hover,
        table.dataTable tbody td button.btn-warning:hover {
            background-color: #e0a800;
            color: #343a40;
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <div class="d-flex justify-content-between mb-3">
            <h2>Manage Courses</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal">Add Course</button>
        </div>

        <table class="table table-bordered table-striped bg-white">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT * FROM baseline_courses");

                $i = 1;
                while ($row = $res->fetch_assoc()) {
                        echo "<tr>
                            <td>{$i}</td>
                            <td>{$row['name']}</td>
                            <td>{$row['description']}</td>
                            <td>{$row['category']}</td>
                            <td>{$row['price']}</td>
                            <td>" . ($row['image'] ? "<img src='{$row['image']}' width='80'>" : "") . "</td>
                            <td>
                                <button class='btn btn-sm btn-danger delete-course' data-id='{$row['id']}'>Delete</button>
                                <button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#courseModal' 
                                    data-id='{$row['id']}' data-name='{$row['name']}' data-desc='{$row['description']}' 
                                    data-price='{$row['price']}' data-category='{$row['category']}'>Update</button>
                            </td>
                        </tr>";
                    $i++;
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="courseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data" id="courseForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Course</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="course_id" id="course_id">
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" id="description" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Category</label>
                            <input type="text" name="category" id="category" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" id="price" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Image</label>
                            <input type="file" name="image" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="save_course" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.table').DataTable({
                "columnDefs": [{
                    "orderable": false,
                    "targets": [5, 6]
                }]
            });
        });

        var courseModal = document.getElementById('courseModal');
        courseModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var modalTitle = courseModal.querySelector('.modal-title');
            var courseId = courseModal.querySelector('#course_id');
            var name = courseModal.querySelector('#name');
            var desc = courseModal.querySelector('#description');
            var price = courseModal.querySelector('#price');
            var category = courseModal.querySelector('#category');

            if (button.getAttribute('data-id')) {
                modalTitle.textContent = "Update Course";
                courseId.value = button.getAttribute('data-id');
                name.value = button.getAttribute('data-name');
                desc.value = button.getAttribute('data-desc');
                price.value = button.getAttribute('data-price');
                category.value = button.getAttribute('data-category');
            } else {
                modalTitle.textContent = "Add Course";
                courseId.value = "";
                name.value = "";
                desc.value = "";
                price.value = "";
                category.value = "";
            }
        });
    </script>

</body>

</html>