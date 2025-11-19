<?php
require "../../src/config/db.php";
require "../../src/config/response.php";

$invitedEmail = $_POST['email'];
$householdId = $_POST['household_id'];
$invitedBy = $_POST['invited_by'];

$stmt = $conn->prepare("
    INSERT INTO INVITATION (ID_HOUSEHOLD, INVITED_EMAIL, INVITED_BY, STATUS)
    VALUES (?, ?, ?, 'pending')
");
$stmt->bind_param("isi", $householdId, $invitedEmail, $invitedBy);

if ($stmt->execute()) {
    jsonResponse("success", "Invite sent");
}

jsonResponse("error", "Failed to send invite");
?>
