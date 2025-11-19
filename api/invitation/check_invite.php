<?php
require "../../src/config/db.php";
require "../../src/config/response.php";

$email = $_POST['email'];

$stmt = $conn->prepare("
    SELECT I.ID_INVITATION, H.HOUSEHOLD_NAME, I.ID_HOUSEHOLD
    FROM INVITATION I
    JOIN HOUSEHOLD H ON I.ID_HOUSEHOLD = H.ID_HOUSEHOLD
    WHERE I.INVITED_EMAIL = ? AND I.STATUS = 'pending'
");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

$invites = [];
while($row = $result->fetch_assoc()) {
    $invites[] = $row;
}

jsonResponse("success", "Invites found", $invites);
?>
