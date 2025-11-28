<?php
require "../../src/config/db.php";
require "../../src/config/response.php";

// Get form data
$name = $_POST['name'] ?? "";
$email = $_POST['email'] ?? "";
$password = $_POST['password'] ?? "";

if(!$name || !$email || !$password) {
    jsonResponse("error", "Missing fields");
}

$avatar_path = NULL;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../IMAGES/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('avatar_') . '.' . $ext;
    $target = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
        $avatar_path = 'IMAGES/avatars/' . $filename;
    }
}

$hashed = password_hash($password, PASSWORD_BCRYPT);

if ($avatar_path) {
    $stmt = $conn->prepare("INSERT INTO USER (USER_NAME, USER_EMAIL, USER_PASSWORD, AVATAR) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed, $avatar_path);
} else {
    $stmt = $conn->prepare("INSERT INTO USER (USER_NAME, USER_EMAIL, USER_PASSWORD) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed);
}

if ($stmt->execute()) {
    header("Location: ../../sign-in.html");
    exit;
} else {
    jsonResponse("error", "Email already exists");
    header("Location: ../../new-account.html");
    exit;
}
?>
