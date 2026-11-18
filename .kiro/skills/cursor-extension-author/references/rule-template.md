# Rule Template

Store project rules as `.kiro/steering/<name>.md`.

```md
---
description: <Clear action and scope>
globs: "<Narrow file patterns>"
alwaysApply: false
---

# <Rule name>

## Scope

Apply this rule when:

- <Condition>

Do not apply it when:

- <Exclusion>

## Project context

- Runtime: <version from project files>
- Framework: <version from project files>
- Main paths: `<paths>`

## Required behavior

- MUST <observable action>.
- SHOULD <default action>.

## Boundaries

- `<layer A>` may depend on `<layer B>`.
- Only `<owner>` may perform `<operation>`.

## Forbidden behavior

- MUST NOT <clear action>.
- Do not invent APIs, fields, paths, or commands.

## Decision rules

- If <condition>, use <choice>.
- If evidence is missing, report the gap instead of guessing.

## Good

```<language>
<short correct example>
```

## Avoid

```<language>
<short wrong example>
```

Reason: <real failure mode>.

## Validation

1. Run `<lint command>`.
2. Run `<test command>`.
3. Check the diff for unrelated changes.
4. Do not claim success unless each check ran.
```

## Frontmatter rules

- Write a clear `description` that says what the rule controls.
- Use the narrowest useful `globs`.
- Use `alwaysApply: true` only for guidance that must affect every request.
- For intelligent use, provide a description and omit globs when that mode is intended.
- For manual use, omit description and globs only when the user wants an `@rule` workflow.

## Writing pattern

Prefer:

```md
- Validate external values before casting.
- Services must not depend on HTTP request objects.
```

Avoid:

```md
- Follow best practices.
- Write clean and scalable code.
```

## Quality limits

- Keep a small convention rule near 20 to 60 lines.
- Keep a workflow rule near 40 to 120 lines.
- Keep an architecture or domain rule near 80 to 200 lines.
- Split a rule when sections have different triggers or file scopes.
- Do not copy formatter settings that are already enforced by tools such as Prettier, ESLint, Pint, Ruff, or PHP-CS-Fixer.