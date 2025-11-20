<?php
session_start();
require "../../src/config/db.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['household'])) {
    die("Unauthorized");
}

$household_id = $_SESSION['household'];
$invited_by = $_SESSION['user_id'];

// Form sends invited_emails[] array
$emails = $_POST['invited_emails'] ?? [];

if (!is_array($emails) || empty($emails)) {
    die("No emails received");
}

foreach ($emails as $email) {

    $email = trim($email);
    if ($email === "") continue;   // skip empty inputs

    // 1. Check if user exists
    $stmt = $conn->prepare("SELECT ID_USER FROM USER WHERE USER_EMAIL = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Optional: skip or record error
        continue;
    }

    $row = $result->fetch_assoc();
    $user_id = $row['ID_USER'];

    // 2. Check if there is already a pending invitation
    $check = $conn->prepare("
        SELECT ID_INVITATION 
        FROM INVITATION
        WHERE INVITED_EMAIL = ? 
        AND ID_HOUSEHOLD = ?
        AND STATUS = 'pending'
    ");
    $check->bind_param("si", $email, $household_id);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows > 0) {
        continue; // skip duplicates
    }

    // 3. Insert into INVITATION table
    $insert = $conn->prepare("
        INSERT INTO INVITATION (ID_HOUSEHOLD, INVITED_EMAIL, INVITED_BY, STATUS)
        VALUES (?, ?, ?, 'pending')
    ");
    $insert->bind_param("isi", $household_id, $email, $invited_by);
    $insert->execute();

    $invitation_id = $conn->insert_id;

    // 4. Create NOTIFICATION for the invited user
    $notification_title = "Household Invitation";
    $notification_message = "You have been invited to join a household.";

    $notif = $conn->prepare("
        INSERT INTO NOTIFICATION 
        (ID_USER, NOTIFICATION_TYPE, REFERENCE_ID, NOTIFICATION_TITLE, NOTIFICATION_MESSAGE, IS_READ, NOTIFICATION_CREATED)
        VALUES (?, 'invitation', ?, ?, ?, 0, NOW())
    ");
    $notif->bind_param("iiss", $user_id, $invitation_id, $notification_title, $notification_message);
    $notif->execute();
}

// Redirect after processing all emails
header("Location: ../../household_members.php");
exit;

