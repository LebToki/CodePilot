<?php
/**
 * CodePilot Studio - Image Generation & Analysis
 */
require_once __DIR__ . '/header.php';
?>

<main class="main-content">
    <div style="padding: 32px; width: 100%; overflow-y: auto;">
        <!-- Header -->
        <div style="margin-bottom: 32px;">
            <h1 class="text-gradient" style="font-size: 28px; margin-bottom: 8px;">Studio</h1>
            <p style="color: var(--text-secondary);">Generate images with AI or analyze existing ones</p>
        </div>
        
        <!-- Tabs -->
        <div style="display: flex; gap: 8px; margin-bottom: 24px;">
            <button class="workspace-tab active" data-tab="generate" onclick="switchTab('generate')">
                🎨 Generate
            </button>
            <button class="workspace-tab" data-tab="analyze" onclick="switchTab('analyze')">
                🔍 Analyze
            </button>
            <button class="workspace-tab" data-tab="gallery" onclick="switchTab('gallery')">
                🖼️ Gallery
            </button>
        </div>
        
        <!-- Generate Tab -->
        <div class="tab-content active" id="tab-generate">
            <div class="glass-card" style="padding: 24px; max-width: 800px;">
                <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <iconify-icon icon="mdi:image-plus"></iconify-icon>
                    Text to Image
                </h3>
                
                <div style="margin-bottom: 20px;">
                    <label for="gen-prompt" style="display: block; margin-bottom: 8px; font-weight: 500;">Prompt</label>
                    <textarea id="gen-prompt" class="form-input" rows="3" placeholder="Describe the image you want to create..."></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div>
                        <label for="gen-aspect" style="display: block; margin-bottom: 8px; font-weight: 500;">Aspect Ratio</label>
                        <select id="gen-aspect" class="form-input">
                            <option value="1:1">1:1 (Square)</option>
                            <option value="16:9">16:9 (Landscape)</option>
                            <option value="9:16">9:16 (Portrait)</option>
                            <option value="4:3">4:3 (Standard)</option>
                            <option value="3:4">3:4 (Tall)</option>
                        </select>
                    </div>
                    <div>
                        <label for="gen-style" style="display: block; margin-bottom: 8px; font-weight: 500;">Style</label>
                        <select id="gen-style" class="form-input">
                            <option value="">None (Natural)</option>
                            <option value="photorealistic">Photorealistic</option>
                            <option value="digital art">Digital Art</option>
                            <option value="3d render">3D Render</option>
                            <option value="illustration">Illustration</option>
                            <option value="anime">Anime</option>
                            <option value="watercolor painting">Watercolor</option>
                            <option value="oil painting">Oil Painting</option>
                            <option value="pixel art">Pixel Art</option>
                            <option value="minimalist">Minimalist</option>
                        </select>
                    </div>
                </div>
                
                <button class="btn btn-primary" onclick="generateImage()" id="gen-btn" style="width: 100%;">
                    <iconify-icon icon="mdi:creation"></iconify-icon>
                    Generate Image
                </button>
                
                <!-- Result -->
                <div id="gen-result" style="margin-top: 24px; display: none;">
                    <div style="border-radius: 12px; overflow: hidden; background: var(--bg-tertiary);">
                        <img id="gen-image" style="width: 100%; display: block;" />
                    </div>
                    <div style="display: flex; gap: 12px; margin-top: 16px;">
                        <button class="btn" style="flex: 1;" onclick="downloadImage()">
                            <iconify-icon icon="mdi:download"></iconify-icon>
                            Download
                        </button>
                        <button class="btn" style="flex: 1;" onclick="copyImageToChat()">
                            <iconify-icon icon="mdi:chat-plus"></iconify-icon>
                            Use in Chat
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Analyze Tab -->
        <div class="tab-content" id="tab-analyze" style="display: none;">
            <div class="glass-card" style="padding: 24px; max-width: 800px;">
                <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <iconify-icon icon="mdi:image-search"></iconify-icon>
                    Image Analysis
                </h3>
                
                <div id="drop-zone" style="border: 2px dashed var(--border-color); border-radius: 12px; padding: 48px; text-align: center; margin-bottom: 20px; cursor: pointer; transition: all 0.2s;">
                    <iconify-icon icon="mdi:cloud-upload" style="font-size: 48px; color: var(--text-muted); display: block; margin-bottom: 16px;"></iconify-icon>
                    <p style="color: var(--text-secondary); margin-bottom: 8px;">Drop an image here or click to upload</p>
                    <p style="color: var(--text-muted); font-size: 13px;">PNG, JPG, GIF, WebP supported</p>
                    <input type="file" id="image-input" accept="image/*" style="display: none;" />
                </div>
                
                <!-- Preview -->
                <div id="analyze-preview" style="display: none; margin-bottom: 20px;">
                    <div style="border-radius: 12px; overflow: hidden; background: var(--bg-tertiary); max-height: 300px; display: flex; align-items: center; justify-content: center;">
                        <img id="preview-image" style="max-width: 100%; max-height: 300px;" />
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label for="analyze-prompt" style="display: block; margin-bottom: 8px; font-weight: 500;">Question (optional)</label>
                    <input type="text" id="analyze-prompt" class="form-input" placeholder="What would you like to know about this image?" value="Analyze this image in detail. If it's code, explain it and identify any issues." />
                </div>
                
                <button class="btn btn-primary" onclick="analyzeImage()" id="analyze-btn" style="width: 100%;" disabled>
                    <iconify-icon icon="mdi:magnify"></iconify-icon>
                    Analyze Image
                </button>
                
                <!-- Result -->
                <div id="analyze-result" style="margin-top: 24px; display: none;">
                    <div style="padding: 20px; background: var(--bg-tertiary); border-radius: 12px;">
                        <div id="analyze-text" style="white-space: pre-wrap; line-height: 1.7;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gallery Tab -->
        <div class="tab-content" id="tab-gallery" style="display: none;">
            <div id="gallery-grid" class="gallery-grid">
                <div style="grid-column: 1 / -1; text-align: center; padding: 48px; color: var(--text-muted);">
                    <iconify-icon icon="mdi:image-multiple" style="font-size: 48px; opacity: 0.5;"></iconify-icon>
                    <p style="margin-top: 16px;">Generated images will appear here</p>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.tab-content {
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

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

textarea.form-input {
    resize: vertical;
    min-height: 80px;
}

#drop-zone.dragover {
    border-color: var(--accent);
    background: rgba(88, 166, 255, 0.1);
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.gallery-item {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    background: var(--bg-tertiary);
    cursor: pointer;
    transition: transform 0.2s;
}

.gallery-item:hover {
    transform: scale(1.02);
}

.gallery-item img {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
    display: block;
}

.gallery-item-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 12px;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    color: white;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.2s;
}

