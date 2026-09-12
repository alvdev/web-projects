import { execSync, spawnSync } from "node:child_process";
import { existsSync } from "node:fs";
import { join } from "node:path";
import { PROJECT_ROOT } from "./deploy";
import { TRANSLATION_LOCALES } from "./content";

function gitRoot(): string {
  return execSync("git rev-parse --show-toplevel", {
    cwd: PROJECT_ROOT,
    stdio: ["ignore", "pipe", "ignore"],
  })
    .toString()
    .trim();
}

function runGit(root: string, args: string[]): void {
  const res = spawnSync("git", args, { cwd: root, stdio: ["ignore", "pipe", "pipe"] });
  if (res.status !== 0) {
    throw new Error(
      `git ${args.join(" ")} failed (exit ${res.status}): ${(res.stderr ?? "").toString().trim()}`,
    );
  }
}

/**
 * Fetch the latest remote, rebase local commits on top so pushes stay
 * fast-forward, then push. Retries a few times to absorb concurrent pushes
 * (e.g. the dev machine pushing while kv55 publishes). On rebase conflicts the
 * rebase is aborted and the error propagates — the bot alerts and retries on
 * the next publish.
 */
function pushWithRebase(root: string): void {
  const branch = "main";
  const MAX_ATTEMPTS = 3;
  let lastErr: Error | null = null;

  for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt++) {
    try {
      runGit(root, ["fetch", "origin", branch]);
    } catch (err) {
      lastErr = err as Error;
      Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, 1500 * attempt);
      continue;
    }
    try {
      runGit(root, ["rebase", "--autostash", `origin/${branch}`]);
    } catch (err) {
      spawnSync("git", ["rebase", "--abort"], { cwd: root, stdio: ["ignore", "pipe", "pipe"] });
      throw err;
    }
    try {
      runGit(root, ["push", "origin", branch]);
      return;
    } catch (err) {
      lastErr = err as Error;
      Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, 1500 * attempt);
    }
  }
  throw lastErr ?? new Error(`git push origin ${branch} failed after ${MAX_ATTEMPTS} attempts`);
}

/**
 * Stage the given repo-root-relative paths, commit if anything changed, then
 * push — rebasing first so commits stranded by earlier failed pushes drain to
 * the remote too. Runs from the repo root (monorepo on dev machine, sparse
 * clone root on kv55); the astro subtree is always at urbanstyle/astro.
 * Throws on failure — callers must NOT fail the publish/deploy.
 */
function stageAndPush(root: string, paths: string[], message: string): void {
  const existing = paths.filter((p) => existsSync(join(root, p)));
  if (existing.length > 0) {
    runGit(root, ["add", ...existing]);
    const staged = spawnSync("git", ["diff", "--cached", "--quiet"], { cwd: root });
    if (staged.status !== 0) {
      runGit(root, ["commit", "-m", message]);
    }
  }
  pushWithRebase(root);
}

/**
 * Commit + push a just-published blog post so the dev machine (and the
 * always-on GitHub repo) gets the generated content. Skips when nothing was
 * staged (no empty commits). Throws on failure — callers must NOT fail the
 * publish.
 */
export function commitAndPushBlogPost(slug: string): void {
  const root = gitRoot();
  const rel = join("urbanstyle", "astro", "src", "content", "blog", slug);

  stageAndPush(root, [rel], "content: add blog post from IG");
}

/**
 * Commit + push a just-translated blog post (Spanish post + its en/it/fr/pt
 * locale copies) so the dev machine and kv55 stay in sync. Skips when nothing
 * was staged. Throws on failure — callers must NOT fail the deploy.
 */
export function commitAndPushTranslations(slug: string): void {
  const root = gitRoot();
  const paths = [
    join("urbanstyle", "astro", "src", "content", "blog", slug),
    ...TRANSLATION_LOCALES.map((locale) =>
      join("urbanstyle", "astro", "src", "content", "blog", locale, slug),
    ),
  ];

  stageAndPush(root, paths, "content: translate blog post to en/fr/it/pt");
}