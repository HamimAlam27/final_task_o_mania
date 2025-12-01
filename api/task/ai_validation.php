<?php
// AI validation endpoint — accepts task_id and household_id (GET/POST)

session_start();
header('Content-Type: application/json');
require '../../src/config/azure_openai.php';
require '../../src/config/db.php';

$task_id = isset($_REQUEST['task_id']) ? intval($_REQUEST['task_id']) : 0;
$household_id = isset($_REQUEST['household_id']) ? intval($_REQUEST['household_id']) : 0;

if (!$task_id || !$household_id) {
    echo json_encode(['error' => 'task_id and household_id are required']);
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
    echo json_encode(['error' => 'Task not found']);
    exit;
}

$images_dir = "../../images/tasks/";

$instruction = $task['TASK_DESCRIPTION'] ?? $task['TASK_NAME'];

// resolve various possible stored filename formats to a real file path
if ($task['IMAGE_BEFORE'] !== null && $task['IMAGE_AFTER'] !== null) {

    $before_file = $images_dir . $task['IMAGE_BEFORE'];
    $after_file = $images_dir . $task['IMAGE_AFTER'];
    // echo $before_file;


    $before_base64 = base64_encode(file_get_contents($before_file));
    $after_base64 = base64_encode(file_get_contents($after_file));



    $payload = [
        "messages" => [
            [
                "role" => "system",
                "content" => "You are an AI that compares two images: a 'before' image and an 'after' image of the same scene. 
                You also receive a textual instruction describing the task that should have been done. 
                Your job is to determine if the task was correctly completed according to the instruction. 
                Return JSON only with: verdict (COMPLETED, NOT_COMPLETED), reason (short explanation), confidence (number between 0 and 1)."
            ],
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "image_url",
                        "image_url" => ["url" => "data:image/jpeg;base64,$before_base64"]
                    ],
                    [
                        "type" => "image_url",
                        "image_url" => ["url" => "data:image/jpeg;base64,$after_base64"]
                    ],
                    [
                        "type" => "text",
                        "text" => $instruction
                    ]
                ]
            ]
        ]
    ];
} else if ($task['IMAGE_AFTER'] !== null and $task['IMAGE_BEFORE'] === null) {
    $after_file = $images_dir . $task['IMAGE_AFTER'];
    $after_base64 = base64_encode(file_get_contents($after_file));

    $prompt = "You are an AI that compares a textual description with an uploaded image. 
You receive a textual instruction describing the task that should have been done. 
Your job is to determine if the task was correctly completed according to the instruction.  
Return JSON only with: verdict (COMPLETED, NOT_COMPLETED), reason (short explanation), confidence (number between 0 and 1).";



    $payload = [
        "messages" => [
            [
                "role" => "system",
                "content" => $prompt

            ],
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "image_url",
                        "image_url" => ["url" => "data:image/jpeg;base64,$after_base64"]
                    ],
                    [
                        "type" => "text",
                        "text" => $instruction
                    ]
                ]
            ]
        ]
    ];
}

$verdict = null;

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
unset($curl);

// Try to extract model content
$content = $response;
$decoded = json_decode($response, true);
if (isset($decoded['choices'][0]['message']['content'])) {
    $content = $decoded['choices'][0]['message']['content'];
}

// Attempt to parse JSON content from model
// echo $response . "\n";
// echo json_encode(json_decode($response, true)) . "\n";
// Remove code fences if present
$clean = trim($content);
$clean = preg_replace('/^```json|```$/m', '', $clean);
$clean = trim($clean);

$model_result = json_decode($clean, true);
if (is_array($model_result) && isset($model_result['verdict'])) {
    $verdict = $model_result['verdict'];
    $confidence = ($model_result['confidence'])*100;
    $reason = $model_result['reason'] ?? '';

    $_SESSION['ai_reason'] = $reason;
    $_SESSION['ai_verdict'] = $verdict;
} else {
    $verdict = 'ERROR';
    $confidence = 0;
}


// if ($verdict == null) {
//     $_SESSION['time'] = $_SESSION['time'] + 1;
//     if ($_SESSION['time'] < 5) {
//         header('Location: ../../api/task/ai_validation.php?task_id=' . urlencode($task_id) . '&household_id=' . urlencode($household_id));
//         exit;
//     }
// }


