<?php
session_start();
require '../../src/config/db.php';

// Validate user session and household
if (!isset($_SESSION['user_id']) || !isset($_SESSION['household_id'])) {
    header('Location: ../../sign-in.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['household_id'];
$household_name = $_POST['household_name'] ?? '';

if (empty($household_name)) {
    $_SESSION['error'] = 'Household name cannot be empty';
    header('Location: ../../settings/household_management.php');
    exit;
}

// Update household name
$stmt = $conn->prepare("UPDATE HOUSEHOLD SET HOUSEHOLD_NAME = ? WHERE ID_HOUSEHOLD = ?");
$stmt->bind_param('si', $household_name, $household_id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Household name updated successfully';
} else {
    $_SESSION['error'] = 'Failed to update household name';
}
$stmt->close();

header('Location: ../../settings/household_management.php');
exit;
?>
