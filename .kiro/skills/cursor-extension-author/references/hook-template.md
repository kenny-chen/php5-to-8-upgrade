# Hook Template

Kiro hooks are configured in `.kiro/hooks.json` for a project. Hook programs exchange JSON over standard input and output.

## Design flow

1. Pick the narrowest event.
2. Define the input fields used by the script.
3. Define allow, block, or observe behavior.
4. Set a timeout.
5. Keep logs free of secrets.
6. Test allowed, blocked, empty, invalid, and timeout cases.

## Configuration shape

Use the exact schema supported by the current Kiro version. Verify it in the current Kiro Hooks docs before writing a final config. A safe project layout is:

```text
.kiro/
├── hooks.json
└── hooks/
    └── <hook-name>.py
```

## Script pattern

```python
#!/usr/bin/env python3
import json
import sys


def main() -> int:
    try:
        payload = json.load(sys.stdin)
    except json.JSONDecodeError:
        print(json.dumps({"error": "Invalid JSON input"}))
        return 2

    # Read only the fields required by this hook.
    # Return the exact response shape required by the event.
    print(json.dumps({"ok": True}))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
```

## Hook rules

- Prefer Python or a small shell script with no extra packages.
- Keep output deterministic.
- Send logs to standard error when standard output is reserved for JSON.
- Never print tokens, environment values, or full secret-bearing commands.
- Use an explicit timeout for child processes and network calls.
- Block only for a clear, explainable reason.
- Return a short fix message when blocking.
- Do not silently allow malformed input in a security gate.
- Do not place model-style advice inside a Hook. Put that in a Rule.