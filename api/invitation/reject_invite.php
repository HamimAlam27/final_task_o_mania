<?php
session_start();
require "../src/config/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$invitation_id = $_POST['invitation_id'];

// Fetch invitation
$stmt = $conn->prepare("SELECT * FROM INVITATION WHERE ID_INVITATION = ? AND INVITED_EMAIL = (SELECT USER_EMAIL FROM USER WHERE ID_USER = ?)");
$stmt->bind_param("ii", $invitation_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Invitation not found.");

// Update invitation status
$stmt = $conn->prepare("UPDATE INVITATION SET STATUS = 'rejected' WHERE ID_INVITATION = ?");
$stmt->bind_param("i", $invitation_id);
$stmt->execute();

header("Location: notifications.php");
exit;
?>
