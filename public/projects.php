<?php
/**
 * CodePilot Projects Manager
 */
require_once __DIR__ . '/header.php';
?>

<main class="main-content">
    <div style="padding: 32px; width: 100%; overflow-y: auto;">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <div>
                <h1 class="text-gradient" style="font-size: 28px; margin-bottom: 8px;">Projects</h1>
                <p style="color: var(--text-secondary);">Manage your coding projects across workspaces</p>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <iconify-icon icon="mdi:plus"></iconify-icon>
                New Project
            </button>
        </div>
        
        <!-- Workspace Tabs -->
        <div style="display: flex; gap: 8px; margin-bottom: 24px;">
            <button class="workspace-tab active" data-workspace="web" onclick="switchWorkspace('web')">
                🌐 Web Projects
            </button>
            <button class="workspace-tab" data-workspace="platform" onclick="switchWorkspace('platform')">
                🐍 Platform Projects
            </button>
        </div>
        
        <!-- Projects Grid -->
        <div class="projects-grid" id="projects-grid">
            <div class="loading-state">
                <div class="loading-dots"><span></span><span></span><span></span></div>
                <p>Loading projects...</p>
            </div>
        </div>
    </div>
</main>

<!-- Create Project Modal -->
<div class="modal" id="create-modal" style="display: none;">
    <div class="glass-card" style="width: 100%; max-width: 500px; padding: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 class="text-gradient">New Project</h2>
            <button class="btn-icon" onclick="closeCreateModal()">
                <iconify-icon icon="mdi:close"></iconify-icon>
            </button>
        </div>
        
        <form id="create-form" onsubmit="createProject(event)">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Project Name</label>
                <input type="text" id="project-name" class="form-input" placeholder="my-awesome-project" required pattern="[a-zA-Z0-9_-]+" title="Only letters, numbers, dashes, and underscores">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Workspace</label>
                <select id="project-workspace" class="form-input">
                    <option value="web">🌐 Web Projects (C:/laragon/www)</option>
                    <option value="platform">🐍 Platform Projects (E:/platform)</option>
                </select>
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Template</label>
                <div class="template-grid" style="grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));">
                    <label class="template-option">
                        <input type="radio" name="template" value="empty" checked>
                        <div class="template-card">
                            <iconify-icon icon="mdi:folder-outline" style="font-size: 28px;"></iconify-icon>
                            <span>Empty</span>
                        </div>
                    </label>
                    <label class="template-option">
                        <input type="radio" name="template" value="php">
                        <div class="template-card">
                            <span style="font-size: 24px;">🐘</span>
                            <span>PHP</span>
                        </div>
                    </label>
                    <label class="template-option">
                        <input type="radio" name="template" value="python">
                        <div class="template-card">
                            <span style="font-size: 24px;">🐍</span>
                            <span>Python</span>
                        </div>
                    </label>
                    <label class="template-option">
                        <input type="radio" name="template" value="nodejs">
                        <div class="template-card">
                            <span style="font-size: 24px;">📦</span>
                            <span>Node.js</span>
                        </div>
                    </label>
                    <label class="template-option">
                        <input type="radio" name="template" value="html">
                        <div class="template-card">
                            <span style="font-size: 24px;">🌐</span>
                            <span>HTML</span>
                        </div>
                    </label>
                    <label class="template-option">
                        <input type="radio" name="template" value="flask">
                        <div class="template-card">
                            <span style="font-size: 24px;">🧪</span>
                            <span>Flask</span>
                        </div>
                    </label>
                    <label class="template-option">
                        <input type="radio" name="template" value="express">
                        <div class="template-card">
                            <span style="font-size: 24px;">⚡</span>
                            <span>Express</span>
                        </div>
                    </label>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn" style="background: var(--bg-tertiary);" onclick="closeCreateModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Project</button>
            </div>
        </form>
    </div>
</div>

<style>
.workspace-tab {
    padding: 12px 20px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    color: var(--text-secondary);
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.workspace-tab:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

.workspace-tab.active {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
}

.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.project-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    padding: 24px;
    cursor: pointer;
    transition: all 0.2s;
}

.project-card:hover {
    border-color: var(--accent);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.project-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 16px;
}

.project-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-tertiary);
    border-radius: 12px;
    font-size: 24px;
}

.project-info {
    flex: 1;
    min-width: 0;
}

.project-name {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.project-type {
    font-size: 13px;
    color: var(--text-secondary);
}

.project-path {
    font-size: 12px;
    color: var(--text-muted);
    font-family: var(--font-mono);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.project-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border-color);
}

