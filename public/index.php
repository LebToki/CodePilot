<?php
/**
 * CodePilot - Main Interface
 */
require_once __DIR__ . '/header.php';

// Get current project from query string or localStorage
$projectPath = $_GET['project'] ?? '';
?>

<style>
.file-tabs::-webkit-scrollbar { height: 4px; }
.file-tab { padding: 8px 16px; border-right: 1px solid var(--border-color); cursor: pointer; font-size: 13px; display: flex; align-items: center; justify-content: space-between; gap: 8px; background: var(--bg-tertiary); color: var(--text-secondary); border-bottom: 2px solid transparent; white-space: nowrap; transition: all 0.2s; min-width: max-content; }
.file-tab:hover { background: var(--bg-secondary); }
.file-tab.active { background: var(--bg-primary); color: var(--accent); border-bottom-color: var(--accent); }
.file-tab .close-btn { opacity: 0.5; border-radius: 4px; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px; transition: all 0.2s; }
.file-tab .close-btn:hover { opacity: 1; background: rgba(255,255,255,0.1); color: var(--error); }
.editor-content { display: flex; flex-direction: column; }
#monaco-editor { flex: 1; height: calc(100% - 36px); min-height: 0; }

.file-item { display: flex; align-items: center; padding: 6px 12px; cursor: pointer; color: var(--text-secondary); }
.file-item:hover { background: var(--bg-tertiary); color: var(--text-primary); }
.file-item .file-actions { margin-left: auto; display: none; gap: 4px; }
.file-item:hover .file-actions { display: flex; }
.file-actions button { background: transparent; border: none; color: inherit; cursor: pointer; padding: 2px; border-radius: 4px; display: flex; align-items: center; justify-content: center; opacity: 0.7; }
.file-actions button:hover { background: rgba(255,255,255,0.1); opacity: 1; }
</style>

