<?php
session_start();
require '../../src/config/db.php';

// Validate user session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['household_id'])) {
    header('Location: ../../sign-in.php');
    exit;
}

$household_id = intval($_POST['household_id'] ?? 0);

if ($household_id !== $_SESSION['household_id']) {
    $_SESSION['error'] = 'Household mismatch';
    header('Location: ../../settings/household_management.php');
    exit;
}

// Delete household and all related data (CASCADE will handle it)
$stmt = $conn->prepare("DELETE FROM HOUSEHOLD WHERE ID_HOUSEHOLD = ?");
$stmt->bind_param('i', $household_id);

if ($stmt->execute()) {
    // Clear household session
    unset($_SESSION['household_id']);
    $_SESSION['success'] = 'Household deleted successfully';
    header('Location: ../../households.php');
} else {
    $_SESSION['error'] = 'Failed to delete household';
    header('Location: ../../settings/household_management.php');
}
$stmt->close();
exit;
?>
