# PHPStan identifiers → fixes (PHP 8.5)

Self-contained catalog for [SKILL.mdc](SKILL.mdc). Every identifier below appears in a typical `_by_identifier.json` PHPStan export (49 types). **Error shape → minimal fix.**

**Examples:** Code blocks use **neutral fictional** names only (`$itemList`, `$item`, `$_item`, `$payload`, `viewState`, etc.)—not production globals such as list/loop variables from the live app. Match **real** names, helpers, and paths in the target repo via [CODEGRAPH.md](CODEGRAPH.md); apply the same **line-level** patterns to PHPStan batch rows.

**Fix order:** (1) **shared `*.inc` helper** if the repo defines it (confirm via [CODEGRAPH.md](CODEGRAPH.md)); (2) **PHP 8.5 syntax** on the same line (`??`, cast, `?->`, etc.). Do not add new helpers in app files—copy the set below into `common.inc` when porting this pack.

**Loop and list temps (v2, all identifiers):** 不要为 PHPStan 新建循环临时量 — do **not** add new loop or list temporaries only to clear PHPStan. This rule applies to every **R** identifier when the fix touches `for`, `foreach`, or a `while` fetch loop.

| | Rule |
|---|------|
| **Must** | Fix on the **same line** as the loop header or the body line PHPStan flags; keep **existing** names (`$data`, `$v`, `$i`, `$_item`). |
| **Must** | Put `asArray()` / `asObject()` / `(array)` **inside** the `foreach (...)` expression or inline in `for` bounds and body (`count(asArray($itemList))`, `asArray($itemList)[$i]`). |
| **Must** | Reassign **existing** globals only after `global` (e.g. `$itemList = asArray($itemList)`). |
| **Must not** | Add list caches before a loop (`$safeList`, `$copyList`, `$cachedList`, …). |
| **Must not** | Add per-iteration aliases (`$line`, `$head`, …) or invent `$_item` when the file never used that name. |
| **Must not** | Split one loop fix into two lines with a new holder unless a `-` line in the same hunk already assigned that **same** `$name`. |

**Validation:** [git-diff-validation.md](git-diff-validation.md) step **5** · Expanded examples: § `property.nonObject`, § `offsetAccess.nonOffsetAccessible`, § `foreach.nonIterable`.

**Tier:** **R** = fix for PHP 8 runtime/syntax · **S** = static/analysis; skip unless user requests cleanup (per SKILL).

| Identifier | T |
|------------|:-:|
| `binaryOp.invalid` | R |
| `property.nonObject` | R |
| `offsetAccess.nonOffsetAccessible` | R |
| `argument.type` | R |
| `variable.undefined` | R |
| `encapsedStringPart.nonString` | R |
| `assignOp.invalid` | R |
| `offsetAccess.invalidOffset` | R |
| `foreach.nonIterable` | R |
| `function.notFound` | R |
| `constant.notFound` | R |
| `arguments.count` | R |
| `echo.nonString` | R |
| `print.nonString` | R |
| `postDec.type` | R |
| `unaryOp.invalid` | R |
| `parameter.requiredAfterOptional` | R |
| `offsetAccess.nonArray` | R |
| `offsetAccess.notFound` | R |
| `callable.nonCallable` | R |
| `isset.offset` | R |
| `isset.variable` | R |
| `cast.int` | R |
| `cast.string` | R |
| `array.invalidKey` | R |
| `class.notFound` | R |
| `whitespace.bom` | R |
| `nullCoalesce.variable` | R |
| `empty.variable` | R |
| `missingType.return` | S |
| `missingType.parameter` | S |
| `expr.resultUnused` | S |
| `equal.alwaysFalse` | S |
| `equal.alwaysTrue` | S |
| `notEqual.alwaysTrue` | S |
| `identical.alwaysFalse` | S |
| `notIdentical.alwaysTrue` | S |
| `deadCode.unreachable` | S |
| `booleanOr.rightAlwaysFalse` | S |
| `booleanOr.rightAlwaysTrue` | S |
| `booleanOr.alwaysFalse` | S |
| `booleanOr.alwaysTrue` | S |
| `booleanAnd.alwaysTrue` | S |
| `booleanAnd.alwaysFalse` | S |
| `booleanAnd.rightAlwaysTrue` | S |
| `booleanNot.alwaysFalse` | S |
| `ternary.alwaysFalse` | S |
| `greater.alwaysTrue` | S |
| `greater.alwaysFalse` | S |

---

## Shared `*.inc` helpers (optional, portable)

When these exist in the project’s shared include (e.g. `common.inc`), use them **before** inline casts/`??`:

