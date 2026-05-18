<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/conn.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Kolkata');

$smtpConfig = [
    'host'      => 'smtp.gmail.com',
    'user'      => 'damanchoudhary2002@gmail.com',
    'pass'      => 'hhnnuzgwyqardrpg',
    'port'      => 587,
    'fromEmail' => 'damanchoudhary2002@gmail.com',
    'fromName'  => 'School Management System'
];

$now = new DateTime();
$tenMinBefore = clone $now;
$tenMinBefore->modify('-10 minutes');
$fiveMinAfter = clone $now;
$fiveMinAfter->modify('+5 minutes');

$startWindow = $tenMinBefore->format('Y-m-d H:i:s');
$endWindow   = $fiveMinAfter->format('Y-m-d H:i:s');

echo "Current Time: " . $now->format('Y-m-d H:i:s') . "\n";
echo "Looking for meetings between $startWindow and $endWindow\n";

// ... [prepare SQL]
$stmt->bind_param("ss", $startWindow, $endWindow);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No meetings found for reminder.\n";
    exit;
}

function sendReminderEmail($toEmail, $toName, $subject, $body, $smtpConfig, $meetingId) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtpConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpConfig['user'];
        $mail->Password   = $smtpConfig['pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpConfig['port'];
        $mail->setFrom($smtpConfig['fromEmail'], $smtpConfig['fromName']);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        echo "Reminder email sent to {$toEmail} (Meeting ID: $meetingId)\n";
        return true;
    } catch (Exception $e) {
        echo "MAIL ERROR for {$toEmail} (Meeting ID: $meetingId): " . $e->getMessage() . "\n";
        return false;
    }
}

// Loop through meetings
while ($row = $result->fetch_assoc()) {
    $startTime = date('d M Y, h:i A', strtotime($row['link_start_time']));
    $emailBody = "
        <p>Hello <b>{$row['teacher_name']}</b> & <b>{$row['student_name']}</b>,</p>
        <p>Your meeting is scheduled to start at <strong>{$startTime}</strong>.</p>
        <p><b>Meeting Link:</b><br>
        <a href='{$row['meeting_link']}' target='_blank'>{$row['meeting_link']}</a></p>
        <p>Please join on time.</p>
        <br><p>— School Management System</p>
    ";

    $teacherSent = sendReminderEmail($row['teacher_email'], $row['teacher_name'], "⏰ Meeting Reminder", $emailBody, $smtpConfig, $row['id']);
    $studentSent = sendReminderEmail($row['student_email'], $row['student_name'], "⏰ Meeting Reminder", $emailBody, $smtpConfig, $row['id']);

    if ($teacherSent && $studentSent) {
        $update = $conn->prepare("UPDATE baseline_request SET reminder_sent = 1 WHERE id = ?");
        $update->bind_param("i", $row['id']);
        $update->execute();
        $update->close();
        echo "Reminder marked as sent for Meeting ID: {$row['id']}\n";
    } else {
        echo "Reminder NOT marked for Meeting ID: {$row['id']} due to email failures\n";
    }
}

$conn->close();
echo "Reminder cron executed successfully.\n";