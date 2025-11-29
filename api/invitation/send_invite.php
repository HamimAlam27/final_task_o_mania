<?php
require "../../src/config/db.php";
require "../../src/config/response.php";

$invitedEmail = $_POST['email'];
$householdId = $_POST['household_id'];
$invitedBy = $_POST['invited_by'];

// Check if invitation already exists for this email in this household
$check_stmt = $conn->prepare("
    SELECT ID_INVITATION FROM INVITATION 
    WHERE ID_HOUSEHOLD = ? AND INVITED_EMAIL = ?
");
$check_stmt->bind_param("is", $householdId, $invitedEmail);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    jsonResponse("error", "This email has already been invited to this household");
    $check_stmt->close();
    exit;
}
$check_stmt->close();

$stmt = $conn->prepare("
    INSERT INTO INVITATION (ID_HOUSEHOLD, INVITED_EMAIL, INVITED_BY, STATUS)
    VALUES (?, ?, ?, 'pending')
");
$stmt->bind_param("isi", $householdId, $invitedEmail, $invitedBy);

// Trigger tr_notify_on_household_invitation automatically creates notification when invitation is inserted
if ($stmt->execute()) {
    jsonResponse("success", "Invite sent");
}

jsonResponse("error", "Failed to send invite");
?>
