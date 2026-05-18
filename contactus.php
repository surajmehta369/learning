<?php
session_name('STUDENT_SESSION');
session_start();

include("conn.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once("phpmailer/PHPMailer.php");
require_once("phpmailer/SMTP.php");
require_once("phpmailer/Exception.php");

// Handle AJAX POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $type = $conn->real_escape_string($_POST['type']);
    $message = $conn->real_escape_string($_POST['message']);

    if (!empty($name) && !empty($email) && !empty($type) && !empty($message)) {
        if ($type === 'query') {
            $insertQuery = "INSERT INTO `Baseline_contactus` (`name`, `email`, `query`) VALUES ('$name', '$email', '$message')";
        } else {
            $insertQuery = "INSERT INTO `Baseline_contactus` (`name`, `email`, `complaint`) VALUES ('$name', '$email', '$message')";
        }

        if ($conn->query($insertQuery)) {
            $mailSent = false;
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'surajmehta369@gmail.com';
                $mail->Password   = 'jnxuesncsbfzpdyy';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('surajmehta369@gmail.com', 'Website Contact Form');
                $mail->addAddress('surajmehta369@gmail.com', 'Admin');

                $mail->isHTML(true);
                $mail->Subject = "New $type from $name";
                $mail->Body    = "<h3>New Contact Form Submission</h3>
                                  <p><b>Name:</b> $name</p>
                                  <p><b>Email:</b> $email</p>
                                  <p><b>Type:</b> $type</p>
                                  <p><b>Message:</b><br>$message</p>";
                $mail->send();
                $mailSent = true;
            } catch (Exception $e) {
                // Log error if needed
            }

            echo json_encode(['status' => 'success', 'message' => "Your $type has been sent successfully!"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Database Error: " . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => "Please fill all fields."]);
    }
    exit; // Crucial: Stop HTML from rendering in the AJAX response
}

// Table creation (Only runs on GET/Initial Load)
$tableQuery = "CREATE TABLE IF NOT EXISTS `Baseline_contactus` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `query` TEXT DEFAULT NULL,
    `complaint` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$conn->query($tableQuery);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://kit.fontawesome.com/c32adfdcda.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>

  <?php include("assets/header.php"); ?>

  <section class="contact-section">
    <div class="container">
      <div class="section-header text-center">
        <h2>Contact Us</h2>
        <p>If you have any questions or complaints, feel free to reach out.</p>
      </div>

      <div class="row g-4">
        <div class="col-lg-6">
          <div class="contact-info-item">
            <i class="fas fa-home"></i>
            <div>
              <h4>Address</h4>
              <p>F-33, Phase-8, Industrial Area,<br>Mohali, Punjab 160071</p>
            </div>
          </div>
          </div>

        <div class="col-lg-6">
          <div class="contact-form">
            <h3>Send Message</h3>
            <form id="contactForm">
              <div class="form-group">
                <input type="text" name="name" required>
                <label>Full Name</label>
              </div>

              <div class="form-group">
                <input type="email" name="email" required>
                <label>Email</label>
              </div>

              <div class="form-group">
                <select name="type" required>
                  <option value="" disabled selected>Select Type</option>
                  <option value="query">Query</option>
                  <option value="complaint">Complaint</option>
                </select>
              </div>

              <div class="form-group">
                <textarea name="message" rows="4" required></textarea>
                <label>Message</label>
              </div>

              <button type="submit" id="submitBtn" class="btn btn-info w-100">Send</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include("assets/footer.php"); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.getElementById('contactForm').addEventListener('submit', function(e) {
      e.preventDefault(); // Stop page reload

      const submitBtn = document.getElementById('submitBtn');
      submitBtn.disabled = true;
      submitBtn.innerText = 'Sending...';

      const formData = new FormData(this);

      fetch(window.location.href, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          submitBtn.disabled = false;
          submitBtn.innerText = 'Send';

          if (data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Sent!',
              text: data.message,
              confirmButtonColor: '#0dcaf0'
            });
            document.getElementById('contactForm').reset();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message
            });
          }
        })
        .catch(error => {
          submitBtn.disabled = false;
          submitBtn.innerText = 'Send';
          Swal.fire({
            icon: 'error',
            title: 'Oops!',
            text: 'Something went wrong with the connection.'
          });
        });
    });
  </script>
</body>
</html>