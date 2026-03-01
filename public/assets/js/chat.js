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
    if (typeof Prism !== 'undefined' && Prism.highlightAllUnder) {
        try {
            Prism.highlightAllUnder(messageDiv);
        } catch (e) {
            console.warn('Prism highlighting error:', e);
        }
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

// Terminal functionality
function runCommand(command = null) {
    const terminalOutput = document.getElementById('terminal-output');
    const terminalCommand = document.getElementById('terminal-command');
    const runBtn = document.getElementById('terminal-run-btn');
    
    const cmd = command || terminalCommand.value.trim();
    if (!cmd) {
        alert('Please enter a command');
        return;
    }
    
    // Add command to output
    terminalOutput.textContent += `$ ${cmd}\n`;
    
    // Disable button and show loading
    runBtn.disabled = true;
    runBtn.innerHTML = '<iconify-icon icon="mdi:loading" class="spin"></iconify-icon> Running...';
    
    // Execute command
    fetch('api/terminal.php?action=execute', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            command: cmd,
            projectPath: currentProject || '',
            timeout: 120
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            terminalOutput.textContent += `Error: ${data.error}\n\n`;
        } else {
            terminalOutput.textContent += `${data.output}\n`;
            if (data.returnCode !== 0) {
                terminalOutput.textContent += `[Exit code: ${data.returnCode}]\n`;
            }
        }
        terminalOutput.scrollTop = terminalOutput.scrollHeight;
    })
    .catch(error => {
        terminalOutput.textContent += `Network error: ${error.message}\n\n`;
        terminalOutput.scrollTop = terminalOutput.scrollHeight;
    })
    .finally(() => {
        // Re-enable button
        runBtn.disabled = false;
        runBtn.innerHTML = '<iconify-icon icon="mdi:play"></iconify-icon> Run';
        terminalCommand.value = '';
    });
}

function clearTerminal() {
    document.getElementById('terminal-output').textContent = '';
}

// Editor tab switching
document.querySelectorAll('.editor-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        // Remove active class from all tabs
        document.querySelectorAll('.editor-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.editor-tab-content').forEach(c => c.style.display = 'none');
        
        // Add active class to clicked tab
        tab.classList.add('active');
        
        // Show corresponding content
        const tabName = tab.dataset.tab;
        if (tabName === 'editor') {
            document.getElementById('monaco-editor').style.display = 'block';
        } else if (tabName === 'terminal') {
            document.getElementById('terminal-content').style.display = 'block';
        } else if (tabName === 'tools') {
            document.getElementById('tools-content').style.display = 'block';
        }
    });
});

// AI Tools functionality
async function analyzeCode(action) {
    const toolsOutput = document.getElementById('tools-output');
    const sourceLang = document.getElementById('tools-source-lang').value;
    const targetLang = document.getElementById('tools-target-lang').value;
    const code = window.monacoEditor.getValue();
    
    if (!code) {
        alert('Please open a file first');
        return;
    }
    
    // Show loading
    toolsOutput.textContent = 'Analyzing code...\n';
    
    try {
        const payload = {
            action: action,
            code: code,
            language: sourceLang
        };
        
        // Add target language for conversion
        if (action === 'convert-code') {
            payload.targetLanguage = targetLang;
        }
        
        const response = await fetch('api/ai-tools.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.error) {
            toolsOutput.textContent = `Error: ${data.error}\n`;
        } else {
            let result = '';
            switch (action) {
                case 'explain-code':
                    result = `## Code Explanation\n\n${data.explanation}`;
                    break;
                case 'optimize-code':
                    result = `## Optimized Code\n\n${data.optimized}`;
                    break;
                case 'generate-tests':
                    result = `## Generated Tests\n\n${data.tests}`;
                    break;
                case 'find-bugs':
                    result = `## Bug Analysis\n\n${data.bugs}`;
                    break;
                case 'convert-code':
                    result = `## Converted Code (${sourceLang} → ${targetLang})\n\n${data.converted}`;
                    break;
            }
            toolsOutput.textContent = result;
        }
        
        // Highlight code if available
        if (typeof Prism !== 'undefined' && Prism.highlightAllUnder) {
            try {
                Prism.highlightAllUnder(toolsOutput);
            } catch (e) {
                console.warn('Prism highlighting error:', e);
            }
        }
        
    } catch (error) {
        toolsOutput.textContent = `Network error: ${error.message}\n`;
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
