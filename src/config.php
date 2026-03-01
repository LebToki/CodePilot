<?php
/**
 * CodePilot Configuration with enhanced security and validation
 */

// Initialize security and logging
require_once dirname(__DIR__) . '/src/Utils/Security.php';
require_once dirname(__DIR__) . '/src/Utils/Logger.php';

\CodePilot\Utils\Logger::init();

// Load .env file with validation
$envFile = dirname(__DIR__) . '/.env';
$envData = [];

if (file_exists($envFile)) {
    try {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Validate key format
                if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $key)) {
                    \CodePilot\Utils\Logger::warning('Invalid environment variable key', ['key' => $key]);
                    continue;
                }
                
                $envData[$key] = $value;
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    } catch (Exception $e) {
        \CodePilot\Utils\Logger::error('Failed to load .env file', ['error' => $e->getMessage()]);
    }
}

// Configuration with validation and defaults
$config = [
    // App
    'appName' => $envData['APP_NAME'] ?? 'CodePilot',
    'debug' => ($envData['APP_DEBUG'] ?? 'false') === 'true',
    'version' => '1.0.0',
    'environment' => $envData['APP_ENV'] ?? 'production',
    
    // Security
    'security' => [
        'rate_limit_requests' => (int)($envData['RATE_LIMIT_REQUESTS'] ?? 100),
        'rate_limit_window' => (int)($envData['RATE_LIMIT_WINDOW'] ?? 3600),
        'max_file_size' => (int)($envData['MAX_FILE_SIZE'] ?? 10485760), // 10MB
        'allowed_file_types' => ['png', 'jpg', 'jpeg', 'webp', 'gif', 'txt', 'md', 'js', 'php', 'py', 'html', 'css'],
    ],
    
    // Default Provider
    'defaultProvider' => $envData['DEFAULT_PROVIDER'] ?? 'deepseek',
    'defaultModel' => $envData['DEFAULT_MODEL'] ?? 'deepseek-chat',
    'allowedProviders' => ['ollama', 'deepseek', 'gemini', 'huggingface'],
    
    // Ollama
    'ollama' => [
        'apiUrl' => $envData['OLLAMA_API_URL'] ?? 'http://localhost:11434/api',
        'timeout' => (int)($envData['OLLAMA_TIMEOUT'] ?? 120),
    ],
    
    // DeepSeek
    'deepseek' => [
        'apiKey' => $envData['DEEPSEEK_API_KEY'] ?? '',
        'apiUrl' => $envData['DEEPSEEK_API_URL'] ?? 'https://api.deepseek.com/v1',
        'timeout' => (int)($envData['DEEPSEEK_TIMEOUT'] ?? 120),
        'models' => [
            'deepseek-chat' => 'DeepSeek Chat',
            'deepseek-coder' => 'DeepSeek Coder',
            'deepseek-reasoner' => 'DeepSeek Reasoner (R1)',
        ],
    ],
    
    // Gemini
    'gemini' => [
        'apiKey' => $envData['GEMINI_API_KEY'] ?? '',
        'timeout' => (int)($envData['GEMINI_TIMEOUT'] ?? 120),
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
        'apiKey' => $envData['HUGGINGFACE_API_KEY'] ?? '',
        'apiUrl' => $envData['HUGGINGFACE_API_URL'] ?? 'https://api-inference.huggingface.co/v1',
        'timeout' => (int)($envData['HUGGINGFACE_TIMEOUT'] ?? 120),
        'models' => [
            'meta-llama/Llama-3.3-70B-Instruct' => 'Llama 3.3 70B',
            'Qwen/Qwen2.5-Coder-32B-Instruct' => 'Qwen 2.5 Coder 32B',
            'mistralai/Mixtral-8x7B-Instruct-v0.1' => 'Mixtral 8x7B',
        ],
    ],
    
    // Workspaces
    'workspaces' => [
        'web' => [
            'name' => 'Web Projects',
            'path' => $envData['WEB_WORKSPACE_PATH'] ?? 'D:/laragon/www',
            'icon' => '🌐',
            'languages' => ['php', 'html', 'css', 'javascript'],
        ],
        'platform' => [
            'name' => 'Platform Projects', 
            'path' => $envData['PLATFORM_WORKSPACE_PATH'] ?? 'E:/platform',
            'icon' => '🐍',
            'languages' => ['python', 'nodejs', 'rust', 'go'],
        ],
    ],
    
    // Branding
    'developerName' => $envData['DEVELOPER_NAME'] ?? 'Developer',
    'companyName' => $envData['COMPANY_NAME'] ?? 'Company',
    'companyUrl' => $envData['COMPANY_URL'] ?? '#',
    'supportEmail' => $envData['SUPPORT_EMAIL'] ?? '',
    
    // Paths
    'paths' => [
        'data' => dirname(__DIR__) . '/data',
        'logs' => dirname(__DIR__) . '/data/logs',
        'generated' => dirname(__DIR__) . '/public/generated',
        'temp' => sys_get_temp_dir(),
    ],
];

// Validate configuration
validateConfig($config);

// Log configuration load
\CodePilot\Utils\Logger::info('Configuration loaded', [
    'app_name' => $config['appName'],
    'environment' => $config['environment'],
    'debug' => $config['debug'],
    'default_provider' => $config['defaultProvider'],
]);

return $config;

/**
 * Validate configuration values
 */
function validateConfig(array $config): void
{
    // Validate app name
    if (empty($config['appName'])) {
        throw new Exception('Application name cannot be empty');
    }
    
    // Validate default provider
    if (!in_array($config['defaultProvider'], $config['allowedProviders'])) {
        throw new Exception('Invalid default provider: ' . $config['defaultProvider']);
    }
    
    // Validate workspace paths
    foreach ($config['workspaces'] as $id => $workspace) {
        if (!is_dir($workspace['path'])) {
            \CodePilot\Utils\Logger::warning('Workspace path does not exist', ['workspace' => $id, 'path' => $workspace['path']]);
        }
    }
    
    // Validate API keys for cloud providers
    if ($config['defaultProvider'] === 'deepseek' && empty($config['deepseek']['apiKey'])) {
        \CodePilot\Utils\Logger::warning('DeepSeek API key not configured');
    }
    
    if ($config['defaultProvider'] === 'gemini' && empty($config['gemini']['apiKey'])) {
        \CodePilot\Utils\Logger::warning('Gemini API key not configured');
    }
    
    if ($config['defaultProvider'] === 'huggingface' && empty($config['huggingface']['apiKey'])) {
        \CodePilot\Utils\Logger::warning('HuggingFace API key not configured');
    }
}
