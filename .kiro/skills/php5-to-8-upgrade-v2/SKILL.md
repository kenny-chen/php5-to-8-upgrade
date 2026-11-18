---
name: php5-to-8-upgrade-v2
description: >-
  PHP 5→8.5 syntax-only on *.php and *.inc. One identifier per session; slices of 500.
  Main agent only—no subagents, no new code, finish full batch loop. CODEGRAPH + git-diff-validation per slice.
globs:
  - "**/*.php"
  - "**/*.inc"
alwaysApply: false
---

# PHP 5 → 8 upgrade (syntax-only, v2)

**Pack:** [SKILL.mdc](SKILL.mdc) · [upgrade-sample-code.md](upgrade-sample-code.md) · [CODEGRAPH.md](CODEGRAPH.md) · [git-diff-validation.md](git-diff-validation.md).

**Batch tool:** `tools/read-stan-batch-slice.php` · `composer stan:batch` (same script).

## Hard rules

| Rule | Meaning |
|------|---------|
| **No subagents** | No Task, Bugbot, or delegated readers/fixers—parent agent only. |
| **No new code** | No new functions, methods, closures, params, branches, variables, or behavior—syntax/API swaps on touched lines only. |
| **Complete the process** | Run the full slice loop for the identifier until count **0**; no mid-identifier stops unless the user asks. |

## Role

Legacy PHP **syntax** engineer. **Main agent only** (see Hard rules).

## Goal

Same behavior on **valid** inputs on **PHP 8.5** (and 8.x). Syntax and API replacements only.

## Allowed

| Rule | Detail |
|------|--------|
| PHP 8.5 syntax | `[]`, `??`, `?->`, `{'prop'}` on touched lines; valid calls to **existing** APIs. |
| Minimal diff | Fewer changed lines; touch only lines required for PHP 8. |
| Prefer existing variables | Same-line fix on the **existing** `$name`; reassign globals (`$init`, `$query`) after `global` only—no new locals or alias temps ([git-diff-validation.md](git-diff-validation.md) step **5**). |
| Shared helpers first | Find existing functions in shared `*.inc` via [CODEGRAPH.md](CODEGRAPH.md)—not new wrappers in app files. |
| Identifier fixes | Match PHPStan identifier to [upgrade-sample-code.md](upgrade-sample-code.md); fix **one identifier** until its count is **0**. |
| `property.nonObject` | Inline **`asObject(…)->{'prop'}`** on each touched line; **`$init = asObject($init)`** / **`$query = asObject($query)`** after `global` only. **No** new loop locals (`$_item`, `$cachedList`, …). See [upgrade-sample-code.md](upgrade-sample-code.md) **Loop and list temps** + § `property.nonObject`. |
| `foreach.nonIterable` | **`foreach (asArray(…))`** or **`(array)`** on the **foreach line**—**no** `$safeList` / `$cachedList` temps. See [upgrade-sample-code.md](upgrade-sample-code.md) **Loop and list temps** + § `foreach.nonIterable`. |
| `mysql_*` removed | `mysqli_*` rename on touched lines per **mysql_* → mysqli_* (light map)** in upgrade-sample-code.md; existing `*.inc` DB helpers first when present. |
| `split()` removed | `explode()` or `preg_split()` on the touched line per **split() → explode() / preg_split()** in upgrade-sample-code.md (literal delimiter → `explode`; regex pattern → `preg_split`). |

## Forbidden

| Rule | Detail |
|------|--------|
| No new functions | No new `function`, method, or closure. |
| No new parameters | No added or renamed arguments. |
| No new logic | No new branches, SQL, or display rules. |
| No new behavior | Same outputs on valid inputs. |
| No renames | Do not rename variables. |
| No new variables | No new locals, loop holders (`$_item`, `$cachedList`, …), or split-line temps—inline on the touched line. |
| No dead-code removal | Unless the user explicitly asks. |
| **No batch skipping** | No `--next` until current `batch_NNNN.txt` rows are fixed and git gate + `php -l` pass. |
| **No stale slices** | After large edits: `composer stan:report` → new `reports/<run>_stan` → `--slice-all` on that folder (do not reuse old `stan-dir`). |

## PHPStan batch workflow (step 6)

**One identifier**, slice size **500** (script default). Tool: `composer stan:batch` (= `tools/read-stan-batch-slice.php`).

**Layout:** `reports/<run>_stan/` · pre-sliced `batches/<id>/batch_NNNN.txt` · `progress.json` (`current_batch`) · chat: attach **current slice `.txt` only** (not full `<id>.json`).

| Step | Action |
|------|--------|
| A | New id or many fixes → `composer stan:report`; note `<run>_stan`. |
| B | After `composer stan:report`, note the new `reports/<run>_stan` → `composer stan:batch -- --slice-all --id <id> --stan-dir reports/<run>_stan`. |
| C | `composer stan:batch -- --current --id <id> --stan-dir reports/<run>_stan` → fix **only** `fix_txt` / current `batch_NNNN.txt` ([upgrade-sample-code.md](upgrade-sample-code.md)). |
| D | `php -l` on touched files → [git-diff-validation.md](git-diff-validation.md) steps **0–7**. |
| E | `composer stan:batch -- --next --id <id> --stan-dir reports/<run>_stan` (advances `current_batch` only). |
| F | Repeat **C→E** until `--current` shows all batches done → `composer stan:report` → `<id>` **0** in `_by_identifier.json`; else fresh report + **B** on the **new** `<run>_stan`. |

**Rhythm:** `slice-all → current → fix → php -l → git gate → next → current` (never `--next` without fixing).

**Never:** chained `--next` without fixes; subagents for batch reads/fixes; reusing an old `stan-dir` after `composer stan:report` (always `--slice-all` on the newest `<run>_stan`).

## Workflow (identifiers)

1. Run `php -v` (target **8.5**).
2. Pick one id from `reports/<run>_stan/_by_identifier.json`.
3. Run the **PHPStan batch workflow** above until that id is **0**.
4. [CODEGRAPH.md](CODEGRAPH.md) — confirm shared `*.inc` helpers exist before call-site edits.

## Validation (required)

Validation does **not** run PHP to modify `*.php` / `*.inc`. Edits are manual or agent-driven; PHP is used only for **`php -l`** (syntax check, read-only).

1. Use **`git diff HEAD`** (staged + unstaged) to list and review **changed** `*.php` / `*.inc` only — not the whole tree.
2. Run inventory and Forbidden checks ([git-diff-validation.md](git-diff-validation.md) steps **0–6**).
3. Run **`php -l`** on **those changed files** only (step **7**).

On Windows PowerShell, use the PowerShell blocks in [git-diff-validation.md](git-diff-validation.md) for steps **5** and **7** (and step **1** where noted).

Do not claim **pass** without command output for each step.

## Session output

```markdown
## Mode: syntax-only PHP 5→8 (v2)
### Identifier | <phpstan-id>
### Stan dir | reports/<run>_stan
### Batch | N / total (size 500) | batch_NNNN.txt
### PHP version | <php -v>
### Shared helpers | <names from codegraph_explore>
### Changes | file:line | syntax fix only
### Git gate | see git-diff-validation.md steps 0–7 (pass/fail each)
### php -l | pass / fail
### Slice | --current shows current batch; --next only after this batch fixed (Hard rules)
```