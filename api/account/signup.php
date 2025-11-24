<?php
require "../../src/config/db.php";
require "../../src/config/response.php";

$name = $_POST['name'] ?? "";
// $username = $_POST['username'] ?? "";
$email = $_POST['email'] ?? "";
$password = $_POST['password'] ?? "";

if(!$name || !$email || !$password) {
    jsonResponse("error", "Missing fields");
}

$invite_link = bin2hex(random_bytes(16)); // simple unique token, can use more complex

$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO USER (USER_NAME, USER_EMAIL, USER_PASSWORD) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $hashed);

if ($stmt->execute()) {
    header("Location: ../../sign-in.html");
        exit;
} else {
    jsonResponse("error", "Email already exists");
    header("Location: ../../new-account.html");
        exit;
}
?>
