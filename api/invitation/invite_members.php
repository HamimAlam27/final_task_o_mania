<?php
session_start();
require "../src/config/db.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['active_household'])) {
    die("Unauthorized");
}

$household_id = $_SESSION['active_household'];
$email = trim($_POST['email']);
$invited_by = $_SESSION['user_id'];

if ($email === "") {
    die("Invalid email");
}

// 1. Check if user exists
$stmt = $conn->prepare("SELECT ID_USER FROM USER WHERE USER_EMAIL = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();
$invited_user_id = $user['ID_USER'];
$user_id = $invited_user_id;

// 2. Check for existing invitation
$check = $conn->prepare("
    SELECT ID_INVITATION 
    FROM INVITATION
    WHERE INVITED_EMAIL = ? AND ID_HOUSEHOLD = ? AND STATUS = 'pending'
");
$check->bind_param("si", $email, $household_id);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows > 0) {
    die("Invitation already sent.");
}

// 3. Insert into INVITATION table (NOT directly into HOUSEHOLD_MEMBER)
$insert = $conn->prepare("
    INSERT INTO INVITATION (ID_HOUSEHOLD, INVITED_EMAIL, INVITED_BY, STATUS)
    VALUES (?, ?, ?, 'pending')
");
$insert->bind_param("isi", $household_id, $email, $invited_by);
$insert->execute();

// 4. Add a notification for the invited user
$invitation_id = $conn->insert_id; // get ID_INVITATION

// Insert notification for invited user
$notification_title = "Household Invitation";
$notification_message = "You have been invited to join a household.";
$stmt = $conn->prepare("INSERT INTO NOTIFICATION (ID_USER, NOTIFICATION_TYPE, REFERENCE_ID, NOTIFICATION_TITLE, NOTIFICATION_MESSAGE, IS_READ, NOTIFICATION_CREATED) VALUES (?, 'invitation', ?, ?, ?, 0, NOW())");
$stmt->bind_param("iiss", $user_id, $invitation_id, $notification_title, $notification_message);
$stmt->execute();


// Redirect back to members page
header("Location: ../household_members.php");
exit;

?>
