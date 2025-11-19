<?php
require "../src/config/db.php";
require "../src/config/response.php";

$name = $_POST['name'] ?? "";
$email = $_POST['email'] ?? "";
$password = $_POST['password'] ?? "";

if(!$name || !$email || !$password){
    jsonResponse("error", "Missing fields");
}

$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO USER (USER_NAME, USER_EMAIL, USER_PASSWORD) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $hashed);

if ($stmt->execute()) {
    header("Location: ../sign-in.html");
        exit;
} else {
    jsonResponse("error", "Email already exists");
}
?>
