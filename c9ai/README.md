# 🌟 C9 AI - Autonomous AI-Powered Productivity System

> Transform your productivity with Claude & Gemini AI agents that can reason, plan, and execute tasks autonomously using local tools.

## ✨ What is C9 AI?

C9 AI is a command-line interface that brings **autonomous AI agents** to your local development environment. Instead of just chatting with AI, C9 AI allows Claude and Gemini to:

- 🧠 **Reason through complex tasks** step by step
- 🔧 **Execute local tools** and commands autonomously  
- 🔄 **Iterate and improve** until goals are achieved
- 📊 **Integrate with your existing workflow** (GitHub, git, npm, etc.)
- 🤖 **Switch between AI models** for specialized tasks

## 🚀 Key Features

### 🎯 Autonomous Execution
```bash
c9ai claude --autonomous "Debug my failing tests and fix all issues"
```
Watch Claude analyze, fix, test, and iterate until all tests pass!

### 🔄 AI Model Switching
```bash
c9ai claude "analyze this data"     # Deep reasoning
c9ai gemini "create a visual summary"  # Creative tasks
c9ai switch gemini                   # Change default model
```

### 📋 Enhanced Todo Management
```bash
c9ai todos list                      # GitHub Issues integration
c9ai claude "execute my high priority todos"  # AI-powered execution
```

### 🔧 Local Tool Integration
- Git operations (status, commit, push)
- File operations (read, write, list)
- Development tools (test, build, deploy)
- Todo management (GitHub Issues)
- System commands

## 📦 Installation

### Prerequisites
- Node.js 16+ ([Download](https://nodejs.org))
- Claude CLI ([Installation guide](https://docs.anthropic.com/claude/docs/cli))
- GitHub CLI for todo management: `brew install gh`

### Quick Install
```bash
git clone <repo>
cd c9ai
./install.sh
```

### Manual Install
```bash
npm install
chmod +x src/index.js
sudo ln -sf $(pwd)/src/index.js /usr/local/bin/c9ai
```

## 🎮 Usage

### Interactive Mode (Recommended)
```bash
c9ai
```
Launches an interactive shell with AI switching, tool access, and todo management.

### Direct Commands
```bash
# AI Commands
c9ai claude "analyze my project structure"
c9ai gemini "create a README for my project" 
c9ai claude --autonomous "set up a new React project"

# Productivity
c9ai todos list
c9ai analytics
c9ai tools

# Configuration
c9ai switch gemini
c9ai config
```

## 🤖 Autonomous Mode Examples

### Software Development
```bash
c9ai claude --autonomous "Fix all failing tests in my project"
```
**What happens:**
1. 📊 Runs test suite to identify failures
2. 🔍 Analyzes each failing test
3. 🔧 Fixes code issues one by one
4. ✅ Re-runs tests until all pass
5. 📝 Commits fixes with descriptive messages

### Project Management  
```bash
c9ai claude --autonomous "Review my GitHub issues and complete urgent tasks"
```
**What happens:**
1. 📋 Fetches all open GitHub issues
2. 🎯 Identifies high-priority tasks
3. ⚡ Executes tasks using local tools
4. ✅ Updates issue status automatically
5. 📊 Provides completion summary

### Documentation
```bash
c9ai gemini --autonomous "Create comprehensive documentation for my API"
```
**What happens:**
1. 📖 Analyzes codebase structure
2. 🔍 Identifies API endpoints and functions
3. ✍️ Generates documentation files
4. 🎨 Creates visual diagrams
5. 📚 Organizes into cohesive documentation

## 🛠️ Configuration

### AI Model Setup
1. **Claude CLI**: Follow [official setup guide](https://docs.anthropic.com/claude/docs/cli)
2. **Gemini CLI**: Install with `npm install -g @google/generative-ai-cli`

### Tool Registry
C9 AI automatically detects and registers available tools:
- Development: npm, git, python, etc.
- File operations: read, write, list, search
- Communication: email, slack (with setup)
- Custom scripts: Add your own tools

View available tools: `c9ai tools`

## 📊 Analytics & Insights

```bash
c9ai analytics
```
Track your AI-powered productivity:
- Command usage patterns
- Task completion rates  
- Time savings from automation
- AI model effectiveness

## 🔧 Extending C9 AI

### Custom Tools
Add your own tools to `~/.c9ai/tools/custom.json`:
```json
{
  "deploy": {
    "command": "npm run deploy",
    "description": "Deploy to production"
  }
}
```

### Scripts
Create automation scripts in `~/.c9ai/scripts/`:
```javascript
// morning-standup.js
module.exports = async (tools) => {
  await tools.git.status();
  await tools.todos.list();
  await tools.ai.claude("summarize my progress");
};
```

## 🎪 Workshop Demo

Perfect for demonstrating:
- **Autonomous AI execution** with real tasks
- **Multi-model AI switching** for specialized work  
- **Local tool integration** with existing workflows
- **Productivity enhancement** through AI automation

### Demo Scenarios
1. **"Fix my broken tests"** - Watch AI debug and repair automatically
2. **"Plan my day"** - AI analyzes todos and creates action plan
3. **"Create documentation"** - AI examines code and generates docs
4. **"Deploy my project"** - AI handles build, test, and deployment

## 🤝 Contributing

C9 AI is designed to be extensible:
- Add new AI providers
- Create custom tool integrations  
- Build workflow automations
- Enhance autonomous execution capabilities

## 📄 License

MIT License - Build amazing AI-powered productivity tools!

---

**Ready to experience autonomous AI productivity?** 🚀

Start with: `c9ai claude "help me be more productive today"`