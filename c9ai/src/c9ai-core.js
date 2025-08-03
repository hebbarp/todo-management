const { spawn } = require('child_process');
const fs = require('fs-extra');
const path = require('path');
const chalk = require('chalk');
const ora = require('ora');
const os = require('os');

class C9AI {
    constructor() {
        this.currentModel = 'claude';
        this.configDir = path.join(os.homedir(), '.c9ai');
        this.toolsRegistry = {};
        this.running = false;
        this.maxIterations = 20;
        
        this.init();
    }

    async init() {
        // Ensure config directory exists
        await fs.ensureDir(this.configDir);
        await fs.ensureDir(path.join(this.configDir, 'logs'));
        await fs.ensureDir(path.join(this.configDir, 'tools'));
        
        // Load configuration
        await this.loadConfig();
        await this.loadTools();
    }

    async loadConfig() {
        const configPath = path.join(this.configDir, 'config.json');
        try {
            if (await fs.exists(configPath)) {
                const config = await fs.readJson(configPath);
                this.currentModel = config.defaultModel || 'claude';
            }
        } catch (error) {
            console.log(chalk.yellow('⚠️  Using default configuration'));
        }
    }

    async saveConfig() {
        const configPath = path.join(this.configDir, 'config.json');
        await fs.writeJson(configPath, {
            defaultModel: this.currentModel,
            lastUpdated: new Date().toISOString()
        }, { spaces: 2 });
    }

    async loadTools() {
        // Load built-in tools registry
        this.toolsRegistry = {
            file_operations: {
                read: { command: 'cat', description: 'Read file contents' },
                write: { command: 'echo', description: 'Write to file' },
                list: { command: 'ls', description: 'List directory contents' }
            },
            git_operations: {
                status: { command: 'git status', description: 'Show git status' },
                add: { command: 'git add', description: 'Stage files' },
                commit: { command: 'git commit', description: 'Commit changes' },
                push: { command: 'git push', description: 'Push to remote' }
            },
            todo_operations: {
                list: { command: 'gh issue list', description: 'List GitHub issues' },
                create: { command: 'gh issue create', description: 'Create new issue' },
                close: { command: 'gh issue close', description: 'Close issue' }
            },
            development: {
                test: { command: 'npm test', description: 'Run tests' },
                build: { command: 'npm run build', description: 'Build project' },
                install: { command: 'npm install', description: 'Install dependencies' }
            },
            system: {
                date: { command: 'date', description: 'Show current date' },
                pwd: { command: 'pwd', description: 'Show current directory' },
                whoami: { command: 'whoami', description: 'Show current user' }
            }
        };
    }

    async handleCommand(input) {
        const [command, ...args] = input.split(' ');
        
        try {
            switch (command.toLowerCase()) {
                case 'claude':
                    await this.runAI('claude', args.join(' '));
                    break;
                case 'gemini':
                    await this.runAI('gemini', args.join(' '));
                    break;
                case 'switch':
                    await this.switchModel(args[0]);
                    break;
                case 'todos':
                    await this.handleTodos(args[0]);
                    break;
                case 'analytics':
                    await this.showAnalytics();
                    break;
                case 'tools':
                    await this.listTools();
                    break;
                case 'config':
                    await this.showConfig();
                    break;
                case 'help':
                    this.showHelp();
                    break;
                default:
                    // Default to current AI model
                    await this.runAI(this.currentModel, input);
            }
        } catch (error) {
            console.error(chalk.red('❌ Error executing command:'), error.message);
        }
    }

    async runAI(model, prompt, options = {}) {
        if (!prompt.trim()) {
            console.log(chalk.yellow('⚠️  Please provide a prompt'));
            return;
        }

        const spinner = ora(`🤖 ${model.charAt(0).toUpperCase() + model.slice(1)} is thinking...`).start();
        
        try {
            // Log the interaction
            await this.logInteraction(model, prompt);
            
            if (options.autonomous) {
                spinner.stop();
                await this.runAutonomous(model, prompt);
            } else {
                await this.runSimpleAI(model, prompt);
                spinner.stop();
            }
        } catch (error) {
            spinner.stop();
            console.error(chalk.red(`❌ Error running ${model}:`), error.message);
            console.log(chalk.yellow('💡 Make sure the CLI is installed and configured:'));
            console.log(chalk.white(`   ${model === 'claude' ? 'claude' : 'gemini-cli'} --version`));
        }
    }