<main class="main-content">
    <!-- File Browser Panel -->
    <div class="file-browser" id="file-browser" style="<?php echo empty($projectPath) ? 'display: none;' : ''; ?>">
        <div style="padding: 12px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">Files</span>
            <div style="display: flex; gap: 4px;">
                <button class="btn-icon" style="width: 24px; height: 24px;" onclick="createNewItem('file')" title="New File" aria-label="New file">
                    <iconify-icon icon="mdi:file-plus" style="font-size: 14px;"></iconify-icon>
                </button>
                <button class="btn-icon" style="width: 24px; height: 24px;" onclick="createNewItem('directory')" title="New Folder" aria-label="New folder">
                    <iconify-icon icon="mdi:folder-plus" style="font-size: 14px;"></iconify-icon>
                </button>
                <button class="btn-icon" style="width: 24px; height: 24px;" onclick="toggleFileBrowser()" title="Close" aria-label="Close">
                    <iconify-icon icon="mdi:close" style="font-size: 14px;"></iconify-icon>
                </button>
            </div>
        </div>
        <div style="padding: 8px;">
            <input type="text" id="file-search" class="form-input" aria-label="Search files" placeholder="Search files..." style="width: 100%; font-size: 12px; padding: 6px 10px;" oninput="filterFiles()">
        </div>
        <div class="file-tree" id="file-tree" style="height: calc(100% - 100px); overflow-y: auto;">
            <!-- Files loaded here -->
        </div>
    </div>

    <!-- Chat Panel -->
    <div class="chat-panel">
        <!-- Project Context Banner -->
        <div id="project-banner" style="display: none; padding: 12px 24px; background: var(--bg-tertiary); border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <iconify-icon icon="mdi:folder-open" style="font-size: 20px; color: var(--accent);"></iconify-icon>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;" id="project-name">Project Name</div>
                        <div style="font-size: 12px; color: var(--text-muted); font-family: var(--font-mono);" id="project-path-display"></div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn-icon" style="width: 32px; height: 32px;" onclick="toggleFileBrowser()" title="Toggle Files" aria-label="Toggle files panel">
                        <iconify-icon icon="mdi:file-tree"></iconify-icon>
                    </button>
                    <button class="btn-icon" style="width: 32px; height: 32px;" onclick="closeProject()" title="Close Project" aria-label="Close project">
                        <iconify-icon icon="mdi:close"></iconify-icon>
                    </button>
                </div>
            </div>
        </div>

        <div class="chat-messages" id="chat-messages">
            <div class="chat-empty-state" id="empty-state">
                <iconify-icon icon="mdi:robot-happy-outline"></iconify-icon>
                <h2 class="text-gradient">Welcome to CodePilot</h2>
                <p>Your AI-powered coding assistant</p>
                <p style="margin-top: 16px; font-size: 13px; max-width: 400px;">
                    Ask me to write code, debug issues, explain concepts, or help with any programming task.
                </p>
                <div style="display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap; justify-content: center;">
                    <button class="btn btn-primary" onclick="insertPrompt('Write a Python function to...')">
                        <iconify-icon icon="mdi:language-python"></iconify-icon>
                        Python
                    </button>
                    <button class="btn btn-primary" onclick="insertPrompt('Create a JavaScript function that...')">
                        <iconify-icon icon="mdi:language-javascript"></iconify-icon>
                        JavaScript
                    </button>
                    <button class="btn btn-primary" onclick="insertPrompt('Write a PHP class for...')">
                        <iconify-icon icon="mdi:language-php"></iconify-icon>
                        PHP
                    </button>
                </div>
                <div style="margin-top: 32px;">
                    <a href="projects.php" style="color: var(--accent); text-decoration: none; font-size: 14px;">
                        <iconify-icon icon="mdi:folder-open"></iconify-icon>
                        Open a project to get started
                    </a>
                </div>
            </div>
        </div>
        
        <div class="chat-input-container">
            <div class="chat-input-wrapper">
                <button class="btn-icon" id="add-context-btn" onclick="addFileContext()" title="Add file to context" aria-label="Add file to context" style="display: none;">
                    <iconify-icon icon="mdi:file-plus"></iconify-icon>
                </button>
                <textarea 
                    id="chat-input" aria-label="Chat input"
                    class="chat-input" 
                    placeholder="Ask me anything about code..."
                    rows="1"
                    onkeydown="handleKeyDown(event)"
                ></textarea>
                <button class="btn-icon primary" id="send-btn" onclick="sendMessage()" title="Send" aria-label="Send message">
                    <iconify-icon icon="mdi:send"></iconify-icon>
                </button>
            </div>
            <div style="text-align: center; margin-top: 8px;">
                <small style="color: var(--text-muted); font-size: 11px;">
                    Press Enter to send, Shift+Enter for new line
                </small>
            </div>
        </div>
    </div>
    
    <!-- Editor Panel -->
        <div class="editor-panel" id="editor-panel">
            <div class="editor-header">
                <div class="editor-tabs">
                    <button class="editor-tab active" data-tab="editor">Editor</button>
                    <button class="editor-tab" data-tab="terminal">Terminal</button>
                    <button class="editor-tab" data-tab="tools">AI Tools</button>
                </div>
                <div style="display: flex; gap: 8px;">
                    <select id="language-select" class="model-select" style="min-width: 120px;" aria-label="Select source language">
                        <option value="javascript">JavaScript</option>
                        <option value="python">Python</option>
                        <option value="php">PHP</option>
                        <option value="typescript">TypeScript</option>
                        <option value="html">HTML</option>
                        <option value="css">CSS</option>
                        <option value="json">JSON</option>
                    </select>
                    <button class="btn-icon" onclick="saveFile()" title="Save File" aria-label="Save file">
                        <iconify-icon icon="mdi:content-save"></iconify-icon>
                    </button>
                    <button class="btn-icon" onclick="copyCode()" title="Copy Code" aria-label="Copy code">
                        <iconify-icon icon="mdi:content-copy"></iconify-icon>
                    </button>
                </div>
            </div>
            <div class="editor-content" style="display: flex; flex-direction: column;">
                <div id="file-tabs" class="file-tabs" style="display: flex; overflow-x: auto; background: var(--bg-primary); border-bottom: 1px solid var(--border-color); min-height: 36px;"></div>
                <div id="monaco-editor" class="editor-tab-content active" style="flex: 1;"></div>
                <div id="terminal-content" class="editor-tab-content" style="display: none; padding: 16px;">
                    <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                        <input type="text" id="terminal-command" class="form-input" aria-label="Terminal command" placeholder="Enter command (e.g., npm install, git status, php artisan serve)" style="flex: 1;">
                        <button class="btn btn-primary" onclick="runCommand()" id="terminal-run-btn">
                            <iconify-icon icon="mdi:play"></iconify-icon>
                            Run
                        </button>
                        <button class="btn" onclick="clearTerminal()">
                            <iconify-icon icon="mdi:clear"></iconify-icon>
                            Clear
                        </button>
                    </div>
                    <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                        <button class="btn" onclick="runCommand('npm install')" title="Install dependencies">
                            <iconify-icon icon="mdi:package-variant"></iconify-icon>
                            npm install
                        </button>
                        <button class="btn" onclick="runCommand('npm run dev')" title="Start development server">
                            <iconify-icon icon="mdi:rocket-launch"></iconify-icon>
                            npm run dev
                        </button>
                        <button class="btn" onclick="runCommand('composer install')" title="Install PHP dependencies">
                            <iconify-icon icon="mdi:composer"></iconify-icon>
                            composer install
                        </button>
                        <button class="btn" onclick="runCommand('git status')" title="Git status">
                            <iconify-icon icon="mdi:git"></iconify-icon>
                            git status
                        </button>
                    </div>
                    <div id="terminal-output" style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; font-family: var(--font-mono); font-size: 13px; min-height: 200px; max-height: 400px; overflow-y: auto; white-space: pre-wrap; margin-bottom: 16px;"></div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 600; color: var(--text-secondary);">Running Processes</span>
                        <button class="btn" onclick="loadProcesses()" title="Refresh Processes" style="padding: 4px 8px; font-size: 12px;">
                            <iconify-icon icon="mdi:refresh"></iconify-icon> Refresh
                        </button>
                    </div>
                    <div id="running-processes" style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px; max-height: 200px; overflow-y: auto;">
                        <div style="color: var(--text-muted); font-size: 12px; text-align: center; padding: 8px;">Click refresh to load processes</div>
                    </div>
                </div>
                <div id="tools-content" class="editor-tab-content" style="display: none; padding: 16px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label for="tools-source-lang" style="display: block; margin-bottom: 8px; font-size: 12px; color: var(--text-secondary);">Source Language</label>
                            <select id="tools-source-lang" class="form-input">
                                <option value="javascript">JavaScript</option>
                                <option value="python">Python</option>
                                <option value="php">PHP</option>
                                <option value="typescript">TypeScript</option>
                                <option value="java">Java</option>
                                <option value="csharp">C#</option>
                                <option value="rust">Rust</option>
                                <option value="go">Go</option>
                            </select>
                        </div>
                        <div>
                            <label for="tools-target-lang" style="display: block; margin-bottom: 8px; font-size: 12px; color: var(--text-secondary);">Target Language (for conversion)</label>
                            <select id="tools-target-lang" class="form-input">
                                <option value="javascript">JavaScript</option>
                                <option value="python">Python</option>
                                <option value="php">PHP</option>
                                <option value="typescript">TypeScript</option>
                                <option value="java">Java</option>
                                <option value="csharp">C#</option>
                                <option value="rust">Rust</option>
                                <option value="go">Go</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
                        <button class="btn btn-primary" onclick="analyzeCode('explain-code')" title="Explain Code">
                            <iconify-icon icon="mdi:help-circle"></iconify-icon>
                            Explain
                        </button>
                        <button class="btn" onclick="analyzeCode('optimize-code')" title="Optimize Code">
                            <iconify-icon icon="mdi:rocket"></iconify-icon>
                            Optimize
                        </button>
                        <button class="btn" onclick="analyzeCode('generate-tests')" title="Generate Tests">
                            <iconify-icon icon="mdi:test-tube"></iconify-icon>
                            Tests
                        </button>
                        <button class="btn" onclick="analyzeCode('find-bugs')" title="Find Bugs">
                            <iconify-icon icon="mdi:bug"></iconify-icon>
                            Bugs
                        </button>
                        <button class="btn" onclick="analyzeCode('convert-code')" title="Convert Code">
                            <iconify-icon icon="mdi:swap-horizontal"></iconify-icon>
                            Convert
                        </button>
                    </div>
                    <div id="tools-output" style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; font-family: var(--font-mono); font-size: 13px; min-height: 200px; max-height: 400px; overflow-y: auto; white-space: pre-wrap;"></div>
                </div>
            </div>
        </div>
