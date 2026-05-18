<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // 🔹 SMTP SETTINGS
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'damanchoudhary2002@gmail.com';
    $mail->Password   = 'hhnnuzgwyqardrpg'; // ✅ NEW APP PASSWORD
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // 🔹 DEBUG
    $mail->SMTPDebug  = 2;
    $mail->Debugoutput = 'html';

    // 🔹 MAIL
    $mail->setFrom('damanchoudhary2002@gmail.com', 'Test Mail');
    $mail->addAddress('damanchoudhary2002@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test';
    $mail->Body    = 'If you received this, SMTP is working ✅';

    $mail->send();
    echo "MAIL SENT SUCCESSFULLY";

} catch (Exception $e) {
    echo "MAIL FAILED: {$mail->ErrorInfo}";
}
