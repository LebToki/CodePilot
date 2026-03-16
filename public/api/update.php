<?php
/**
 * CodePilot Update API - Pulls latest changes from git
 */
header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/src/Utils/Logger.php';

use CodePilot\Utils\Logger;

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    return;
}

try {
    Logger::info('CodePilot update triggered');

    // Make sure we are in the right directory
    $projectRoot = dirname(__DIR__, 2);
    $currentDir = getcwd();
    chdir($projectRoot);

    // Execute git pull
    // Use an absolute path to git or expect it to be in PATH
    // 2>&1 redirects stderr to stdout so we can capture error messages
    $output = shell_exec('git pull 2>&1');

    // Restore directory
    chdir($currentDir);

    if ($output === null) {
        throw new Exception('Failed to execute git command');
    }

    $success = strpos($output, 'fatal:') === false && strpos($output, 'error:') === false;

    if ($success) {
        Logger::info('CodePilot updated successfully', ['output' => $output]);
        echo json_encode([
            'success' => true,
            'message' => 'CodePilot updated successfully',
            'output' => trim($output)
        ]);
    } else {
        Logger::error('CodePilot update failed', ['output' => $output]);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Git pull failed',
            'output' => trim($output)
        ]);
    }

} catch (Exception $e) {
    Logger::error('Exception during CodePilot update', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