| Helper | Use for |
|--------|---------|
| `asFloat($value)` | `mixed` in `/`, `+`, unary `-`, `sqrt()`, etc. |
| `asInt($value)` | `mixed` → int; `--` on mixed |
| `asBool($value)` | `mixed` → bool (legacy truthy strings) |
| `asString($value)` | `mixed` → string (`echo`/`print`, interp, `.`, `cast.string`) |
| `asArray($value)` | `foreach` / array APIs on non-array `mixed` |
| `asObject($value)` | `mixed` → object (`global $init` / `$query`, `property.nonObject` on globals) |
| `firstRow($itemList)` | first element when code used `$itemList[0]` on `mixed` (confirm helper parameter name in target `common.inc` via [CODEGRAPH.md](CODEGRAPH.md)) |
| `safeFopen($path, $mode = 'r')` | `fopen` when missing/unreadable path should stay `false` |
| `getDBConnection($dbconfig)` | open mysqli, set `$init->{"dbh"}` (shared `database.inc`) |
| `executeQuery($query)` | `mysql_query` / `mysqli_query` wrapper on `$init->{"dbh"}` |
| `fetchrowHashRef($result)` | one row as object (`mysqli_fetch_object`) |
| `getDataArray($query)` | query + fetch all rows into an array |
| `closeDBConnection($connection)` | `mysqli_close` wrapper |

**Reference shapes** (copy into target repo once—not per call site):

```php
function asFloat($value) {
    return is_numeric($value) ? (float)$value : 0.0;
}
function asInt($value) {
    return is_numeric($value) ? (int)$value : 0;
}
function asBool($value) {
    $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $result !== null ? $result : false;
}
function asString($value) {
    return is_scalar($value) || $value === null ? (string)$value : '';
}
function asArray($value) {
    return is_array($value) ? $value : [];
}
function asObject($value) {
    return is_object($value) ? $value : new stdClass();
}
function firstRow($itemList) {
    return (is_array($itemList) && isset($itemList[0])) ? $itemList[0] : null;
}
function safeFopen($path, $mode = 'r') {
    if ($mode[0] === 'r' && !is_readable($path)) {
        return false;
    }
    $fh = fopen($path, $mode);
    return $fh === false ? false : $fh;
}
```

**DB reference shapes** (typical `include/database.inc`—confirm names in target repo via [CODEGRAPH.md](CODEGRAPH.md); copy once per project, not per call site):

```php
function getDBConnection($dbconfig) {
    global $init;
    $settings = $GLOBALS['appSettings'][$dbconfig];
    $con = mysqli_connect(
        $settings['host'],
        $settings['user'],
        $settings['credential']
    );
    mysqli_select_db($con, $settings['database']);
    if (!$con) {
        die('Could not connect (' . $dbconfig . '): ' . mysqli_connect_error());
    }
    $init->{"dbh"} = $con;
    $init->{"dbconfig"} = $dbconfig;
    return $con;
}
function executeQuery($query) {
    global $init;
    return mysqli_query($init->{"dbh"}, $query);
}
function fetchrowHashRef($result) {
    return mysqli_fetch_object($result);
}
function getDataArray($query) {
    $result = executeQuery($query);
    $data = [];
    while ($item = fetchrowHashRef($result)) {
        $data[] = $item;
    }
    return $data;
}
function closeDBConnection($connection) {
    return mysqli_close($connection);
}
```

**`mysql_*` fix order:** (1) shared `database.inc` (or equivalent) helper when it exists; (2) **`mysqli_*` on `$init->{"dbh"}`** with `global $init;` on touched lines; (3) only if the file already uses a local link variable, keep that name—do not add new connection variables.

**Example contract (every R identifier):** When a row in the helper table applies, that section **must** show **`GOOD (helper)`** before **`GOOD (syntax)`**. Use **`GOOD (syntax only)`** only when no shared helper fits.

| Helper | Identifiers (R tier) |
|--------|----------------------|
| `asFloat($value)` | `binaryOp.invalid`, `argument.type`, `assignOp.invalid`, `unaryOp.invalid` |
| `asInt($value)` | `argument.type`, `assignOp.invalid`, `postDec.type`, `cast.int` |
| `asBool($value)` | `argument.type` (bool params / legacy flags) |
| `asString($value)` | `cast.string`, `echo.nonString`, `print.nonString`, `encapsedStringPart.nonString`, `binaryOp.invalid` (`.`), `argument.type` (string params) |
| `asArray($value)` | `foreach.nonIterable`, `nullCoalesce.variable`, `isset.offset` |
| `asObject($value)` | `property.nonObject` (when `global $init` / `$query` or other mixed must be an object) |
| `firstRow($itemList)` | `offsetAccess.nonOffsetAccessible`, `offsetAccess.nonArray`, `property.nonObject` (when one element came from a mixed list) |
| `safeFopen($path, $mode)` | `function.notFound` only when the missing symbol is `fopen` and the call is unreadable-path sensitive (same pattern as mixed path guards) |
| `executeQuery` / `getDataArray` / `fetchrowHashRef` / `getDBConnection` / `closeDBConnection` | `function.notFound` (`mysql_*`), mysql_* section |
| *(no helper)* | `function.notFound` (`split`), PHPCompatibility `split` removed — **split() → explode() / preg_split()** section |

