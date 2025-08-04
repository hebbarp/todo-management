# c9ai System Summary

This document provides an overview of the `c9ai` system as it has been developed and refined during our sessions. It outlines the core philosophy, key features, and current architectural decisions.

## 1. Core Philosophy: Local First, AI Fallback

`c9ai` is designed to be an intelligent productivity assistant that prioritizes efficiency and user experience:

-   **Local First:** For tasks with a clear, deterministic solution, `c9ai` attempts to execute local shell commands or scripts directly. This ensures speed, reliability, and cost-effectiveness.
-   **AI Fallback:** If a local command fails, `c9ai` automatically captures the error output and passes it to the default AI model. The AI then analyzes the error and provides a step-by-step solution or diagnosis, turning failures into actionable insights.

## 2. Key Features Implemented

### 2.1. Interactive Command-Line Interface (CLI)

`c9ai` provides an interactive shell (`c9ai>`) for seamless interaction.

-   **Basic Commands:**
    -   `help`: Displays a list of available commands.
    -   `logo` / `banner`: Shows the `c9ai` ASCII art logo.
    -   `exit` / `quit`: Exits the `c9ai` interactive shell.

### 2.2. Shell Command Passthrough (`!` Sigil)

Users can execute any arbitrary shell command directly from the `c9ai>` prompt.

-   **Usage:** `! <command>` (e.g., `!ls -l`, `!git status`)
-   **Directory Change:** Special handling for `!cd <path>` to change `c9ai`'s current working directory.
-   **Robustness:** The `c9ai` loop continues even if the executed shell command fails.

### 2.3. Interactive AI Sessions (`@` Sigil)

For open-ended conversations, creative tasks, or complex problem-solving, users can initiate direct interactive sessions with AI models.

-   **Usage:** `@claude`, `@gemini`
-   **Behavior:** Drops the user into a dedicated interactive session with the respective AI CLI. The AI is aware of `c9ai`'s local tools.
-   **Exit:** Type `exit` or `quit` to return to the `c9ai>` prompt.
-   **Note:** Single-shot `claude <prompt>` and `gemini <prompt>` commands have been removed due to incompatibility with interactive AI CLIs.

### 2.4. Unified Todo Management (`todos` Command)

The `todos` command provides a comprehensive view and management system for tasks.

-   **Unified Listing (`todos`):** Displays tasks from two primary sources:
    -   Open GitHub issues (fetched via `gh issue list`).
    -   Local tasks stored in `todo.md`.

-   **Adding Tasks (`add`):** Allows users to add tasks to `todo.md` directly from the CLI.
    -   **Usage:** `add <task description> [@<verb> <target>]`
    -   **Intent-Based DSL:** Supports an intuitive `@<verb> <target>` syntax for defining actionable tasks (e.g., `@open example.com`, `@run script.sh`).

-   **Listing Actionable Todos (`todos actions`):** Shows only the tasks in `todo.md` that have an associated intent, providing a quick overview of executable tasks.

-   **Executing Actionable Todos (`todos execute`):** Presents an interactive checklist of all actionable tasks from `todo.md`.
    -   Users select tasks to execute.
    -   Each selected task's intent is processed by the **Intent-Based Action Engine**.
    -   If an execution fails, the AI Fallback mechanism is triggered.

### 2.5. Intent-Based Action Engine

This is a core component that translates user intents into concrete, platform-specific shell commands.

-   **Mechanism:** Parses the `@<verb> <target>` from `todo.md` entries.
-   **Supported Verbs (Intents):**
    -   `@open <path_or_url>`: Opens files, directories, or URLs (uses `open` on macOS, `start` on Windows, `xdg-open` on Linux).
    -   `@compile <file>`: Compiles documents (currently supports `.tex` files using `pdflatex`).
    -   `@run <tool_name>`: Executes scripts or binaries from the `~/.c9ai/tools` directory. Automatically infers interpreter based on extension (`.sh` -> `bash`, `.py` -> `python3`, `.js` -> `node`).
    -   `@search <query>`: Performs a web search (opens a Google search URL).

### 2.6. Flexible Tool Registry

`c9ai` manages external scripts and tools in a simple, flexible manner.

-   **Location:** All executable scripts are stored directly in `~/.c9ai/tools` (previously `~/.c9ai/scripts`).
-   **Discovery:** `c9ai> tools` command scans this directory and lists all executable files found.
-   **Ease of Use:** Users can simply drop new scripts into this directory and make them executable (`chmod +x`) for `c9ai` to discover and use them via the `@run` intent.

## 3. Current State and Knowns

-   **Technology Stack:** Node.js application using `commander`, `inquirer`, `chalk`, `ora`, `fs-extra`, `node-fetch`, `chokidar`, `yaml`.
-   **External Dependencies:** Relies on external CLIs like `gh` (GitHub CLI), `pdflatex`, `python3`, `node`, and system commands (`open`, `start`, `xdg-open`).
-   **Configuration:** Stores user configuration (e.g., default AI model) in `~/.c9ai/config.json`.
-   **Logging:** Logs AI interactions to `~/.c9ai/logs/`.

## 4. Next Steps (Based on recent interactions)

-   **Verify `todos execute`:** Confirm that the `inquirer` prompt correctly captures selections and that the execution loop proceeds as expected, with intents being run and output displayed.
-   **Expand Intent Verbs:** Add more verbs and their corresponding execution logic to the `runIntent` function (e.g., for mailing, social media, data processing).
-   **Refine Error Handling:** Further enhance the AI Fallback mechanism and user feedback for failed commands.
