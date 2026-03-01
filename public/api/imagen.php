<?php
/**
 * CodePilot Image Generation API
 * Text-to-image using Imagen 3 via Gemini API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$config = require_once dirname(__DIR__, 2) . '/src/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$prompt = $input['prompt'] ?? '';
$aspectRatio = $input['aspectRatio'] ?? '1:1'; // 1:1, 16:9, 9:16, 4:3, 3:4
$style = $input['style'] ?? ''; // Optional style modifier

if (empty($prompt)) {
    http_response_code(400);
    echo json_encode(['error' => 'Prompt required']);
    exit;
}

$apiKey = $config['gemini']['apiKey'];
if (empty($apiKey)) {
    http_response_code(400);
    echo json_encode(['error' => 'Gemini API key not configured']);
    exit;
}

try {
    // Use Imagen 3 via Gemini API
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-002:predict?key={$apiKey}";
    
    // Build full prompt with style
    $fullPrompt = $prompt;
    if ($style) {
        $fullPrompt .= ", {$style} style";
    }
    
    $requestData = [
        'instances' => [
            [
                'prompt' => $fullPrompt
            ]
        ],
        'parameters' => [
            'sampleCount' => 1,
            'aspectRatio' => $aspectRatio,
            'personGeneration' => 'DONT_ALLOW', // Safety setting
            'safetyFilterLevel' => 'BLOCK_MEDIUM_AND_ABOVE',
        ]
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 120,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        // Try alternative endpoint format
        $altApiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";
        
        $altRequestData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => "Generate an image: {$fullPrompt}"]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseModalities' => ['IMAGE', 'TEXT'],
            ]
        ];
        
        $ch = curl_init($altApiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($altRequestData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 120,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Image generation error ($httpCode): " . $response);
        }
    }
    
    $result = json_decode($response, true);
    
    // Parse response - handle both Imagen and Gemini response formats
    $imageData = null;
    $mimeType = 'image/png';
    
    // Imagen format
    if (isset($result['predictions'][0]['bytesBase64Encoded'])) {
        $imageData = $result['predictions'][0]['bytesBase64Encoded'];
        $mimeType = $result['predictions'][0]['mimeType'] ?? 'image/png';
    }
    // Gemini format
    elseif (isset($result['candidates'][0]['content']['parts'])) {
        foreach ($result['candidates'][0]['content']['parts'] as $part) {
            if (isset($part['inlineData'])) {
                $imageData = $part['inlineData']['data'];
                $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';
                break;
            }
        }
    }
    
    if (!$imageData) {
        throw new Exception('No image generated in response');
    }
    
    // Save to generated images folder
    $outputDir = dirname(__DIR__) . '/generated';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $filename = 'img_' . date('Ymd_His') . '_' . substr(md5($prompt), 0, 8);
    $extension = $mimeType === 'image/jpeg' ? '.jpg' : '.png';
    $filepath = $outputDir . '/' . $filename . $extension;
    
    file_put_contents($filepath, base64_decode($imageData));
    
    echo json_encode([
        'success' => true,
        'image' => 'data:' . $mimeType . ';base64,' . $imageData,
        'filename' => $filename . $extension,
        'path' => '/generated/' . $filename . $extension,
        'prompt' => $fullPrompt,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
