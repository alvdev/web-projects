<!-- graymatter:instructions:begin — managed by `graymatter init`; edits inside this block are overwritten -->
## Memory (GrayMatter)

This project has persistent agent memory via the `graymatter` MCP tools:

- `memory_search` (`agent_id`, `query`) — call at the **start of a task** when prior context might matter.
- `memory_add` (`agent_id`, `text`) — call whenever you learn something **durable**: user preferences, decisions, conventions, gotchas.
- `memory_reflect` (`action`, `agent`, `text`/`target`) — update or forget stale facts. ⚠ takes `agent`, not `agent_id`.
- `checkpoint_save` / `checkpoint_resume` (`agent_id`) — snapshot/restore session state before major refactors or across restarts.

Use a stable `agent_id` of the form `<project>-<role>` (e.g. `myapp-backend`). Store conclusions, not conversation logs. Err on the side of remembering.
<!-- graymatter:instructions:end -->

## Testing

Fast suite (also enforced by a `pre-push` git hook):

- `composer test` — PHPUnit: pure logic, plugin seams, SQLite, Kirby routes (151 tests)
- `bun run test:unit` — Vitest: timezones and SteamDB parsers (30 tests)
- `bun run test:e2e` — Playwright: browser flows against a temp server (7 tests, ~20s)
- `bun run test:all` — all three

The hook is committed at `scripts/git-hooks/pre-push` and symlinked into the repo's git hooks. Reinstall with:

```bash
ln -sf ../../diario.games/scripts/git-hooks/pre-push "$(git rev-parse --show-toplevel)/.git/hooks/pre-push"
```

It runs the fast suite only when the push contains `diario.games/` changes (this is a monorepo). Bypass once with `git push --no-verify`.
