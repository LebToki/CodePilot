<?php
/**
 * CodePilot Files API
 * Browse and manage project files
 */

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';
$path = $_GET['path'] ?? $_POST['path'] ?? '';

// Security: Validate path is within allowed directories
$allowedPaths = [
    'D:/laragon/www',
    '/app',
    'E:/platform',
    '/app',
    '/home/jules'
];

try {
    switch ($action) {
        case 'list':
            if (empty($path)) throw new Exception('Path required');
            validatePath($path, $allowedPaths);
            
            $files = listDirectory($path);
            echo json_encode(['files' => $files, 'path' => $path]);
            break;
            
        case 'read':
            if (empty($path)) throw new Exception('Path required');
            validatePath($path, $allowedPaths);
            
            $content = read_file($path);
            echo json_encode(['content' => $content, 'path' => $path]);
            break;
            
        case 'write':
            $input = json_decode(file_get_contents('php://input'), true);
            $path = $input['path'] ?? '';
            $content = $input['content'] ?? '';
            
            if (empty($path)) throw new Exception('Path required');
            validatePath($path, $allowedPaths);
            
            $result = writeFile($path, $content);
            echo json_encode($result);
            break;
            
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            $path = $input['path'] ?? '';
            $type = $input['type'] ?? 'file'; // file or directory
            
            if (empty($path)) throw new Exception('Path required');
            validatePath(dirname($path), $allowedPaths);
            
            $result = createItem($path, $type);
            echo json_encode($result);
            break;
            
        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $path = $input['path'] ?? '';
            
            if (empty($path)) throw new Exception('Path required');
            validatePath($path, $allowedPaths);
            
            $result = deleteItem($path);
            echo json_encode($result);
            break;
            
        case 'rename':
            $input = json_decode(file_get_contents('php://input'), true);
            $oldPath = $input['oldPath'] ?? '';
            $newPath = $input['newPath'] ?? '';
            
            if (empty($oldPath) || empty($newPath)) throw new Exception('Paths required');
            validatePath($oldPath, $allowedPaths);
            validatePath(dirname($newPath), $allowedPaths);
            
            $result = renameItem($oldPath, $newPath);
            echo json_encode($result);
            break;
            
        case 'search':
            if (empty($path)) throw new Exception('Path required');
            $query = $_GET['query'] ?? $_POST['query'] ?? '';
            if (empty($query)) throw new Exception('Query required');
            validatePath($path, $allowedPaths);

            $results = searchDirectory($path, $query);
            echo json_encode(['results' => $results, 'path' => $path, 'query' => $query]);
            break;

        default:
            throw new Exception('Unknown action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Validate path is within allowed directories
 */
function validatePath($path, $allowedPaths) {
    $realPath = realpath($path);
    
    // For new files, check parent directory
    if (!$realPath) {
        $realPath = realpath(dirname($path));
    }
    
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
 * List directory contents
 */
function listDirectory($path) {
    if (!is_dir($path)) {
        throw new Exception('Not a directory');
    }
    
    $items = [];
    $entries = scandir($path);
    
    // Directories first
    $dirs = [];
    $files = [];
    
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        
        $fullPath = $path . '/' . $entry;
        $isDir = is_dir($fullPath);
        
        $item = [
            'name' => $entry,
            'path' => str_replace('\\', '/', $fullPath),
            'isDir' => $isDir,
            'size' => $isDir ? null : filesize($fullPath),
            'modified' => date('Y-m-d H:i', filemtime($fullPath)),
            'extension' => $isDir ? null : strtolower(pathinfo($entry, PATHINFO_EXTENSION)),
        ];
        
        if ($isDir) {
            // Skip common non-essential directories
            if (in_array($entry, ['node_modules', 'vendor', '__pycache__', '.git', '.idea', '.vscode'])) {
                $item['collapsed'] = true;
            }
            $dirs[] = $item;
        } else {
            $files[] = $item;
        }
    }
    
    // Sort alphabetically
    usort($dirs, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    
    return array_merge($dirs, $files);
}

/**
 * Read file content
 */
function read_file($path) {
    if (!is_file($path)) {
        throw new Exception('Not a file');
    }
    
    // Check file size (limit to 1MB)
    if (filesize($path) > 1024 * 1024) {
        throw new Exception('File too large (max 1MB)');
    }
    
    // Check if binary
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $path);
    finfo_close($finfo);
    
    if (strpos($mime, 'text/') !== 0 && !in_array($mime, ['application/json', 'application/javascript', 'application/xml'])) {
        throw new Exception('Binary files not supported');
    }
    
    return file_get_contents($path);
}

/**
 * Write file content
 */
function writeFile($path, $content) {
    $result = file_put_contents($path, $content);
    
    if ($result === false) {
        throw new Exception('Failed to write file');
    }
    
    return ['success' => true, 'bytes' => $result];
}

/**
 * Create file or directory
 */
function createItem($path, $type) {
    if (file_exists($path)) {
        throw new Exception('Item already exists');
    }
    
    if ($type === 'directory') {
        $result = mkdir($path, 0755, true);
    } else {
        $result = file_put_contents($path, '');
    }
    
    if ($result === false) {
        throw new Exception('Failed to create item');
    }
    
    return ['success' => true, 'path' => $path];
}

/**
 * Delete file or directory
 */
function deleteItem($path) {
    if (!file_exists($path)) {
        throw new Exception('Item not found');
    }
    
    if (is_dir($path)) {
        // Recursive delete
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir($path);
    } else {
        unlink($path);
    }
    
    return ['success' => true];
}

/**
 * Rename file or directory
 */
function renameItem($oldPath, $newPath) {
    if (!file_exists($oldPath)) {
        throw new Exception('Item not found');
    }
    
    if (file_exists($newPath)) {
        throw new Exception('Target already exists');
    }
    
    $result = rename($oldPath, $newPath);
    
    if (!$result) {
        throw new Exception('Failed to rename');
    }
    
    return ['success' => true, 'path' => $newPath];
}

/**
 * Search for a query in directory contents (recursively)
 */
function searchDirectory($path, $query) {
    if (!is_dir($path)) {
        throw new Exception('Not a directory');
    }

    $results = [];
    $ignoredDirs = ['node_modules', 'vendor', '__pycache__', '.git', '.idea', '.vscode', 'dist', 'build', 'generated'];

    // We use a custom filter iterator to easily skip ignored directories
    class IgnoredDirFilter extends RecursiveFilterIterator {
        private $ignored;
        public function __construct($iterator, $ignored = []) {
            parent::__construct($iterator);
            $this->ignored = $ignored;
        }
        public function accept(): bool {
            if ($this->hasChildren()) {
                if (in_array($this->current()->getFilename(), $this->ignored)) {
                    return false;
                }
            }
            return true;
        }

        public function getChildren(): ?RecursiveFilterIterator {
            return new self($this->getInnerIterator()->getChildren(), $this->ignored);
        }
    }

    $dirIter = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
    $filter = new IgnoredDirFilter($dirIter, $ignoredDirs);
    $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            // simple binary check by size > 1MB
            if ($file->getSize() > 1024 * 1024) continue;

            $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'pdf', 'zip', 'tar', 'gz', 'exe', 'dll', 'so', 'sqlite', 'db'])) continue;

            $filePath = $file->getRealPath();
            $contents = @file_get_contents($filePath);

            if ($contents !== false && stripos($contents, $query) !== false) {
                // Find line number and snippet
                $lines = explode("\n", $contents);
                $matches = [];
                foreach ($lines as $index => $line) {
                    if (stripos($line, $query) !== false) {
                        $matches[] = [
                            'line' => $index + 1,
                            'text' => trim($line)
                        ];
                        // Limit to 5 matches per file
                        if (count($matches) >= 5) break;
                    }
                }

                $results[] = [
                    'path' => str_replace('\\', '/', $filePath),
                    'name' => $file->getFilename(),
                    'matches' => $matches
                ];
            }
        }
    }

    return $results;
}