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
        
        <!-- Providers Status -->
        <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
            <h2 style="font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <iconify-icon icon="mdi:cloud-check"></iconify-icon>
                Provider Status
            </h2>
            
            <div style="display: grid; gap: 12px;">
                <!-- Ollama -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; background: var(--bg-tertiary); border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 24px;">🦙</span>
                        <div>
                            <div style="font-weight: 600;">Ollama (Local)</div>
                            <div style="font-size: 13px; color: var(--text-secondary);">localhost:11434</div>
                        </div>
                    </div>
                    <div id="ollama-status" style="display: flex; align-items: center; gap: 8px;">
                        <span class="loading-dots"><span></span><span></span><span></span></span>
                    </div>
                </div>
                
                <!-- DeepSeek -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; background: var(--bg-tertiary); border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 24px;">🔮</span>
                        <div>
                            <div style="font-weight: 600;">DeepSeek</div>
                            <div style="font-size: 13px; color: var(--text-secondary);">api.deepseek.com</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <?php if (!empty($config['deepseek']['apiKey'])): ?>
                            <iconify-icon icon="mdi:check-circle" style="color: var(--success); font-size: 20px;"></iconify-icon>
                            <span style="color: var(--success); font-size: 13px;">Configured</span>
                        <?php else: ?>
                            <iconify-icon icon="mdi:alert-circle" style="color: var(--warning); font-size: 20px;"></iconify-icon>
                            <span style="color: var(--warning); font-size: 13px;">Not configured</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Gemini -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; background: var(--bg-tertiary); border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 24px;">✨</span>
                        <div>
                            <div style="font-weight: 600;">Google Gemini</div>
                            <div style="font-size: 13px; color: var(--text-secondary);">generativelanguage.googleapis.com</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <?php if (!empty($config['gemini']['apiKey'])): ?>
                            <iconify-icon icon="mdi:check-circle" style="color: var(--success); font-size: 20px;"></iconify-icon>
                            <span style="color: var(--success); font-size: 13px;">Configured</span>
                        <?php else: ?>
                            <iconify-icon icon="mdi:alert-circle" style="color: var(--warning); font-size: 20px;"></iconify-icon>
                            <span style="color: var(--warning); font-size: 13px;">Not configured</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- HuggingFace -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; background: var(--bg-tertiary); border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 24px;">🤗</span>
                        <div>
                            <div style="font-weight: 600;">HuggingFace</div>
                            <div style="font-size: 13px; color: var(--text-secondary);">api-inference.huggingface.co</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <?php if (!empty($config['huggingface']['apiKey'])): ?>
                            <iconify-icon icon="mdi:check-circle" style="color: var(--success); font-size: 20px;"></iconify-icon>
                            <span style="color: var(--success); font-size: 13px;">Configured</span>
                        <?php else: ?>
                            <iconify-icon icon="mdi:alert-circle" style="color: var(--warning); font-size: 20px;"></iconify-icon>
                            <span style="color: var(--warning); font-size: 13px;">Not configured</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Configuration -->
        <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
            <h2 style="font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <iconify-icon icon="mdi:cog"></iconify-icon>
                Configuration
            </h2>
            
            <p style="color: var(--text-secondary); margin-bottom: 16px; font-size: 14px;">
                API keys are configured in the <code style="background: var(--bg-tertiary); padding: 2px 8px; border-radius: 4px;">.env</code> file.
            </p>
            
            <div style="background: var(--bg-tertiary); border-radius: 10px; padding: 16px; font-family: var(--font-mono); font-size: 13px; overflow-x: auto;">
                <pre style="margin: 0; color: var(--text-primary);"># Location: C:\laragon\www\opencode\.env

DEEPSEEK_API_KEY=your_key_here
GEMINI_API_KEY=your_key_here
HUGGINGFACE_API_KEY=your_key_here</pre>
            </div>
        </div>
        
        <!-- About -->
        <div class="glass-card" style="padding: 24px;">
            <h2 style="font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <iconify-icon icon="mdi:information"></iconify-icon>
                About CodePilot
            </h2>
            
            <p style="color: var(--text-secondary); line-height: 1.7; font-size: 14px;">
                CodePilot is a web-based AI coding assistant that supports multiple AI providers.
                Use local models via Ollama or cloud providers like DeepSeek, Gemini, and HuggingFace.
            </p>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <small style="color: var(--text-muted);">
                    Made with ❤️ by <?php echo htmlspecialchars($config['companyName']); ?>
                </small>
            </div>
        </div>
    </div>
</main>

<script>
// Check Ollama status
async function checkOllamaStatus() {
    const statusEl = document.getElementById('ollama-status');
    try {
        const response = await fetch('api/providers.php?action=models&provider=ollama');
        const data = await response.json();
        
        if (data.models && data.models.length > 0 && data.models[0].id !== 'error') {
            statusEl.innerHTML = `
                <iconify-icon icon="mdi:check-circle" style="color: var(--success); font-size: 20px;"></iconify-icon>
                <span style="color: var(--success); font-size: 13px;">${data.models.length} models</span>
            `;
        } else {
            statusEl.innerHTML = `
                <iconify-icon icon="mdi:alert-circle" style="color: var(--error); font-size: 20px;"></iconify-icon>
                <span style="color: var(--error); font-size: 13px;">Not running</span>
            `;
        }
    } catch (e) {
        statusEl.innerHTML = `
            <iconify-icon icon="mdi:alert-circle" style="color: var(--error); font-size: 20px;"></iconify-icon>
            <span style="color: var(--error); font-size: 13px;">Error</span>
        `;
    }
}

document.addEventListener('DOMContentLoaded', checkOllamaStatus);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
