# c9ai Manual

Welcome to the manual for `c9ai`, your autonomous AI-powered productivity system. This guide details the core concepts and commands to help you get the most out of the tool.

## Core Philosophy: Local First, AI Fallback

`c9ai` is built on a simple but powerful principle: use fast, reliable local commands for predictable tasks, and reserve the power of AI for complex problem-solving and error handling.

-   **Local First:** When you run a command like `todos`, `c9ai` executes a pre-defined local script first. This is fast, efficient, and free.
-   **AI Fallback:** If a local command fails, `c9ai` doesn't just give you an error message. It automatically captures the error and passes it to your default AI model, asking for a diagnosis and a solution. This turns failures into learning opportunities.

## Command Guide

### Starting `c9ai`

To start the `c9ai` interactive shell, simply type `c9ai` in your terminal:

```bash
c9ai
```

You will then see the `c9ai>` prompt.

### Basic Commands

-   **`help`**: Displays a list of all available commands and their usage.
-   **`logo`** or **`banner`**: Shows the `c9ai` ASCII art logo.
-   **`exit`** or **`quit`**: Exits the `c9ai` interactive shell.

### Shell Command Passthrough (`!` sigil)

To run any shell command directly without leaving the `c9ai` prompt, use the `!` sigil. This provides the convenience of a full shell environment inside the CLI. The `c9ai` loop will continue even if a command fails.

**Usage:**
-   `c9ai> !ls -l`
-   `c9ai> !git status`
-   `c9ai> !cd ../another-project` (This changes the current working directory for the `c9ai` process itself)

### Interactive AI Sessions (`@` sigil)

When you need the full power of an AI for creative tasks, brainstorming, or complex problem-solving, you can drop into an interactive session. The AI will be aware of the custom scripts available to `c9ai`, allowing it to use your tools to accomplish tasks.

**Usage:**
-   `c9ai> @claude`
-   `c9ai> @gemini`

Type `exit` or `quit` to return to the main `c9ai` prompt.

### Unified Todo Management (`todos` command)

The `todos` command is your central hub for managing tasks. It provides a unified view of all your tasks from different sources.

-   **View All Todos (Unified List):**
    `c9ai> todos`

    When you run this command, `c9ai` will display:
    1.  **GitHub Issues:** A list of your open issues from the `hebbarp/todo-management` repository.
    2.  **Local Tasks:** A list of tasks from your local `todo.md` file.

-   **Add a Task (with Intent-Based Actions):**
    You can quickly add a task to your `todo.md` file directly from the prompt. For actionable tasks, use the `@<verb> <target>` syntax.

    **Syntax:** `c9ai> add <task description> @<verb> <target>`

    **Supported Verbs (Intents):**
    *   **`@open`**: For opening files, folders, or websites.
        *   Example: `c9ai> add open project docs @open docs/README.md`
        *   Example: `c9ai> add check latest news @open https://news.google.com`
        *   Example: `c9ai> add open current folder @open .`
    *   **`@compile`**: For compiling documents or code (e.g., `.tex` files).
        *   Example: `c9ai> add compile proposal @compile proposal.tex`
    *   **`@run`**: For executing a script (e.g., `.sh` scripts located in `~/.c9ai/scripts`).
        *   Example: `c9ai> add run weekly cleanup @run cleanup-weekly.sh`
    *   **`@search`**: For performing a web search.
        *   Example: `c9ai> add find top movies @search top movies 2025`

-   **List Actionable Todos:**
    To see only the tasks in your `todo.md` that have an associated action, without executing them.

    `c9ai> todos actions`

    This will display a non-interactive list of your actionable tasks and their intents.

-   **Execute Actionable Todos (Interactive):**
    To run the actions defined in your `todo.md` file.

    `c9ai> todos execute`

    This will present an interactive checklist of all actionable tasks. You can select which actions you want to run. If an action fails, `c9ai` will automatically invoke the default AI model to help you diagnose and resolve the error.