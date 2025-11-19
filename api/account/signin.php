<?php
session_start();
require "../src/config/db.php";

$email = $_POST['email'] ?? "";
$password = $_POST['password'] ?? "";

// Fetch user
$stmt = $conn->prepare("SELECT ID_USER, USER_PASSWORD, USER_NAME FROM USER WHERE USER_EMAIL = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: ../sign-in.php?error=account_not_found");
    exit;
}

$user = $res->fetch_assoc();

// Verify password
if (!password_verify($password, $user['USER_PASSWORD'])) {
    header("Location: ../sign-in.php?error=wrong_password");
    exit;
}

$_SESSION['user_id'] = $user['ID_USER'];

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

// Redirect based on number of households
if (count($households) === 0) {
    header("Location: ../no_household.php");
} elseif (count($households) === 1) {
    $_SESSION['active_household'] = $households[0]['ID_HOUSEHOLD'];
    header("Location: ../households.php");
} else {
    header("Location: ../households.php");
}
exit;
?>
