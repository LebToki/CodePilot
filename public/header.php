<?php
/**
 * CodePilot Header
 */

$config = require dirname(__DIR__) . '/src/config.php';
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
    <title><?php echo htmlspecialchars($appName); ?> - AI Code Assistant</title>
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
                <button class="btn-icon" style="width: 28px; height: 28px;" onclick="createNewSession()" title="New Session">
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
                Made with ❤️ by <?php echo htmlspecialchars($config['companyName']); ?>
            </small>
        </div>
    </aside>
    
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <select id="provider-select" class="provider-select" onchange="onProviderChange()">
                <option value="ollama" <?php echo $defaultProvider === 'ollama' ? 'selected' : ''; ?>>🦙 Ollama (Local)</option>
                <option value="deepseek" <?php echo $defaultProvider === 'deepseek' ? 'selected' : ''; ?>>🔮 DeepSeek</option>
                <option value="gemini" <?php echo $defaultProvider === 'gemini' ? 'selected' : ''; ?>>✨ Gemini</option>
                <option value="huggingface" <?php echo $defaultProvider === 'huggingface' ? 'selected' : ''; ?>>🤗 HuggingFace</option>
            </select>
            <select id="model-select" class="model-select">
                <option value="">Loading models...</option>
            </select>
        </div>
        <div class="header-right">
            <button class="btn-icon" onclick="toggleEditor()" title="Toggle Editor">
                <iconify-icon icon="mdi:code-tags"></iconify-icon>
            </button>
            <button class="btn-icon" onclick="clearChat()" title="Clear Chat">
                <iconify-icon icon="mdi:delete-outline"></iconify-icon>
            </button>
        </div>
    </header>
