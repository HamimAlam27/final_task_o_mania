<?php
// AI validation endpoint — accepts task_id and household_id (GET/POST)

session_start();
header('Content-Type: application/json');
require '../../src/config/azure_openai.php'; // Assuming this file exists and provides credentials
require '../../src/config/db.php';         // Assuming this file exists and provides $conn

$task_id = isset($_REQUEST['task_id']) ? intval($_REQUEST['task_id']) : 0;
$household_id = isset($_REQUEST['household_id']) ? intval($_REQUEST['household_id']) : 0;

$response_data = [
    'verdict' => 'ERROR',
    'confidence' => 0,
    'reason' => 'Initial state',
    'status' => 'error',
    'message' => 'Script did not finish execution.'
];

if (!$task_id || !$household_id) {
    $response_data['message'] = 'task_id and household_id are required';
    echo json_encode($response_data);
    exit;
}

// Fetch task from DB
$stmt = $conn->prepare("SELECT ID_USER, TASK_NAME, TASK_DESCRIPTION, IMAGE_BEFORE, IMAGE_AFTER, TASK_POINT FROM TASK WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
$stmt->bind_param('ii', $task_id, $household_id);
$stmt->execute();
$res = $stmt->get_result();
$task = $res->fetch_assoc();
$stmt->close();

if (!$task) {
    $response_data['message'] = 'Task not found';
    echo json_encode($response_data);
    exit;
}

$images_dir = "../../images/tasks/";

$instruction = $task['TASK_DESCRIPTION'] ?? $task['TASK_NAME'];
$payload = null;

// --- BUILD PAYLOAD ---
if ($task['IMAGE_AFTER'] !== null) {
    // Case 1: Before and After images are available
    if ($task['IMAGE_BEFORE'] !== null) {
        $before_file = $images_dir . $task['IMAGE_BEFORE'];
        $after_file = $images_dir . $task['IMAGE_AFTER'];

        if (!file_exists($before_file) || !file_exists($after_file)) {
             $response_data['message'] = 'One or both image files not found on server.';
             echo json_encode($response_data);
             exit;
        }

        $before_base64 = base64_encode(file_get_contents($before_file));
        $after_base64 = base64_encode(file_get_contents($after_file));

        $payload = [
            "messages" => [
                [
                    "role" => "system",
                    "content" => "You are an AI that compares two images: a 'before' image and an 'after' image of the same scene. You also receive a textual instruction describing the task that should have been done. Your job is to determine if the task was correctly completed according to the instruction. Return JSON only with: verdict (COMPLETED, NOT_COMPLETED), reason (short explanation), confidence (number between 0 and 1)."
                ],
                [
                    "role" => "user",
                    "content" => [
                        ["type" => "image_url", "image_url" => ["url" => "data:image/jpeg;base64,$before_base64"]],
                        ["type" => "image_url", "image_url" => ["url" => "data:image/jpeg;base64,$after_base64"]],
                        ["type" => "text", "text" => $instruction]
                    ]
                ]
            ]
        ];
    } 
    // Case 2: Only After image is available
    else {
        $after_file = $images_dir . $task['IMAGE_AFTER'];

        if (!file_exists($after_file)) {
             $response_data['message'] = 'Image file not found on server.';
             echo json_encode($response_data);
             exit;
        }

        $after_base64 = base64_encode(file_get_contents($after_file));

        $prompt = "You are an AI that compares a textual description with an uploaded image. You receive a textual instruction describing the task that should have been done. Your job is to determine if the task was correctly completed according to the instruction. Return JSON only with: verdict (COMPLETED, NOT_COMPLETED), reason (short explanation), confidence (number between 0 and 1).";

        $payload = [
            "messages" => [
                [
                    "role" => "system",
                    "content" => $prompt
                ],
                [
                    "role" => "user",
                    "content" => [
                        ["type" => "image_url", "image_url" => ["url" => "data:image/jpeg;base64,$after_base64"]],
                        ["type" => "text", "text" => $instruction]
                    ]
                ]
            ]
        ];
    }
}

if (!$payload) {
    $response_data['message'] = 'No image found for validation.';
    echo json_encode($response_data);
    exit;
}

// --- CALL AZURE OPENAI ---
$url = $AZURE_OPENAI_ENDPOINT . "/openai/deployments/$AZURE_OPENAI_DEPLOYMENT/chat/completions?api-version=2024-08-01-preview";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "api-key: $AZURE_OPENAI_KEY"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($curl);
// Check for cURL errors
if (curl_errno($curl)) {
    $response_data['message'] = 'cURL Error: ' . curl_error($curl);
    echo json_encode($response_data);
    curl_close($curl);
    exit;
}
unset($curl);

// --- PROCESS AI RESPONSE ---
$content = $response;
$decoded = json_decode($response, true);
if (isset($decoded['choices'][0]['message']['content'])) {
    $content = $decoded['choices'][0]['message']['content'];
}

// Attempt to parse JSON content from model (removing code fences)
$clean = trim($content);
$clean = preg_replace('/^```json|```$/m', '', $clean);
$clean = trim($clean);

$model_result = json_decode($clean, true);

$verdict = 'ERROR';
$confidence = 0;
$reason = 'AI response unparseable or error.';

if (is_array($model_result) && isset($model_result['verdict'])) {
    $verdict = $model_result['verdict'];
    $confidence = floatval($model_result['confidence']) * 100; // Store as percent
    $reason = $model_result['reason'] ?? 'No reason provided.';

    $response_data['verdict'] = $verdict;
    $response_data['confidence'] = $confidence;
    $response_data['reason'] = $reason;
} else {
    $response_data['message'] = 'AI returned an invalid JSON object.';
    // Set fallback verdict/confidence from initial ERROR state
    $response_data['verdict'] = $verdict;
    $response_data['confidence'] = $confidence;
    $response_data['reason'] = $reason;
    echo json_encode($response_data);
    exit;
}

// --- FINAL DECISION & DB UPDATE (NO REDIRECTS) ---

// Fetch household threshold
$stmtH = $conn->prepare("SELECT AI_CONFIDENCE FROM HOUSEHOLD WHERE ID_HOUSEHOLD = ?");
$stmtH->bind_param('i', $household_id);
$stmtH->execute();
$resH = $stmtH->get_result();
$house = $resH->fetch_assoc();
$stmtH->close();
$threshold = isset($house['AI_CONFIDENCE']) ? intval($house['AI_CONFIDENCE']) : 0;


if ($verdict === 'COMPLETED' && $confidence >= $threshold) {
    // 1. AI AUTO-APPROVAL PATH
    $conn->begin_transaction();
    try {
        // Update TASK status
        $upd = $conn->prepare("UPDATE TASK SET TASK_STATUS = 'completed', AI_VALIDATION = 1 WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
        $upd->bind_param('ii', $task_id, $household_id);
        $upd->execute();
        $upd->close();

        // Fetch assignees from PROGRESS
        $ps = $conn->prepare("SELECT ID_USER FROM PROGRESS WHERE ID_TASK = ?");
        $ps->bind_param('i', $task_id);
        $ps->execute();
        $rs = $ps->get_result();
        $assignees = [];
        while ($r = $rs->fetch_assoc()) $assignees[] = $r['ID_USER'];
        $ps->close();

        $num = count($assignees);
        $task_points = isset($task['TASK_POINT']) ? intval($task['TASK_POINT']) : 0;
        $per_user = $num > 0 ? intval(floor($task_points / $num)) : 0;

        $updPoints = $conn->prepare("UPDATE POINTS SET TOTAL_POINTS = TOTAL_POINTS + ? WHERE ID_USER = ? AND ID_HOUSEHOLD = ?");
        $insCompletion = $conn->prepare("INSERT INTO COMPLETION (ID_TASK, ID_HOUSEHOLD, SUBMITTED_BY, APPROVED_BY, POINTS, COMPLETED_AT) VALUES (?, ?, ?, ?, ?, NOW())");

        foreach ($assignees as $uid) {
            $updPoints->bind_param('iii', $per_user, $uid, $household_id);
            $updPoints->execute();

            $approved_by = $task['ID_USER'];
            $insCompletion->bind_param('iiiii', $task_id, $household_id, $uid, $approved_by, $per_user);
            $insCompletion->execute();
        }

        if (isset($updPoints)) $updPoints->close();
        if (isset($insCompletion)) $insCompletion->close();

        // remove progress entries for this task
        $delProg = $conn->prepare("DELETE FROM PROGRESS WHERE ID_TASK = ?");
        $delProg->bind_param('i', $task_id);
        $delProg->execute();
        $delProg->close();

        $conn->commit();
        $response_data['status'] = 'completed_auto';
        $response_data['message'] = 'Task was automatically approved.';

    } catch (Exception $e) {
        $conn->rollback();
        $response_data['status'] = 'db_error';
        $response_data['message'] = 'DB transaction failed during completion: ' . $e->getMessage();
    }
}

else if ($verdict === 'COMPLETED' && $confidence < $threshold) {
    // 2. AI SUGGESTS COMPLETE BUT LOW CONFIDENCE -> PENDING REVIEW
    $upd2 = $conn->prepare("UPDATE TASK SET AI_VALIDATION = 0 WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
    $upd2->bind_param('ii', $task_id, $household_id);
    $upd2->execute();
    $upd2->close();
    
    $response_data['status'] = 'pending_low_confidence';
    $response_data['message'] = 'Task suggests COMPLETED, but confidence is low. Creator review required.';
}

else if ($verdict === 'NOT_COMPLETED' && $confidence >= 80) {
    // 3. AI REJECTS WITH HIGH CONFIDENCE -> TASK TO DO
    $upd3 = $conn->prepare("UPDATE TASK SET TASK_STATUS = 'todo', AI_VALIDATION = 1, IMAGE_AFTER = null WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
    $upd3->bind_param('ii', $task_id, $household_id);
    $upd3->execute();
    $upd3->close();
    
    // remove any progress entries for this task (clear assignees)
    $delProg = $conn->prepare("DELETE FROM PROGRESS WHERE ID_TASK = ?");
    $delProg->bind_param('i', $task_id);
    $delProg->execute();
    $delProg->close();
    
    $response_data['status'] = 'rejected_high_confidence';
    $response_data['message'] = 'Work rejected by AI with high confidence. Progress removed.';
}

else if ($verdict === 'NOT_COMPLETED' && $confidence < 80) {
    // 4. AI SUGGESTS NOT COMPLETE BUT LOW CONFIDENCE -> PENDING REVIEW
    $upd4 = $conn->prepare("UPDATE TASK SET AI_VALIDATION = 0 WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
    $upd4->bind_param('ii', $task_id, $household_id);
    $upd4->execute();
    $upd4->close();
    
    $response_data['status'] = 'pending_low_confidence_reject';
    $response_data['message'] = 'Work suggests NOT_COMPLETED, but confidence is low. Creator review required.';
}

// Final JSON output
echo json_encode($response_data);
exit;
?>