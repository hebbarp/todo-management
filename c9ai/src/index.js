#!/usr/bin/env node

const { program } = require('commander');
const chalk = require('chalk');
const inquirer = require('inquirer');
const C9AI = require('./c9ai-core');

// ASCII Art Banner
const banner = `
${chalk.cyan('🌟 ============================================ 🌟')}
${chalk.cyan('    ____  ___    _    ___                        ')}
${chalk.cyan('   / ___|/ _ \\  / \\  |_ _|                       ')}
${chalk.cyan('  | |   | (_) |/ _ \\  | |                        ')}
${chalk.cyan('  | |___|\\__, / ___ \\ | |                        ')}
${chalk.cyan('   \\____| /_/_/   \\_\\___|                       ')}
${chalk.cyan('                                                 ')}
${chalk.yellow('  Autonomous AI-Powered Productivity System     ')}
${chalk.green('  🤖 Claude CLI    ✨ Gemini CLI    🚀 Tool Use  ')}
${chalk.cyan('🌟 ============================================ 🌟')}
`;

// Core CLI instance
const c9ai = new C9AI();

// Interactive mode
async function interactiveMode() {
    console.log(banner);
    console.log(chalk.green('\\n📋 Available Commands:'));
    console.log(chalk.white('  🤖 claude <prompt>   - Deep reasoning and analysis'));
    console.log(chalk.white('  ✨ gemini <prompt>   - Creative and multimodal tasks'));
    console.log(chalk.white('  🔄 switch <model>    - Switch default AI model'));
    console.log(chalk.white('  📋 todos             - Manage your tasks'));
    console.log(chalk.white('  📊 analytics         - View productivity insights'));
    console.log(chalk.white('  🔧 tools             - Available local tools'));
    console.log(chalk.white('  ⚙️  config            - System configuration'));
    console.log(chalk.white('  📖 help              - Show help'));
    console.log(chalk.white('  🚪 exit              - Quit c9ai\\n'));

    while (true) {
        try {
            const { command } = await inquirer.prompt([
                {
                    type: 'input',
                    name: 'command',
                    message: chalk.cyan('c9ai>'),
                    prefix: ''
                }
            ]);

            if (command.trim() === 'exit' || command.trim() === 'quit') {
                console.log(chalk.yellow('\\n👋 Thanks for using C9 AI!'));
                process.exit(0);
            }

            if (command.trim() === '') continue;

            await c9ai.handleCommand(command.trim());
        } catch (error) {
            if (error.isTtyError) {
                console.log(chalk.red('Interactive mode not supported in this environment'));
                process.exit(1);
            } else {
                console.error(chalk.red('Error:'), error.message);
            }
        }
    }
}

// CLI Commands
program
    .name('c9ai')
    .description('C9 AI - Autonomous AI-Powered Productivity System')
    .version('1.0.0');

program
    .command('claude <prompt...>')
    .description('Execute prompt with Claude AI')
    .option('-a, --autonomous', 'Enable autonomous execution with tool use')
    .action(async (prompt, options) => {
        await c9ai.runAI('claude', prompt.join(' '), options);
    });

program
    .command('gemini <prompt...>')
    .description('Execute prompt with Gemini AI')
    .option('-a, --autonomous', 'Enable autonomous execution with tool use')
    .action(async (prompt, options) => {
        await c9ai.runAI('gemini', prompt.join(' '), options);
    });

program
    .command('switch <model>')
    .description('Switch default AI model (claude|gemini)')
    .action(async (model) => {
        await c9ai.switchModel(model);
    });

program
    .command('todos [action]')
    .description('Manage todos (list|add|sync)')
    .action(async (action) => {
        await c9ai.handleTodos(action);
    });

program
    .command('analytics')
    .description('Show productivity analytics')
    .action(async () => {
        await c9ai.showAnalytics();
    });

program
    .command('tools')
    .description('List available tools')
    .action(async () => {
        await c9ai.listTools();
    });

program
    .command('interactive')
    .alias('i')
    .description('Start interactive mode')
    .action(interactiveMode);

// Default action - start interactive mode
if (process.argv.length === 2) {
    interactiveMode();
} else {
    program.parse();
}

// Handle uncaught errors
process.on('uncaughtException', (error) => {
    console.error(chalk.red('\\n❌ Uncaught Error:'), error.message);
    process.exit(1);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error(chalk.red('\\n❌ Unhandled Rejection:'), reason);
    process.exit(1);
});