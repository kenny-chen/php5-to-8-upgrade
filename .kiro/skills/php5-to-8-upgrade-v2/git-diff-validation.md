# Git diff validation (code review gate)

Self-contained gate for [SKILL.mdc](SKILL.mdc). Run from **repository root** after each syntax-only batch.

**Scope:** All tracked changes **staged + unstaged** vs `HEAD` (`git diff HEAD`). Plain `git diff` alone is **not** enough.

**What this gate does not do:** No PHP batch rewrite, no `php` run over every `*.php` / `*.inc` in the repo. Changed paths come from **git**; **`php -l`** only checks syntax on that list (step 7).

**Shell:** Use **Git Bash** or WSL for the `grep` steps. On Windows PowerShell, use the [PowerShell appendix](#powershell-appendix) or run the same blocks in Git Bash.

---

## Session output (per Forbidden rule)

Copy one row per step. Do not claim **pass** until you ran the command and recorded the result.

| Step | Forbidden rule | Command section | Result |
|------|----------------|-----------------|--------|
| 0 | — | [Inventory](#step-0--inventory) | |
| 1 | No new functions | [New `function` / `fn`](#step-1--no-new-functions) | |
| 2 | No new parameters | [Signature pairs](#step-2--no-new-parameters) | |
| 3 | No new logic | [New control flow](#step-3--no-new-logic) | |
| 4 | No renames | [Variable renames](#step-4--no-renames) | |
| 5 | No new variables | [New assignments](#step-5--no-new-variables) | |
| 6 | No dead-code removal | [Syntax-only deletes](#step-6--no-dead-code-removal) | |
| 7 | — | [`php -l`](#step-7--php--l) | |

---

## Step 0 — Inventory

```bash
git status -u --short -- '*.php' '*.inc'
git ls-files --others --exclude-standard -- '*.php' '*.inc'
```

**Pass when:** Syntax-only batches have **no** new untracked `*.php` / `*.inc` unless the user explicitly asked for a new file. List any untracked paths in session output.

---

## Step 1 — No new functions

**Forbidden:** New `function`, method, or closure ([SKILL.mdc](SKILL.mdc)).

```bash
git diff --unified=0 HEAD -- '*.php' '*.inc' |
grep -En '^\+[^+].*\b(function|fn)[[:space:]]*(\(|$|[a-zA-Z_])' || true
```

**Pass when:** No output, **or** every hit is explained below.

| Hit | Verdict |
|-----|---------|
| `+function foo` in a hunk with **no** `-function foo` on the same name | **Fail** — new function |
| `+function foo` and `-function foo` in the same hunk (body/return type/`[]` only) | **Review** — not a new name; still must respect [step 2](#step-2--no-new-parameters) |
| `+fn(` | **Fail** — new closure syntax at call site |
| `+    public function __construct` with new promoted properties | **Fail** — new parameters / signature change |

**Avoid**

```php
+function wrapper_x($a) { return legacy_x($a); }
```

**Good**

```php
$y = (float)$x / 100;   // same line, no new function
```

---

## Step 2 — No new parameters

**Forbidden:** Added or renamed arguments ([SKILL.mdc](SKILL.mdc)).

```bash
git diff --unified=0 HEAD -- '*.php' '*.inc' |
grep -En '^[+-][^+-].*\bfunction[[:space:]]+[a-zA-Z_][a-zA-Z0-9_]*[[:space:]]*\(' || true
```

**Pass when:** No `+`/`-` pairs that change the parameter list, return type, or visibility of an existing named function.

**Manual check:** For each function name that appears on both `+` and `-`:

- Same parameter count and names (order may change only if behavior is unchanged — default **fail** unless user approved).
- Return type additions (e.g. `: never`) → **fail** for strict syntax-only unless the batch goal explicitly includes that fix.

---

## Step 3 — No new logic

**Forbidden:** New branches, SQL, or display rules ([SKILL.mdc](SKILL.mdc)).

### 3a — Control flow on added lines

```bash
git diff --unified=0 HEAD -- '*.php' '*.inc' |
grep -En '^\+[^+].*\b(if|elseif|else|for|foreach|while|do|switch|match|try|catch|throw)\b' || true
```

**Pass when:** No hits, **or** each hit is an allowed mechanical replacement (e.g. `foreach` replacing `each`, `isset` / `??` on the **same** line as before).

**Fail:** New `if` blocks wrapping many lines, new `try/catch` in app code, new `switch`.

### 3b — SQL and display (manual)

Review `+` lines that change string literals inside `mysql_*` / PDO / query helpers, or HTML/JSON output text. **Fail** if copy, labels, or query text changes beyond what PHP 8 syntax requires.

---

## Step 4 — No renames

**Forbidden:** Do not rename variables ([SKILL.mdc](SKILL.mdc)).

Git cannot prove renames in one command. Use this review:

```bash
git diff -U1 HEAD -- '*.php' '*.inc'
```

**Fail when:** A hunk renames identifiers (`$row` → `$record`) without a syntax-only need. **Pass** when only casts, `[]`, `??`, `?->`, or `{'prop'}` change on the same variable names.

---

## Step 5 — No new variables

**Forbidden:** Do not introduce new locals, alias temps, or loop-only holders ([SKILL.mdc](SKILL.mdc), [upgrade-sample-code.md](upgrade-sample-code.md) **Loop and list temps**, § `property.nonObject`, § `offsetAccess.nonOffsetAccessible`, § `foreach.nonIterable`). Prefer **same-line** casts / `asObject()` / `asArray()` in `foreach (...)` / `firstRow()` on **existing** names.

### 5a — Assignment lines on `+`

```bash
git diff --unified=0 HEAD -- '*.php' '*.inc' |
grep -En '^\+[^+].*\$[a-zA-Z_][a-zA-Z0-9_]*\s*(\[[^]]*\])?\s*=[^=>]' || true
```

**Pass when:** No output, **or** every hit is explained below. Grep is a **triage list**—confirm each hit in the hunk (`git diff -U1`).

| Hit | Verdict |
|-----|---------|
| `+$cachedList = …`, `+$_item = …`, new alias not on any `-` line in the hunk | **Fail** — new variable |
| `+$y = (float)$x …` with `- $y = $x …` on the same LHS | **Pass** — same name, syntax-only |
| `+$init = asObject($init)` after `global $init` (name already used in file / batch) | **Pass** — reassign existing global |
| `+$row = asObject($row)` replacing one read line (no extra `+$tmp = …`) | **Pass** — reassign on existing name |
| New `+` assignment splits one old line into two with an extra holder | **Fail** — refactor / new local |
| `foreach (… as $x)` new loop variable on a **new** `+foreach` | Covered by [step 3a](#3a--control-flow-on-added-lines); default **fail** unless mechanical `each` → `foreach` |

**Avoid**

```php
-$v = $row->name;
+$row = asObject($row);
+$v = $row->{'name'};
```

```php
+$cachedList = asArray($payload->{'items'});
```

**Good**

```php
-$y = $x / 100;
+$y = (float)$x / 100;
```

```php
-$v = $row->name;
+$v = asObject($row)?->{'name'};
```

```php
global $init;
+$init = asObject($init);
```

### 5b — Same hunk (manual)

For each **Fail** candidate, search the hunk for the same `$name` on a `-` line (assignment or use). No `-` mention and the name is not a known global/`$this` field in scope → **fail**.

---

## Step 6 — No dead-code removal

**Forbidden:** Do not remove dead code unless the user explicitly asks ([SKILL.mdc](SKILL.mdc)).

```bash
git diff --unified=0 HEAD -- '*.php' '*.inc' |
grep -En '^\-[^-].*[^;{}\s]$' | head -20
```

Use the diff hunk context, not grep alone.

**Fail when:** Whole statements, unused branches, or commented blocks disappear without a PHP 8 syntax requirement on the same hunk.

**Good:** Delete `mysql_*` call only when replacing with an existing helper on the same lines (user batch includes that API swap).

---

## Step 7 — `php -l`

```bash
{
  git diff --name-only --diff-filter=ACMR HEAD -- '*.php' '*.inc'
  git ls-files --others --exclude-standard -- '*.php' '*.inc'
} | sort -u |
while IFS= read -r file; do
  [ -n "$file" ] && php -l "$file" || exit 1
done
```

**Pass when:** Every file prints `No syntax errors detected`.

---

## Optional — Human context

```bash
git diff --function-context HEAD -- '*.php' '*.inc'
```

Use before signing off when step 1, 2, or 5 has **Review** / unexplained grep hits.

---

## PowerShell appendix

Run from repo root.

### Step 1 — No new functions (example)

```powershell
git diff --unified=0 HEAD -- '*.php' '*.inc' |
  Select-String -Pattern '^\+\+\+|^\+[^+].*\b(function|fn)\b' |
  Where-Object { $_.Line -notmatch '^\+\+\+' }
```

Re-run steps 2–5 in Git Bash when possible; paired `+`/`-` signature and new-variable review is simpler there.

### Step 5 — No new variables (example)

```powershell
git diff --unified=0 HEAD -- '*.php' '*.inc' |
  Select-String -Pattern '^\+\+\+|^\+[^+].*\$[a-zA-Z_][a-zA-Z0-9_]*\s*(\[[^\]]*\])?\s*=[^=>]' |
  Where-Object { $_.Line -notmatch '^\+\+\+' }
```

Review each match in `git diff -U1` per [step 5b](#5b--same-hunk-manual).

### Step 7 — `php -l` on changed files only

Same file list as bash step 7: modified/added paths from `git diff HEAD` plus untracked `*.php` / `*.inc` (unless user-approved new files).

```powershell
$files = @(
  git diff --name-only --diff-filter=ACMR HEAD -- '*.php' '*.inc'
  git ls-files --others --exclude-standard -- '*.php' '*.inc'
) | Where-Object { $_ } | Sort-Object -Unique

if (-not $files) {
  Write-Host 'No changed PHP/inc files; step 7 skip or pass (empty batch).'
} else {
  $failed = $false
  foreach ($f in $files) {
    php -l $f
    if ($LASTEXITCODE -ne 0) { $failed = $true }
  }
  if ($failed) { exit 1 }
}
```

**Pass when:** Each file prints `No syntax errors detected` (or the list is empty and the batch had no PHP edits).

---

## Quick checklist (agent)

1. Used `git diff … HEAD` for all steps?
2. Untracked PHP list empty (or user-approved)?
3. Each Forbidden row in the session table is **pass** or explained **fail**?
4. `php -l` run on every changed file?
5. Do not merge or claim batch complete while any Forbidden step fails.
