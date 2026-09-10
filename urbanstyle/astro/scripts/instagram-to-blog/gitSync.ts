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

function stageAndPush(root: string, paths: string[], message: string): void {
  const existing = paths.filter((p) => existsSync(join(root, p)));
  if (existing.length === 0) return;

  execSync(`git add ${existing.map((p) => `"${p}"`).join(" ")}`, {
    cwd: root,
    stdio: ["ignore", "pipe", "pipe"],
  });

  const staged = spawnSync("git", ["diff", "--cached", "--quiet"], { cwd: root });
  if (staged.status === 0) return;

  execSync(`git commit -m "${message}"`, {
    cwd: root,
    stdio: ["ignore", "pipe", "pipe"],
  });
  execSync("git push origin main", { cwd: root, stdio: ["ignore", "pipe", "pipe"] });
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