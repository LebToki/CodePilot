<?php
/**
 * CodePilot Projects API
 * Manage projects across multiple workspaces with security enhancements
 */

// Security headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
\CodePilot\Utils\Logger::info('Projects API request started');

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!\CodePilot\Utils\Security::checkRateLimit($ip, 50, 3600)) {
    http_response_code(429);
    \CodePilot\Utils\Logger::warning('Rate limit exceeded', ['ip' => $ip]);
    echo json_encode(['error' => 'Too many requests. Please try again later.']);
    exit;
}

$config = require_once dirname(__DIR__, 2) . '/src/config.php';

// Define workspaces with validation
$workspaces = [
    'web' => [
        'name' => 'Web Projects',
        'path' => 'D:/laragon/www',
        'icon' => '🌐',
        'languages' => ['php', 'html', 'css', 'javascript'],
    ],
    'platform' => [
        'name' => 'Platform Projects', 
        'path' => 'E:/platform',
        'icon' => '🐍',
        'languages' => ['python', 'nodejs', 'rust', 'go'],
    ],
];

// Validate workspace paths exist
foreach ($workspaces as $id => $workspace) {
    if (!is_dir($workspace['path'])) {
        \CodePilot\Utils\Logger::warning('Workspace path does not exist', ['workspace' => $id, 'path' => $workspace['path']]);
        unset($workspaces[$id]);
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'workspaces':
            echo json_encode(['workspaces' => array_map(function($id, $ws) {
                return ['id' => $id, 'name' => $ws['name'], 'path' => $ws['path'], 'icon' => $ws['icon']];
            }, array_keys($workspaces), $workspaces)]);
            break;
            
        case 'list':
            $workspace = \CodePilot\Utils\Security::sanitizeInput($_GET['workspace'] ?? 'web', 'string');
            if (!isset($workspaces[$workspace])) {
                throw new Exception('Invalid workspace');
            }
            $projects = listProjects($workspaces[$workspace]['path']);
            echo json_encode(['projects' => $projects]);
            break;
            
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON');
            }
            
            $workspace = \CodePilot\Utils\Security::sanitizeInput($input['workspace'] ?? 'web', 'string');
            $name = \CodePilot\Utils\Security::sanitizeInput($input['name'] ?? '', 'filename');
            $template = \CodePilot\Utils\Security::sanitizeInput($input['template'] ?? 'empty', 'string');
            
            // Validate inputs
            if (empty($name)) {
                throw new Exception('Project name required');
            }
            
            if (!isset($workspaces[$workspace])) {
                throw new Exception('Invalid workspace');
            }
            
            $allowedTemplates = ['empty', 'php', 'python', 'nodejs', 'html', 'flask', 'express'];
            if (!in_array($template, $allowedTemplates)) {
                throw new Exception('Invalid template');
            }
            
            // Validate name format
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
                throw new Exception('Project name can only contain letters, numbers, hyphens, and underscores');
            }
            
            $result = createProject($workspaces[$workspace]['path'], $name, $template);
            \CodePilot\Utils\Logger::info('Project created', ['name' => $name, 'workspace' => $workspace, 'template' => $template]);
            echo json_encode($result);
            break;
            
        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON');
            }
            
            $path = \CodePilot\Utils\Security::sanitizeInput($input['path'] ?? '', 'path');
            
            if (empty($path) || !isValidProjectPath($path, $workspaces)) {
                throw new Exception('Invalid project path');
            }
            
            // Additional security: ensure path is within allowed workspaces
            $realPath = realpath($path);
            if (!$realPath) {
                throw new Exception('Project not found');
            }
            
            $result = deleteProject($path);
            \CodePilot\Utils\Logger::info('Project deleted', ['path' => $path]);
            echo json_encode($result);
            break;
            
        case 'info':
            $path = \CodePilot\Utils\Security::sanitizeInput($_GET['path'] ?? '', 'path');
            if (empty($path)) throw new Exception('Path required');
            
            if (!isValidProjectPath($path, $workspaces)) {
                throw new Exception('Invalid project path');
            }
            
            $info = getProjectInfo($path);
            echo json_encode($info);
            break;
            
        default:
            throw new Exception('Unknown action');
    }
} catch (Exception $e) {
    http_response_code(400);
    \CodePilot\Utils\Logger::error('Projects API error', ['error' => $e->getMessage(), 'action' => $action]);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * List projects in a directory
 */
function listProjects($basePath) {
    $projects = [];
    
    if (!is_dir($basePath)) {
        return $projects;
    }
    
    // ⚡ Bolt: Replace scandir() with FilesystemIterator to reduce memory footprint via lazy iteration over large directories
    $iterator = new FilesystemIterator($basePath, FilesystemIterator::SKIP_DOTS);

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isDir()) continue;
        
        $dir = $fileInfo->getFilename();
        $fullPath = str_replace('\\', '/', $fileInfo->getPathname());
        
        // Skip hidden directories and common non-project dirs
        if (strpos($dir, '.') === 0) continue;
        if (in_array($dir, ['node_modules', 'vendor', '__pycache__', '.git'])) continue;
        
        $modified = date('Y-m-d H:i'); // Fallback
        try {
            $modified = date('Y-m-d H:i', $fileInfo->getMTime());
        } catch (RuntimeException $e) {
            // Ignore unreadable files or broken symlinks to prevent API crash
        }

        $projects[] = [
            'name' => $dir,
            'path' => $fullPath,
            'type' => detectProjectType($fullPath),
            'modified' => $modified,
        ];
    }
    
    // Sort by modified date descending (optimized: array_multisort avoids calling strtotime in closures)
    // Note: This relies on the modified date format being lexicographically sortable (Y-m-d H:i)
    if (!empty($projects)) {
        array_multisort(array_column($projects, 'modified'), SORT_DESC, SORT_STRING, $projects);
    }
    
    return $projects;
}