Snippet pattern in sections below: **`GOOD (helper)`** then **`GOOD (syntax)`** when a helper applies; otherwise **`GOOD (syntax only)`**.

**Include paths:** `require_once`, `require`, `include_once`, and `include` **must** use **`GOOD (syntax only)`** on that line—no `asString` / `asArray` in the path (see § `offsetAccess.nonOffsetAccessible`).

**`safeFopen` (when call site is being fixed for mixed/unreadable paths):**

```php
// BAD
$fh = fopen($path, 'r');

// GOOD (helper)
$fh = safeFopen($path, 'r');

// GOOD (syntax) — match existing guard style in file
$fh = is_readable($path) ? fopen($path, 'r') : false;
```

**`asBool` (when legacy string flags must stay valid):**

```php
// GOOD (helper)
if (asBool($entry['active'])) { }

// GOOD (syntax)
if (filter_var($entry['active'], FILTER_VALIDATE_BOOLEAN)) { }
```

---

## `binaryOp.invalid`

**Message:** `Binary operation "/" between mixed and N results in an error.`

```php
// BAD
$y = $x / 100;
$path = $cfg['basePath'].'lib/bootstrap.inc';

// GOOD (helper)
$y = asFloat($x) / 100;
$path = asString($cfg['basePath'] ?? '').'lib/bootstrap.inc';

// GOOD (syntax)
$y = (float)$x / 100;
$path = (string)($cfg['basePath'] ?? '').'lib/bootstrap.inc';
```

---

## `property.nonObject`

**Message:** `Cannot access property $col on mixed.`

**Style (v2, minimal diff):** Use **`asObject()` / `firstRow()` on the same line** as the `->` read. Do **not** add loop-only locals (`$_item`, `$cachedList`, …) or extra “alias” variables (**Loop and list temps** above). **No new function parameters.** Reassigning **existing** globals (`$init = asObject($init)`) after `global` is allowed.

```php
// BAD
$v = $entry->name;
$entry->active = 1;
$v = $itemList->name;
global $query;
$query->{"page"} = 1;

// BAD — new locals only for Stan (forbidden in v2 batches)
$cachedList = asArray($payload->{"items"});
for ($i = 0; $i < count($cachedList); $i++) {
    $_item = asObject($cachedList[$i]);
    $label = legacyFormat($_item->{"title"});
}

// GOOD (helper) — first element of a mixed list (keep the file’s existing variable name)
$v = firstRow($itemList)?->{'name'};

// GOOD (helper) — after global $init / $query (mixed → object)
global $init, $query;
$init = asObject($init);
$query = asObject($query);
$query->{'page'} = 1;

// GOOD (helper) — nested decode / list index: wrap the **mixed receiver** inline
$title = asString(asObject(asArray($payload->{"items"})[$i])->{"title"});
if (asObject(asArray($payload->{"items"})[$i])->{"status"} == "") { }
$code = asObject(asObject(asArray($payload->{"items"})[$i])->{"meta"})->{"code"};
for ($i = count(asArray($payload->{"items"})) - 1; $i >= 0; $i--) {
    $qty = floor(abs(asFloat(asObject(asArray($payload->{"items"})[$i])->{"qty"})));
}

// GOOD (helper) — mixed element from a result list
$score = abs(asFloat(asObject($itemList[$i])->{"score"}) / $scale);

// GOOD (syntax) — mixed object already in hand (same variable name)
$v = $item->{'name'};
$item->{'active'} = 1;
$v = $item?->{'name'};
```

---

## `offsetAccess.nonOffsetAccessible`

**Message:** `Cannot access offset 'key' on mixed` / `offset 0 on mixed` / `offset int<0, max> on mixed`.

**Style (v2, minimal diff):** Coerce on the **same line** as the offset read (`asArray($itemList)[$i]`, `firstRow($list)`, `??` on the existing container). Do **not** add list temps (`$safeList`, `$copyList`, `$cachedList`, …) or per-index aliases (`$line`, `$head`, …) only for PHPStan (**Loop and list temps** above). Reassigning **existing** globals after `global` (`$itemList = asArray($itemList)`, `$viewState = asArray($viewState)`) is allowed—same rule as [git-diff-validation.md](git-diff-validation.md) step **5**.

