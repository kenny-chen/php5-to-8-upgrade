# CodeGraph (this pack)

Use MCP **`codegraph_explore`** before grep/read when the target repo has `.codegraph/`.

| Task | Query shape |
|------|-------------|
| Shared helper | Symbol name or “Where is `foo` defined?” scoped to `*.inc` |
| Type/cast helpers | `asFloat`, `asInt`, `asBool`, `asString`, `asArray`, `asObject`, `firstRow`, `safeFopen` (if present—see **Shared `*.inc` helpers** in upgrade-sample-code.md) |
| DB wrapper | `executeQuery`, `getDataArray`, `fetchrowHashRef`, `getDBConnection`, `closeDBConnection` in `database.inc` (or equivalent) |
| Blast radius | Who calls symbol X before editing shared bootstrap |

One call returns line-numbered source and call paths; trust indexed files unless a staleness banner lists pending re-index paths.

If not indexed: search the repo for `function Name` in shared `*.inc` only—do not invent new helpers.
