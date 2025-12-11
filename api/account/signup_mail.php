<?php
session_start();
require "../../src/config/db.php";
require "../../src/config/response.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';
require '../../PHPMailer/src/Exception.php';

$mail = new PHPMailer(true);


$mode = $_GET['mode'] ?? "";

if ($mode === "new") {
    $name = $_POST['name'] ?? "";
    // $username = $_POST['username'] ?? "";
    $email = $_POST['email'] ?? "";
    $password = $_POST['password'] ?? "";
    $is_kid = $_POST['is_kid'] ?? "0";

    $avatar_path = NULL;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../images/profiles/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('avatar_') . '.' . $ext;
    $target = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
        // $avatar_path = 'images/profiles/' . $filename;
        $avatar_path = $filename;
    }
}



     $invite_link = bin2hex(random_bytes(4)); // simple unique token

    $_SESSION['pending_name'] = $name;
    $_SESSION['pending_email'] = $email;
    $_SESSION['pending_password'] = $password;   // raw password
    $_SESSION['pending_code'] = $invite_link;
    $_SESSION['pending_avatar'] = $avatar_path;
    $_SESSION['pending_is_kid'] = $is_kid;



    if (!$name || !$email || !$password) {
        jsonResponse("error", "Missing fields");
    }

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
        $mail->addAddress($email);
        // MESSAGE CONTENT
        $mail->isHTML(true);
        $mail->Subject = 'Verification Email from Task-O-Mania';
        $mail->Body = '<h3>Do not share this code</h3><p>' . $invite_link . '</p>';

        $mail->send();
        echo "Email sent successfully!";
        header("Location: ../../verify-account.html");
    } catch (Exception $e) {
        echo "Failed to send email: {$mail->ErrorInfo}";
    }
} elseif ($mode === "verify") {

    $code = $_POST['code'] ?? "";
    // additional checks for new account can be added here
    if ($_SESSION['pending_code'] !== $code) {
            echo "<script>alert('Incorrect verification code. Please try again.'); window.location.href='../../new-account.html';</script>";
    exit;
    }
    $hashed = password_hash($_SESSION['pending_password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO USER (USER_NAME, USER_EMAIL, USER_PASSWORD, AVATAR, IS_KID) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $_SESSION['pending_name'], $_SESSION['pending_email'], $hashed, $_SESSION['pending_avatar'], $_SESSION['pending_is_kid']);

    if ($stmt->execute()) {

            session_unset();
    session_destroy();

        header("Location: ../../sign-in.html");
        exit;
    } else {
        jsonResponse("error", "Email already exists");
        header("Location: ../../new-account.html");
        exit;
    }
}









// <div style="color: red; padding: 10px; background: #ffe6e6; border-radius: 5px; margin-bottom: 10px;">
//     Incorrect verification code. Please try again.
// </div>