```php
// BAD
$p = $cfg['basePath'];
$item = $list[0];
require_once $GLOBALS["config"]["basePath"].'lib/bootstrap.inc';
$total = $itemList[0]->{"count"};
$_item = $itemList[$i];
$viewState['bodyHtml'] = $html;
$ts = $itemList[0]->{"created"};
$entry = $lookup[normalizeKey($record->{'id'})];

// BAD — new locals only for Stan (forbidden in v2 batches)
$safeList = asArray($itemList);
$total = asObject($safeList[0])->{"count"};
$copyList = asArray($itemList);
$head = firstRow($copyList);
$ts = asObject($head)->{"created"};
$cachedList = asArray($itemList);
for ($i = 0; $i < count($cachedList); $i++) {
    $line = asObject($cachedList[$i]);
    $label = asString($line['name'] ?? '');
}
// BAD — inventing $_item when the file never used that name
for ($i = 0; $i < count(asArray($itemList)); $i++) {
    $_item = asObject(asArray($itemList)[$i]);
    $label = asString($_item['name'] ?? '');
}

// GOOD (helper)
$item = firstRow($list);
$total = asObject(firstRow($itemList))->{"count"};
$_item = asObject(asArray($itemList)[$i]); // file already had `$_item = $itemList[$i];`
$ts = asObject(firstRow($itemList))->{"created"};
$entry = asArray($lookup)[normalizeKey($record->{'id'})];

// GOOD (helper) — mixed global array written by key: coerce once after `global`
global $viewState, $lookup, $registry, $itemList;
$viewState = asArray($viewState);
$lookup = asArray($lookup);
$registry = asArray($registry);
$itemList = asArray($itemList);
$viewState['bodyHtml'] = $html;
$viewState[$type.'selected'] = 'selected';

// GOOD (helper) — value already at offset (use `??` on mixed container)
$p = asString($cfg['basePath'] ?? '');
$path = asString(asArray($GLOBALS["config"])['basePath'] ?? '');

// GOOD (syntax)
$p = (string)($cfg['basePath'] ?? '');
$item = (is_array($list) && isset($list[0])) ? $list[0] : null;

// GOOD (syntax only) — `require_once` / `include*` path from mixed: PHP 8.5 on the include line; no helper
require_once (string)((is_array($GLOBALS['config'] ?? null) ? ($GLOBALS['config']['basePath'] ?? '') : '')).'lib/bootstrap.inc';
require_once (string)($cfg['basePath'] ?? '').'lib/bootstrap.inc';

// GOOD (helper) — indexed read / loop: inline on existing `$itemList` / `$i` (no list temp)
$label = asString(asArray($itemList)[$i]['name'] ?? '');
for ($i = 0; $i < count(asArray($itemList)); $i++) {
    $label = asString(asObject(asArray($itemList)[$i])->{'name'} ?? '');
}

// GOOD (helper) — file already used `$_item` in the loop body: reassign that name only
for ($i = 0; $i < count(asArray($itemList)); $i++) {
    $_item = asObject(asArray($itemList)[$i]);
    $label = asString($_item['name'] ?? '');
}
```

**Batch notes (v2):** Prefer **one** `$_item = asObject(asArray($itemList)[$i])` per loop iteration when the file already uses that loop variable; otherwise inline `asObject(asArray($itemList)[$i])` on touched lines only—no new loop-local names just for Stan (see **BAD — new locals** above).

**Include paths:** On `require_once`, `require`, `include_once`, and `include`, fix mixed path pieces with **`GOOD (syntax only)`** on that line (`is_array` + `??`, `(string)` cast)—**do not** use `asString`, `asArray`, or `firstRow` in the path expression.

---

## `argument.type`

**Message:** `Parameter #N of function foo expects float, mixed given.`

```php
// BAD
sqrt($t);
needsInt($mixed);
needsArray($mixed);
needsBool($flag);
needsString($mixed);

// GOOD (helper)
sqrt(asFloat($t));
needsInt(asInt($mixed));
needsArray(asArray($mixed));
needsBool(asBool($flag));
needsString(asString($mixed));

// GOOD (syntax)
sqrt((float)$t);
needsInt(is_numeric($mixed) ? (int)$mixed : 0);
needsArray(is_array($mixed) ? $mixed : []);
needsBool(filter_var($flag, FILTER_VALIDATE_BOOLEAN));
```

---

## `variable.undefined`

**Message:** `Undefined variable: $x` / `might not be defined`.

```php
// BAD
function f($m) {
    if ($m === 'a') { $r = 1; }
    return $r;
}

// GOOD (syntax only) — no coercion helper; initialize at scope entry
function f($m) {
    $r = 0;
    if ($m === 'a') { $r = 1; }
    return $r;
}
```

