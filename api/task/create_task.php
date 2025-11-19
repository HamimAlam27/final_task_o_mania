<?php
session_start();
require "../src/config/db.php";

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../sign-in.php");
    exit;
}

// Make sure a household is active
if (!isset($_SESSION['active_household'])) {
    header("Location: ../households.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['active_household'];

// Form data
$task_name = $_POST['task_name'] ?? "";
$task_description = $_POST['task_description'] ?? "";
$task_points = $_POST['task_points'] ?? 0;
// $is_collab = isset($_POST['ai_validation']) ? 1 : 0;  // reuse as collaborative or leave separate
$is_collab = 0;

// Validate required fields
if (empty($task_name) || empty($task_description)) {
    die("Missing data");
}

// ---------------------
// HANDLE IMAGE UPLOAD
// ---------------------
$task_image = null;

if (!empty($_FILES['task_photo']['name'])) {
    $uploadDir = "../uploads/tasks/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileTmp = $_FILES['task_photo']['tmp_name'];
    $fileName = time() . "_" . basename($_FILES['task_photo']['name']);
    $dest = $uploadDir . $fileName;

    if (move_uploaded_file($fileTmp, $dest)) {
        $task_image = $fileName; // store file name only
    }
}

// ---------------------
// INSERT INTO DATABASE
// ---------------------
$stmt = $conn->prepare("
    INSERT INTO TASK 
    (ID_HOUSEHOLD, ID_USER, TASK_NAME, TASK_DESCRIPTION, TASK_POINT, TASK_IMAGE, IS_COLLABORATIVE, TASK_CREATED)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param(
    "iissisi",
    $household_id,
    $user_id,
    $task_name,
    $task_description,
    $task_points,
    $task_image,
    $is_collab
);

if ($stmt->execute()) {
    header("Location: ../dashboard.php?household_id=" . $household_id);
    exit;
} else {
    echo "Database error: " . $stmt->error;
}
?>