/**
 * Detect project type based on files present
 */
function detectProjectType($path) {
    if (file_exists($path . '/composer.json')) return ['type' => 'php', 'icon' => '🐘', 'label' => 'PHP'];
    if (file_exists($path . '/package.json')) return ['type' => 'nodejs', 'icon' => '📦', 'label' => 'Node.js'];
    if (file_exists($path . '/requirements.txt') || file_exists($path . '/pyproject.toml')) return ['type' => 'python', 'icon' => '🐍', 'label' => 'Python'];
    if (file_exists($path . '/Cargo.toml')) return ['type' => 'rust', 'icon' => '🦀', 'label' => 'Rust'];
    if (file_exists($path . '/go.mod')) return ['type' => 'go', 'icon' => '🐹', 'label' => 'Go'];
    if (file_exists($path . '/index.html')) return ['type' => 'html', 'icon' => '🌐', 'label' => 'HTML'];
    return ['type' => 'unknown', 'icon' => '📁', 'label' => 'Project'];
}

/**
 * Create a new project
 */
function createProject($basePath, $name, $template) {
    // Sanitize name
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $projectPath = $basePath . '/' . $name;
    
    if (file_exists($projectPath)) {
        throw new Exception('Project already exists');
    }
    
    mkdir($projectPath, 0755, true);
    
    // Common README
    $readme = "# $name\n\nA project created with CodePilot.\n\n## Getting Started\n\n";
    
    // Apply template
    switch ($template) {
        case 'php':
            createPhpProject($projectPath, $name, $readme);
            break;
            
        case 'python':
            createPythonProject($projectPath, $name, $readme);
            break;
            
        case 'nodejs':
            createNodeProject($projectPath, $name, $readme);
            break;
            
        case 'html':
            createHtmlProject($projectPath, $name, $readme);
            break;
            
        case 'flask':
            createFlaskProject($projectPath, $name, $readme);
            break;
            
        case 'express':
            createExpressProject($projectPath, $name, $readme);
            break;
            
        default:
            file_put_contents($projectPath . '/README.md', "# $name\n\nA new project created with CodePilot.\n\n## Description\n\nAdd your project description here.\n\n## Getting Started\n\n```bash\n# Add setup instructions\n```\n");
            file_put_contents($projectPath . '/.gitignore', "# IDE\n.idea/\n.vscode/\n*.swp\n*.swo\n\n# OS\n.DS_Store\nThumbs.db\n");
    }
    
    return [
        'success' => true,
        'path' => $projectPath,
        'name' => $name,
    ];
}

