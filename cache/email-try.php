<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

$mail = new PHPMailer(true);

try {
    // SERVER SETTINGS
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    // Your Gmail + App Password
    $mail->Username = 'taskmania25@gmail.com';
    $mail->Password = 'yrth ozid lndq uluq';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // SENDER + RECEIVER
    $mail->setFrom('taskmania25@gmail.com', 'Task-O-Mania');
    $mail->addAddress('rahi.alam2k20@gmail.com'); // change to whoever you want

    // MESSAGE CONTENT
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Task-O-Mania';
    $mail->Body = '<h3>This is a test email!</h3><p>Sent using Gmail + PHPMailer.</p>';

    $mail->send();
    echo "Email sent successfully!";
} catch (Exception $e) {
    echo "Failed to send email: {$mail->ErrorInfo}";
}
