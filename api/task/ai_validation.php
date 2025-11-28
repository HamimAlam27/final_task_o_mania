<?php
require '../../src/config/azure_openai.php';
header('Content-Type: application/json');

if (isset($_FILES['before']) && isset($_FILES['after'])) {


$before_data = file_get_contents($_FILES['before']['tmp_name']);
$after_data = file_get_contents($_FILES['after']['tmp_name']);

$before_base64 = base64_encode($before_data);
$after_base64 = base64_encode($after_data);

$instruction = $_POST['instruction'] ?? "";

$payload = [
    "messages" => [
        [
            "role" => "system",
            "content" => "You are an AI that compares two images: a 'before' image and an 'after' image of the same scene. 
You also receive a textual instruction describing the task that should have been done. 
Your job is to determine if the task was correctly completed according to the instruction. 
Return JSON only with:
- verdict: COMPLETED, NOT_COMPLETED, or PARTIALLY_COMPLETED
- reason: short explanation
- confidence: number between 0 and 1 indicating how certain you are."

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

echo $response;
}








require '../../src/config/azure_openai.php';
header('Content-Type: application/json');

if (isset($_FILES['after']) && !isset($_FILES['before'])) {


$prompt0 = "You are an AI that compares a textual description of a scene with an uploaded image. 
The description may be incorrect, exaggerated, or wishful. 
Return verdict MATCH only if the description **accurately describes what is visible in the image**, otherwise NO_MATCH. 
Respond in JSON only with keys: verdict (MATCH/NO_MATCH) and reason.";

$prompt1 = "You are an AI that compares a textual description with an uploaded image. 
The description may be incorrect, exaggerated, wishful, command or a request. 
Return verdict MATCH only if the description accurately describes the visible contents of the image, otherwise NO_MATCH. 
Also provide a confidence score between 0 and 1, where 1 means you are completely certain. 
Respond in JSON with keys: verdict, reason, confidence.";

$prompt = "You are an AI that compares a textual description with an uploaded image. 
You also receive a textual instruction describing the task that should have been done. 
Your job is to determine if the task was correctly completed according to the instruction.  
Return verdict: completed or not completed. 
Also provide a confidence score between 0 and 1, where 1 means you are completely certain. 
Respond in JSON with keys: verdict, reason, confidence.";

$image_data = file_get_contents($_FILES['image']['tmp_name']);
$image_base64 = base64_encode($image_data);

// Get manual description from form
$manual_description = $_POST['description'] ?? "";

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
                    "image_url" => [
                        "url" => "data:image/jpeg;base64,$image_base64"
                    ]
                ],
                [
                    "type" => "text",
                    "text" => $manual_description
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

echo (json_decode($response, true)["choices"][0]["message"]['content']);
}?>