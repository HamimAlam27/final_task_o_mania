<?php
session_start();
require "../src/config/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$invitation_id = $_POST['invitation_id'];
$notification_id = $_POST['notification_id'];

// Mark notification as read
$stmt = $conn->prepare("UPDATE notification SET IS_READ = 1 WHERE ID_NOTIFICATION = ?");
$stmt->bind_param("i", $notification_id);
$stmt->execute();

// Add user to household
$stmt = $conn->prepare("SELECT ID_HOUSEHOLD, INVITED_EMAIL, INVITED_BY FROM invitation WHERE ID_INVITATION = ?");
$stmt->bind_param("i", $invitation_id);
$stmt->execute();
$result = $stmt->get_result();
$invite = $result->fetch_assoc();

$household_id = $invite['ID_HOUSEHOLD'];
$stmt = $conn->prepare("SELECT ID_USER FROM USER WHERE USER_EMAIL = ?");
$stmt->bind_param("s", $invite['INVITED_EMAIL']);
$stmt->execute();
$user_res = $stmt->get_result()->fetch_assoc();
$added_user_id = $user_res['ID_USER'];

// Add to household members
// Trigger tr_create_points_on_household_join automatically creates POINTS record when user is added
$stmt = $conn->prepare("INSERT INTO household_member (ID_HOUSEHOLD, ID_USER, ROLE) VALUES (?, ?, 'member')");
$stmt->bind_param("ii", $household_id, $added_user_id);
$stmt->execute();

// Update invitation status
$stmt = $conn->prepare("UPDATE invitation SET STATUS = 'accepted' WHERE ID_INVITATION = ?");
$stmt->bind_param("i", $invitation_id);
$stmt->execute();

header("Location: ../notifications.php");
exit;
?>
