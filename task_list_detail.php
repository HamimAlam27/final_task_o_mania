<?php
session_start();
require 'src/config/db.php';

// Validate user session
if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['household_id'] ?? null;
$task_id = intval($_GET['task_id'] ?? 0);

// If no household selected or invalid task_id, redirect
if (!$household_id || !$task_id) {
    header('Location: total_task_list_bt_columns.php');
    exit;
}

// Fetch task details
$task_stmt = $conn->prepare("
    SELECT ID_TASK, TASK_NAME, TASK_DESCRIPTION, TASK_POINT, IMAGE_BEFORE, IMAGE_AFTER, TASK_STATUS, ID_USER, TASK_CREATED, IMAGE_NEEDED, AI_VALIDATION
    FROM TASK 
    WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?
");
$task_stmt->bind_param('ii', $task_id, $household_id);
$task_stmt->execute();
$task_result = $task_stmt->get_result();
$task = $task_result->fetch_assoc();
$task_stmt->close(); 

$image_needed = isset($task['IMAGE_NEEDED']) ? intval($task['IMAGE_NEEDED']) : 0;
$ai_validation = isset($task['AI_VALIDATION']) ? intval($task['AI_VALIDATION']) : 0;

if ($ai_validation === 1) {
    $image_needed = 1;
}

// If task not found, redirect
if (!$task) {
    header('Location: total_task_list_bt_columns.php');
    exit;
}

$is_creator = ($task['ID_USER'] == $user_id);

// Normalize task status (spaces and hyphens -> underscores)
$raw_status = $task['TASK_STATUS'] ?? '';
$normalized_status = strtolower(str_replace([' ', '-'], '_', $raw_status));

// Check if user is already in progress on this task
$progress_stmt = $conn->prepare("
    SELECT ID_PROGRESS FROM PROGRESS 
    WHERE ID_TASK = ? AND ID_USER = ?
");
$progress_stmt->bind_param('ii', $task_id, $user_id);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();
$already_joined = $progress_result->num_rows > 0;
$progress_stmt->close();

// Function to send notifications
function sendNotification($userId, $title, $message, $type, $referenceId, $conn) {
    $stmt = $conn->prepare("INSERT INTO NOTIFICATION (ID_USER, NOTIFICATION_TITLE, NOTIFICATION_MESSAGE, NOTIFICATION_TYPE, REFERENCE_ID, NOTIFICATION_CREATED) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('isssi', $userId, $title, $message, $type, $referenceId);
    $stmt->execute();
    $stmt->close();
}

// === MODIFICATION START: Handle Submission ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_completion'])) {
        if (!$already_joined) {
            $_SESSION['error'] = 'You must be working on this task before submitting proof.';
            header('Location: task_list_detail.php?task_id=' . $task_id);
            exit;
        }
        $filename = null;
        $status = 'pending';
        
        // Handle image upload if required
        if ($image_needed === 1) {
            if (empty($_FILES['completion_image']['name']) || $_FILES['completion_image']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'Image is required for this task.';
                header('Location: task_list_detail.php?task_id=' . $task_id);
                exit;
            }
            $upload_dir = 'images/tasks/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ext = pathinfo($_FILES['completion_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('task_') . '.' . $ext;
            $target = $upload_dir . $filename;
            
            if (!move_uploaded_file($_FILES['completion_image']['tmp_name'], $target)) {
                 $_SESSION['error'] = 'Failed to upload image.';
                 header('Location: task_list_detail.php?task_id=' . $task_id);
                 exit;
            }
        } 
        
        // Update TASK table with new status and image (if any)
        $update_sql = " 
            UPDATE TASK
            SET TASK_STATUS = ?, IMAGE_AFTER = ?
            WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?
        ";
        $update_stmt = $conn->prepare($update_sql);
        
        if ($image_needed === 1) {
            // For tasks with image/AI, status is 'pending'
            $update_stmt->bind_param('ssii', $status, $filename, $task_id, $household_id);
        } else {
            // For simple tasks, status is 'pending' but IMAGE_AFTER is NULL
            $null_filename = null;
            $update_stmt = $conn->prepare("UPDATE TASK SET TASK_STATUS = ?, IMAGE_AFTER = NULL WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
            $update_stmt->bind_param('sii', $status, $task_id, $household_id);
        }

        if ($update_stmt->execute()) {
            $_SESSION['success'] = 'Task submitted successfully.';
            $_SESSION['success_type'] = 'submit_task';

            if ($ai_validation ===0){
            // Send notification to the task creator
            $creatorId = $task['ID_USER'];
            $title = "Task Submission";
            $message = "A task has been submitted for your review: " . htmlspecialchars($task['TASK_NAME']);
            $type = "task_submission";
            sendNotification($creatorId, $title, $message, $type, $task_id, $conn);
        }

            // *** NEW ASYNC LOGIC ***
            if ($ai_validation === 1 && $image_needed === 1) {
                // Set a flag for the JavaScript to start the AI check
                $_SESSION['submit_ai_task_id'] = $task_id;
                // DO NOT REDIRECT TO api/task/ai_validation.php
            }

        } else {
            $_SESSION['error'] = 'Failed to submit the task. DB error: ' . $update_stmt->error;
        }
        $update_stmt->close();
    }

    // Handle approval/rejection (Unchanged logic, kept for completeness)
    if (isset($_POST['approve_task']) || isset($_POST['reject_task'])) {
        if (!$is_creator) {
            $_SESSION['error'] = 'Only the creator can verify this task.';
            header('Location: task_list_detail.php?task_id=' . $task_id);
            exit;
        }

        $new_status = isset($_POST['approve_task']) ? 'completed' : 'todo';

        if ($new_status === 'completed') {
            $selected_assignees = isset($_POST['assignees']) && is_array($_POST['assignees'])
                ? array_map('intval', $_POST['assignees'])
                : [];

            if (!empty($selected_assignees)) {
                $total_points = (int)($task['TASK_POINT'] ?? 0);
                $share = $total_points > 0 ? intdiv($total_points, count($selected_assignees)) : 0;

                if ($share > 0) {
                    $points_stmt = $conn->prepare("
                        UPDATE POINTS 
                        SET TOTAL_POINTS = TOTAL_POINTS + ? 
                        WHERE ID_USER = ? AND ID_HOUSEHOLD = ?
                    ");
                    $completion_stmt = $conn->prepare("
                        INSERT INTO COMPLETION (ID_TASK, ID_HOUSEHOLD, SUBMITTED_BY, APPROVED_BY, POINTS, COMPLETED_AT)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    foreach ($selected_assignees as $assignee_id) {
                        $points_stmt->bind_param('iii', $share, $assignee_id, $household_id);
                        $points_stmt->execute();

                        $completion_stmt->bind_param('iiiii', $task_id, $household_id, $assignee_id, $user_id, $share);
                        $completion_stmt->execute();
                    }
                    $points_stmt->close();
                    $completion_stmt->close();
                }
            }
        }

        $verify_stmt = $conn->prepare(" 
            UPDATE TASK
            SET TASK_STATUS = ?
            WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?
        ");
        $verify_stmt->bind_param('sii', $new_status, $task_id, $household_id);
        if ($verify_stmt->execute()) {
            $_SESSION['success'] = $new_status === 'completed'
                ? 'Task marked as completed and points awarded.'
                : 'Task rejected and returned to To Do.';

            if ($new_status === 'completed') {
                // Send notification to workers
                foreach ($selected_assignees as $assignee_id) {
                    $title = "Task Approved";
                    $message = "The task \"" . htmlspecialchars($task['TASK_NAME']) . "\" has been approved.";
                    $type = "task_approval";
                    sendNotification($assignee_id, $title, $message, $type, $task_id, $conn);
                }
            }
        } else {
            $_SESSION['error'] = 'Failed to update task status.';
        }

        $verify_stmt->close();
    }

    // Always redirect on POST (excluding the AI submission path)
    header('Location: task_list_detail.php?task_id=' . $task_id);
    exit;
}
// === MODIFICATION END: Handle Submission ===

// Capture flash messages
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';
$success_type = $_SESSION['success_type'] ?? '';

// === NEW FLAG CAPTURE ===
$submit_ai_task_id = $_SESSION['submit_ai_task_id'] ?? 0;
// =========================

unset($_SESSION['success'], $_SESSION['error'], $_SESSION['success_type'], $_SESSION['submit_ai_task_id']); 
$show_submission_modal = ($success_type === 'submit_task' || $submit_ai_task_id > 0);

// Fetch task creator's name (Unchanged)
$creator_stmt = $conn->prepare("SELECT USER_NAME FROM USER WHERE ID_USER = ?");
$creator_stmt->bind_param('i', $task['ID_USER']);
$creator_stmt->execute();
$creator_result = $creator_stmt->get_result();
$creator = $creator_result->fetch_assoc();
$creator_name = $creator['USER_NAME'] ?? 'Unknown';
$creator_stmt->close();

// Fetch people working on this task (Unchanged)
$workers = [];
if (in_array($normalized_status, ['in_progress', 'pending'], true)) {
    $workers_stmt = $conn->prepare("
        SELECT u.ID_USER, u.USER_NAME 
        FROM PROGRESS p
        JOIN USER u ON p.ID_USER = u.ID_USER
        WHERE p.ID_TASK = ?
    ");
    $workers_stmt->bind_param('i', $task_id);
    $workers_stmt->execute();
    $workers_result = $workers_stmt->get_result();
    while ($worker = $workers_result->fetch_assoc()) {
        $workers[] = $worker;
    }
    $workers_stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Task Details - Task-o-Mania</title>
    <meta name="description" content="Task details and actions." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_task_list.css" />
    <link rel="stylesheet" href="style_households.css" />
    <link rel="stylesheet" href="style_user_chrome.css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            lucide.createIcons();
        });
    </script>
    <style>
        /* CSS styles here (unchanged from the previous response for brevity, 
           but should include all CSS from the last provided task_list_detail.php) 
        */
        .task-detail-card {
            align-items: stretch;
            text-align: left;
            gap: 18px;
        }

        .task-detail__header {
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .task-detail__title {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 8px 0;
            color: #1a1a1a;
        }

        .task-detail__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 14px;
            color: #666;
            align-items: center;
        }

        .task-detail__section {
            display: grid;
            gap: 8px;
        }

        .task-detail__label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.02em;
        }

        .task-detail__value {
            font-size: 15px;
            color: #333;
            line-height: 1.6;
        }

        .task-detail__points {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #7c65ff, #9087ff);
            color: white;
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 700;
        }

        .task-detail__image {
            width: 100%;
            max-height: 320px;
            object-fit: cover;
            border-radius: 18px;
            margin: 8px 0 12px;
            box-shadow: 0 12px 26px rgba(0, 0, 0, 0.08);
        }

        .task-detail__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
            padding-top: 12px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #7c65ff, #9087ff);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(124, 101, 255, 0.25);
        }

        .btn-danger {
            background: #ff6b6b;
            color: white;
        }

        .btn-danger:hover {
            background: #ff5252;
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .workers {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 6px;
        }

        .worker-tag {
            background: #f0f0f0;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 12px;
            color: #666;
        }

        .message {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message.error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
        }

        .proof-upload {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
            padding-top: 12px;
        }

        .proof-upload__actions {
            display: flex;
            justify-content: flex-end;
        }

        .proof-upload__field {
            display: grid;
            gap: 10px;
        }

        .proof-upload__label {
            font-size: 14px;
            font-weight: 700;
            color: rgba(22, 22, 29, 0.8);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .proof-upload__input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            clip-path: inset(50%);
            border: 0;
            white-space: nowrap;
        }

        .proof-upload__dropzone {
            display: grid;
            gap: 6px;
            place-items: center;
            padding: 22px 18px;
            border-radius: 18px;
            border: 2px dashed rgba(132, 124, 255, 0.35);
            background: rgba(255, 255, 255, 0.78);
            color: rgba(22, 22, 29, 0.6);
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .proof-upload__dropzone:hover,
        .proof-upload__dropzone:focus-visible {
            border-color: rgba(124, 101, 255, 0.65);
            box-shadow: 0 12px 30px rgba(124, 101, 255, 0.18);
            transform: translateY(-2px);
            color: rgba(22, 22, 29, 0.75);
        }

        .proof-upload__dropzone--filled {
            border-color: rgba(124, 101, 255, 0.65);
            color: rgba(22, 22, 29, 0.75);
            box-shadow: 0 12px 26px rgba(124, 101, 255, 0.12);
        }

        .proof-upload__cta {
            font-size: 15px;
            color: #4b2dbd;
        }

        .proof-upload__filename {
            font-size: 13px;
            font-weight: 500;
            color: rgba(22, 22, 29, 0.55);
        }

        .proof-upload__required {
            color: #ff6b6b;
            margin-left: 4px;
        }

        .proof-upload__hint {
            font-size: 12px;
            color: rgba(22, 22, 29, 0.5);
        }

        .proof-upload__preview {
            display: none;
            max-width: 220px;
            border-radius: 12px;
            margin-top: 12px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        .proof-upload__preview.is-visible {
            display: block;
        }

        .success-modal {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(13, 7, 34, 0.55);
            backdrop-filter: blur(12px);
            padding: 24px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
            z-index: 1000;
        }

        .success-modal--visible {
            opacity: 1;
            pointer-events: auto;
        }

        .success-modal__card {
            width: min(420px, 100%);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 26px;
            padding: clamp(24px, 4vw, 36px);
            text-align: center;
            box-shadow: 0 32px 70px rgba(44, 25, 94, 0.28);
        }

        .success-modal__card h2 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        .success-modal__card p {
            margin: 0 0 24px;
            color: rgba(22, 16, 54, 0.75);
        }

        .success-modal__actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .success-modal__actions button,
        .success-modal__actions a {
            border-radius: 999px;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .success-modal__primary {
            background: linear-gradient(135deg, #7c65ff, #9087ff);
            color: #fff;
            box-shadow: 0 16px 32px rgba(124, 101, 255, 0.35);
        }

        .success-modal__primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 22px 40px rgba(124, 101, 255, 0.4);
        }
    </style>
</head>

<body>
    <div class="background" aria-hidden="true"></div>

    <div class="dashboard-shell">
        <?php include 'sidebar.php'; ?>

        <div class="content">
            <header class="topbar">
                <button class="back-btn" type="button" aria-label="Go back" onclick="window.location.href='total_task_list_bt_columns.php';">
                    <span aria-hidden="true">&#x2039;</span>
                </button>

                <?php include 'header.php'; ?>
            </header>

            <main class="page" role="main">
                <?php $review_form_open = ($normalized_status === 'pending' && $is_creator); ?>
                <?php if ($review_form_open): ?>
                    <form method="POST" action="task_list_detail.php?task_id=<?php echo intval($task_id); ?>">
                <?php endif; ?>

                <section class="panel households-panel" aria-label="Task detail">
                    <div class="household-grid">
                        <div class="household-card task-detail-card">
                            <div class="task-detail__header">
                                <h3 class="task-detail__title"><?php echo htmlspecialchars($task['TASK_NAME']); ?></h3>
                                <div class="task-detail__meta">
                                    <span><strong><?php echo htmlspecialchars($creator_name); ?></strong> created this</span>
                                    <span><?php echo htmlspecialchars($task['TASK_STATUS']); ?></span>
                                    <span class="task-detail__points"><?php echo intval($task['TASK_POINT']); ?> pts</span>
                                </div>
                            </div>

                            <?php if (!empty($task['IMAGE_BEFORE'])): ?>
                                <div class="task-detail__section">
                                    <img src="images/tasks/<?php echo htmlspecialchars($task['IMAGE_BEFORE']); ?>" alt="Task image before" class="task-detail__image" />
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($task['IMAGE_AFTER'])): ?>
                                <div class="task-detail__section">
                                    <img src="images/tasks/<?php echo htmlspecialchars($task['IMAGE_AFTER']); ?>" alt="Task image after" class="task-detail__image" />
                                </div>
                            <?php endif; ?>

                            <div class="task-detail__section">
                                <div class="task-detail__label">Description</div>
                                <div class="task-detail__value">
                                    <?php echo htmlspecialchars($task['TASK_DESCRIPTION'] ?? 'No description provided'); ?>
                                </div>
                            </div>

                            <?php if (in_array($normalized_status, ['in_progress', 'pending'], true) && !empty($workers)): ?>
                                <div class="task-detail__section">
                                    <div class="task-detail__label">
                                        <?php echo $normalized_status === 'pending' ? 'Submitted by' : 'People working on this'; ?>
                                    </div>

                                    <div class="workers">
                                        <?php foreach ($workers as $worker): ?>
                                            <?php if ($review_form_open): ?>
                                                <label class="worker-tag">
                                                    <input
                                                        type="checkbox"
                                                        name="assignees[]"
                                                        value="<?php echo intval($worker['ID_USER']); ?>"
                                                        checked
                                                        style="margin-right: 6px;" />
                                                    <?php echo htmlspecialchars($worker['USER_NAME']); ?>
                                                </label>
                                            <?php else: ?>
                                                <div class="worker-tag"><?php echo htmlspecialchars($worker['USER_NAME']); ?></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="task-detail__actions">

                                <?php if ($is_creator): ?>
                                    <?php if ($normalized_status === 'pending'): ?>
                                        <button type="submit" name="approve_task" class="btn btn-primary">Approve</button>
                                        <button type="submit" name="reject_task" class="btn btn-secondary">Reject</button>

                                    <?php endif; ?>
                                    <?php if ($normalized_status === 'todo'): ?>
                                        <form method="POST" action="api/task/delete.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                            <input type="hidden" name="task_id" value="<?php echo intval($task_id); ?>" />
                                            <button type="submit" class="btn btn-danger">Delete Task</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <?php if ($normalized_status === 'todo' && !$already_joined): ?>
                                        <form method="POST" action="api/task/join.php" style="display: inline;">
                                            <input type="hidden" name="task_id" value="<?php echo intval($task_id); ?>" />
                                            <button type="submit" class="btn btn-primary join-task-button">Do this task</button>
                                        </form>
                                    <?php elseif ($normalized_status === 'todo' && $already_joined): ?>
                                        <button class="btn btn-secondary" disabled>Already in progress</button>
                                    <?php elseif ($normalized_status === 'in_progress' && !$already_joined): ?>
                                        <form method="POST" action="api/task/join.php" style="display: inline;">
                                            <input type="hidden" name="task_id" value="<?php echo intval($task_id); ?>" />
                                            <button type="submit" class="btn btn-primary join-task-button">Join this task</button>
                                        </form>
                                    <?php elseif ($normalized_status === 'in_progress' && $already_joined): ?>
                                        <form method="POST" enctype="multipart/form-data" action="task_list_detail.php?task_id=<?php echo intval($task_id); ?>" style="width: 100%;">
                                            <div class="proof-upload">
                                                <div class="proof-upload__field">
                                                    <span class="proof-upload__label">
                                                        Upload proof image
                                                        <span class="proof-upload__required" aria-hidden="true">*</span>
                                                    </span>
                                                    <label for="completion-image" class="proof-upload__dropzone" role="button" tabindex="0">
                                                        <span class="proof-upload__cta">Upload your photo</span>
                                                        <span
                                                            id="completion-filename"
                                                            class="proof-upload__filename"
                                                            data-placeholder="No file selected"
                                                            aria-live="polite">
                                                            No file selected
                                                        </span>
                                                    </label>
                                                    <input
                                                        class="proof-upload__input"
                                                        type="file"
                                                        name="completion_image"
                                                        id="completion-image"
                                                        accept="image/*"
                                                        aria-describedby="completion-filename completion-hint"
                                                        <?php if ($image_needed === 1): ?>
                                                        required 
                                                        <?php endif; ?>
                                                        />
                                                    <span id="completion-hint" class="proof-upload__hint">Accepted formats: JPG, PNG (max 10MB)</span>
                                                </div>
                                                <div class="proof-upload__actions">
                                                    <button type="submit" name="submit_completion" class="btn btn-primary">Submit task</button>
                                                </div>
                                            </div>
                                            <img id="completion-preview" class="proof-upload__preview" src="#" alt="Proof preview" />
                                        </form>
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                var input = document.getElementById('completion-image');
                                                var preview = document.getElementById('completion-preview');
                                                var dropzone = document.querySelector('.proof-upload__dropzone');
                                                var filename = document.getElementById('completion-filename');
                                                var previewUrl = '';

                                                if (!input) {
                                                    return;
                                                }

                                                var placeholder = filename ? (filename.dataset.placeholder || 'No file selected') : 'No file selected';

                                                var resetPreview = function() {
                                                    if (filename) {
                                                        filename.textContent = placeholder;
                                                    }
                                                    if (dropzone) {
                                                        dropzone.classList.remove('proof-upload__dropzone--filled');
                                                    }
                                                    if (preview) {
                                                        preview.classList.remove('is-visible');
                                                        if (previewUrl) {
                                                            URL.revokeObjectURL(previewUrl);
                                                            previewUrl = '';
                                                        }
                                                        preview.src = '';
                                                        preview.alt = 'Proof preview';
                                                    }
                                                };

                                                resetPreview();

                                                input.addEventListener('change', function(e) {
                                                    var file = e.target.files && e.target.files[0];
                                                    if (!file) {
                                                        if (dropzone) {
                                                            dropzone.classList.remove('proof-upload__dropzone--filled');
                                                        }
                                                        resetPreview();
                                                        return;
                                                    }

                                                    if (filename) {
                                                        filename.textContent = file.name;
                                                    }

                                                    if (dropzone) {
                                                        dropzone.classList.add('proof-upload__dropzone--filled');
                                                    }

                                                    if (preview) {
                                                        if (previewUrl) {
                                                            URL.revokeObjectURL(previewUrl);
                                                        }
                                                        previewUrl = URL.createObjectURL(file);
                                                        preview.src = previewUrl;
                                                        preview.alt = 'Preview of ' + file.name;
                                                        preview.classList.add('is-visible');
                                                    }
                                                });

                                                if (dropzone) {
                                                    dropzone.addEventListener('keydown', function(event) {
                                                        if (event.key === 'Enter' || event.key === ' ') {
                                                            event.preventDefault();
                                                            input.click();
                                                        }
                                                    });
                                                }
                                            });
                                        </script>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <?php if ($review_form_open): ?>
                    </form>
                <?php endif; ?>
                <div
                    id="submission-modal"
                    class="success-modal"
                    role="alertdialog"
                    aria-modal="true"
                    aria-hidden="true"
                    aria-labelledby="submission-modal-title"
                    data-show="<?php echo $show_submission_modal ? '1' : '0'; ?>"
                    data-task-id="<?php echo intval($submit_ai_task_id); ?>"
                    data-household-id="<?php echo intval($household_id); ?>"
                    >
                    <div class="success-modal__card">
                        <h2 id="submission-modal-title">Task submitted!</h2>
                        <p id="submission-modal-message">
                            <?php 
                            if ($submit_ai_task_id > 0) {
                                echo 'Image uploaded. Automatic validation is now running in the background...';
                            } else {
                                echo 'The task is submitted and is pending manual approval.';
                            }
                            ?>
                        </p>
                        <div class="success-modal__actions">
                            <button type="button" class="success-modal__primary" id="submission-ok">OK</button>
                        </div>
                    </div>
                </div>
                <div id="join-success-modal" class="success-modal" role="alertdialog" aria-modal="true" aria-hidden="true" aria-labelledby="join-success-title">
                    <div class="success-modal__card">
                        <h2 id="join-success-title">You are now working on this task!</h2>
                        <div class="success-modal__actions">
                            <button type="button" class="success-modal__primary" id="join-success-ok">OK</button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
    (function() {
        const joinButtons = document.querySelectorAll('.join-task-button');
        const joinModal = document.getElementById('join-success-modal');
        const joinOk = document.getElementById('join-success-ok');
        const submissionModal = document.getElementById('submission-modal');
        const submissionOk = document.getElementById('submission-ok');
        
        // Retrieve PHP flags from the modal data attributes
        const shouldShowSubmission = submissionModal && submissionModal.dataset.show === '1';
        const aiTaskId = parseInt(submissionModal?.dataset.taskId) || 0;
        const householdId = parseInt(submissionModal?.dataset.householdId) || 0;
        const modalTitle = document.getElementById('submission-modal-title');
        const modalMessage = document.getElementById('submission-modal-message');

        const toggleModal = (modal, visible) => {
            if (!modal) return;
            modal.setAttribute('aria-hidden', visible ? 'false' : 'true');
            modal.classList.toggle('success-modal--visible', visible);
        };

        const fireConfetti = () => {
            if (typeof confetti !== 'function') return;
            confetti({ particleCount: 120, spread: 70, origin: { y: 0.65 } });
            setTimeout(() => {
                confetti({ particleCount: 90, spread: 90, origin: { y: 0.6 } });
            }, 250);
        };

        const hideJoinModal = () => {
            toggleModal(joinModal, false);
        };

        const handleJoinOk = () => {
            hideJoinModal();
            window.location.href = 'total_task_list_bt_columns.php';
        };

        const closeSubmissionModal = () => {
            toggleModal(submissionModal, false);
            if (submissionModal) {
                submissionModal.dataset.show = '0';
            }
        };

        // NEW/MODIFIED FUNCTION: Handles closing AND redirecting
        const handleSubmissionOk = (redirectUrl = null) => {
            closeSubmissionModal();
            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        };
        // END NEW/MODIFIED FUNCTION

        // Asynchronous AI Validation Logic
        const runAiValidation = (taskId, houseId) => {
            if (!modalMessage || !modalTitle) return;

            modalMessage.textContent = 'Automatic validation running... Please wait.';
            
            // Call the AI validation endpoint
            fetch(`api/task/ai_validation.php?task_id=${taskId}&household_id=${houseId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('AI Response:', data);

                    let redirectAfterOk = null;

                    // Update modal based on AI result
                    if (data.status === 'completed_auto') {
                        modalTitle.textContent = 'Task Approved! 🎉';
                        modalMessage.textContent = `The AI auto-approved your task. Reason: ${data.reason}. Points awarded!`;
                        redirectAfterOk = 'total_task_list_bt_columns.php';
                        
                    } else if (data.status.includes('rejected')) {
                        modalTitle.textContent = 'Task Rejected ❌';
                        modalMessage.textContent = `The AI rejected your submission. Reason: ${data.reason}. Please review and resubmit.`;
                        redirectAfterOk = `task_list_detail.php?task_id=${taskId}`;

                    } else if (data.status.includes('pending')) {
                        modalTitle.textContent = 'Pending Manual Review ⚠️';
                        modalMessage.textContent = `AI ran, but confidence was low or the verdict was unclear. The task creator must manually review it. Reason: ${data.reason}`;
                        redirectAfterOk = `task_list_detail.php?task_id=${taskId}`; // Redirect to refresh the detail page to show PENDING status
                    } else {
                        // Error/Fallback
                        modalTitle.textContent = 'AI Error 🛑';
                        modalMessage.textContent = `An error occurred during AI validation. Status: ${data.message || 'Check logs'}. The task is pending manual review.`;
                        redirectAfterOk = `task_list_detail.php?task_id=${taskId}`;
                    }

                    // 1. Update the OK button action
                    submissionOk.textContent = 'OK';
                    submissionOk.disabled = false;
                    
                    // 2. Remove previous listener and attach a new one with the specific redirect logic
                    submissionOk.removeEventListener('click', closeSubmissionModal); // Remove the general close handler
                    submissionOk.onclick = () => handleSubmissionOk(redirectAfterOk); // Assign the new handler

                })
                .catch(error => {
                    console.error('AJAX AI Validation Error:', error);
                    modalTitle.textContent = 'Connection Error 🌐';
                    modalMessage.textContent = 'Could not reach the AI service. The task is marked pending for manual review.';
                    
                    // Allow the user to close the modal and stay on the current page
                    submissionOk.textContent = 'OK';
                    submissionOk.disabled = false;
                    submissionOk.onclick = () => handleSubmissionOk(`task_list_detail.php?task_id=${taskId}`); 
                });
        };

        // Initial setup on page load
        if (shouldShowSubmission) {
            setTimeout(() => {
                fireConfetti();
                toggleModal(submissionModal, true);
                
                if (aiTaskId > 0) {
                    // Start the AI process immediately after showing the submission success modal
                    submissionOk.disabled = true; // Disable until AI process finishes
                    submissionOk.textContent = 'Processing...'; 
                    runAiValidation(aiTaskId, householdId);
                }
            }, 200);
        }

        // --- Other event listeners (Modified for non-auto-closing) ---
        joinButtons.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                const form = btn.closest('form');
                if (!form) return;
                event.preventDefault();
                const formData = new FormData(form);
                fetch(form.action, {
                    method: form.method || 'POST',
                    body: formData,
                }).then(() => {
                    toggleModal(joinModal, true);
                }).catch(() => {
                    toggleModal(joinModal, true);
                });
            });
        });

        if (joinOk) {
            joinOk.addEventListener('click', handleJoinOk);
        }

        if (joinModal) {
            joinModal.addEventListener('click', (event) => {
                if (event.target === joinModal) {
                    handleJoinOk();
                }
            });
        }

        if (submissionOk && aiTaskId === 0) {
            // Assign default close for non-AI submissions
             submissionOk.onclick = () => handleSubmissionOk('total_task_list_bt_columns.php'); 
        }

        if (submissionModal) {
            submissionModal.addEventListener('click', (event) => {
                if (event.target === submissionModal) {
                    // Prevent closing if AI validation is running or if the final action is not set
                    if (submissionOk.disabled) return; 
                    if (submissionOk.onclick) {
                        submissionOk.onclick();
                    } else {
                        handleSubmissionOk(null); // Fallback
                    }
                }
            });
        }

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                if (joinModal && joinModal.classList.contains('success-modal--visible')) {
                    hideJoinModal();
                }
                if (submissionModal && submissionModal.classList.contains('success-modal--visible')) {
                    // Only allow escape if the OK button is enabled
                    if (!submissionOk.disabled) {
                         if (submissionOk.onclick) {
                            submissionOk.onclick();
                        } else {
                            handleSubmissionOk(null); 
                        }
                    }
                }
            }
        });
    })();
</script>
</body>
</html>