// Normalize model result to array with verdict/confidence

// echo json_encode($model_result) . "\n";
// echo "<h1>$verdict</h1>";
// echo "<h1>$confidence</h1>";


// After verdict/confidence extraction

// Fetch household threshold
$stmtH = $conn->prepare("SELECT AI_CONFIDENCE FROM HOUSEHOLD WHERE ID_HOUSEHOLD = ?");
$stmtH->bind_param('i', $household_id);
$stmtH->execute();
$resH = $stmtH->get_result();
$house = $resH->fetch_assoc();
$stmtH->close();
$threshold = isset($house['AI_CONFIDENCE']) ? intval($house['AI_CONFIDENCE']) : 0;

// echo $verdict, "\n";
// echo $confidence, "\n";
// echo $threshold, "\n";

if ($verdict === 'COMPLETED' && $confidence !== null && $confidence >= $threshold) {

    echo "<script>alert('Reason: " . addslashes($model_result['reason']) . "');</script>";

    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("UPDATE TASK SET TASK_STATUS = 'completed', AI_VALIDATION = 1 WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
        $upd->bind_param('ii', $task_id, $household_id);
        $upd->execute();
        if ($upd->errno) throw new Exception('Failed to update TASK: ' . $upd->error);
        $upd->close();

        // assignees
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
            if ($insCompletion->errno) throw new Exception('Failed insert COMPLETION: ' . $insCompletion->error);
        }

        if (isset($updPoints)) $updPoints->close();
        if (isset($insPoints)) $insPoints->close();
        if (isset($insCompletion)) $insCompletion->close();

        // remove progress entries for this task now that it's completed
        $delProg = $conn->prepare("DELETE FROM PROGRESS WHERE ID_TASK = ?");
        $delProg->bind_param('i', $task_id);
        $delProg->execute();
        $delProg->close();

        $conn->commit();
        header('Location: ../../total_task_list_bt_columns.php');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'DB transaction failed: ' . $e->getMessage()]);
        exit;
    }
}

else if ($verdict === 'COMPLETED' && ($confidence === null || $confidence < $threshold)) {
    echo "<script>alert('Reason: " . addslashes($model_result['reason']) . ", and the confidence is below the threshold. So now, the creator needs to approve your task');</script>";
    $upd2 = $conn->prepare("UPDATE TASK SET AI_VALIDATION = 0 WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
    $upd2->bind_param('ii', $task_id, $household_id);
    $upd2->execute();
    $upd2->close();
    header('Location: ../../task_list_detail.php?task_id=' . $task_id . '&household_id=' . $household_id);
    exit;
}
else if ($verdict === 'NOT_COMPLETED' && $confidence >=80) {
    echo "<script>alert('Reason: " . addslashes($model_result['reason']) . ", your work has been rejected and your progress has been removed');</script>";
    $upd3 = $conn->prepare("UPDATE TASK SET TASK_STATUS = 'todo', AI_VALIDATION = 1, IMAGE_AFTER = null WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
    $upd3->bind_param('ii', $task_id, $household_id);
    $upd3->execute();
    $upd3->close();
    // remove any progress entries for this task (clear assignees)
    $delProg = $conn->prepare("DELETE FROM PROGRESS WHERE ID_TASK = ?");
    $delProg->bind_param('i', $task_id);
    $delProg->execute();
    $delProg->close();
    header('Location: ../../task_list_detail.php?task_id=' . $task_id . '&household_id=' . $household_id);
    exit;
}

else if ($verdict === 'NOT_COMPLETED' && $confidence <80) {
    echo "<script>alert('Reason: " . addslashes($model_result['reason']) . ", your work has been rejected but the confidence is low, so the creator needs to review it');</script>";
    $upd4 = $conn->prepare("UPDATE TASK SET AI_VALIDATION = 0 WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
    $upd4->bind_param('ii', $task_id, $household_id);
    $upd4->execute();
    $upd4->close();
    header('Location: ../../task_list_detail.php?task_id=' . $task_id . '&household_id=' . $household_id);
    exit;
} else {
    echo "<script>alert('Unable to determine verdict from AI model');</script>";
    header('location: ../../task_list_bt_columns.php');
    exit;
    
}

?>