<?php
/**
 * CodePilot Vision API
 * Image analysis using Gemini Vision
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$config = require_once dirname(__DIR__, 2) . '/src/config.php';

// Check for file upload or base64 image
$imageData = null;
$prompt = '';

if (isset($_FILES['image'])) {
    // File upload
    $file = $_FILES['image'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image type. Allowed: JPEG, PNG, GIF, WebP']);
        exit;
    }
    
    // Read and encode
    $imageData = base64_encode(file_get_contents($file['tmp_name']));
    $mimeType = $file['type'];
    $prompt = $_POST['prompt'] ?? 'Describe this image in detail.';
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // JSON request with base64 image
    $input = json_decode(file_get_contents('php://input'), true);
    $imageData = $input['image'] ?? null;
    $mimeType = $input['mimeType'] ?? 'image/png';
    $prompt = $input['prompt'] ?? 'Describe this image in detail.';
    
    // Remove data URL prefix if present
    if ($imageData && strpos($imageData, 'base64,') !== false) {
        $parts = explode('base64,', $imageData);
        $imageData = $parts[1] ?? $imageData;
        
        // Extract mime type from data URL
        if (preg_match('/data:([^;]+);/', $parts[0] ?? '', $matches)) {
            $mimeType = $matches[1];
        }
    }
}

if (!$imageData) {
    http_response_code(400);
    echo json_encode(['error' => 'No image provided']);
    exit;
}

$apiKey = $config['gemini']['apiKey'];
if (empty($apiKey)) {
    http_response_code(400);
    echo json_encode(['error' => 'Gemini API key not configured']);
    exit;
}

try {
    // Use Gemini 2.0 Flash for vision (best balance of speed/quality)
    $model = 'gemini-2.0-flash';
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    
    // Build request with image
    $requestData = [
        'contents' => [
            [
                'parts' => [
                    [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data' => $imageData
                        ]
                    ],
                    [
                        'text' => $prompt
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.4,
            'maxOutputTokens' => 4096,
        ],
        'systemInstruction' => [
            'parts' => [
                [
                    'text' => 'You are CodePilot, an AI coding assistant. When analyzing images, focus on:
- Code screenshots: Identify syntax errors, suggest improvements, explain the code
- UI mockups: Describe the layout, suggest implementation approaches
- Diagrams: Explain the architecture, data flow, or relationships shown
- Error messages: Help debug and solve the issue
Be concise and actionable in your responses.'
                ]
            ]
        ]
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 60,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Gemini Vision error ($httpCode): " . $response);
    }
    
    $result = json_decode($response, true);
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'No response generated';
    
    echo json_encode([
        'response' => $text,
        'model' => $model,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