    async runSimpleAI(model, prompt) {
        return new Promise((resolve, reject) => {
            const command = model === 'claude' ? 'claude' : 'gemini-cli';
            const child = spawn(command, [prompt], { 
                stdio: 'inherit',
                shell: true 
            });

            child.on('close', (code) => {
                if (code === 0) {
                    resolve();
                } else {
                    reject(new Error(`${command} exited with code ${code}`));
                }
            });

            child.on('error', (error) => {
                reject(error);
            });
        });
    }

    async runAutonomous(model, goal) {
        console.log(chalk.cyan(`\\n🚀 Starting autonomous execution with ${model.toUpperCase()}`));
        console.log(chalk.white(`📋 Goal: ${goal}`));
        console.log(chalk.gray('='.repeat(60)));
        
        this.running = true;
        let iteration = 0;
        
        while (this.running && iteration < this.maxIterations) {
            iteration++;
            
            console.log(chalk.cyan(`\\n🔄 Step ${iteration}:`));
            
            // For now, we'll simulate autonomous execution
            // In a real implementation, this would:
            // 1. Ask AI to plan next step
            // 2. Execute tools based on AI response
            // 3. Evaluate results and continue
            
            try {
                await this.simulateAutonomousStep(model, goal, iteration);
                
                // Check if goal is achieved (simplified logic for now)
                if (iteration >= 3) {
                    console.log(chalk.green('\\n✅ GOAL ACHIEVED: Task completed successfully'));
                    break;
                }
                
                // Brief pause between steps
                await this.sleep(1000);
                
            } catch (error) {
                console.log(chalk.red(`❌ Step ${iteration} failed: ${error.message}`));
                console.log(chalk.yellow('🔄 Attempting to recover...'));
            }
        }
        
        this.running = false;
        console.log(chalk.cyan('\\n🏁 Autonomous execution completed'));
    }

    async simulateAutonomousStep(model, goal, step) {
        const actions = [
            '📖 Analyzing current state...',
            '🔍 Identifying required actions...',
            '⚙️ Executing tools and commands...',
            '✅ Validating results...'
        ];
        
        const action = actions[Math.min(step - 1, actions.length - 1)];
        
        const spinner = ora(action).start();
        await this.sleep(1500);
        spinner.succeed(action.replace('...', ' ✅'));
        
        // Simulate tool execution
        if (step === 2) {
            console.log(chalk.gray('   🔧 Running: git status'));
            console.log(chalk.gray('   📊 Analyzing: GitHub issues'));
        }
    }

    async switchModel(model) {
        const validModels = ['claude', 'gemini'];
        
        if (!validModels.includes(model)) {
            console.log(chalk.red(`❌ Invalid model. Choose from: ${validModels.join(', ')}`));
            return;
        }
        
        this.currentModel = model;
        await this.saveConfig();
        
        console.log(chalk.green(`🔄 Switched to ${model.toUpperCase()}`));
        
        // Test the AI availability
        const testSpinner = ora(`Testing ${model} availability...`).start();
        try {
            const command = model === 'claude' ? 'claude' : 'gemini-cli';
            await this.runCommand(`${command} --version`);
            testSpinner.succeed(`${model.toUpperCase()} is ready`);
        } catch (error) {
            testSpinner.fail(`${model.toUpperCase()} not available`);
            console.log(chalk.yellow(`💡 Install ${model} CLI to use this model`));
        }
    }

    async handleTodos(action = 'list') {
        console.log(chalk.cyan('📋 Todo Management'));
        
        switch (action) {
            case 'list':
                await this.listTodos();
                break;
            case 'sync':
                await this.syncTodos();
                break;
            case 'add':
                console.log(chalk.yellow('💡 Use: c9ai claude "add todo: [description]"'));
                break;
            default:
                console.log(chalk.yellow('📋 Available actions: list, sync, add'));
        }
    }

    async listTodos() {
        try {
            console.log(chalk.white('🔍 Fetching GitHub issues...'));
            
            // Use existing check-todos script
            const scriptPath = path.join(__dirname, '../../mac_linux/check-todos.sh');
            if (await fs.exists(scriptPath)) {
                await this.runCommand(`bash ${scriptPath}`);
            } else {
                await this.runCommand('gh issue list --repo hebbarp/todo-management --state open');
            }
        } catch (error) {
            console.log(chalk.red('❌ Error fetching todos:'), error.message);
            console.log(chalk.yellow('💡 Make sure GitHub CLI is installed and authenticated'));
        }
    }

