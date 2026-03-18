<?php
/**
 * CodePilot Header
 */

$config = require_once dirname(__DIR__) . '/src/config.php';
$appName = $config['appName'] ?? 'CodePilot';
$defaultProvider = $config['defaultProvider'] ?? 'deepseek';
$defaultModel = $config['defaultModel'] ?? 'deepseek-chat';

// Get current page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CodePilot - High-performance AI-powered code assistant with multi-provider support (DeepSeek, Gemini, Ollama, HuggingFace). Build, debug, and optimize code faster.">
    <meta name="keywords" content="AI coding assistant, CodePilot, Tarek Tarabichi, DeepSeek, Gemini, Code IDE, Ollama, LLM coding, 2TInteractive">
    <meta name="author" content="Tarek Tarabichi - 2TInteractive">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($appName); ?> - AI Code Assistant">
    <meta property="og:description" content="An advanced AI coding IDE featuring multi-provider chat, project management, and Monaco editor.">
    <meta property="og:image" content="assets/img/favicon.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($appName); ?> - AI Code Assistant">
    <meta property="twitter:description" content="An advanced AI coding IDE featuring multi-provider chat, project management, and Monaco editor.">
    <meta property="twitter:image" content="assets/img/favicon.png">

    <title><?php echo htmlspecialchars($appName); ?> - AI Code Assistant</title>
    
    <!-- Icons -->
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="apple-touch-icon" href="assets/img/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/codepilot.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@2.1.0/dist/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-python.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-bash.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo"><?php echo htmlspecialchars($appName); ?></div>
            <p style="color: var(--text-secondary); font-size: 13px; margin-top: 4px;">AI Code Assistant</p>
        </div>
        
        <nav>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="index.php" class="sidebar-nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                        <iconify-icon icon="mdi:code-braces"></iconify-icon>
                        <span>Code Chat</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="projects.php" class="sidebar-nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>">
                        <iconify-icon icon="mdi:folder-multiple"></iconify-icon>
                        <span>Projects</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="studio.php" class="sidebar-nav-link <?php echo $currentPage === 'studio' ? 'active' : ''; ?>">
                        <iconify-icon icon="mdi:palette"></iconify-icon>
                        <span>Studio</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="settings.php" class="sidebar-nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                        <iconify-icon icon="mdi:cog"></iconify-icon>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Sessions -->
        <div style="padding: 16px 12px; border-top: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">Sessions</span>
                <button class="btn-icon" style="width: 28px; height: 28px;" onclick="createNewSession()" title="New Session" aria-label="New session">
                    <iconify-icon icon="mdi:plus"></iconify-icon>
                </button>
            </div>
            <div class="sessions-list" id="sessions-list">
                <!-- Sessions loaded here -->
            </div>
        </div>
        
        <!-- Footer -->
        <div style="margin-top: auto; padding: 16px; border-top: 1px solid var(--border-color); text-align: center;">
            <small style="color: var(--text-muted); font-size: 11px;">
                © <?php echo date('Y'); ?> 2TInteractive<br>
                Crafted by <strong>Tarek Tarabichi</strong>
            </small>
        </div>
    </aside>
    
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <select id="provider-select" class="provider-select" onchange="onProviderChange()" aria-label="Select AI provider">
                <option value="ollama" <?php echo $defaultProvider === 'ollama' ? 'selected' : ''; ?>>🦙 Ollama (Local)</option>
                <option value="deepseek" <?php echo $defaultProvider === 'deepseek' ? 'selected' : ''; ?>>🔮 DeepSeek</option>
                <option value="gemini" <?php echo $defaultProvider === 'gemini' ? 'selected' : ''; ?>>✨ Gemini</option>
                <option value="huggingface" <?php echo $defaultProvider === 'huggingface' ? 'selected' : ''; ?>>🤗 HuggingFace</option>
            </select>
            <select id="model-select" class="model-select" aria-label="Select AI model">
                <option value="">Loading models...</option>
            </select>
        </div>
        <div class="header-right">
            <button class="btn-icon" onclick="toggleEditor()" title="Toggle Editor" aria-label="Toggle code editor">
                <iconify-icon icon="mdi:code-tags"></iconify-icon>
            </button>
            <button class="btn-icon" onclick="clearChat()" title="Clear Chat" aria-label="Clear chat messages">
                <iconify-icon icon="mdi:delete-outline"></iconify-icon>
            </button>
        </div>
    </header>
