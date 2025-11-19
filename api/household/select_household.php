<?php
session_start();

if (!isset($_GET['hid'])) {
    header("Location: households.php");
    exit;
}

$household_id = (int)$_GET['hid'];
$_SESSION['active_household'] = $household_id;

header("Location: ../dashboard.php");
exit;
?>