---

## `encapsedStringPart.nonString`

**Message:** `Part $x (mixed) of encapsed string cannot be cast to string.`

```php
// BAD
$s = "id=$id";
echo "$label";

// GOOD (helper)
$s = 'id=' . asString($id);
echo asString($label);

// GOOD (syntax)
$s = "id={(string)$id}";
echo (string)$label;
```

---

## `assignOp.invalid`

**Message:** Compound assign on `mixed`.

```php
// BAD
$n += $x;
$count += $step;

// GOOD (helper)
$n = asFloat($n) + asFloat($x);
$count = asInt($count) + asInt($step);

// GOOD (syntax)
$n = (float)$n + (float)$x;
$count = (int)$count + (int)$step;
```

---

## `offsetAccess.invalidOffset`

**Message:** Invalid offset type / curly offset.

```php
// BAD
$a{$k} = 1;

// GOOD (syntax only) — bracket form only; no helper
$a[$k] = 1;
```

---

## `foreach.nonIterable`

**Message:** `mixed supplied for foreach`.

**Style (v2, minimal diff):** Coerce the iterable on the **`foreach` line** (`foreach (asArray($data) as $v)`). Do **not** assign `asArray($data)` to a new list temp first. Keep **existing** loop variables (`$v`, `$k`). See **Loop and list temps** in the header and [git-diff-validation.md](git-diff-validation.md) step **5**.

```php
// BAD — mixed iterable (error remains)
foreach ($data as $v) { }

// BAD — new locals only for Stan (forbidden in v2 batches)
$safeList = asArray($data);
foreach ($safeList as $v) { }

$copyList = asArray($itemList);
foreach ($copyList as $k => $v) { }

// BAD — list temp + new body alias
$cachedList = asArray($payload->{"items"});
foreach ($cachedList as $row) {
    $label = asString($row['name'] ?? '');
}

// BAD — rename loop variable only for Stan (`$v` → `$_item`)
foreach (asArray($itemList) as $_item) {
    $label = asString($_item['name'] ?? '');
}

// GOOD (helper) — coerce inside foreach; same `$v` (empty loop when not array)
foreach (asArray($data) as $v) { }

// GOOD (helper) — key + value; same names
foreach (asArray($itemList) as $k => $v) { }

// GOOD (syntax)
foreach ((array)$data as $v) { }

// GOOD (helper) — existing global mixed list: reassign that name after `global`
global $itemList;
$itemList = asArray($itemList);
foreach ($itemList as $v) { }

// GOOD (helper) — body uses property on `$v`: inline on touched line (no `$line` temp)
foreach (asArray($itemList) as $v) {
    $label = asString(asObject($v)->{'name'} ?? '');
}

// GOOD (helper) — file already used `$_item` as the foreach value
foreach (asArray($itemList) as $_item) {
    $label = asString($_item['name'] ?? '');
}

// GOOD (syntax) — mechanical `each()` → `foreach` (git-diff-validation step 3a)
// while (list($k, $v) = each($data)) { ... }
foreach (asArray($data) as $k => $v) { ... }
```

**Batch notes (v2):** When the only issue is `mixed supplied for foreach`, change **only** the `foreach (...)` line. Do **not** add `+$list = asArray(...)` on the line above unless the hunk’s `-` line already had that assignment on the **same** `$list` name.

---

## `function.notFound`

**Message:** `Function mysql_query not found` / typo. For **`Function split not found`** (or PHPCompatibility `split()` removed), see **split() → explode() / preg_split()** below—not `mysqli`.

```php
// BAD
mysql_query($sql);
wrngName($sql);
$fh = fopen($path, 'r');

// GOOD (helper) — shared database.inc (see CODEGRAPH.md)
$r = executeQuery($sql);
$itemList = getDataArray($sql);
$fh = safeFopen($path, 'r');

// GOOD (syntax) — no DB helper; use app connection on $init (see mysql_* section)
global $init;
$r = mysqli_query($init->{"dbh"}, $sql);
$fh = is_readable($path) ? fopen($path, 'r') : false;
```

---

## `split()` → `explode()` / `preg_split()`

**When:** PHPCompatibility `Function split() is deprecated since PHP 5.3 and removed since PHP 7.0`; runtime / `function.notFound` on PHP 8.x. **Syntax/API swap only**—same delimiter behavior on the touched line.

**Decision (minimal):**

