<?php
require '../../src/config/azure_openai.php';
header('Content-Type: application/json');

if (!isset($_FILES['before']) || !isset($_FILES['after'])) {
    echo json_encode(["error" => "Please upload both before and after images"]);
    exit;
}

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