.project-date {
    font-size: 12px;
    color: var(--text-muted);
}

.project-actions {
    display: flex;
    gap: 8px;
}

.loading-state {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 48px;
    color: var(--text-secondary);
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    color: var(--text-primary);
    font-size: 14px;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-glow);
}

.template-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
}

.template-option input {
    display: none;
}

.template-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 12px;
    background: var(--bg-tertiary);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 13px;
}

.template-option input:checked + .template-card {
    border-color: var(--accent);
    background: rgba(88, 166, 255, 0.1);
}

.template-card:hover {
    border-color: var(--accent);
}

.modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}

.empty-state {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 64px;
    color: var(--text-secondary);
    text-align: center;
}

.empty-state iconify-icon {
    font-size: 64px;
    opacity: 0.5;
}
</style>

<script>
let currentWorkspace = 'web';

document.addEventListener('DOMContentLoaded', () => {
    loadProjects();
});

function switchWorkspace(workspace) {
    currentWorkspace = workspace;
    
    // Update tabs
    document.querySelectorAll('.workspace-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.workspace === workspace);
    });
    
    loadProjects();
}

async function loadProjects() {
    const grid = document.getElementById('projects-grid');
    grid.innerHTML = '<div class="loading-state"><div class="loading-dots"><span></span><span></span><span></span></div><p>Loading projects...</p></div>';
    
    try {
        const response = await fetch(`api/projects.php?action=list&workspace=${currentWorkspace}`);
        const data = await response.json();
        
        if (data.error) throw new Error(data.error);
        
        if (data.projects.length === 0) {
            grid.innerHTML = `
                <div class="empty-state">
                    <iconify-icon icon="mdi:folder-plus-outline"></iconify-icon>
                    <h3>No projects yet</h3>
                    <p>Create your first project to get started</p>
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <iconify-icon icon="mdi:plus"></iconify-icon>
                        Create Project
                    </button>
                </div>
            `;
            return;
        }
        
        grid.innerHTML = data.projects.map(project => `
            <div class="project-card" onclick="openProject('${project.path.replace(/\\/g, '\\\\')}')">
                <div class="project-header">
                    <div class="project-icon">${project.type.icon}</div>
                    <div class="project-info">
                        <div class="project-name">${project.name}</div>
                        <div class="project-type">${project.type.label}</div>
                    </div>
                </div>
                <div class="project-path">${project.path}</div>
                <div class="project-meta">
                    <span class="project-date">Modified: ${project.modified}</span>
                    <div class="project-actions">
                        <button class="btn-icon" style="width: 32px; height: 32px;" onclick="event.stopPropagation(); deleteProject('${project.path.replace(/\\/g, '\\\\')}')" title="Delete">
                            <iconify-icon icon="mdi:delete-outline"></iconify-icon>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
        
    } catch (error) {
        grid.innerHTML = `
            <div class="empty-state">
                <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
                <h3>Error loading projects</h3>
                <p>${error.message}</p>
            </div>
        `;
    }
}

function openProject(path) {
    // Store current project and redirect to chat
    localStorage.setItem('codepilot_project', path);
    window.location.href = 'index.php?project=' + encodeURIComponent(path);
}

function openCreateModal() {
    document.getElementById('create-modal').style.display = 'flex';
    document.getElementById('project-workspace').value = currentWorkspace;
    document.getElementById('project-name').focus();
}

function closeCreateModal() {
    document.getElementById('create-modal').style.display = 'none';
    document.getElementById('create-form').reset();
}

async function createProject(event) {
    event.preventDefault();
    
    const name = document.getElementById('project-name').value;
    const workspace = document.getElementById('project-workspace').value;
    const template = document.querySelector('input[name="template"]:checked').value;
    
    try {
        const response = await fetch('api/projects.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, workspace, template }),
        });
        
        const data = await response.json();
        
        if (data.error) throw new Error(data.error);
        
        closeCreateModal();
        currentWorkspace = workspace;
        
        // Update tabs
        document.querySelectorAll('.workspace-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.workspace === workspace);
        });
        
        loadProjects();
        
        // Open the new project
        setTimeout(() => openProject(data.path), 500);
        
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function deleteProject(path) {
    if (!confirm('Delete this project? This cannot be undone.')) return;
    
    try {
        const response = await fetch('api/projects.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ path }),
        });
        
        const data = await response.json();
        
        if (data.error) throw new Error(data.error);
        
        loadProjects();
        
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