.gallery-item:hover .gallery-item-overlay {
    opacity: 1;
}
</style>

<script>
let currentImageData = null;
let uploadedImageData = null;

// Tab switching
function switchTab(tab) {
    document.querySelectorAll('.workspace-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.tab === tab);
    });
    
    document.querySelectorAll('.tab-content').forEach(c => {
        c.style.display = 'none';
    });
    
    document.getElementById('tab-' + tab).style.display = 'block';
    
    if (tab === 'gallery') {
        loadGallery();
    }
}

// Generate image
async function generateImage() {
    const prompt = document.getElementById('gen-prompt').value.trim();
    if (!prompt) {
        alert('Please enter a prompt');
        return;
    }
    
    const btn = document.getElementById('gen-btn');
    const result = document.getElementById('gen-result');
    
    btn.disabled = true;
    btn.innerHTML = '<iconify-icon icon="mdi:loading" class="spin"></iconify-icon> Generating...';
    result.style.display = 'none';
    
    try {
        const response = await fetch('api/imagen.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                prompt: prompt,
                aspectRatio: document.getElementById('gen-aspect').value,
                style: document.getElementById('gen-style').value,
            })
        });
        
        const data = await response.json();
        
        if (data.error) throw new Error(data.error);
        
        currentImageData = data.image;
        document.getElementById('gen-image').src = data.image;
        result.style.display = 'block';
        
        // Refresh gallery
        loadGallery();
        
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<iconify-icon icon="mdi:creation"></iconify-icon> Generate Image';
    }
}

