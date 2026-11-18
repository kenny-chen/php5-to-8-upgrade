# Command Template

Store a project command as `.kiro/commands/<command-name>.md` unless the active Kiro version or project uses a different command path.

```md
# <Command title>

<One sentence that states the result.>

## Inputs

Use the user's text after the command as `<task>`.
If no task is given, ask for the missing task.

## Steps

1. Read the relevant files before editing.
2. Confirm the current stack and local patterns.
3. Make the smallest complete change.
4. Run the relevant checks.
5. Review the diff.

## Rules

- Do not edit unrelated files.
- Do not invent project commands or APIs.
- State any assumption that changes the result.
- Do not claim checks passed unless they ran.

## Output

Return:

- Files changed
- Main decisions
- Checks run and results
- Open risks
```

## Command rules

- Give the command one user-visible job.
- Start the job at once when enough input exists.
- Ask only for missing input that blocks safe work.
- Use numbered steps for order-sensitive work.
- Define the final output shape.
- Keep deep domain knowledge in a Skill or Rule, not in the Command.