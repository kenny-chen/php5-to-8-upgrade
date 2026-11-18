# Quality Checklist

## All components

- [ ] The component solves one clear job.
- [ ] The target path and file type are correct.
- [ ] The text uses simple Grade 8 English.
- [ ] Each bullet has one requirement.
- [ ] Required and forbidden behavior are clear.
- [ ] Project facts come from real files or stated user input.
- [ ] No API, field, command, path, version, or test result is invented.
- [ ] Edge cases and failure paths are covered.
- [ ] Validation steps are real and relevant.
- [ ] The output contract is clear.
- [ ] Secrets and personal data are not exposed.
- [ ] The component is no longer than needed.

## Rule checks

- [ ] File uses `.md`.
- [ ] Frontmatter has valid `description`, `globs`, and `alwaysApply` for the chosen rule mode.
- [ ] Globs are narrow.
- [ ] The rule uses observable actions, not broad advice.
- [ ] Good and bad examples are short and useful.

## Skill checks

- [ ] `SKILL.md` has only `name` and `description` in frontmatter.
- [ ] Name is lowercase kebab-case.
- [ ] Description includes what it does and when it triggers.
- [ ] References are linked directly from `SKILL.md`.
- [ ] Added scripts were run and tested.
- [ ] Sample and unused files were removed.

## Command checks

- [ ] The command has one direct action.
- [ ] Missing input handling is clear.
- [ ] Steps are ordered.
- [ ] Final output is defined.

## Hook checks

- [ ] The event is the narrowest useful event.
- [ ] Config matches current Kiro hook schema.
- [ ] JSON input and output are handled safely.
- [ ] Timeout and error behavior are set.
- [ ] Allowed and blocked cases are tested.
- [ ] Logs do not leak secrets.

## Subagent checks

- [ ] The delegation trigger is specific.
- [ ] The parent must pass enough context.
- [ ] The role has a clear boundary.
- [ ] The return format is compact and useful.
- [ ] Parallel work does not overlap.