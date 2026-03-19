<?php
/**
 * CodePilot Terminal API
 * Execute commands and manage processes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Security: Define allowed commands and directories
$allowedCommands = [
    'npm', 'yarn', 'pnpm', 'composer', 'python', 'pip', 'git', 'php', 'node', 'npm run', 'yarn run', 'pnpm run',
    'composer install', 'composer update', 'composer require', 'composer remove',
    'npm install', 'npm update', 'npm run', 'npm start', 'npm test', 'npm build',
    'yarn install', 'yarn add', 'yarn remove', 'yarn run', 'yarn start', 'yarn test', 'yarn build',
    'python -m', 'pip install', 'pip uninstall', 'pip list',
    'git status', 'git add', 'git commit', 'git push', 'git pull', 'git clone', 'git branch', 'git checkout',
    'php -S', 'php artisan', 'phpunit', 'phpstan', 'phpcs', 'phpcbf',
    'node', 'nodemon', 'eslint', 'prettier', 'tsc', 'webpack', 'vite'
];

$allowedPaths = [
    'D:/laragon/www',
    'E:/platform',
    '/app',
    '/home/jules'
];

$action = $_GET['action'] ?? 'execute';
$input = json_decode(file_get_contents('php://input'), true);
$command = $input['command'] ?? '';
$projectPath = $input['projectPath'] ?? '';
$timeout = $input['timeout'] ?? 120; // 2 minutes default

try {
    switch ($action) {
        case 'execute':
            if (empty($command)) {
                throw new Exception('Command required');
            }
            
            // Validate command
            if (!isAllowedCommand($command)) {
                throw new Exception('Command not allowed for security reasons');
            }
            
            // Validate path
            if (!empty($projectPath)) {
                validatePath($projectPath, $allowedPaths);
            }
            
            $result = executeCommand($command, $projectPath, $timeout);
            echo json_encode($result);
            break;
            
        case 'list-processes':
            $processes = listProcesses();
            echo json_encode(['processes' => $processes]);
            break;
            
        case 'kill-process':
            $pid = $input['pid'] ?? '';
            if (empty($pid)) throw new Exception('PID required');
            $result = killProcess($pid);
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Unknown action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Validate if command is allowed
 */
function isAllowedCommand($command) {
    global $allowedCommands;
    
    // Normalize command for checking
    $command = trim($command);

    // Security: Prevent command injection via chaining operators and shell metacharacters
    if (preg_match('/[;&|`$<>\n\r]/', $command)) {
        return false;
    }

    $commandLower = strtolower($command);
    
    // Check exact matches
    foreach ($allowedCommands as $allowed) {
        if (stripos($command, $allowed) === 0) {
            return true;
        }
    }
    
    // Allow basic system commands
    $basicCommands = ['ls', 'dir', 'pwd', 'cd', 'cat', 'type', 'echo', 'clear', 'cls'];
    $commandParts = explode(' ', $command);
    if (in_array(strtolower($commandParts[0]), $basicCommands)) {
        return true;
    }
    
    return false;
}

/**
 * Validate path is within allowed directories
 */
function validatePath($path, $allowedPaths) {
    $realPath = realpath($path);
    if (!$realPath) {
        throw new Exception('Invalid path');
    }
    
    $isValid = false;
    foreach ($allowedPaths as $allowed) {
        $allowedReal = realpath($allowed);
        if ($allowedReal && strpos($realPath, $allowedReal) === 0) {
            $isValid = true;
            break;
        }
    }
    
    if (!$isValid) {
        throw new Exception('Access denied: Path not in allowed directories');
    }
}

/**
 * Execute command with timeout
 */
function executeCommand($command, $projectPath = '', $timeout = 120) {
    $output = [];
    $returnCode = 0;
    $startTime = time();
    $process = null;
    $descriptorspec = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w']   // stderr
    ];
    
    // Change to project directory if specified
    $cwd = $projectPath ?: getcwd();
    
    // Add Windows compatibility
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = 'cmd /c "' . $command . '"';
    }
    
    $process = proc_open($command, $descriptorspec, $pipes, $cwd);
    
    if (is_resource($process)) {
        // Close stdin
        fclose($pipes[0]);
        
        // Read output
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
    }
    
    // Format output
    $outputText = '';
    if (!empty($stdout)) {
        $outputText .= $stdout;
    }
    if (!empty($stderr)) {
        $outputText .= "\n[ERROR] " . $stderr;
    }
    
    return [
        'success' => $returnCode === 0,
        'command' => $command,
        'output' => $outputText,
        'returnCode' => $returnCode,
        'executionTime' => time() - $startTime,
        'cwd' => $cwd
    ];
}

/**
 * List running processes (simplified)
 */
function listProcesses() {
    $processes = [];
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - parse tasklist CSV for better accuracy
        $output = shell_exec('tasklist /FO CSV /NH');
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            // "Image Name","PID","Session Name","Session#","Mem Usage"
            $parts = str_getcsv($line);
            if (count($parts) >= 2) {
                // Ignore system processes
                $name = $parts[0];
                if (!in_array(strtolower($name), ['svchost.exe', 'smss.exe', 'csrss.exe', 'wininit.exe', 'services.exe'])) {
                    $processes[] = ['pid' => $parts[1], 'name' => $name];
                }
            }
        }
    } else {
        // Unix-like
        $output = shell_exec('ps -eo pid,comm,args --no-headers');
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) >= 3) {
                $pid = $parts[0];
                $comm = $parts[1];
                $args = $parts[2];

                // Exclude the ps command itself, basic system processes, and our own PHP processes
                if (strpos($args, 'ps -eo pid') === false && !in_array($comm, ['systemd', 'kthreadd', 'rcu_sched'])) {
                    $processes[] = ['pid' => $pid, 'name' => $args];
                }
            }
        }
    }
    
    return $processes;
}

/**
 * Kill a process by PID
 */
function killProcess($pid) {
    // Security: Validate that PID is an integer to prevent command injection
    $pid = (int)$pid;
    if ($pid <= 0) {
        throw new Exception('Invalid PID');
    }

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $result = shell_exec("taskkill /PID " . escapeshellarg($pid) . " /F");
        return ['success' => strpos($result, 'SUCCESS') !== false, 'output' => $result];
    } else {
        $result = shell_exec("kill -9 " . escapeshellarg($pid) . " 2>&1");
        return ['success' => empty($result), 'output' => $result];
    }
}