1. **Literal string delimiter** (no regex metacharacters in the pattern, e.g. `"_"`, `":"`, `"|"`) → **`explode($delimiter, $string, $limit)`**. Pass **`$limit`** when legacy `split` had a third argument.
2. **Regex pattern** in the first argument (e.g. `"[;,]"`, `"/\s+/"`) → **`preg_split($pattern, $string, $limit)`** with the **same** pattern string PHP 5 used (confirm in the hunk; do not invent a new pattern).
3. Change **only** the `split(...)` call (or the `list(...) = split(...)` line). Do not add variables, wrappers, or new branches ([git-diff-validation.md](git-diff-validation.md) steps **3** and **5**).

**Repo examples** (search / data / chart JSON under `hk/data/`):

```php
// BAD (PHP 8) — order token: index_sortDir
$orders = split("_", $query->{"order"});

// GOOD (syntax) — same array shape for $orders[0], $orders[1], isset(...)
$orders = explode("_", $query->{"order"});
```

```php
// BAD (PHP 8) — limit 2: first segment + rest (bull/bear ratio string)
list($bull, $bear) = split(":", $ratio_data[0]->{"ratio"}, 2);

// GOOD (syntax) — explode limit matches legacy split limit
list($bull, $bear) = explode(":", $ratio_data[0]->{"ratio"}, 2);
```

```php
// BAD (PHP 8) — regex delimiter (illustrative; use only when the removed call used a pattern)
$parts = split("[;,]", $line);

// GOOD (syntax) — preg_split when split’s first arg was a regex
$parts = preg_split("/[;,]/", $line);
```

**Do not:** use `explode` when the old first argument was a regex (behavior diverges). **Do not:** add a `split()` polyfill function.

**Validation:** `php -l` on each touched file; re-run `composer compat:report` and confirm `RemovedFunctions.split` count drops.

---

## `mysql_*` → `mysqli_*` (light map)

**When:** PHPStan/runtime `Function mysql_* not found`. The `mysql` extension was removed in PHP 7; on PHP 8.5 use **`mysqli_*`** with the same behavior—syntax/API swap only.

**Rules (minimal):**

1. **Helpers first:** `executeQuery`, `getDataArray`, `fetchrowHashRef`, `getDBConnection`, `closeDBConnection` in shared `database.inc` (names vary—confirm via [CODEGRAPH.md](CODEGRAPH.md)).
2. **No helper on the touched line:** `global $init;` then pass **`$init->{"dbh"}`** as the mysqli link (same property `getDBConnection` sets in the reference `database.inc` above).
3. Same basename: `mysql_query` → `mysqli_query`, `mysql_fetch_assoc` → `mysqli_fetch_assoc`, etc.
4. **Flip argument order** when the link and a string/query appear together (shown below).
5. After `mysqli_connect`, use `mysqli_error($link)` / `mysqli_errno($link)` on the link handle; on failed connect, `mysqli_connect_error()`.
6. **Fetch/meta** calls take the result only—no link: `mysqli_fetch_array($result)`.
7. Do not add new DB wrapper functions in app files—only call **existing** shared helpers or rename to `mysqli_*` in place.

**Call-site patterns (helper before syntax):**

```php
// BAD
$r = mysql_query($sql);
while ($ref = mysql_fetch_array($r)) { }

// GOOD (helper) — query + loop body unchanged except fetch helper
$r = executeQuery($sql);
while ($ref = fetchrowHashRef($r)) { } // object rows; use mysqli_fetch_array below if legacy needed assoc/array

// GOOD (helper) — replace manual query+loop when the whole list is needed
$data = getDataArray($sql);

// GOOD (syntax) — mysqli on $init->{"dbh"}
global $init;
$r = mysqli_query($init->{"dbh"}, $sql);
while ($ref = mysqli_fetch_array($r)) { }
```

```php
// BAD
mysql_real_escape_string($s);
mysql_affected_rows();
mysql_insert_id();
mysql_close($link);

// GOOD (helper) — `closeDBConnection` only; no escape/rows helpers in this pack
closeDBConnection($link);

// GOOD (syntax) — always pass $init->{"dbh"} where mysql_* took a link (explicit or implicit)
global $init;
$safe = mysqli_real_escape_string($init->{"dbh"}, $s);
$n = mysqli_affected_rows($init->{"dbh"});
$id = mysqli_insert_id($init->{"dbh"});
mysqli_close($init->{"dbh"});
```

**Argument order flips (only these need extra care):**

| `mysql_*` | `mysqli_*` |
|-----------|------------|
| `mysql_query($sql, $link)` | `mysqli_query($link, $sql)` |
| `mysql_select_db($db, $link)` | `mysqli_select_db($link, $db)` |
| `mysql_real_escape_string($s, $link)` | `mysqli_real_escape_string($link, $s)` |
| `mysql_set_charset($charset, $link)` | `mysqli_set_charset($link, $charset)` |

