## Modernize PHP without risking the business.

![PHP5 to 8 upgrade workflow screenshot](assets/screen008-20261118a.jpg)

> [PHP2Shel](https://en.wikipedia.org/wiki/PHP2Shell) security risks are driving companies to upgrade, and [php5-to-8-upgrade](https://github.com/kenny-chen/php5-to-8-upgrade) makes framework modernization as easy as a software update.

## Beneficiaries

![php5-to-8-upgrade GitHub repository](assets/github-20261119a.jpg)

[php5-to-8-upgrade](https://github.com/kenny-chen/php5-to-8-upgrade) helps financial firms upgrade important old PHP systems in 3 days. Subagents provide 3× engineering capacity while keeping 100% of domain knowledge and systems stable.

## Build Reusable Business AI with AWS Kiro: A 7-Step Plan

### ① Structure knowledge for secure reuse

![Step 1: Structure knowledge for secure reuse](assets/kiro-01-command-skills-rules-20261119a.jpg)

First, write industry workflows in `./command.md`, industry knowledge in `./skills.md`, industry principles in `./rules.md`, and reusable industry workflows in `./subagent.md`. Split industry knowledge across these four components to remove sensitive data and support reuse.

### ② Templates keep AI output consistent

![Step 2: Templates keep AI output consistent](assets/kiro-02-templates-consistent-output-20261119a.jpg)

Second, prompt engineering skills should generate templates for these four components. The five most important template elements are: (1) professional role, (2) expected goal, (3) execution process, (4) good vs. bad examples, and (5) quality checks.

### ③ Shared tools produce stable code

![Step 3: Shared tools produce stable code](assets/kiro-03-shared-tools-stable-code-20261119a.jpg)

Third, build the AI tool library in `.script/tool.py`. The AI must use this library instead of creating `_temp.py` files during development. This helps the AI deliver stable, high-quality code.

1. Design workflows and calling methods that AI can easily understand. Do not force the AI to use complex parameters it handles poorly. Human preferences vary and may lead to poor workflow design.

2. Use the existing tool library first. Do not let the AI create its own tools. This improves stability and saves tokens for high-value business goals.

### ④ Domain documents connect business workflows

Fourth, write business domain-driven `.md` documents in `.docs/`. Use `MCP` to select database tables and map them to business constants and workflows. The goal is to capture domain knowledge during development by using Domain-Driven Design (`DDD`) templates.

### ⑤ Prompt standards align different models

![Step 5: Prompt standards align different models](assets/kiro-05-writing-cursor-components-20261119a.jpg)

Fifth, `.skills/writing-cursor-components.mdc` contains the distilled prompt engineering standards. You must use these standards when running `/modify-rules`, `/modify-skills`, `/modify-workflows`, and `/modify-sub-agent`. The goal is to create general-purpose components that deliver the same quality across different base models.

### ⑥ Goal-based rules simplify AI guidance

![Step 6: Goal-based rules simplify AI guidance](assets/kiro-06-modify-rules-by-goal-20261119a.jpg)

Sixth, organize the `/modify-rules` architecture by goal. For example, distill high-quality coding standards into `./rules/code-reference.md`. Distill code audit standards into `./rules/code-diff-validation.md`. Organize rules by goal, not by system feature or business workflow.

### ⑦ LLM tools strengthen engineering results

```json
{
  "name": "php5-to-8-upgrade",
  "description": "scans your old PHP code and helps you fix it so it runs on PHP 8.0+",
  "scripts": {
    "compat:report": "php tools/generate-compat-reports.php",
    "compat:phpcbf": "php tools/generate-phpcbf-reports.php",
    "rector:report": "php tools/generate-rector-reports.php",
    "stan:report": "php tools/generate-stan-reports.php",
    "stan:batch": "php tools/read-stan-batch-slice.php",
    "codegraph:init": "codegraph init --local-only",
    "codegraph:sync": "codegraph sync"
  }
}
```

Seventh, let the AI use LLM-specific tools. The goal is to achieve: **Engineering + LLM > 2**. For example:

1. Use `CodeGraph` to understand call relationships.

2. Use Abstract Syntax Trees (`ASTs`) to check code quality.

3. Use codebase `RAG` to compare multiple projects.

4. Use Cline’s `referenceMap` to load `.md` skills reliably.

---

[Kenny Chan](https://www.linkedin.com/in/kennychenkk/)

SR 0.73 | SoR 3.2 | PF 1.8 | WR 58% | Skew +0.44 | 8Y Max DD 18% | Aggressive Convex Macro Alpha

Top skills: Macro Trading • Structured Brokerage & Risk • QuantAI Solutions
