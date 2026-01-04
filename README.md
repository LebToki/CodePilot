<div align="center">

# 🚀 CodePilot

### AI-Powered Code Assistant with Multi-Provider Support

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://php.net)
[![Made with ❤️](https://img.shields.io/badge/Made%20with-❤️-red.svg)](https://2tinteractive.com)

**A web-based AI coding IDE featuring multi-provider chat, project management, image generation, and Monaco editor.**

[Features](#-features) • [Installation](#-installation) • [Screenshots](#-screenshots) • [API](#-api-endpoints) • [Contributing](#-contributing)

</div>

---

## ✨ Features

| Feature                  | Description                                   |
|--------------------------|-----------------------------------------------|
| 🤖 **Multi-Provider AI** | Ollama, DeepSeek, Gemini 2.5, HuggingFace     |
| 📁 **Project Manager**   | Create projects with 7 starter templates      |
| 🎨 **AI Studio**         | Image generation (Imagen 3) & Vision analysis |
| 📝 **Monaco Editor**     | VS Code's editor engine built-in              |
| 🗂️ **File Browser**     | Browse, edit, and save project files          |
| 🌙 **Dark Theme**        | Beautiful glassmorphism design                |

---

## 🖼️ Screenshots

<div align="center">

| Chat Interface                                                       | Studio                                                                   | Projects                                                                     |
|----------------------------------------------------------------------|--------------------------------------------------------------------------|------------------------------------------------------------------------------|
| ![Chat](https://via.placeholder.com/300x200/1a1a2e/ffffff?text=Chat) | ![Studio](https://via.placeholder.com/300x200/1a1a2e/ffffff?text=Studio) | ![Projects](https://via.placeholder.com/300x200/1a1a2e/ffffff?text=Projects) |

</div>

---

## 🚀 Installation

### Requirements
- PHP 8.1+
- Apache with mod_rewrite (or Laragon/XAMPP)
- API keys for your preferred providers

### Quick Start

```bash
# Clone the repository
git clone https://github.com/LebToki/CodePilot.git

# Navigate to the project
cd CodePilot

# Copy environment file
cp .env.example .env

# Configure your API keys in .env
```

### Configuration

Edit `.env` with your API keys:

```env
# DeepSeek
DEEPSEEK_API_KEY=your_key_here

# Google Gemini
GEMINI_API_KEY=your_key_here

# HuggingFace (optional)
HUGGINGFACE_API_KEY=your_key_here

# Ollama (local)
OLLAMA_API_URL=http://localhost:11434/api
```

### Access

Open in browser: `http://localhost/CodePilot/public/`

---

## 📦 Project Templates

| Template       | Description                       | Files Included                        |
|----------------|-----------------------------------|---------------------------------------|
| 🐘 **PHP**     | Composer project with PSR-4       | `composer.json`, `src/App.php`, tests |
| 🐍 **Python**  | Modern Python with pyproject.toml | CLI, pytest, type hints               |
| 📦 **Node.js** | ESM package with tests            | `package.json`, utilities             |
| 🌐 **HTML**    | Landing page starter              | Hero, features, responsive CSS        |
| 🧪 **Flask**   | Python web framework              | App factory, blueprints               |
| ⚡ **Express**  | Node.js REST API                  | Routes, middleware, CORS              |

---

## 🎨 AI Studio

### Image Generation
Generate images using Imagen 3 with:
- Multiple aspect ratios (1:1, 16:9, 9:16)
- Style presets (photorealistic, digital art, anime, etc.)
- Auto-saved to gallery

### Vision Analysis
Upload images for AI analysis:
- Code screenshot debugging
- UI/UX mockup analysis
- Diagram interpretation
- Error message explanation

---

## 🔌 API Endpoints

| Endpoint             | Method   | Description           |
|----------------------|----------|-----------------------|
| `/api/chat.php`      | POST     | Multi-provider chat   |
| `/api/vision.php`    | POST     | Image analysis        |
| `/api/imagen.php`    | POST     | Image generation      |
| `/api/projects.php`  | GET/POST | Project management    |
| `/api/files.php`     | GET/POST | File operations       |
| `/api/providers.php` | GET      | List available models |

---

## 🗂️ Project Structure

```
CodePilot/
├── public/
│   ├── index.php          # Main chat interface
│   ├── projects.php       # Project manager
│   ├── studio.php         # AI Studio
│   ├── settings.php       # Configuration
│   ├── api/               # API endpoints
│   ├── assets/            # CSS & JS
│   └── generated/         # Generated images
├── src/
│   └── config.php         # Configuration loader
├── .env                   # Environment variables
└── README.md
```

---

## 🛠️ Supported Providers

| Provider        | Models                       | Type  |
|-----------------|------------------------------|-------|
| **Ollama**      | Any local model              | Local |
| **DeepSeek**    | Chat, Coder, Reasoner (R1)   | Cloud |
| **Gemini**      | 2.5 Pro, 2.5 Flash, 2.0, 1.5 | Cloud |
| **HuggingFace** | Llama 3.3, Qwen 2.5, Mixtral | Cloud |

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author

**Tarek Tarabichi**

- Website: [2tinteractive.com](https://2tinteractive.com)
- GitHub: [@LebToki](https://github.com/LebToki)

---

<div align="center">

**Made with ❤️ by [2TInteractive](https://2tinteractive.com)**

⭐ Star this repo if you find it helpful!

</div>
