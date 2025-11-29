<?php
/**
 * Azure OpenAI helper functions
 * Centralizes cURL calls, retries, and robust parsing of the model response.
 */

function call_azure_openai(array $payload, int $maxRetries = 4, int $timeout = 30) {
    global $AZURE_OPENAI_ENDPOINT, $AZURE_OPENAI_DEPLOYMENT, $AZURE_OPENAI_KEY;

    $url = rtrim($AZURE_OPENAI_ENDPOINT, '/') . 
        "/openai/deployments/" . $AZURE_OPENAI_DEPLOYMENT . 
        "/chat/completions?api-version=2024-08-01-preview";

    $attempt = 0;
    $lastErr = null;
    $lastResponse = null;

    while ($attempt <= $maxRetries) {
        $attempt++;
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "api-key: $AZURE_OPENAI_KEY"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        if (function_exists('curl_reset')) curl_reset($curl);
        unset($curl);

        $lastErr = $err;
        $lastResponse = $response;

        if ($err) {
            // retry with exponential backoff
            if ($attempt <= $maxRetries) {
                sleep($attempt); // simple backoff
                continue;
            }
            return ['ok' => false, 'error' => $err, 'response' => $response];
        }

        // parse structured response when available
        $decoded = json_decode($response, true);
        $content = $response;
        if (isset($decoded['choices'][0]['message']['content'])) {
            $content = $decoded['choices'][0]['message']['content'];
        }


            $tmp = json_decode($content, true);
            $model_result = $tmp;
        

        return ['ok' => true, 'response' => $response, 'decoded' => $decoded, 'content' => $content, 'model_result' => $model_result];
    }

    return ['ok' => false, 'error' => $lastErr, 'response' => $lastResponse];
}

/**
 * Extract verdict and confidence from model_result or raw content.
 * Returns ['verdict' => string|null, 'confidence' => float|null]
 */
function parse_verdict_confidence($model_result, $rawContent) {
    $verdict = null;
    $confidence = null;


        if (is_array($model_result)) {
            if (isset($model_result['verdict'])) $verdict = strtoupper(trim($model_result['verdict']));
            if (isset($model_result['confidence'])) $confidence = floatval($model_result['confidence']);
        }
    

    // fallback heuristics
    if (!$verdict && is_string($rawContent)) {
        if (stripos($rawContent, 'completed') !== false) $verdict = 'COMPLETED';
        elseif (stripos($rawContent, 'not_completed') !== false || stripos($rawContent, 'not completed') !== false) $verdict = 'NOT_COMPLETED';
    }

    if ($confidence === null && is_string($rawContent)) {
        if (preg_match('/([01](?:\.\d+)?|0?\.\d+)/', $rawContent, $m)) {
            $confidence = floatval($m[1]);
        }
    }

    return ['verdict' => $verdict, 'confidence' => $confidence];
}

?>
