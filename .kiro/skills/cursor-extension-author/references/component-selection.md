# Component Selection

| Need | Best component | Main reason |
|---|---|---|
| Keep coding standards active | Rule | Adds steady project guidance |
| Run a repeatable multi-step task | Skill | Holds workflow, references, and scripts |
| Start a named action with `/` | Command | Gives the user a direct entry point |
| Check or control an agent event | Hook | Runs deterministic code at a lifecycle point |
| Delegate deep or parallel work | Subagent | Uses a separate context window |

## Split rules

Use more than one component when the jobs have different triggers.

Examples:

- Use a Rule for PHP 8 safety and a Command for `/review-php8`.
- Use a Skill for release work and a Hook for secret scanning before shell use.
- Use a Subagent for codebase research and a Command to start the full review flow.

## Wrong choices

- Do not put a long business knowledge base in an always-on Rule.
- Do not use a Hook for advice that the model can follow as a Rule.
- Do not use a Command for guidance that must apply without user action.
- Do not use a Subagent only to rename a file or edit one line.