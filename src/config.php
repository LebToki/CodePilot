<?php
/**
 * CodePilot Configuration
 */

// Load .env file
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

return [
    // App
    'appName' => $_ENV['APP_NAME'] ?? 'CodePilot',
    'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    
    // Default Provider
    'defaultProvider' => $_ENV['DEFAULT_PROVIDER'] ?? 'deepseek',
    'defaultModel' => $_ENV['DEFAULT_MODEL'] ?? 'deepseek-chat',
    
    // Ollama
    'ollama' => [
        'apiUrl' => $_ENV['OLLAMA_API_URL'] ?? 'http://localhost:11434/api',
    ],
    
    // DeepSeek
    'deepseek' => [
        'apiKey' => $_ENV['DEEPSEEK_API_KEY'] ?? '',
        'apiUrl' => $_ENV['DEEPSEEK_API_URL'] ?? 'https://api.deepseek.com/v1',
        'models' => [
            'deepseek-chat' => 'DeepSeek Chat',
            'deepseek-coder' => 'DeepSeek Coder',
            'deepseek-reasoner' => 'DeepSeek Reasoner (R1)',
        ],
    ],
    
    // Gemini
    'gemini' => [
        'apiKey' => $_ENV['GEMINI_API_KEY'] ?? '',
        'models' => [
            // Gemini 2.5 (Latest)
            'gemini-2.5-pro-preview-06-05' => 'Gemini 2.5 Pro',
            'gemini-2.5-flash-preview-05-20' => 'Gemini 2.5 Flash',
            // Gemini 2.0
            'gemini-2.0-flash' => 'Gemini 2.0 Flash',
            'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash Lite',
            // Gemini 1.5
            'gemini-1.5-pro' => 'Gemini 1.5 Pro',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            // Experimental
            'gemini-2.0-flash-thinking-exp' => 'Gemini 2.0 Thinking (Exp)',
        ],
    ],
    
    // HuggingFace
    'huggingface' => [
        'apiKey' => $_ENV['HUGGINGFACE_API_KEY'] ?? '',
        'apiUrl' => $_ENV['HUGGINGFACE_API_URL'] ?? 'https://api-inference.huggingface.co/v1',
        'models' => [
            'meta-llama/Llama-3.3-70B-Instruct' => 'Llama 3.3 70B',
            'Qwen/Qwen2.5-Coder-32B-Instruct' => 'Qwen 2.5 Coder 32B',
            'mistralai/Mixtral-8x7B-Instruct-v0.1' => 'Mixtral 8x7B',
        ],
    ],
    
    // Branding
    'developerName' => $_ENV['DEVELOPER_NAME'] ?? 'Developer',
    'companyName' => $_ENV['COMPANY_NAME'] ?? 'Company',
    'companyUrl' => $_ENV['COMPANY_URL'] ?? '#',
];
