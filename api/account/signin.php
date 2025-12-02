<?php
session_start();
require "../../src/config/db.php";

$identifier = $_POST['username'] ?? "";  // can be email or username
$password   = $_POST['password'] ?? "";

// Fetch user by email OR username
$stmt = $conn->prepare("
    SELECT ID_USER, USER_PASSWORD, USER_NAME, USER_EMAIL, IS_KID
    FROM USER 
    WHERE USER_EMAIL = ? OR USER_NAME = ?
    LIMIT 1
");
$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: ../../sign-in.php?error=account_not_found");
    exit;
}

$user = $res->fetch_assoc();

// Verify password
if (!password_verify($password, $user['USER_PASSWORD'])) {
    header("Location: ../../sign-in.php?error=wrong_password");
    exit;
}

// Login OK
$_SESSION['user_id'] = $user['ID_USER'];
$_SESSION['is_kid'] = $user['IS_KID'];
$_SESSION['user_name'] = $user['USER_NAME'];

// Fetch households
$stmt2 = $conn->prepare("
    SELECT H.ID_HOUSEHOLD, H.HOUSEHOLD_NAME 
    FROM HOUSEHOLD_MEMBER HM
    JOIN HOUSEHOLD H ON HM.ID_HOUSEHOLD = H.ID_HOUSEHOLD
    WHERE HM.ID_USER = ?
");
$stmt2->bind_param("i", $user['ID_USER']);
$stmt2->execute();
$result = $stmt2->get_result();

$households = [];
while ($row = $result->fetch_assoc()) {
    $households[] = $row;
}

// Redirect (you have same redirect for all cases)
header("Location: ../../households.php");
exit;

?>
