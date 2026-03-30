<?php
/**
 * CodePilot Configuration with enhanced security and validation
 */

// Initialize security and logging
require_once dirname(__DIR__) . '/src/Utils/Security.php';
require_once dirname(__DIR__) . '/src/Utils/Logger.php';

\CodePilot\Utils\Logger::init();

if (!function_exists('validateConfig')) {
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
}

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
    'allowedProviders' => ['ollama', 'deepseek', 'gemini', 'huggingface', 'qwen', 'mistral'],
    
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
            // Gemini 2.5 (Latest - 2025)
            'gemini-2.5-pro' => 'Gemini 2.5 Pro',
            'gemini-2.5-pro-preview-06-05' => 'Gemini 2.5 Pro (Preview)',
            'gemini-2.5-flash' => 'Gemini 2.5 Flash',
            'gemini-2.5-flash-preview-05-20' => 'Gemini 2.5 Flash (Preview)',
            // Gemini 2.0 (Current stable)
            'gemini-2.0-flash' => 'Gemini 2.0 Flash',
            'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash Lite',
            'gemini-2.0-flash-thinking-exp' => 'Gemini 2.0 Thinking (Exp)',
            // Gemini 1.5 (Legacy - Still available with BYOK)
            'gemini-1.5-pro' => 'Gemini 1.5 Pro',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            'gemini-1.5-flash-8b' => 'Gemini 1.5 Flash 8B',
            // Gemma 3 (Open Models)
            'gemma-3-27b' => 'Gemma 3 27B',
            'gemma-3-12b' => 'Gemma 3 12B',
            'gemma-3-4b' => 'Gemma 3 4B',
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

    // Qwen (Alibaba Cloud)
    'qwen' => [
        'apiKey' => $envData['QWEN_API_KEY'] ?? '',
        'apiUrl' => $envData['QWEN_API_URL'] ?? 'https://dashscope.aliyuncs.com/compatible-mode/v1',
        'timeout' => (int)($envData['QWEN_TIMEOUT'] ?? 120),
        'models' => [
            // Qwen 3.5 (Latest - 2025)
            'qwen-3.5-235b-a22b' => 'Qwen 3.5 235B A22B',
            'qwen-3.5-32b' => 'Qwen 3.5 32B',
            // Qwen 3 (Current)
            'qwen-3-235b-a22b' => 'Qwen 3 235B A22B',
            'qwen-3-30b-a3b' => 'Qwen 3 30B A3B',
            'qwen-3-32b' => 'Qwen 3 32B',
            // Qwen 2.5
            'qwen-2.5-72b-instruct' => 'Qwen 2.5 72B Instruct',
            'qwen-2.5-coder-32b-instruct' => 'Qwen 2.5 Coder 32B',
            'qwen-2.5-32b-instruct' => 'Qwen 2.5 32B Instruct',
            'qwen-2.5-14b-instruct' => 'Qwen 2.5 14B Instruct',
            'qwen-2.5-7b-instruct' => 'Qwen 2.5 7B Instruct',
            // Qwen Max/Plus/Turbo
            'qwen-max' => 'Qwen Max',
            'qwen-max-longcontext' => 'Qwen Max Long Context',
            'qwen-plus' => 'Qwen Plus',
            'qwen-turbo' => 'Qwen Turbo',
            // Qwen Coder (Specialized)
            'qwen-coder-plus' => 'Qwen Coder Plus',
            'qwen-coder-turbo' => 'Qwen Coder Turbo',
            // Qwen VL (Vision)
            'qwen-vl-max' => 'Qwen VL Max',
            'qwen-vl-plus' => 'Qwen VL Plus',
        ],
    ],

    // Mistral AI
    'mistral' => [
        'apiKey' => $envData['MISTRAL_API_KEY'] ?? '',
        'apiUrl' => $envData['MISTRAL_API_URL'] ?? 'https://api.mistral.ai/v1',
        'timeout' => (int)($envData['MISTRAL_TIMEOUT'] ?? 120),
        'models' => [
            // Mistral Large
            'mistral-large-latest' => 'Mistral Large (Latest)',
            'mistral-large-2411' => 'Mistral Large 24.11',
            // Mistral Small
            'mistral-small-latest' => 'Mistral Small (Latest)',
            'mistral-small-3.1' => 'Mistral Small 3.1',
            // Codestral (Code Specialist)
            'codestral-latest' => 'Codestral (Latest)',
            'codestral-2501' => 'Codestral 25.01',
            // Ministral (Lightweight)
            'ministral-8b-latest' => 'Ministral 8B',
            'ministral-3b-latest' => 'Ministral 3B',
            // Pixtral (Vision)
            'pixtral-large-latest' => 'Pixtral Large',
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
