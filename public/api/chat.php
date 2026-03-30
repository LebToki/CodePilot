<?php
/**
 * CodePilot Chat API
 * Multi-provider chat endpoint with security enhancements
 */

// Security headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize security and logging
require_once dirname(__DIR__, 2) . '/src/Utils/Security.php';
require_once dirname(__DIR__, 2) . '/src/Utils/Logger.php';

\CodePilot\Utils\Logger::init();
\CodePilot\Utils\Logger::info('Chat API request started');

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!\CodePilot\Utils\Security::checkRateLimit($ip, 100, 3600)) {
    http_response_code(429);
    \CodePilot\Utils\Logger::warning('Rate limit exceeded', ['ip' => $ip]);
    echo json_encode(['error' => 'Too many requests. Please try again later.']);
    exit;
}

$config = require_once dirname(__DIR__, 2) . '/src/config.php';

// Get and validate request data
$input = json_decode(file_get_contents('php://input'), true);

// Check if JSON parsing failed
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    \CodePilot\Utils\Logger::warning('Invalid JSON received', ['error' => json_last_error_msg()]);
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

// Validate input
$provider = \CodePilot\Utils\Security::sanitizeInput($input['provider'] ?? 'deepseek', 'string');
$model = \CodePilot\Utils\Security::sanitizeInput($input['model'] ?? 'deepseek-chat', 'string');
$messages = $input['messages'] ?? [];
$stream = filter_var($input['stream'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Validate provider
$allowedProviders = ['ollama', 'deepseek', 'gemini', 'huggingface', 'qwen'];
if (!in_array($provider, $allowedProviders)) {
    http_response_code(400);
    \CodePilot\Utils\Logger::warning('Invalid provider', ['provider' => $provider]);
    echo json_encode(['error' => 'Invalid provider']);
    exit;
}

// Validate messages
if (empty($messages) || !is_array($messages)) {
    http_response_code(400);
    echo json_encode(['error' => 'No messages provided']);
    exit;
}

// Validate and sanitize messages
$sanitizedMessages = [];
foreach ($messages as $message) {
    if (!isset($message['role']) || !isset($message['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid message format']);
        exit;
    }
    
    $role = \CodePilot\Utils\Security::sanitizeInput($message['role'], 'string');
    $content = \CodePilot\Utils\Security::sanitizeInput($message['content'], 'string');
    
    // Validate role
    if (!in_array($role, ['system', 'user', 'assistant'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid message role']);
        exit;
    }
    
    // Limit message content length
    if (strlen($content) > 10000) {
        http_response_code(400);
        echo json_encode(['error' => 'Message content too long']);
        exit;
    }
    
    $sanitizedMessages[] = ['role' => $role, 'content' => $content];
}

$messages = $sanitizedMessages;

// Add system prompt for coding assistant
$systemPrompt = "You are CodePilot, an expert AI coding assistant. You help with:
- Writing clean, efficient code
- Debugging and fixing issues
- Explaining programming concepts
- Code reviews and best practices
- Converting code between languages

Always provide well-formatted code with proper syntax highlighting markers (```language).
Be concise but thorough. If you need clarification, ask.";

// Prepend system message if not already present
if (empty($messages) || $messages[0]['role'] !== 'system') {
    array_unshift($messages, ['role' => 'system', 'content' => $systemPrompt]);
}

// Log request
\CodePilot\Utils\Logger::info('Chat request', [
    'provider' => $provider,
    'model' => $model,
    'messages_count' => count($messages),
    'ip' => $ip
]);
try {
    switch ($provider) {
        case 'ollama':
            $response = callOllama($config, $model, $messages, $stream);
            break;
        case 'deepseek':
            $response = callDeepSeek($config, $model, $messages, $stream);
            break;
        case 'gemini':
            $response = callGemini($config, $model, $messages, $stream);
            break;
        case 'huggingface':
            $response = callHuggingFace($config, $model, $messages, $stream);
            break;
        case 'qwen':
            $response = callQwen($config, $model, $messages, $stream);
            break;
        default:
            throw new Exception("Unknown provider: $provider");
    }
    
    // Sanitize response before sending
    $response = \CodePilot\Utils\Security::sanitizeInput($response, 'string');
    
    echo json_encode(['response' => $response]);
    
} catch (Exception $e) {
    http_response_code(500);
    \CodePilot\Utils\Logger::error('Chat API error', ['error' => $e->getMessage()]);
    echo json_encode(['error' => 'An error occurred while processing your request']);
}

/**
 * Call Ollama API
 */
function callOllama($config, $model, $messages, $stream) {
    $apiUrl = $config['ollama']['apiUrl'] . '/chat';
    
    $data = [
        'model' => $model,
        'messages' => $messages,
        'stream' => false,
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 120,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Ollama error: " . $response);
    }
    
    $result = json_decode($response, true);
    return $result['message']['content'] ?? '';
}

/**
 * Call DeepSeek API
 */
function callDeepSeek($config, $model, $messages, $stream) {
    $apiUrl = $config['deepseek']['apiUrl'] . '/chat/completions';
    $apiKey = $config['deepseek']['apiKey'];
    
    if (empty($apiKey)) {
        throw new Exception("DeepSeek API key not configured");
    }
    
    $data = [
        'model' => $model,
        'messages' => $messages,
        'stream' => false,
        'temperature' => 0.7,
        'max_tokens' => 4096,
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 120,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("DeepSeek error ($httpCode): " . $response);
    }
    
    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? '';
}

/**
 * Call Gemini API
 */
function callGemini($config, $model, $messages, $stream) {
    $apiKey = $config['gemini']['apiKey'];
    
    if (empty($apiKey)) {
        throw new Exception("Gemini API key not configured");
    }
    
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    
    // Convert messages to Gemini format
    $contents = [];
    foreach ($messages as $msg) {
        if ($msg['role'] === 'system') {
            // Gemini handles system prompts differently
            continue;
        }
        $contents[] = [
            'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $msg['content']]],
        ];
    }
    
    // Add system instruction
    $systemInstruction = null;
    foreach ($messages as $msg) {
        if ($msg['role'] === 'system') {
            $systemInstruction = $msg['content'];
            break;
        }
    }
    
    $data = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 4096,
        ],
    ];
    
    if ($systemInstruction) {
        $data['systemInstruction'] = ['parts' => [['text' => $systemInstruction]]];
    }
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 120,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Gemini error ($httpCode): " . $response);
    }
    
    $result = json_decode($response, true);
    return $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
}

/**
 * Call HuggingFace API
 */
function callHuggingFace($config, $model, $messages, $stream) {
    $apiUrl = $config['huggingface']['apiUrl'] . '/chat/completions';
    $apiKey = $config['huggingface']['apiKey'];

    if (empty($apiKey)) {
        throw new Exception("HuggingFace API key not configured");
    }

    $data = [
        'model' => $model,
        'messages' => $messages,
        'stream' => false,
        'max_tokens' => 4096,
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 120,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("HuggingFace error ($httpCode): " . $response);
    }

    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? '';
}

/**
 * Call Qwen (Alibaba Cloud DashScope) API
 */
function callQwen($config, $model, $messages, $stream) {
    $apiUrl = $config['qwen']['apiUrl'] . '/chat/completions';
    $apiKey = $config['qwen']['apiKey'];

    if (empty($apiKey)) {
        throw new Exception("Qwen API key not configured");
    }

    $data = [
        'model' => $model,
        'messages' => $messages,
        'stream' => false,
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => $config['qwen']['timeout'] ?? 120,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Qwen error ($httpCode): " . $response);
    }

    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? '';
}