With **`$init->{"dbh"}`**, the flipped form is e.g. `mysqli_query($init->{"dbh"}, $sql)`.

**Common 1:1 renames (same argument order as `mysql_*`):**

| `mysql_*` | `mysqli_*` |
|-----------|------------|
| `mysql_connect` | `mysqli_connect` (optional 4th arg: database name); prefer existing `getDBConnection($dbconfig)` |
| `mysql_close` | `mysqli_close` / `closeDBConnection($connection)` when defined |
| `mysql_ping` | `mysqli_ping` |
| `mysql_error` / `mysql_errno` | `mysqli_error` / `mysqli_errno` on `$init->{"dbh"}` |
| `mysql_affected_rows` / `mysql_insert_id` | `mysqli_affected_rows` / `mysqli_insert_id` on `$init->{"dbh"}` |
| `mysql_num_rows` / `mysql_num_fields` | `mysqli_num_rows` / `mysqli_num_fields` |
| `mysql_fetch_row` / `_assoc` / `_array` / `_object` | same with `mysqli_` |
| `mysql_data_seek` / `mysql_free_result` | `mysqli_data_seek` / `mysqli_free_result` |
| `mysql_info` / `mysql_stat` | `mysqli_info` / `mysqli_stat` on `$init->{"dbh"}` |

**No direct mysqli twin—run SQL via `mysqli_query($init->{"dbh"}, …)`:** `mysql_create_db`, `mysql_drop_db`, `mysql_list_*`, `mysql_db_query` (select db then query), `mysql_tablename`, `mysql_db_name`.

**Replace `mysql_result($res, $rowIndex, $field)`:** `mysqli_data_seek($res, $rowIndex)` then read from `mysqli_fetch_row` / `mysqli_fetch_assoc`; do not add new helper functions unless the project already has one.

**Do not port:** `mysql_escape_string($s)` → use `mysqli_real_escape_string($init->{"dbh"}, $s)`.

**Persistent connect:** `mysql_pconnect(...)` → `mysqli_connect('p:' . $host, $user, $credential, ...)`.

```php
// BAD (PHP 8) — legacy mysql ext
mysql_query("SET NAMES utf8");
$r = mysql_query($sql);
$record = mysql_fetch_assoc($r);

// GOOD (helper)
executeQuery("SET NAMES utf8");
$r = executeQuery($sql);
$record = mysqli_fetch_assoc($r); // no helper for assoc—mysqli_* on $result only

// GOOD (syntax) — bootstrap already set $init->{"dbh"} (e.g. getDBConnection)
global $init;
mysqli_query($init->{"dbh"}, "SET NAMES utf8");
$r = mysqli_query($init->{"dbh"}, $sql);
$record = mysqli_fetch_assoc($r);
```

```php
// BAD — missing link (invalid mysqli; mysql implicit link is gone)
mysqli_query($sql);

// GOOD (helper)
executeQuery($sql);

// GOOD (syntax)
global $init;
mysqli_query($init->{"dbh"}, $sql);
```

---

## `constant.notFound`

**Message:** `Constant nmll not found` (bare name used as constant).

```php
// BAD
$val->{nmll};
$x = nmll;

// GOOD (syntax only) — quote bare name as string key; no helper
$val->{'nmll'};
$x = $item->{'nmll'};
```

---

## `arguments.count`

**Message:** `Function foo invoked with N parameters, M required.`

```php
// BAD
getSeries();
json_decode($raw, $badExtra);

// GOOD (syntax only) — fix arity; no shared helper
getSeries($a, $b, $c);
json_decode($raw, true);
```

---

## `echo.nonString` / `print.nonString`

**Message:** `Parameter #1 (mixed) of echo cannot be converted to string.`

```php
// BAD
echo $mixed;
print $mixed;

// GOOD (helper)
echo asString($mixed);
print asString($mixed);

// GOOD (syntax)
echo (string)$mixed;
print (string)$mixed;
```

---

## `postDec.type`

**Message:** `Cannot use -- on mixed.`

```php
// BAD
$x--;

// GOOD (helper)
$x = asInt($x) - 1;

// GOOD (syntax)
$x = (int)$x - 1;
```

---

## `unaryOp.invalid`

**Message:** `Unary operation "-" on mixed results in an error.`

```php
// BAD
$y = -$x;

// GOOD (helper)
$y = -asFloat($x);

// GOOD (syntax)
$y = -(float)$x;
```

---

## `parameter.requiredAfterOptional`

**Message:** `Required parameter follows optional parameter`.

```php
// BAD
function f($a, $opt = 1, $req) { }

// GOOD (syntax only) — reorder parameters; no helper
function f($a, $req, $opt = 1) { }
```

