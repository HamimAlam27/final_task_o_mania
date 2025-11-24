<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

// FORM INPUTS
$to = $_POST['to'] ?? '';
$subject = $_POST['subject'] ?? '';
$message = $_POST['message'] ?? '';

// Validate
if (!$to || !$subject || !$message) {
    die("All fields are required!");
}

$mail = new PHPMailer(true);

try {
    // SMTP SETUP
    $mail->isSMTP();
    $mail->Host = 'smtp.mail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'taskmania@mail.com'; 
    $mail->Password = 'taskmania2025'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // WHO SENDS THE EMAIL
    $mail->setFrom('taskmania@mail.com', 'Task-O-Mania');

    // WHO RECEIVES IT
    $mail->addAddress($to);

    // EMAIL CONTENT
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = nl2br($message);

    $mail->send();
    echo "Email sent successfully to <b>$to</b>!";
} catch (Exception $e) {
    echo "Failed to send email: {$mail->ErrorInfo}";
}
