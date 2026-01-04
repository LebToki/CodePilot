/**
 * CodePilot Chat JavaScript
 */

// State
let currentProvider = localStorage.getItem('codepilot_provider') || 'deepseek';
let currentModel = localStorage.getItem('codepilot_model') || 'deepseek-chat';
let messages = [];
let isLoading = false;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initProvider();
    loadModels();
    autoResizeTextarea();
    
    // Set provider dropdown
    document.getElementById('provider-select').value = currentProvider;
});

// Provider change
function onProviderChange() {
    currentProvider = document.getElementById('provider-select').value;
    localStorage.setItem('codepilot_provider', currentProvider);
    loadModels();
}

// Load models for current provider
async function loadModels() {
    const modelSelect = document.getElementById('model-select');
    modelSelect.innerHTML = '<option value="">Loading...</option>';
    
    try {
        const response = await fetch(`api/providers.php?action=models&provider=${currentProvider}`);
        const data = await response.json();
        
        modelSelect.innerHTML = '';
        data.models.forEach(model => {
            const option = document.createElement('option');
            option.value = model.id;
            option.textContent = model.name;
            modelSelect.appendChild(option);
        });
        
        // Set saved model if exists
        if (currentProvider === localStorage.getItem('codepilot_provider')) {
            const savedModel = localStorage.getItem('codepilot_model');
            if (savedModel && modelSelect.querySelector(`option[value="${savedModel}"]`)) {
                modelSelect.value = savedModel;
            }
        }
        
        currentModel = modelSelect.value;
        
        modelSelect.onchange = () => {
            currentModel = modelSelect.value;
            localStorage.setItem('codepilot_model', currentModel);
        };
        
    } catch (error) {
        console.error('Failed to load models:', error);
        modelSelect.innerHTML = '<option value="">Error loading models</option>';
    }
}

// Initialize provider
function initProvider() {
    const savedProvider = localStorage.getItem('codepilot_provider');
    if (savedProvider) {
        currentProvider = savedProvider;
    }
}

// Handle keyboard
function handleKeyDown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

// Auto-resize textarea
function autoResizeTextarea() {
    const textarea = document.getElementById('chat-input');
    textarea.addEventListener('input', () => {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
    });
}

// Send message
async function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (!message || isLoading) return;
    
    // Hide empty state
    document.getElementById('empty-state').style.display = 'none';
    
    // Add user message
    addMessage('user', message);
    messages.push({ role: 'user', content: message });
    
    // Clear input
    input.value = '';
    input.style.height = 'auto';
    
    // Show loading
    isLoading = true;
    const loadingId = addLoadingMessage();
    
    try {
        const response = await fetch('api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                provider: currentProvider,
                model: currentModel,
                messages: messages,
            }),
        });
        
        const data = await response.json();
        
        // Remove loading
        removeLoadingMessage(loadingId);
        
        if (data.error) {
            addMessage('assistant', `❌ Error: ${data.error}`, true);
        } else {
            addMessage('assistant', data.response);
            messages.push({ role: 'assistant', content: data.response });
        }
        
    } catch (error) {
        removeLoadingMessage(loadingId);
        addMessage('assistant', `❌ Network error: ${error.message}`, true);
    }
    
    isLoading = false;
}

// Add message to chat
function addMessage(role, content, isError = false) {
    const container = document.getElementById('chat-messages');
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message';
    
    const avatarIcon = role === 'user' ? 'mdi:account' : 'mdi:robot';
    
    // Parse markdown
    let htmlContent = content;
    if (typeof marked !== 'undefined') {
        marked.setOptions({
            highlight: function(code, lang) {
                if (typeof Prism !== 'undefined' && Prism.languages[lang]) {
                    return Prism.highlight(code, Prism.languages[lang], lang);
                }
                return code;
            }
        });
        htmlContent = marked.parse(content);
    }
    
    messageDiv.innerHTML = `
        <div class="message-avatar ${role}">
            <iconify-icon icon="${avatarIcon}" style="font-size: 24px; color: white;"></iconify-icon>
        </div>
        <div class="message-content">
            <div class="message-role">${role === 'user' ? 'You' : 'CodePilot'}</div>
            <div class="message-text ${isError ? 'error' : ''}">${htmlContent}</div>
        </div>
    `;
    
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
    
    // Highlight code blocks
    if (typeof Prism !== 'undefined') {
        Prism.highlightAllUnder(messageDiv);
    }
}

// Loading message
function addLoadingMessage() {
    const container = document.getElementById('chat-messages');
    const id = 'loading-' + Date.now();
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message';
    messageDiv.id = id;
    
    messageDiv.innerHTML = `
        <div class="message-avatar assistant">
            <iconify-icon icon="mdi:robot" style="font-size: 24px; color: white;"></iconify-icon>
        </div>
        <div class="message-content">
            <div class="message-role">CodePilot</div>
            <div class="message-text">
                <div class="loading-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
    
    return id;
}

function removeLoadingMessage(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

// Clear chat
function clearChat() {
    if (confirm('Clear all messages?')) {
        messages = [];
        document.getElementById('chat-messages').innerHTML = `
            <div class="chat-empty-state" id="empty-state">
                <iconify-icon icon="mdi:robot-happy-outline"></iconify-icon>
                <h2 class="text-gradient">Welcome to CodePilot</h2>
                <p>Your AI-powered coding assistant</p>
            </div>
        `;
    }
}

// Toggle editor panel
function toggleEditor() {
    const panel = document.getElementById('editor-panel');
    panel.style.display = panel.style.display === 'none' ? 'flex' : 'none';
}

// Insert prompt
function insertPrompt(text) {
    const input = document.getElementById('chat-input');
    input.value = text;
    input.focus();
    document.getElementById('empty-state').style.display = 'none';
}

// Copy code from editor
function copyCode() {
    if (window.monacoEditor) {
        const code = window.monacoEditor.getValue();
        navigator.clipboard.writeText(code);
        // Could add a toast notification here
    }
}

// Sessions (placeholder for now)
function createNewSession() {
    messages = [];
    document.getElementById('chat-messages').innerHTML = `
        <div class="chat-empty-state" id="empty-state">
            <iconify-icon icon="mdi:robot-happy-outline"></iconify-icon>
            <h2 class="text-gradient">Welcome to CodePilot</h2>
            <p>Your AI-powered coding assistant</p>
        </div>
    `;
}
