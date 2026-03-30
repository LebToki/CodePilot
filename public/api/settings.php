<?php
/**
 * CodePilot Settings API - Securely update .env configuration
 */
header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/src/config.php';
use CodePilot\Utils\Logger;

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $envFile = dirname(__DIR__, 2) . '/.env';
    if (!file_exists($envFile)) {
        // Fallback to .env.example if .env doesn't exist
        $exampleFile = dirname(__DIR__, 2) . '/.env.example';
        if (file_exists($exampleFile)) {
            copy($exampleFile, $envFile);
        } else {
            throw new Exception('.env file not found');
        }
    }

    // Read existing .env
    $content = file_get_contents($envFile);
    $lines = explode("\n", $content);
    
    // Mapping of form keys to .env keys
    $mapping = [
        'appName' => 'APP_NAME',
        'deepseekKey' => 'DEEPSEEK_API_KEY',
        'geminiKey' => 'GEMINI_API_KEY',
        'qwenKey' => 'QWEN_API_KEY',
        'mistralKey' => 'MISTRAL_API_KEY',
        'huggingfaceKey' => 'HUGGINGFACE_API_KEY',
        'ollamaUrl' => 'OLLAMA_API_URL',
        'qwenUrl' => 'QWEN_API_URL',
        'mistralUrl' => 'MISTRAL_API_URL',
        'defaultProvider' => 'DEFAULT_PROVIDER',
        'defaultModel' => 'DEFAULT_MODEL',
        'developerName' => 'DEVELOPER_NAME',
        'companyName' => 'COMPANY_NAME',
        'companyUrl' => 'COMPANY_URL',
        'webWorkspace' => 'WEB_WORKSPACE_PATH',
        'platformWorkspace' => 'PLATFORM_WORKSPACE_PATH',
        'debug' => 'APP_DEBUG',
        'environment' => 'APP_ENV'
    ];

    $updatedKeys = [];
    foreach ($mapping as $formKey => $envKey) {
        if (isset($input[$formKey])) {
            $value = trim($input[$formKey]);
            
            // Security: Prevent CRLF injection in .env file
            $value = str_replace(["\r", "\n"], '', $value);

            // Simple validation
            if ($formKey === 'companyUrl' && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                continue; // Skip invalid URLs
            }

            $keyFound = false;
            foreach ($lines as $i => $line) {
                if (strpos(trim($line), $envKey . '=') === 0) {
                    $lines[$i] = $envKey . '=' . $value;
                    $keyFound = true;
                    break;
                }
            }
            
            if (!$keyFound) {
                $lines[] = $envKey . '=' . $value;
            }
            $updatedKeys[] = $envKey;
        }
    }

    // Save back to .env
    file_put_contents($envFile, implode("\n", $lines));
    
    Logger::info('Settings updated via API', ['keys' => $updatedKeys]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Settings saved successfully',
        'updated' => $updatedKeys
    ]);

} catch (Exception $e) {
    Logger::error('Failed to save settings', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