</main>

<!-- Monaco Editor -->
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js"></script>
<script>
    // Current project state
    let currentProject = localStorage.getItem('codepilot_project') || <?php echo json_encode($projectPath, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    let currentFile = null;
    let openFiles = {}; // Store open file models
    
    // Initialize Monaco Editor
    require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' } });
    require(['vs/editor/editor.main'], function () {
        window.monacoEditor = monaco.editor.create(document.getElementById('monaco-editor'), {
            value: '// Select a file or start coding...\n',
            language: 'javascript',
            theme: 'vs-dark',
            automaticLayout: true,
            fontSize: 14,
            fontFamily: "'JetBrains Mono', 'Fira Code', Consolas, monospace",
            minimap: { enabled: false },
            scrollBeyondLastLine: false,
            padding: { top: 16 },
            lineNumbers: 'on',
            renderLineHighlight: 'all',
            cursorBlinking: 'smooth',
            smoothScrolling: true,
        });
        
        // Language change
        document.getElementById('language-select').addEventListener('change', function() {
            monaco.editor.setModelLanguage(window.monacoEditor.getModel(), this.value);
        });
        
        // Initialize project if set
        if (currentProject) {
            loadProject(currentProject);
        }
    });
    
        // Toggle search panel
    function toggleSearch() {
        const panel = document.getElementById('file-search-panel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        if (panel.style.display === 'block') {
            document.getElementById('file-search-input').focus();
        }
    }

    // Execute file search
    async function executeFileSearch() {
        const query = document.getElementById('file-search-input').value.trim();
        const resultsContainer = document.getElementById('file-search-results');

        if (!query || !currentProject) return;

        resultsContainer.innerHTML = '<div style="color: var(--text-muted); text-align: center; padding: 10px;">Searching...</div>';

        try {
            const response = await fetch(`api/files.php?action=search&path=${encodeURIComponent(currentProject)}&query=${encodeURIComponent(query)}`);
            const data = await response.json();

            if (data.error) throw new Error(data.error);

            if (!data.results || data.results.length === 0) {
                resultsContainer.innerHTML = '<div style="color: var(--text-muted); padding: 5px;">No matches found.</div>';
                return;
            }

            let html = '';
            data.results.forEach(file => {
                html += `
                    <div style="margin-bottom: 8px;">
                        <div style="font-weight: bold; color: var(--accent); cursor: pointer; padding: 2px 0;" onclick="openFileAndGoToLine('${file.path.replace(/\\/g, '/')}')">
                            <iconify-icon icon="mdi:file-document"></iconify-icon> ${file.name}
                        </div>
                `;

                if (file.matches && file.matches.length > 0) {
                    file.matches.forEach(match => {
                        html += `
                            <div style="padding-left: 16px; cursor: pointer; color: var(--text-secondary); padding: 2px 0;" onclick="openFileAndGoToLine('${file.path.replace(/\\/g, '/')}', ${match.line})">
                                <span style="color: var(--text-muted); width: 30px; display: inline-block;">${match.line}</span>
                                <span style="background: var(--bg-tertiary); padding: 0 4px; border-radius: 2px;">${escapeHtml(match.text)}</span>
                            </div>
                        `;
                    });
                }
                html += `</div>`;
            });

            resultsContainer.innerHTML = html;

        } catch (error) {
            resultsContainer.innerHTML = `<div style="color: var(--error); padding: 5px;">${error.message}</div>`;
        }
    }

    // Open file and optionally go to line
    async function openFileAndGoToLine(path, line = null) {
        await openFile(path);

        if (line && window.monacoEditor) {
            setTimeout(() => {
                window.monacoEditor.revealLineInCenter(line);
                window.monacoEditor.setPosition({ lineNumber: line, column: 1 });
            }, 100);
        }
    }

    // Helper to escape HTML for search results
    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }
    // Load project
    function loadProject(path) {
        currentProject = path;
        localStorage.setItem('codepilot_project', path);
        
        // Show UI elements
        document.getElementById('project-banner').style.display = 'block';
        document.getElementById('file-browser').style.display = 'block';
        document.getElementById('add-context-btn').style.display = 'flex';
        
        // Set project name
        const projectName = path.split('/').pop() || path.split('\\').pop();
        document.getElementById('project-name').textContent = projectName;
        document.getElementById('project-path-display').textContent = path;
        
        // Load files
        loadFiles(path);
    }
    
    // Load files
    async function loadFiles(path) {
        const tree = document.getElementById('file-tree');
        tree.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--text-muted);">Loading...</div>';
        
        try {
            const response = await fetch(`api/files.php?action=list&path=${encodeURIComponent(path)}`);
            const data = await response.json();
            
            if (data.error) throw new Error(data.error);
            
            tree.innerHTML = renderFileTree(data.files, path);
            
        } catch (error) {
            tree.innerHTML = `<div style="padding: 20px; text-align: center; color: var(--error);">${error.message}</div>`;
        }
    }
    

    // Filter files in the file browser
    function filterFiles() {
        const query = document.getElementById('file-search').value.toLowerCase();
        const items = document.querySelectorAll('#file-tree .file-item');

        items.forEach(item => {
            const fileName = item.querySelector('span').textContent.toLowerCase();
            if (fileName.includes(query)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Render file tree
    function renderFileTree(files, basePath) {
        return files.map(file => {
            const icon = file.isDir ? 'mdi:folder' : getFileIcon(file.extension);
            const clickHandler = file.isDir 
                ? `loadFiles('${file.path.replace(/\\/g, '/')}')`
                : `openFile('${file.path.replace(/\\/g, '/')}')`;
            
            return `
                <div class="file-item ${currentFile === file.path ? 'selected' : ''}" onclick="${clickHandler}">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <iconify-icon icon="${icon}" style="color: ${file.isDir ? 'var(--accent)' : 'var(--text-secondary)'};"></iconify-icon>
                        <span>${file.name}</span>
                    </div>
                    <div class="file-actions" onclick="event.stopPropagation();">
                        <button onclick="renameFileItem(event, '${file.path.replace(/\\/g, '\\\\')}', '${file.name}')" title="Rename" aria-label="Rename file">
                            <iconify-icon icon="mdi:pencil" style="font-size: 14px;"></iconify-icon>
                        </button>
                        <button onclick="deleteFileItem(event, '${file.path.replace(/\\/g, '\\\\')}')" title="Delete" aria-label="Delete file">
                            <iconify-icon icon="mdi:delete" style="font-size: 14px;"></iconify-icon>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    // Get file icon based on extension
    function getFileIcon(ext) {
        const icons = {
            'js': 'mdi:language-javascript',
            'ts': 'mdi:language-typescript',
            'py': 'mdi:language-python',
            'php': 'mdi:language-php',
            'html': 'mdi:language-html5',
            'css': 'mdi:language-css3',
            'json': 'mdi:code-json',
            'md': 'mdi:language-markdown',
            'txt': 'mdi:file-document',
            'sql': 'mdi:database',
            'sh': 'mdi:console',
            'bat': 'mdi:console',
        };
        return icons[ext] || 'mdi:file';
    }
    
    // Open file in editor
    async function openFile(path) {
        try {
            // Check if already open
            if (openFiles[path]) {
                switchToFile(path);
                return;
            }

            const response = await fetch(`api/files.php?action=read&path=${encodeURIComponent(path)}`);
            const data = await response.json();
            
            if (data.error) throw new Error(data.error);
            
            // Detect language
            const ext = path.split('.').pop().toLowerCase();
            const langMap = {
                'js': 'javascript', 'ts': 'typescript', 'py': 'python',
                'php': 'php', 'html': 'html', 'css': 'css', 'json': 'json',
                'md': 'markdown', 'sql': 'sql', 'sh': 'shell', 'yml': 'yaml', 'yaml': 'yaml'
            };
            const lang = langMap[ext] || 'plaintext';
            
            // Store file content
            openFiles[path] = {
                content: data.content,
                language: lang,
                name: path.split('/').pop() || path.split('\\').pop()
            };

            switchToFile(path);
            renderFileTabs();
            
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }
    

    function switchToFile(path) {
        if (!openFiles[path]) return;

        // Save current file's content before switching
        if (currentFile && openFiles[currentFile]) {
            openFiles[currentFile].content = window.monacoEditor.getValue();
        }

        currentFile = path;
        const file = openFiles[path];

        // Update Monaco Editor value and language
        window.monacoEditor.setValue(file.content);
        monaco.editor.setModelLanguage(window.monacoEditor.getModel(), file.language);

        const langSelect = document.getElementById('language-select');
        if (langSelect) langSelect.value = file.language;

        renderFileTabs();

        // Update file tree selection highlighting
        const tree = document.getElementById('file-tree');
        if (tree) {
            const items = tree.querySelectorAll('.file-item');
            items.forEach(item => item.classList.remove('selected'));

            // Re-render tree to show selection
            loadFiles(currentProject);
        }
    }

    function closeFile(path) {
        if (!openFiles[path]) return;

        // Remove from open files
        delete openFiles[path];

        // If we closed the active file, switch to another one
        if (currentFile === path) {
            const remainingFiles = Object.keys(openFiles);
            if (remainingFiles.length > 0) {
                switchToFile(remainingFiles[remainingFiles.length - 1]);
            } else {
                currentFile = null;
                window.monacoEditor.setValue('// Select a file or start coding...\n');
                renderFileTabs();
                // Update file tree selection
                loadFiles(currentProject);
            }
        } else {
            renderFileTabs();
        }
    }

    function renderFileTabs() {
        const tabsContainer = document.getElementById('file-tabs');
        if (!tabsContainer) return;

        let html = '';
        for (const [path, file] of Object.entries(openFiles)) {
            const isActive = path === currentFile;
            html += `
                <div class="file-tab ${isActive ? 'active' : ''}" onclick="switchToFile('${path.replace(/\\/g, '\\\\')}')">
                    <span style="font-size: 13px;">${file.name}</span>
                    <div class="close-btn" onclick="event.stopPropagation(); closeFile('${path.replace(/\\/g, '\\\\')}')">
                        <iconify-icon icon="mdi:close" style="font-size: 14px;"></iconify-icon>
                    </div>
                </div>
            `;
        }

        tabsContainer.innerHTML = html;
        tabsContainer.style.display = Object.keys(openFiles).length > 0 ? 'flex' : 'none';

        // Ensure monaco editor takes full height if tabs are hidden
        const editorDiv = document.getElementById('monaco-editor');
        if (editorDiv) {
            if (Object.keys(openFiles).length > 0) {
                editorDiv.style.height = 'calc(100% - 36px)';
            } else {
                editorDiv.style.height = '100%';
            }
        }
    }


    // File Operations
    async function createNewItem(type) {
        if (!currentProject) {
            alert('Please open a project first');
            return;
        }

        const name = prompt(`Enter new ${type} name:`);
        if (!name) return;

        const path = currentProject + '/' + name;

        try {
            const response = await fetch('api/files.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path, type })
            });

            const data = await response.json();
            if (data.error) throw new Error(data.error);

            loadFiles(currentProject);
        } catch (error) {
            alert('Error creating ' + type + ': ' + error.message);
        }
    }

    async function renameFileItem(event, oldPath, oldName) {
        event.stopPropagation();

        const newName = prompt('Enter new name:', oldName);
        if (!newName || newName === oldName) return;

        // Extract dir path and append new name
        const lastSlashIndex = Math.max(oldPath.lastIndexOf('/'), oldPath.lastIndexOf('\\'));
        const dirPath = oldPath.substring(0, lastSlashIndex);
        const newPath = dirPath + '/' + newName;

        try {
            const response = await fetch('api/files.php?action=rename', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ oldPath, newPath })
            });

            const data = await response.json();
            if (data.error) throw new Error(data.error);

            // Handle active file rename
            if (openFiles[oldPath]) {
                const fileData = openFiles[oldPath];
                fileData.name = newName;
                openFiles[newPath] = fileData;
                delete openFiles[oldPath];

                if (currentFile === oldPath) {
                    currentFile = newPath;
                }
                renderFileTabs();
            }

            loadFiles(currentProject);
        } catch (error) {
            alert('Error renaming item: ' + error.message);
        }
    }

    async function deleteFileItem(event, path) {
        event.stopPropagation();

        if (!confirm('Are you sure you want to delete this item?')) return;

        try {
            const response = await fetch('api/files.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path })
            });

            const data = await response.json();
            if (data.error) throw new Error(data.error);

            // Close file if it's open
            if (openFiles[path]) {
                closeFile(path);
            }

            loadFiles(currentProject);
        } catch (error) {
            alert('Error deleting item: ' + error.message);
        }
    }

    // Save file
    async function saveFile() {
        if (!currentFile || !openFiles[currentFile]) {
            alert('No file open');
            return;
        }
        
        try {
            // Update stored content
            openFiles[currentFile].content = window.monacoEditor.getValue();

            const response = await fetch('api/files.php?action=write', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    path: currentFile,
                    content: openFiles[currentFile].content
                })
            });
            
            const data = await response.json();
            if (data.error) throw new Error(data.error);
            
            // Visual feedback
            const btn = event.target.closest('button');
            if (btn) {
                const originalBg = btn.style.background;
                btn.style.background = 'var(--success)';
                setTimeout(() => btn.style.background = originalBg, 1000);
            }
            
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }
    
    // Toggle file browser
    function toggleFileBrowser() {
        const fb = document.getElementById('file-browser');
        fb.style.display = fb.style.display === 'none' ? 'block' : 'none';
    }
    
    // Close project
    function closeProject() {
        currentProject = null;
        currentFile = null;
        localStorage.removeItem('codepilot_project');
        
        document.getElementById('project-banner').style.display = 'none';
        document.getElementById('file-browser').style.display = 'none';
        document.getElementById('add-context-btn').style.display = 'none';
        
        window.monacoEditor.setValue('// Select a file or start coding...\n');
    }
    
    // Add file context to chat
    function addFileContext() {
        if (!currentFile) {
            alert('Open a file first');
            return;
        }
        
        const content = window.monacoEditor.getValue();
        const filename = currentFile.split('/').pop();
        const input = document.getElementById('chat-input');
        
        input.value = `Here's my ${filename} file:\n\`\`\`\n${content}\n\`\`\`\n\n`;
        input.focus();
    }
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