// Download image
function downloadImage() {
    if (!currentImageData) return;
    
    const link = document.createElement('a');
    link.href = currentImageData;
    link.download = 'codepilot_' + Date.now() + '.png';
    link.click();
}

// Copy to chat
function copyImageToChat() {
    if (!currentImageData) return;
    localStorage.setItem('codepilot_pending_image', currentImageData);
    window.location.href = 'index.php';
}

// Drop zone
const dropZone = document.getElementById('drop-zone');
const imageInput = document.getElementById('image-input');

dropZone.addEventListener('click', () => imageInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        handleImageUpload(file);
    }
});

imageInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        handleImageUpload(file);
    }
});

function handleImageUpload(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        uploadedImageData = e.target.result;
        document.getElementById('preview-image').src = uploadedImageData;
        document.getElementById('analyze-preview').style.display = 'block';
        document.getElementById('analyze-btn').disabled = false;
    };
    reader.readAsDataURL(file);
}

// Analyze image
async function analyzeImage() {
    if (!uploadedImageData) {
        alert('Please upload an image first');
        return;
    }
    
    const btn = document.getElementById('analyze-btn');
    const result = document.getElementById('analyze-result');
    
    btn.disabled = true;
    btn.innerHTML = '<iconify-icon icon="mdi:loading" class="spin"></iconify-icon> Analyzing...';
    result.style.display = 'none';
    
    try {
        const response = await fetch('api/vision.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                image: uploadedImageData,
                prompt: document.getElementById('analyze-prompt').value,
            })
        });
        
        const data = await response.json();
        
        if (data.error) throw new Error(data.error);
        
        document.getElementById('analyze-text').textContent = data.response;
        result.style.display = 'block';
        
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<iconify-icon icon="mdi:magnify"></iconify-icon> Analyze Image';
    }
}

// Load gallery
async function loadGallery() {
    const grid = document.getElementById('gallery-grid');
    
    try {
        const response = await fetch('api/files.php?action=list&path=' + encodeURIComponent('<?php echo str_replace('\\', '/', dirname(__DIR__)); ?>/generated'));
        const data = await response.json();
        
        if (data.error || !data.files || data.files.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 48px; color: var(--text-muted);">
                    <iconify-icon icon="mdi:image-multiple" style="font-size: 48px; opacity: 0.5;"></iconify-icon>
                    <p style="margin-top: 16px;">No generated images yet</p>
                </div>
            `;
            return;
        }
        
        // Filter to only images
        const images = data.files.filter(f => ['png', 'jpg', 'jpeg', 'webp', 'gif'].includes(f.extension));
        
        if (images.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 48px; color: var(--text-muted);">
                    <iconify-icon icon="mdi:image-multiple" style="font-size: 48px; opacity: 0.5;"></iconify-icon>
                    <p style="margin-top: 16px;">No generated images yet</p>
                </div>
            `;
            return;
        }
        
        grid.innerHTML = images.map(img => `
            <div class="gallery-item" onclick="viewImage('${img.path}')">
                <img src="generated/${img.name}" alt="${img.name}" />
                <div class="gallery-item-overlay">${img.name}</div>
            </div>
        `).join('');
        
    } catch (error) {
        console.error('Gallery load error:', error);
    }
}

function viewImage(path) {
    // Open in new tab or modal
    window.open('generated/' + path.split('/').pop(), '_blank');
}

// Add spin animation
const style = document.createElement('style');
style.textContent = `
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .spin { animation: spin 1s linear infinite; }
`;
document.head.appendChild(style);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
