# Subagent Template

Create a subagent when work needs isolated context, deep search, or parallel execution. Use the path and frontmatter format supported by the active Kiro version. A common project layout is `.kiro/agents/<name>.md`.

```md
---
name: <lowercase-kebab-case>
description: <Exact tasks the parent agent should delegate>
model: <optional supported model or inherit>
---

# <Subagent name>

## Mission

<One clear outcome.>

## Inputs from the parent

The parent must provide:

- Goal
- Relevant paths or files
- Constraints
- Required output

If a key input is missing, report the gap. Do not guess.

## Workflow

1. Inspect the given scope.
2. Gather evidence.
3. Perform the focused task.
4. Check the result.
5. Return a compact report to the parent.

## Boundaries

- Work only inside the assigned scope.
- Do not repeat work owned by another subagent.
- Do not make destructive changes unless the parent asks.
- Do not assume access to the parent's earlier conversation.

## Output

Return:

- Findings or changes
- File and line evidence when relevant
- Checks run
- Risks and missing context
- Clear next action for the parent
```

## Subagent rules

- Write the description as a delegation trigger.
- Give the subagent one specialty.
- Assume a clean context window.
- Require the parent to pass all needed context.
- Use background mode for long, independent work.
- Use foreground mode when the parent needs the result before the next step.
- Return a summary, not raw search noise.
- Limit tool access to the needed tools when configuration supports it.