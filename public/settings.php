<?php
/**
 * CodePilot Settings
 */
require_once __DIR__ . '/header.php';

?>

<main class="main-content" style="overflow-y: auto; max-height: calc(100vh - 60px);">
    <div style="padding: 32px; max-width: 1200px; margin: 0 auto; width: 100%; padding-bottom: 100px;">
        <h1 class="text-gradient" style="font-size: 28px; margin-bottom: 8px;">Settings</h1>
        <p style="color: var(--text-secondary); margin-bottom: 32px;">Configure your AI providers and preferences</p>

        <!-- Configuration Form -->
        <form id="settings-form" onsubmit="saveSettings(event)">
            <!-- AI Providers -->
            <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
                <h2 style="font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <iconify-icon icon="mdi:brain"></iconify-icon>
                    AI Providers
                </h2>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <!-- DeepSeek -->
                    <div class="form-group">
                        <label class="form-label">
                            <iconify-icon icon="mdi:hexagon-multiple" style="vertical-align: middle;"></iconify-icon>
                            DeepSeek API Key
                        </label>
                        <div style="position: relative;">
                            <input type="password" name="deepseekKey" class="form-input" value="<?php echo htmlspecialchars($config['deepseek']['apiKey'] ?? ''); ?>" placeholder="sk-...">
                            <button type="button" class="btn-icon" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%);" onclick="togglePassword(this)">
                                <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                            </button>
                        </div>
                        <small style="color: var(--text-secondary); display: block; margin-top: 4px;">
                            <a href="https://platform.deepseek.com/" target="_blank" style="color: var(--primary);">Get API Key →</a>
                        </small>
                    </div>

                    <!-- Google Gemini -->
                    <div class="form-group">
                        <label class="form-label">
                            <iconify-icon icon="mdi:google" style="vertical-align: middle;"></iconify-icon>
                            Google Gemini API Key
                        </label>
                        <div style="position: relative;">
                            <input type="password" name="geminiKey" class="form-input" value="<?php echo htmlspecialchars($config['gemini']['apiKey'] ?? ''); ?>" placeholder="AIza...">
                            <button type="button" class="btn-icon" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%);" onclick="togglePassword(this)">
                                <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                            </button>
                        </div>
                        <small style="color: var(--text-secondary); display: block; margin-top: 4px;">
                            <a href="https://aistudio.google.com/apikey" target="_blank" style="color: var(--primary);">Get API Key →</a>
                        </small>
                    </div>

                    <!-- Alibaba Qwen -->
                    <div class="form-group">
                        <label class="form-label">
                            <iconify-icon icon="mdi:star-four-points" style="vertical-align: middle;"></iconify-icon>
                            Alibaba Qwen API Key
                        </label>
                        <div style="position: relative;">
                            <input type="password" name="qwenKey" class="form-input" value="<?php echo htmlspecialchars($config['qwen']['apiKey'] ?? ''); ?>" placeholder="sk-...">
                            <button type="button" class="btn-icon" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%);" onclick="togglePassword(this)">
                                <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                            </button>
                        </div>
                        <small style="color: var(--text-secondary); display: block; margin-top: 4px;">
                            <a href="https://dashscope.console.aliyun.com/" target="_blank" style="color: var(--primary);">Get API Key →</a>
                        </small>
                    </div>

                    <!-- Mistral AI -->
                    <div class="form-group">
                        <label class="form-label">
                            <iconify-icon icon="mdi:weather-windy" style="vertical-align: middle;"></iconify-icon>
                            Mistral AI API Key
                        </label>
                        <div style="position: relative;">
                            <input type="password" name="mistralKey" class="form-input" value="<?php echo htmlspecialchars($config['mistral']['apiKey'] ?? ''); ?>" placeholder="...">
                            <button type="button" class="btn-icon" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%);" onclick="togglePassword(this)">
                                <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                            </button>
                        </div>
                        <small style="color: var(--text-secondary); display: block; margin-top: 4px;">
                            <a href="https://console.mistral.ai/api-keys/" target="_blank" style="color: var(--primary);">Get API Key →</a>
                        </small>
                    </div>

                    <!-- HuggingFace -->
                    <div class="form-group">
                        <label class="form-label">
                            <iconify-icon icon="mdi:face-man-outline" style="vertical-align: middle;"></iconify-icon>
                            HuggingFace API Key <span style="color: var(--text-secondary); font-weight: normal;">(Optional)</span>
                        </label>
                        <div style="position: relative;">
                            <input type="password" name="huggingfaceKey" class="form-input" value="<?php echo htmlspecialchars($config['huggingface']['apiKey'] ?? ''); ?>" placeholder="hf_...">
                            <button type="button" class="btn-icon" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%);" onclick="togglePassword(this)">
                                <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                            </button>
                        </div>
                        <small style="color: var(--text-secondary); display: block; margin-top: 4px;">
                            <a href="https://huggingface.co/settings/tokens" target="_blank" style="color: var(--primary);">Get API Key →</a>
                        </small>
                    </div>

                    <!-- Ollama (Local) -->
                    <div class="form-group">
                        <label class="form-label">
                            <iconify-icon icon="mdi:llama" style="vertical-align: middle;"></iconify-icon>
                            Ollama API URL
                        </label>
                        <input type="text" name="ollamaUrl" class="form-input" value="<?php echo htmlspecialchars($config['ollama']['apiUrl'] ?? 'http://localhost:11434/api'); ?>">
                        <small style="color: var(--text-secondary); display: block; margin-top: 4px;">
                            <a href="https://ollama.ai" target="_blank" style="color: var(--primary);">Download Ollama →</a>
                        </small>
                    </div>

                    <!-- Qwen API URL -->
                    <div class="form-group">
                        <label class="form-label">
                            <iconify-icon icon="mdi:web" style="vertical-align: middle;"></iconify-icon>
                            Qwen API URL
                        </label>
                        <input type="text" name="qwenUrl" class="form-input" value="<?php echo htmlspecialchars($config['qwen']['apiUrl'] ?? 'https://dashscope.aliyuncs.com/compatible-mode/v1'); ?>">
                    </div>
                </div>
            </div>

            <!-- Default Provider -->
            <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
                <h2 style="font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <iconify-icon icon="mdi:star"></iconify-icon>
                    Default Provider
                </h2>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Default AI Provider</label>
                        <select name="defaultProvider" class="form-select">
                            <option value="deepseek" <?php echo ($config['defaultProvider'] ?? 'deepseek') === 'deepseek' ? 'selected' : ''; ?>>🔮 DeepSeek</option>
                            <option value="gemini" <?php echo ($config['defaultProvider'] ?? '') === 'gemini' ? 'selected' : ''; ?>>✨ Google Gemini</option>
                            <option value="qwen" <?php echo ($config['defaultProvider'] ?? '') === 'qwen' ? 'selected' : ''; ?>>💫 Alibaba Qwen</option>
                            <option value="huggingface" <?php echo ($config['defaultProvider'] ?? '') === 'huggingface' ? 'selected' : ''; ?>>🤗 HuggingFace</option>
                            <option value="ollama" <?php echo ($config['defaultProvider'] ?? '') === 'ollama' ? 'selected' : ''; ?>>🦙 Ollama (Local)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Default Model</label>
                        <input type="text" name="defaultModel" class="form-input" value="<?php echo htmlspecialchars($config['defaultModel'] ?? 'deepseek-chat'); ?>" placeholder="e.g., deepseek-chat">
                        <small style="color: var(--text-secondary); display: block; margin-top: 4px;">Model name for the selected provider</small>
                    </div>
                </div>
            </div>

            <!-- Branding -->
            <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
                <h2 style="font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <iconify-icon icon="mdi:brush"></iconify-icon>
                    Personalization
                </h2>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Application Name</label>
                        <input type="text" name="appName" class="form-input" value="<?php echo htmlspecialchars($config['appName'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Developer Name</label>
                        <input type="text" name="developerName" class="form-input" value="<?php echo htmlspecialchars($config['developerName'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="companyName" class="form-input" value="<?php echo htmlspecialchars($config['companyName'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company URL</label>
                        <input type="text" name="companyUrl" class="form-input" value="<?php echo htmlspecialchars($config['companyUrl'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Workspaces -->
            <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
                <h2 style="font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <iconify-icon icon="mdi:folder-network"></iconify-icon>
                    Workspaces
                </h2>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Web Projects Path (Laragon/www)</label>
                        <input type="text" name="webWorkspace" class="form-input" value="<?php echo htmlspecialchars($config['workspaces']['web']['path']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Platform Projects Path</label>
                        <input type="text" name="platformWorkspace" class="form-input" value="<?php echo htmlspecialchars($config['workspaces']['platform']['path'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
                <h2 style="font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <iconify-icon icon="mdi:tune"></iconify-icon>
                    Advanced
                </h2>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Debug Mode</label>
                        <select name="debug" class="form-select">
                            <option value="true" <?php echo ($config['debug'] ?? false) ? 'selected' : ''; ?>>Enabled</option>
                            <option value="false" <?php echo !($config['debug'] ?? false) ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Environment</label>
                        <select name="environment" class="form-select">
                            <option value="development" <?php echo ($config['environment'] ?? 'production') === 'development' ? 'selected' : ''; ?>>Development</option>
                            <option value="production" <?php echo ($config['environment'] ?? 'production') === 'production' ? 'selected' : ''; ?>>Production</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="window.history.back()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="mdi:content-save" style="vertical-align: middle;"></iconify-icon>
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</main>

<script>
function togglePassword(button) {
    const input = button.parentElement.querySelector('input');
    const icon = button.querySelector('iconify-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.icon = 'mdi:eye-off-outline';
    } else {
        input.type = 'password';
        icon.icon = 'mdi:eye-outline';
    }
}

async function saveSettings(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const data = {
        deepseekKey: formData.get('deepseekKey'),
        geminiKey: formData.get('geminiKey'),
        qwenKey: formData.get('qwenKey'),
        huggingfaceKey: formData.get('huggingfaceKey'),
        ollamaUrl: formData.get('ollamaUrl'),
        qwenUrl: formData.get('qwenUrl'),
        defaultProvider: formData.get('defaultProvider'),
        defaultModel: formData.get('defaultModel'),
        appName: formData.get('appName'),
        developerName: formData.get('developerName'),
        companyName: formData.get('companyName'),
        companyUrl: formData.get('companyUrl'),
        webWorkspace: formData.get('webWorkspace'),
        platformWorkspace: formData.get('platformWorkspace'),
        debug: formData.get('debug') === 'true',
        environment: formData.get('environment'),
    };
    
    try {
        const response = await fetch('api/settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Settings saved successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Failed to save settings: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Error saving settings: ' + error.message, 'error');
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        background: var(--${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'});
        color: white;
        border-radius: 8px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