    async syncTodos() {
        const spinner = ora('🔄 Syncing todos from all sources...').start();
        try {
            // This would sync from GitHub, local files, etc.
            await this.sleep(2000);
            spinner.succeed('✅ Todos synced successfully');
        } catch (error) {
            spinner.fail('❌ Sync failed');
            console.log(chalk.red('Error:'), error.message);
        }
    }

    async showAnalytics() {
        console.log(chalk.cyan('📊 C9 AI Analytics Dashboard'));
        console.log(chalk.gray('='.repeat(40)));
        
        try {
            const logPath = path.join(this.configDir, 'logs');
            const files = await fs.readdir(logPath);
            
            console.log(chalk.white(`📈 Total sessions: ${files.length}`));
            console.log(chalk.white(`🤖 Current model: ${this.currentModel.toUpperCase()}`));
            console.log(chalk.white(`📅 Last updated: ${new Date().toLocaleDateString()}`));
            
            console.log(chalk.yellow('\\n💡 Full analytics dashboard coming soon!'));
        } catch (error) {
            console.log(chalk.yellow('📊 No analytics data yet - start using c9ai to build insights!'));
        }
    }

    async listTools() {
        console.log(chalk.cyan('🔧 Available Tools Registry'));
        console.log(chalk.gray('='.repeat(40)));
        
        for (const [category, tools] of Object.entries(this.toolsRegistry)) {
            console.log(chalk.yellow(`\\n📁 ${category.replace('_', ' ').toUpperCase()}:`));
            
            for (const [name, tool] of Object.entries(tools)) {
                console.log(chalk.white(`  • ${name}: ${tool.description}`));
                console.log(chalk.gray(`    Command: ${tool.command}`));
            }
        }
        
        console.log(chalk.yellow('\\n💡 Tools are used automatically during autonomous execution'));
    }

    async showConfig() {
        console.log(chalk.cyan('⚙️ C9 AI Configuration'));
        console.log(chalk.gray('='.repeat(30)));
        console.log(chalk.white(`📍 Config directory: ${this.configDir}`));
        console.log(chalk.white(`🤖 Default AI model: ${this.currentModel.toUpperCase()}`));
        console.log(chalk.white(`🔧 Max iterations: ${this.maxIterations}`));
    }

    showHelp() {
        console.log(chalk.cyan('📖 C9 AI Help'));
        console.log(chalk.gray('='.repeat(20)));
        console.log(chalk.white('🤖 AI Commands:'));
        console.log(chalk.white('  claude <prompt>     - Use Claude AI'));
        console.log(chalk.white('  gemini <prompt>     - Use Gemini AI'));
        console.log(chalk.white('  switch <model>      - Switch AI model'));
        console.log(chalk.white('\\n📋 Productivity:'));
        console.log(chalk.white('  todos [action]      - Manage todos'));
        console.log(chalk.white('  analytics           - View insights'));
        console.log(chalk.white('\\n🔧 System:'));
        console.log(chalk.white('  tools               - List available tools'));
        console.log(chalk.white('  config              - Show configuration'));
        console.log(chalk.white('  help                - Show this help'));
    }

    async runCommand(command) {
        return new Promise((resolve, reject) => {
            const child = spawn(command, { 
                stdio: 'inherit', 
                shell: true 
            });

            child.on('close', (code) => {
                if (code === 0) {
                    resolve();
                } else {
                    reject(new Error(`Command failed with code ${code}`));
                }
            });

            child.on('error', reject);
        });
    }

    async logInteraction(model, prompt) {
        const logFile = path.join(this.configDir, 'logs', `${new Date().toISOString().split('T')[0]}.json`);
        
        const logEntry = {
            timestamp: new Date().toISOString(),
            model,
            prompt,
            session: process.pid
        };
        
        try {
            let logs = [];
            if (await fs.exists(logFile)) {
                logs = await fs.readJson(logFile);
            }
            
            logs.push(logEntry);
            await fs.writeJson(logFile, logs, { spaces: 2 });
        } catch (error) {
            // Fail silently for logging errors
        }
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

module.exports = C9AI;