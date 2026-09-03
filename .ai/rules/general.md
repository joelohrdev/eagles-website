---
paths:
  - '**'
---

# General

## Commit straight to main — no feature branches
This is a solo repo. Commit directly to `main` when asked to commit. Do not create a feature branch first, and do not open PRs, unless the user explicitly asks for one. (Owner feedback, 2026-09-03: a branch for a one-file doc change was unwanted overhead.)

## No co-author or session trailers in commit messages
Commit messages carry the subject and body only. Never add `Co-Authored-By:`, `Claude-Session:`, or any other agent attribution trailer, and no "Generated with Claude Code" footer in PRs. (Owner instruction, 2026-09-03.) See also: commit straight to main, no feature branches.
