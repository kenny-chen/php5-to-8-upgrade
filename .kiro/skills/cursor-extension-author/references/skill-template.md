# Skill Template

Store a project skill under `.kiro/skills/<skill-name>/SKILL.md` or the skill directory supported by the current Kiro setup.

```text
<skill-name>/
├── SKILL.md
├── scripts/      # optional deterministic work
├── references/   # optional detailed guidance
└── assets/       # optional output templates
```

```md
---
name: <lowercase-kebab-case>
description: <What the skill does and exact user requests that should trigger it>
---

# <Display name>

## Workflow

1. Inspect <required inputs>.
2. Choose <decision path>.
3. Perform <main work>.
4. Validate <result>.
5. Return <output contract>.

## Core rules

- <Specific rule>
- <Specific rule>

## Resources

- For <case>, read [reference](references/<file>.md).
- For <repeatable operation>, run `scripts/<script>`.

## Output

Return:

- <Item>
- <Item>
```

## Skill rules

- Put all trigger details in the frontmatter description.
- Keep `SKILL.md` as the control plane, not a knowledge dump.
- Aim for fewer than 500 lines in `SKILL.md`.
- Keep references one link away from `SKILL.md`.
- Use scripts for fragile, exact, or repeated operations.
- Test each script before delivery.
- Remove sample files and empty folders.
- Use assets only for files that will be copied or used in output.
- Keep the full package at or below the supported upload limit.