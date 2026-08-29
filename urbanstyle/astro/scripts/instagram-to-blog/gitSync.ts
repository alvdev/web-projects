import { execSync, spawnSync } from "node:child_process";
import { join } from "node:path";
import { PROJECT_ROOT } from "./deploy";

function gitRoot(): string {
  return execSync("git rev-parse --show-toplevel", {
    cwd: PROJECT_ROOT,
    stdio: ["ignore", "pipe", "ignore"],
  })
    .toString()
    .trim();
}

/**
 * Commit + push a just-published blog post so the dev machine (and the
 * always-on GitHub repo) gets the generated content. Runs from the repo root
 * (monorepo on dev machine, sparse clone root on kv55); the astro subtree is
 * always at urbanstyle/astro. Skips when nothing was staged (no empty
 * commits). Throws on failure — callers must NOT fail the publish.
 */
export function commitAndPushBlogPost(slug: string): void {
  const root = gitRoot();
  const rel = join("urbanstyle", "astro", "src", "content", "blog", slug);

  execSync(`git add "${rel}"`, { cwd: root, stdio: ["ignore", "pipe", "pipe"] });

  const staged = spawnSync("git", ["diff", "--cached", "--quiet"], { cwd: root });
  if (staged.status === 0) return;

  execSync('git commit -m "content: add blog post from IG"', {
    cwd: root,
    stdio: ["ignore", "pipe", "pipe"],
  });
  execSync("git push origin main", { cwd: root, stdio: ["ignore", "pipe", "pipe"] });
}