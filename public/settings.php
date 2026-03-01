<?php
/**
 * CodePilot Settings
 */
require_once __DIR__ . '/header.php';
$config = require dirname(__DIR__) . '/src/config.php';
?>

<main class="main-content">
    <div style="padding: 32px; max-width: 800px; margin: 0 auto; width: 100%;">
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
                
                <div style="display: grid; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">DeepSeek API Key</label>
                        <div style="position: relative;">
                            <input type="password" name="deepseekKey" class="form-input" value="<?php echo htmlspecialchars($config['deepseek']['apiKey'] ?? ''); ?>" placeholder="sk-...">
                            <button type="button" class="btn-icon" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%);" onclick="togglePassword(this)">
                                <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Google Gemini API Key</label>
                        <div style="position: relative;">
                            <input type="password" name="geminiKey" class="form-input" value="<?php echo htmlspecialchars($config['gemini']['apiKey'] ?? ''); ?>" placeholder="AIza...">
                            <button type="button" class="btn-icon" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%);" onclick="togglePassword(this)">
                                <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">HuggingFace API Key (Optional)</label>
                        <div style="position: relative;">
                            <input type="password" name="huggingfaceKey" class="form-input" value="<?php echo htmlspecialchars($config['huggingface']['apiKey'] ?? ''); ?>" placeholder="hf_...">
                            <button type="button" class="btn-icon" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%);" onclick="togglePassword(this)">
                                <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ollama API URL</label>
                        <input type="text" name="ollamaUrl" class="form-input" value="<?php echo htmlspecialchars($config['ollama']['apiUrl'] ?? 'http://localhost:11434/api'); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Branding -->
            <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
                <h2 style="font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <iconify-icon icon="mdi:brush"></iconify-icon>
                    Personalization
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Application Name</label>
                        <input type="text" name="appName" class="form-input" value="<?php echo htmlspecialchars($config['appName']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Developer Name</label>
                        <input type="text" name="developerName" class="form-input" value="<?php echo htmlspecialchars($config['developerName']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="companyName" class="form-input" value="<?php echo htmlspecialchars($config['companyName']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company URL</label>
                        <input type="text" name="companyUrl" class="form-input" value="<?php echo htmlspecialchars($config['companyUrl']); ?>">
                    </div>
                </div>
            </div>

            <!-- Workspaces -->
            <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
                <h2 style="font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <iconify-icon icon="mdi:folder-network"></iconify-icon>
                    Workspaces
                </h2>
                
                <div style="display: grid; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Web Projects Path (Laragon/www)</label>
                        <input type="text" name="webWorkspace" class="form-input" value="<?php echo htmlspecialchars($config['workspaces']['web']['path']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Platform Projects Path</label>
                        <input type="text" name="platformWorkspace" class="form-input" value="<?php echo htmlspecialchars($config['workspaces']['platform']['path']); ?>">
                    </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 40px;">
                <button type="submit" id="save-btn" class="btn btn-primary" style="padding: 12px 32px;">
                    <iconify-icon icon="mdi:content-save"></iconify-icon>
                    Save Configuration
                </button>
            </div>
        </form>
    </div>
</main>

<script>
// Toggle password visibility
function togglePassword(btn) {
    const input = btn.previousElementSibling;
    const icon = btn.querySelector('iconify-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('icon', 'mdi:eye-off-outline');
    } else {
        input.type = 'password';
        icon.setAttribute('icon', 'mdi:eye-outline');
    }
}

// Save settings via API
async function saveSettings(event) {
    event.preventDefault();
    const btn = document.getElementById('save-btn');
    const originalContent = btn.innerHTML;
    
    // Loading state
    btn.disabled = true;
    btn.innerHTML = 'Saving...';
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('api/settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            btn.style.background = 'var(--success)';
            btn.innerHTML = '<iconify-icon icon="mdi:check-circle"></iconify-icon> Saved!';
            setTimeout(() => {
                btn.disabled = false;
                btn.style.background = '';
                btn.innerHTML = originalContent;
                window.location.reload(); // Reload to apply changes
            }, 1000);
        } else {
            throw new Error(result.error || 'Failed to save settings');
        }
    } catch (e) {
        alert('Error: ' + e.message);
        btn.disabled = false;
        btn.style.background = 'var(--error)';
        btn.innerHTML = '<iconify-icon icon="mdi:alert-circle"></iconify-icon> Error';
        setTimeout(() => {
            btn.style.background = '';
            btn.innerHTML = originalContent;
        }, 3000);
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
