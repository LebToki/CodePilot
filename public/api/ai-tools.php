<?php
/**
 * CodePilot AI Tools API
 * Additional AI-powered development tools
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
$action = $_GET['action'] ?? $input['action'] ?? '';
$code = $input['code'] ?? '';
$language = $input['language'] ?? 'javascript';
$context = $input['context'] ?? '';

try {
    switch ($action) {
        case 'explain-code':
            $result = explainCode($config, $code, $language);
            echo json_encode(['explanation' => $result]);
            break;
            
        case 'optimize-code':
            $result = optimizeCode($config, $code, $language);
            echo json_encode(['optimized' => $result]);
            break;
            
        case 'generate-tests':
            $result = generateTests($config, $code, $language);
            echo json_encode(['tests' => $result]);
            break;
            
        case 'find-bugs':
            $result = findBugs($config, $code, $language);
            echo json_encode(['bugs' => $result]);
            break;
            
        case 'convert-code':
            $targetLanguage = $input['targetLanguage'] ?? 'javascript';
            $result = convertCode($config, $code, $language, $targetLanguage);
            echo json_encode(['converted' => $result]);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Explain code functionality
 */
function explainCode($config, $code, $language) {
    $prompt = "Explain what this {$language} code does in simple terms. Focus on the main functionality and key concepts. Return a clear, concise explanation:\n\n{$code}";
    return callAI($config, $prompt);
}

/**
 * Optimize code for performance
 */
function optimizeCode($config, $code, $language) {
    $prompt = "Optimize this {$language} code for better performance, readability, and best practices. Keep the same functionality but improve the implementation. Return only the optimized code:\n\n{$code}";
    return callAI($config, $prompt);
}

/**
 * Generate unit tests for code
 */
function generateTests($config, $code, $language) {
    $prompt = "Generate comprehensive unit tests for this {$language} code. Include edge cases and error conditions. Return test code that can be run directly:\n\n{$code}";
    return callAI($config, $prompt);
}

/**
 * Find potential bugs and issues
 */
function findBugs($config, $code, $language) {
    $prompt = "Analyze this {$language} code for potential bugs, security vulnerabilities, performance issues, and code smells. Provide specific recommendations for fixes:\n\n{$code}";
    return callAI($config, $prompt);
}

/**
 * Convert code between languages
 */
function convertCode($config, $code, $sourceLang, $targetLang) {
    $prompt = "Convert this {$sourceLang} code to {$targetLang}. Maintain the same functionality and include comments explaining the conversion:\n\n{$code}";
    return callAI($config, $prompt);
}

/**
 * Call AI provider with prompt
 */
function callAI($config, $prompt) {
    // Use the first available provider
    $provider = 'ollama'; // Default to local for speed
    $model = 'llama3.2:latest';
    
    if (isset($config['deepseek']['apiKey'])) {
        $provider = 'deepseek';
        $model = 'deepseek-chat';
    }
    
    switch ($provider) {
        case 'ollama':
            return callOllama($config, $model, [['role' => 'user', 'content' => $prompt]], false);
        case 'deepseek':
            return callDeepSeek($config, $model, [['role' => 'user', 'content' => $prompt]], false);
        default:
            return "AI analysis not available";
    }
}

/**
 * Call Ollama API (copied from chat.php)
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
 * Call DeepSeek API (copied from chat.php)
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