---

## `offsetAccess.nonArray`

**Message:** `Cannot use array destructuring on list<string>|false.`

```php
// BAD
[$a, $b] = explode(',', $line);

// GOOD (helper) — same failure path as before when false
$parts = explode(',', $line);
$a = firstRow($parts) ?? '';
$b = (is_array($parts) && isset($parts[1])) ? $parts[1] : '';

// GOOD (syntax)
$parts = explode(',', $line);
$a = $parts[0] ?? '';
$b = $parts[1] ?? '';
```

---

## `offsetAccess.notFound`

**Message:** `Offset 'name' might not exist on array{...}.`

```php
// BAD
$v = $item['name'];

// GOOD (syntax only) — `??` default; no helper (item is already a typed array)
$v = $item['name'] ?? '';
```

---

## `callable.nonCallable`

**Message:** `Trying to invoke mixed but it's not a callable.`

```php
// BAD
$cb($arg);

// GOOD (syntax only) — guard only if file already used this pattern; no helper
if (is_callable($cb)) { $cb($arg); }
```

---

## `isset.offset`

**Message:** `Offset mixed on array{} in isset() does not exist.`

```php
// BAD
isset($arr[$dynamicKey])

// GOOD (helper)
array_key_exists((string)$dynamicKey, asArray($arr))

// GOOD (syntax)
is_array($arr) && array_key_exists((string)$dynamicKey, $arr)
```

Use only when the line is already being fixed; prefer `??` with same default as legacy.

---

## `isset.variable`

**Message:** Variable in `isset()` never defined.

```php
// BAD
if (isset($x)) { }

// GOOD (syntax only) — one initializer at scope start (same as legacy “unset”)
$x = null;
if (isset($x)) { }
```

---

## `cast.int` / `cast.string`

**Message:** `Cannot cast mixed to int.` / `Cannot cast mixed to string.`

```php
// BAD
$n = (int)$mixed;
$s = (string)$mixed;

// GOOD (helper)
$n = asInt($mixed);
$s = asString($mixed);

// GOOD (syntax)
$n = is_numeric($mixed) ? (int)$mixed : 0;
$s = is_scalar($mixed) || $mixed === null ? (string)$mixed : '';
```

Non-scalars default: `asInt` / `asFloat` → `0`; `asString` → `''`.

---

## `array.invalidKey`

**Message:** `Possibly invalid array key type mixed.`

```php
// BAD
$arr[$key] = $v;

// GOOD (syntax only) — normalize key type; no array-key helper
$arr[(string)$key] = $v;
```

---

## `class.notFound`

**Message:** `Instantiated class Foo not found.`

```php
// BAD
$p = new Foo();

// GOOD (syntax only) — require existing class file already used in repo; no new class
require_once 'existing/path/Foo.php';
$p = new Foo();
```

---

## `whitespace.bom`

**Message:** `File begins with UTF-8 BOM character.`

**Fix:** Re-save the file as **UTF-8 without BOM** (editor/CLI). No PHP logic change.

---

## `nullCoalesce.variable`

**Message:** `Variable $x on left side of ?? is never defined.`

```php
// BAD
$v = $opts ?? [];

// GOOD (helper) — when value may be non-array
$opts = asArray($opts ?? []);
$v = $opts;

// GOOD (syntax)
$opts = $opts ?? [];
$v = $opts;
```

Or assign the default once at scope entry, then use `$opts` without `??` on the same variable.

---

## `empty.variable`

**Message:** `Variable in empty() is undefined.`

```php
// BAD
if (empty($x)) { }

// GOOD (syntax only) — initialize before empty(); no helper
$x = $x ?? null;
if (empty($x)) { }
```

---

## Static tier (S) — default: skip per SKILL

| Identifier | If user requests cleanup |
|------------|-------------------------|
| `missingType.return` | Do not add return types in syntax-only batches (signature change). |
| `missingType.parameter` | Do not add param types without user approval. |
| `expr.resultUnused` | Do not remove; avoid `(void)` unless already used in file. |
| `deadCode.unreachable` | Do not delete unreachable code per SKILL. |
| `equal.always*` / `notEqual.always*` / `identical.always*` / `notIdentical.always*` | PHPStan path proof; no change unless fixing sibling **R** issue on same line. |
| `booleanOr.*` / `booleanAnd.*` / `booleanNot.alwaysFalse` | Simplify condition only if it removes no branches and user allows. |
| `ternary.alwaysFalse` / `greater.always*` | Same: no churn; fix co-located **R** issue only. |

---

## After each batch

[SKILL.mdc](SKILL.mdc) validation: `git diff HEAD`, forbidden `+` scan, `php -l` on touched `*.php` / `*.inc`.