/**
 * PHP Project Template
 */
function createPhpProject($path, $name, $readme) {
    // Directory structure
    mkdir($path . '/public', 0755, true);
    mkdir($path . '/src', 0755, true);
    mkdir($path . '/config', 0755, true);
    mkdir($path . '/tests', 0755, true);
    
    // composer.json
    file_put_contents($path . '/composer.json', json_encode([
        'name' => 'codepilot/' . strtolower($name),
        'description' => 'A PHP project created with CodePilot',
        'type' => 'project',
        'license' => 'MIT',
        'autoload' => [
            'psr-4' => [
                str_replace('-', '', ucwords($name, '-')) . '\\\\' => 'src/'
            ]
        ],
        'require' => [
            'php' => '^8.1'
        ],
        'require-dev' => [
            'phpunit/phpunit' => '^10.0'
        ],
        'scripts' => [
            'test' => 'phpunit tests/',
            'serve' => 'php -S localhost:8000 -t public/'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    // public/index.php
    $publicIndex = <<<'PHP'
<?php
/**
 * Application Entry Point
 */

require_once __DIR__ . '/../vendor/autoload.php';

use %NAMESPACE%\App;

// Load config
$config = require __DIR__ . '/../config/app.php';

// Initialize app
$app = new App($config);
$app->run();
PHP;
    file_put_contents($path . '/public/index.php', str_replace('%NAMESPACE%', str_replace('-', '', ucwords($name, '-')), $publicIndex));
    
    // public/.htaccess
    file_put_contents($path . '/public/.htaccess', "RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^ index.php [QSA,L]\n");
    
    // config/app.php
    file_put_contents($path . '/config/app.php', "<?php\n\nreturn [\n    'name' => '$name',\n    'debug' => true,\n    'timezone' => 'UTC',\n];\n");
    
    // src/App.php
    $appClass = <<<'PHP'
<?php

namespace %NAMESPACE%;

class App
{
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function run(): void
    {
        echo "<h1>Welcome to " . htmlspecialchars($this->config['name']) . "</h1>";
        echo "<p>Your PHP project is ready!</p>";
        echo "<p>Edit <code>src/App.php</code> to get started.</p>";
    }
}
PHP;
    file_put_contents($path . '/src/App.php', str_replace('%NAMESPACE%', str_replace('-', '', ucwords($name, '-')), $appClass));
    
    // tests/AppTest.php
    $testClass = <<<'PHP'
<?php

namespace %NAMESPACE%\Tests;

use PHPUnit\Framework\TestCase;
use %NAMESPACE%\App;

class AppTest extends TestCase
{
    public function testAppCanBeInstantiated(): void
    {
        $app = new App(['name' => 'Test']);
        $this->assertInstanceOf(App::class, $app);
    }
}
PHP;
    file_put_contents($path . '/tests/AppTest.php', str_replace('%NAMESPACE%', str_replace('-', '', ucwords($name, '-')), $testClass));
    
    // .gitignore
    file_put_contents($path . '/.gitignore', "/vendor/\n.env\n*.log\n.phpunit.cache/\n");
    
    // README
    file_put_contents($path . '/README.md', $readme . "```bash\ncomposer install\ncomposer serve\n```\n\nOpen http://localhost:8000\n\n## Testing\n\n```bash\ncomposer test\n```\n");
}

/**
 * Python Project Template
 */
function createPythonProject($path, $name, $readme) {
    // Directory structure
    $moduleName = str_replace('-', '_', strtolower($name));
    mkdir($path . '/' . $moduleName, 0755, true);
    mkdir($path . '/tests', 0755, true);
    
    // pyproject.toml
    $pyproject = <<<TOML
[build-system]
requires = ["setuptools>=61.0"]
build-backend = "setuptools.build_meta"

[project]
name = "$name"
version = "0.1.0"
description = "A Python project created with CodePilot"
readme = "README.md"
license = {text = "MIT"}
requires-python = ">=3.9"
dependencies = []

[project.optional-dependencies]
dev = ["pytest>=7.0", "black", "ruff"]

[project.scripts]
$name = "$moduleName.main:main"

[tool.black]
line-length = 88

[tool.ruff]
line-length = 88
select = ["E", "F", "W", "I"]
TOML;
    file_put_contents($path . '/pyproject.toml', $pyproject);
    
    // requirements.txt
    file_put_contents($path . '/requirements.txt', "# Production dependencies\n\n# Development dependencies\npytest>=7.0\nblack\nruff\n");
    
    // Module __init__.py
    file_put_contents($path . '/' . $moduleName . '/__init__.py', "\"\"\"$name - A Python project created with CodePilot.\"\"\"\n\n__version__ = \"0.1.0\"\n");
    
    // Module main.py
    $mainPy = <<<PYTHON
#!/usr/bin/env python3
\"\"\"Main entry point for $name.\"\"\"

import argparse
import sys


def parse_args() -> argparse.Namespace:
    \"\"\"Parse command line arguments.\"\"\"
    parser = argparse.ArgumentParser(
        description="$name - A Python project created with CodePilot"
    )
    parser.add_argument(
        "-v", "--verbose",
        action="store_true",
        help="Enable verbose output"
    )
    return parser.parse_args()


def main() -> int:
    \"\"\"Main function.\"\"\"
    args = parse_args()
    
    print(f"Welcome to $name!")
    print("Your Python project is ready.")
    
    if args.verbose:
        print("Verbose mode enabled.")
    
    return 0


if __name__ == "__main__":
    sys.exit(main())
PYTHON;
    file_put_contents($path . '/' . $moduleName . '/main.py', $mainPy);
    
    // tests/__init__.py
    file_put_contents($path . '/tests/__init__.py', "");
    
    // tests/test_main.py
    $testPy = <<<PYTHON
\"\"\"Tests for $name.\"\"\"

import pytest
from $moduleName.main import main


def test_main_returns_zero():
    \"\"\"Test that main returns 0 on success.\"\"\"
    assert main() == 0
PYTHON;
    file_put_contents($path . '/tests/test_main.py', $testPy);
    
    // .gitignore
    file_put_contents($path . '/.gitignore', "__pycache__/\n*.py[cod]\n*\$py.class\n.env\nvenv/\n.venv/\ndist/\nbuild/\n*.egg-info/\n.pytest_cache/\n.ruff_cache/\n");
    
    // README
    file_put_contents($path . '/README.md', $readme . "```bash\npython -m venv venv\nsource venv/bin/activate  # or venv\\Scripts\\activate on Windows\npip install -e \".[dev]\"\n```\n\n## Usage\n\n```bash\npython -m $moduleName.main\n# or\n$name\n```\n\n## Testing\n\n```bash\npytest\n```\n");
}

/**
 * Node.js Project Template
 */
function createNodeProject($path, $name, $readme) {
    mkdir($path . '/src', 0755, true);
    mkdir($path . '/tests', 0755, true);
    
    // package.json
    file_put_contents($path . '/package.json', json_encode([
        'name' => strtolower($name),
        'version' => '1.0.0',
        'description' => 'A Node.js project created with CodePilot',
        'main' => 'src/index.js',
        'type' => 'module',
        'scripts' => [
            'start' => 'node src/index.js',
            'dev' => 'node --watch src/index.js',
            'test' => 'node --test tests/',
            'lint' => 'eslint src/'
        ],
        'keywords' => [],
        'author' => '',
        'license' => 'MIT',
        'devDependencies' => [
            'eslint' => '^8.0.0'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    // src/index.js
    $indexJs = <<<'JS'
/**
 * %NAME% - A Node.js project created with CodePilot
 */

import { greet } from './utils.js';

function main() {
    console.log(greet('%NAME%'));
    console.log('Your Node.js project is ready!');
    console.log('Edit src/index.js to get started.');
}

main();
JS;
    file_put_contents($path . '/src/index.js', str_replace('%NAME%', $name, $indexJs));
    
    // src/utils.js
    $utilsJs = <<<'JS'
/**
 * Utility functions
 */

/**
 * Generate a greeting message
 * @param {string} name - Name to greet
 * @returns {string} Greeting message
 */
export function greet(name) {
    return `Welcome to ${name}!`;
}

/**
 * Format a date
 * @param {Date} date - Date to format
 * @returns {string} Formatted date
 */
export function formatDate(date = new Date()) {
    return date.toISOString().split('T')[0];
}
JS;
    file_put_contents($path . '/src/utils.js', $utilsJs);
    
    // tests/utils.test.js
    $testJs = <<<'JS'
import { describe, it } from 'node:test';
import assert from 'node:assert';
import { greet, formatDate } from '../src/utils.js';

describe('utils', () => {
    describe('greet', () => {
        it('should return a greeting message', () => {
            const result = greet('Test');
            assert.strictEqual(result, 'Welcome to Test!');
        });
    });

    describe('formatDate', () => {
        it('should format a date', () => {
            const date = new Date('2024-01-15');
            const result = formatDate(date);
            assert.strictEqual(result, '2024-01-15');
        });
    });
});
JS;
    file_put_contents($path . '/tests/utils.test.js', $testJs);
    
    // .gitignore
    file_put_contents($path . '/.gitignore', "node_modules/\n.env\n*.log\ndist/\ncoverage/\n");
    
    // .eslintrc.json
    file_put_contents($path . '/.eslintrc.json', json_encode([
        'env' => ['node' => true, 'es2022' => true],
        'parserOptions' => ['ecmaVersion' => 'latest', 'sourceType' => 'module'],
        'rules' => ['no-unused-vars' => 'warn', 'no-console' => 'off']
    ], JSON_PRETTY_PRINT));
    
    // README
    file_put_contents($path . '/README.md', $readme . "```bash\nnpm install\nnpm run dev\n```\n\n## Testing\n\n```bash\nnpm test\n```\n");
}

/**
 * HTML Project Template
 */
function createHtmlProject($path, $name, $readme) {
    mkdir($path . '/css', 0755, true);
    mkdir($path . '/js', 0755, true);
    mkdir($path . '/img', 0755, true);
    
    // index.html
    $indexHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="%NAME% - A project created with CodePilot">
    <title>%NAME%</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <nav class="nav container">
            <a href="#" class="logo">%NAME%</a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <h1>Welcome to %NAME%</h1>
                <p>Your project is ready. Start building something amazing!</p>
                <a href="#features" class="btn btn-primary">Get Started</a>
            </div>
        </section>

        <section id="features" class="features">
            <div class="container">
                <h2>Features</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🚀</div>
                        <h3>Fast</h3>
                        <p>Lightning-fast performance out of the box.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🎨</div>
                        <h3>Beautiful</h3>
                        <p>Modern, clean design that looks great.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📱</div>
                        <h3>Responsive</h3>
                        <p>Works perfectly on all devices.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="about" class="about">
            <div class="container">
                <h2>About</h2>
                <p>This project was created with CodePilot, your AI-powered coding assistant.</p>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 %NAME%. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
HTML;
    file_put_contents($path . '/index.html', str_replace('%NAME%', $name, $indexHtml));
    
    // css/style.css
    $styleCss = <<<'CSS'
/* Variables */
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --text: #1f2937;
    --text-light: #6b7280;
    --bg: #ffffff;
    --bg-alt: #f9fafb;
    --border: #e5e7eb;
    --radius: 12px;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Reset */
*, *::before, *::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Base */
html {
    scroll-behavior: smooth;
}

body {
    font-family: 'Inter', system-ui, sans-serif;
    line-height: 1.6;
    color: var(--text);
    background: var(--bg);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

/* Typography */
h1, h2, h3 {
    font-weight: 700;
    line-height: 1.2;
}

h1 { font-size: clamp(2.5rem, 5vw, 4rem); }
h2 { font-size: clamp(2rem, 4vw, 3rem); margin-bottom: 24px; }
h3 { font-size: 1.25rem; }

/* Buttons */
.btn {
    display: inline-block;
    padding: 14px 28px;
    border-radius: var(--radius);
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

/* Header */
.header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border);
    z-index: 100;
}

.nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 72px;
}

.logo {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    text-decoration: none;
}

.nav-links {
    display: flex;
    gap: 32px;
    list-style: none;
}

.nav-links a {
    color: var(--text-light);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.nav-links a:hover {
    color: var(--primary);
}

/* Hero */
.hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding-top: 72px;
    background: linear-gradient(180deg, var(--bg) 0%, var(--bg-alt) 100%);
}

.hero h1 {
    margin-bottom: 24px;
}

.hero p {
    font-size: 1.25rem;
    color: var(--text-light);
    margin-bottom: 32px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* Features */
.features {
    padding: 120px 0;
    text-align: center;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 32px;
    margin-top: 48px;
}

.feature-card {
    padding: 40px 32px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    transition: transform 0.2s;
}

.feature-card:hover {
    transform: translateY(-4px);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 16px;
}

.feature-card h3 {
    margin-bottom: 12px;
}

.feature-card p {
    color: var(--text-light);
}

/* About */
.about {
    padding: 120px 0;
    background: var(--bg-alt);
    text-align: center;
}

.about p {
    font-size: 1.125rem;
    color: var(--text-light);
    max-width: 600px;
    margin: 0 auto;
}

/* Footer */
.footer {
    padding: 40px 0;
    text-align: center;
    border-top: 1px solid var(--border);
}

.footer p {
    color: var(--text-light);
    font-size: 0.875rem;
}

/* Responsive */
@media (max-width: 768px) {
    .nav-links {
        display: none;
    }
}
CSS;
    file_put_contents($path . '/css/style.css', $styleCss);
    
    // js/main.js
    $mainJs = <<<'JS'
/**
 * %NAME% - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('%NAME% loaded!');
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.querySelector(anchor.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // Header scroll effect
    const header = document.querySelector('.header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        } else {
            header.style.boxShadow = 'none';
        }
    });
});
JS;
    file_put_contents($path . '/js/main.js', str_replace('%NAME%', $name, $mainJs));
    
    // README
    file_put_contents($path . '/README.md', $readme . "Simply open `index.html` in your browser.\n\nFor development with live reload, use a local server:\n```bash\nnpx serve .\n# or\npython -m http.server 8000\n```\n");
}

/**
 * Flask Project Template
 */
function createFlaskProject($path, $name, $readme) {
    $moduleName = str_replace('-', '_', strtolower($name));
    mkdir($path . '/' . $moduleName, 0755, true);
    mkdir($path . '/' . $moduleName . '/templates', 0755, true);
    mkdir($path . '/' . $moduleName . '/static/css', 0755, true);
    mkdir($path . '/' . $moduleName . '/static/js', 0755, true);
    mkdir($path . '/tests', 0755, true);
    
    // requirements.txt
    file_put_contents($path . '/requirements.txt', "flask>=3.0.0\npython-dotenv\ngunicorn\n\n# Development\npytest\nblack\nruff\n");
    
    // Module __init__.py
    $initPy = <<<PYTHON
\"\"\"$name Flask application.\"\"\"

from flask import Flask


def create_app():
    \"\"\"Application factory.\"\"\"
    app = Flask(__name__)
    app.config['SECRET_KEY'] = 'dev-secret-change-in-production'
    
    from . import routes
    app.register_blueprint(routes.bp)
    
    return app
PYTHON;
    file_put_contents($path . '/' . $moduleName . '/__init__.py', $initPy);
    
    // Module routes.py
    $routesPy = <<<PYTHON
\"\"\"Application routes.\"\"\"

from flask import Blueprint, render_template

bp = Blueprint('main', __name__)


@bp.route('/')
def index():
    \"\"\"Home page.\"\"\"
    return render_template('index.html', title='$name')


@bp.route('/api/health')
def health():
    \"\"\"Health check endpoint.\"\"\"
    return {'status': 'ok'}
PYTHON;
    file_put_contents($path . '/' . $moduleName . '/routes.py', $routesPy);
    
    // Template
    $indexHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ title }}</title>
    <link rel="stylesheet" href="{{ url_for('static', filename='css/style.css') }}">
</head>
<body>
    <div class="container">
        <h1>Welcome to {{ title }}</h1>
        <p>Your Flask project is ready!</p>
    </div>
</body>
</html>
HTML;
    file_put_contents($path . '/' . $moduleName . '/templates/index.html', $indexHtml);
    
    // Static CSS
    file_put_contents($path . '/' . $moduleName . '/static/css/style.css', "body {\n    font-family: system-ui, sans-serif;\n    max-width: 800px;\n    margin: 2rem auto;\n    padding: 0 1rem;\n}\n");
    
    // run.py
    $runPy = <<<PYTHON
#!/usr/bin/env python3
\"\"\"Run the Flask application.\"\"\"

from $moduleName import create_app

app = create_app()

if __name__ == '__main__':
    app.run(debug=True)
PYTHON;
    file_put_contents($path . '/run.py', $runPy);
    
    // .gitignore
    file_put_contents($path . '/.gitignore', "__pycache__/\n*.py[cod]\n.env\nvenv/\n.venv/\ninstance/\n.pytest_cache/\n");
    
    // README
    file_put_contents($path . '/README.md', $readme . "```bash\npython -m venv venv\nsource venv/bin/activate\npip install -r requirements.txt\npython run.py\n```\n\nOpen http://localhost:5000\n");
}

/**
 * Express.js Project Template
 */
function createExpressProject($path, $name, $readme) {
    mkdir($path . '/src/routes', 0755, true);
    mkdir($path . '/src/middleware', 0755, true);
    mkdir($path . '/public', 0755, true);
    mkdir($path . '/tests', 0755, true);
    
    // package.json
    file_put_contents($path . '/package.json', json_encode([
        'name' => strtolower($name),
        'version' => '1.0.0',
        'description' => 'An Express.js API created with CodePilot',
        'main' => 'src/index.js',
        'type' => 'module',
        'scripts' => [
            'start' => 'node src/index.js',
            'dev' => 'node --watch src/index.js',
            'test' => 'node --test tests/'
        ],
        'dependencies' => [
            'express' => '^4.18.0',
            'cors' => '^2.8.0',
            'dotenv' => '^16.0.0'
        ],
        'devDependencies' => [
            'eslint' => '^8.0.0'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    // src/index.js
    $indexJs = <<<'JS'
/**
 * %NAME% - Express.js API
 */

import express from 'express';
import cors from 'cors';
import { router as apiRouter } from './routes/api.js';

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('public'));

// Routes
app.use('/api', apiRouter);

app.get('/', (req, res) => {
    res.json({
        name: '%NAME%',
        message: 'Welcome to your Express API!',
        docs: '/api'
    });
});

// Start server
app.listen(PORT, () => {
    console.log(`🚀 %NAME% running at http://localhost:${PORT}`);
});
JS;
    file_put_contents($path . '/src/index.js', str_replace('%NAME%', $name, $indexJs));
    
    // src/routes/api.js
    $apiJs = <<<'JS'
/**
 * API Routes
 */

import { Router } from 'express';

export const router = Router();

router.get('/', (req, res) => {
    res.json({
        endpoints: {
            'GET /api/health': 'Health check',
            'GET /api/users': 'List users',
            'POST /api/users': 'Create user'
        }
    });
});

router.get('/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

router.get('/users', (req, res) => {
    res.json([
        { id: 1, name: 'Alice' },
        { id: 2, name: 'Bob' }
    ]);
});

router.post('/users', (req, res) => {
    const { name } = req.body;
    if (!name) {
        return res.status(400).json({ error: 'Name is required' });
    }
    res.status(201).json({ id: Date.now(), name });
});
JS;
    file_put_contents($path . '/src/routes/api.js', $apiJs);
    
    // .env.example
    file_put_contents($path . '/.env.example', "PORT=3000\nNODE_ENV=development\n");
    
    // .gitignore
    file_put_contents($path . '/.gitignore', "node_modules/\n.env\n*.log\n");
    
    // README
    file_put_contents($path . '/README.md', $readme . "```bash\nnpm install\nnpm run dev\n```\n\nOpen http://localhost:3000\n\n## API Endpoints\n\n- `GET /` - API info\n- `GET /api` - Endpoints list\n- `GET /api/health` - Health check\n- `GET /api/users` - List users\n- `POST /api/users` - Create user\n");
}

/**
 * Delete a project
 */
function deleteProject($path) {
    if (!is_dir($path)) {
        throw new Exception('Project not found');
    }
    
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
    
    return ['success' => true];
}

/**
 * Validate project path is within allowed workspaces
 */
function isValidProjectPath($path, $workspaces) {
    $realPath = realpath($path);
    if (!$realPath) return false;
    
    foreach ($workspaces as $ws) {
        $wsPath = realpath($ws['path']);
        if ($wsPath) {
            if ($realPath === $wsPath) {
                return true;
            }

            $normalizedReal = str_replace('\\', '/', $realPath);
            $normalizedAllowed = rtrim(str_replace('\\', '/', $wsPath), '/') . '/';

            if (strpos($normalizedReal, $normalizedAllowed) === 0) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Get project info
 */
function getProjectInfo($path) {
    if (!is_dir($path)) {
        throw new Exception('Project not found');
    }
    
    $type = detectProjectType($path);
    $files = countFiles($path);
    
    return [
        'name' => basename($path),
        'path' => $path,
        'type' => $type,
        'files' => $files,
        'modified' => date('Y-m-d H:i', filemtime($path)),
    ];
}

/**
 * Count files in directory
 */
function countFiles($path) {
    $count = 0;
    $dirIterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);

    // Skip large common directories to improve performance
    $filterIterator = new RecursiveCallbackFilterIterator($dirIterator, function ($current, $key, $iterator) {
        if ($iterator->hasChildren()) {
            $filename = $current->getFilename();
            if (in_array($filename, ['node_modules', 'vendor', '.git', '__pycache__', '.venv', 'venv', '.idea', '.vscode', 'dist', 'build', 'coverage'])) {
                return false;
            }
        }
        return true;
    });

    $iterator = new RecursiveIteratorIterator($filterIterator);
    
    foreach ($iterator as $file) {
        if ($file->isFile()) $count++;
    }
    
    return $count;
}
