<?php
session_start();
require '../../src/config/db.php';

// Validate user session
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  header('Content-Type: application/json');
  die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['household_id'] ?? null;

// Check if household is selected
if (!$household_id) {
  http_response_code(400);
  header('Content-Type: application/json');
  die(json_encode(['status' => 'error', 'message' => 'No household selected']));
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Content-Type: application/json');
  die(json_encode(['status' => 'error', 'message' => 'Method not allowed']));
}

// Get form data
$task_name = trim($_POST['task_name'] ?? '');
$task_points = intval($_POST['task_points'] ?? 0);
$task_description = trim($_POST['task_description'] ?? '');
$ai_validation = isset($_POST['ai_validation']) ? 1 : 0;
$task_status = 'todo'; // Default status
$task_image = null;

// Validate required fields
if (empty($task_name)) {
  http_response_code(400);
  header('Content-Type: application/json');
  die(json_encode(['status' => 'error', 'message' => 'Task name is required']));
}

if ($task_points < 0) {
  http_response_code(400);
  header('Content-Type: application/json');
  die(json_encode(['status' => 'error', 'message' => 'Points must be a positive number']));
}

// Handle file upload if provided
if (isset($_FILES['task_photo']) && $_FILES['task_photo']['error'] === UPLOAD_ERR_OK) {
  $file = $_FILES['task_photo'];
  $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  
  if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => 'Invalid file type. Please upload an image']));
  }
  
  // Read file as binary data
  $task_image = file_get_contents($file['tmp_name']);
}

// Insert into database
$insert_stmt = $conn->prepare("
  INSERT INTO TASK (ID_HOUSEHOLD, ID_USER, TASK_NAME, TASK_DESCRIPTION, TASK_POINT, TASK_IMAGE, TASK_STATUS)
  VALUES (?, ?, ?, ?, ?, ?, ?)
");

if (!$insert_stmt) {
  http_response_code(500);
  header('Content-Type: application/json');
  die(json_encode(['status' => 'error', 'message' => 'Database error']));
}

$insert_stmt->bind_param(
  'iissiis',
  $household_id,
  $user_id,
  $task_name,
  $task_description,
  $task_points,
  $task_image,
  $task_status
);

if ($insert_stmt->execute()) {
  $task_id = $conn->insert_id;
  http_response_code(201);
  header('Content-Type: application/json');
  echo json_encode([
    'status' => 'success',
    'message' => 'Task created successfully',
    'task_id' => $task_id
  ]);
} else {
  http_response_code(500);
  header('Content-Type: application/json');
  die(json_encode(['status' => 'error', 'message' => 'Failed to create task']));
}

$insert_stmt->close();
?>
