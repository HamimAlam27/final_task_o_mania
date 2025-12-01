<?php
header('Content-Type: application/json');
require '../../src/config/azure_openai.php';
require '../../src/config/db.php';

$task_id = intval($_REQUEST['task_id'] ?? 0);
$household_id = intval($_REQUEST['household_id'] ?? 0);

if (!$task_id || !$household_id) {
    echo json_encode(['error' => 'task_id and household_id are required']);
    exit;
}

$stmt = $conn->prepare("SELECT TASK_NAME, TASK_DESCRIPTION, IMAGE_BEFORE, IMAGE_AFTER, TASK_POINT FROM TASK WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
$stmt->bind_param('ii', $task_id, $household_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
    echo json_encode(['error' => 'Task not found']);
    exit;
}

$images_dir = "../../images/tasks/";
$before_file = $images_dir . $task['IMAGE_BEFORE'];
$after_file = $images_dir . $task['IMAGE_AFTER'];

$before_base64 = base64_encode(file_get_contents($before_file));
$after_base64 = base64_encode(file_get_contents($after_file));

$instruction = $task['TASK_DESCRIPTION'] ?: $task['TASK_NAME'];

$payload = [
    "messages" => [
        [
            "role" => "system",
            "content" => "Return JSON only with keys: verdict, confidence, reason."
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

$url = "$AZURE_OPENAI_ENDPOINT/openai/deployments/$AZURE_OPENAI_DEPLOYMENT/chat/completions?api-version=2024-08-01-preview";

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
curl_close($curl);

$decoded = json_decode($response, true);

$content = $decoded['choices'][0]['message']['content'][0]['text'] ?? null;

$model_result = json_decode($content, true);

if (!$model_result) {
    echo json_encode(["error" => "AI returned invalid JSON", "raw" => $content, "api" => $response]);
    exit;
}

$verdict = $model_result['verdict'] ?? null;

echo json_encode($model_result);
?>












<!-- ************working******************* -->
 
<?php
// AI validation endpoint — accepts task_id and household_id (GET/POST)
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
$stmt = $conn->prepare("SELECT TASK_NAME, TASK_DESCRIPTION, IMAGE_BEFORE, IMAGE_AFTER, TASK_POINT FROM TASK WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
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

// resolve various possible stored filename formats to a real file path
if ($task['IMAGE_BEFORE'] !== null && $task['IMAGE_AFTER'] !== null) {

$before_file = $images_dir . $task['IMAGE_BEFORE'];
$after_file = $images_dir . $task['IMAGE_AFTER'];
// echo $before_file;


$before_base64 = base64_encode(file_get_contents($before_file));
$after_base64 = base64_encode(file_get_contents($after_file));

    $instruction = $task['TASK_DESCRIPTION'] ?? $task['TASK_NAME'];

    $payload = [
        "messages" => [
            [
                "role" => "system",
                "content" => "You are an AI that compares two images: a 'before' image and an 'after' image of the same scene. You also receive a textual instruction describing the task that should have been done. Your job is to determine if the task was correctly completed according to the instruction. Return JSON only with: verdict (COMPLETED, NOT_COMPLETED), reason (short explanation), confidence (number between 0 and 1)."
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
    $model_result = json_decode($content, true);
    $verdict = $model_result['verdict'];

    // Normalize model result to array with verdict/confidence
    
echo json_encode($model_result) . "\n";
echo "<h1>$verdict</h1>";
}


?>














<!-- *************another working**************** -->
 <?php
// AI validation endpoint — accepts task_id and household_id (GET/POST)

session_start();
header('Content-Type: application/json');
require '../../src/config/azure_openai.php';
require '../../src/config/db.php';
$_SESSION['time'] = intval(0);
$task_id = isset($_REQUEST['task_id']) ? intval($_REQUEST['task_id']) : 0;
$household_id = isset($_REQUEST['household_id']) ? intval($_REQUEST['household_id']) : 0;

if (!$task_id || !$household_id) {
    echo json_encode(['error' => 'task_id and household_id are required']);
    exit;
}

// Fetch task from DB
$stmt = $conn->prepare("SELECT TASK_NAME, TASK_DESCRIPTION, IMAGE_BEFORE, IMAGE_AFTER, TASK_POINT FROM TASK WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
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

// resolve various possible stored filename formats to a real file path
if ($task['IMAGE_BEFORE'] !== null && $task['IMAGE_AFTER'] !== null) {

$before_file = $images_dir . $task['IMAGE_BEFORE'];
$after_file = $images_dir . $task['IMAGE_AFTER'];
// echo $before_file;


$before_base64 = base64_encode(file_get_contents($before_file));
$after_base64 = base64_encode(file_get_contents($after_file));

    $instruction = $task['TASK_DESCRIPTION'] ?? $task['TASK_NAME'];

    $payload = [
        "messages" => [
            [
                "role" => "system",
                "content" => "You are an AI that compares two images: a 'before' image and an 'after' image of the same scene. You also receive a textual instruction describing the task that should have been done. Your job is to determine if the task was correctly completed according to the instruction. Return JSON only with: verdict (COMPLETED, NOT_COMPLETED), reason (short explanation), confidence (number between 0 and 1)."
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

    $verdict = null;
    while ($_SESSION['time'] < 3  && $verdict === null) {
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
    echo $response . "\n";
    // echo json_encode(json_decode($response, true)) . "\n";
    $model_result = json_decode($content, true);
    $verdict = $model_result['verdict'];
    $confidence = $model_result['confidence'];
    $_SESSION['time'] = $_SESSION['time'] + 1;

    // if ($verdict == null) {
    //     $_SESSION['time'] = $_SESSION['time'] + 1;
    //     if ($_SESSION['time'] < 5) {
    //         header('Location: ../../api/task/ai_validation.php?task_id=' . urlencode($task_id) . '&household_id=' . urlencode($household_id));
    //         exit;
    //     }
    // }
}

    // Normalize model result to array with verdict/confidence
    
echo json_encode($model_result) . "\n";
echo "<h1>$verdict</h1>";
echo "<h1>$confidence</h1>";
}


?>












<!-- ***********working 5****************** -->
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
$stmt = $conn->prepare("SELECT TASK_NAME, TASK_DESCRIPTION, IMAGE_BEFORE, IMAGE_AFTER, TASK_POINT FROM TASK WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
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
    $confidence = $model_result['confidence'];
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

echo json_encode($model_result) . "\n";
echo "<h1>$verdict</h1>";
echo "<h1>$confidence</h1>";



?>











