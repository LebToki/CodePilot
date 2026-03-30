<?php
/**
 * CodePilot Providers API
 * Returns available models for each provider
 */

header('Content-Type: application/json');

$config = require_once dirname(__DIR__, 2) . '/src/config.php';

$action = $_GET['action'] ?? 'list';
$provider = $_GET['provider'] ?? null;

switch ($action) {
    case 'list':
        // List all providers
        echo json_encode([
            'providers' => [
                [
                    'id' => 'ollama',
                    'name' => 'Ollama (Local)',
                    'icon' => '🦙',
                    'configured' => true,
                ],
                [
                    'id' => 'deepseek',
                    'name' => 'DeepSeek',
                    'icon' => '🔮',
                    'configured' => !empty($config['deepseek']['apiKey']),
                ],
                [
                    'id' => 'gemini',
                    'name' => 'Gemini',
                    'icon' => '✨',
                    'configured' => !empty($config['gemini']['apiKey']),
                ],
                [
                    'id' => 'qwen',
                    'name' => 'Qwen (Alibaba)',
                    'icon' => '💫',
                    'configured' => !empty($config['qwen']['apiKey']),
                ],
                [
                    'id' => 'mistral',
                    'name' => 'Mistral AI',
                    'icon' => '🌬️',
                    'configured' => !empty($config['mistral']['apiKey']),
                ],
                [
                    'id' => 'huggingface',
                    'name' => 'HuggingFace',
                    'icon' => '🤗',
                    'configured' => !empty($config['huggingface']['apiKey']),
                ],
            ],
        ]);
        break;
        
    case 'models':
        // Get models for a specific provider
        if (!$provider) {
            http_response_code(400);
            echo json_encode(['error' => 'Provider required']);
            exit;
        }
        
        $models = getModelsForProvider($provider, $config);
        echo json_encode(['models' => $models]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}

function getModelsForProvider($provider, $config) {
    switch ($provider) {
        case 'ollama':
            return getOllamaModels($config);
        case 'deepseek':
            return array_map(function($name, $id) {
                return ['id' => $id, 'name' => $name];
            }, $config['deepseek']['models'], array_keys($config['deepseek']['models']));
        case 'gemini':
            return array_map(function($name, $id) {
                return ['id' => $id, 'name' => $name];
            }, $config['gemini']['models'], array_keys($config['gemini']['models']));
        case 'huggingface':
            return array_map(function($name, $id) {
                return ['id' => $id, 'name' => $name];
            }, $config['huggingface']['models'], array_keys($config['huggingface']['models']));
        case 'qwen':
            return array_map(function($name, $id) {
                return ['id' => $id, 'name' => $name];
            }, $config['qwen']['models'], array_keys($config['qwen']['models']));
        case 'mistral':
            return array_map(function($name, $id) {
                return ['id' => $id, 'name' => $name];
            }, $config['mistral']['models'], array_keys($config['mistral']['models']));
        default:
            return [];
    }
}

function getOllamaModels($config) {
    $apiUrl = $config['ollama']['apiUrl'] . '/tags';
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [['id' => 'error', 'name' => 'Ollama not available']];
    }
    
    $result = json_decode($response, true);
    $models = [];
    
    foreach ($result['models'] ?? [] as $model) {
        $models[] = [
            'id' => $model['name'],
            'name' => $model['name'],
        ];
    }
    
    return $models ?: [['id' => 'none', 'name' => 'No models installed']];
}
