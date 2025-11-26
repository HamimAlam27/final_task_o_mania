<?php
require '../../src/config/azure_openai.php';
header('Content-Type: application/json');

if (!isset($_FILES['image'])) {
    echo json_encode(["error" => "Please upload an image"]);
    exit;
}

$image_data = file_get_contents($_FILES['image']['tmp_name']);
$image_base64 = base64_encode($image_data);

$payload = [
    "messages" => [
        [
            "role" => "system",
            "content"=> "You are an AI that analyzes an uploaded image."
        ],
        [
            "role" => "user",
            "content" => [
                [
                    "type" => "image_url",
                    "image_url" => [
                        "url" => "data:image/jpeg;base64,$image_base64"
                    ]
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
