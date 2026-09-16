/**
 * Sandboxed fake pipeline for scripts/instagram-to-blog.
 *
 * Runs the REAL pipeline modules (MDX writing, Astro build, state JSON,
 * nodemailer, git commit/push) inside a disposable clone under /tmp with:
 *   - DRY_RUN=1         → no FTPS upload, no Buffer/Facebook publishing
 *   - local SMTP sink   → emails are captured, never sent
 *   - local bare origin → git push can never reach GitHub
 *   - fixture post/article, canned approved texts, no LLM calls
 *
 * Usage (from the repo):
 *   bun run scripts/instagram-to-blog/sandbox/run.ts            # clean up after
 *   bun run scripts/instagram-to-blog/sandbox/run.ts --keep     # keep clone
 *   EXPECT_EMAILS=1 bun run .../sandbox/run.ts                  # assert email count
 *
 * The same file re-executes itself inside the clone with --in-clone.
 */
import { execSync, spawnSync } from "node:child_process";
import { cpSync, existsSync, mkdirSync, rmSync, writeFileSync } from "node:fs";
import { createServer } from "node:http";
import type { AddressInfo } from "node:net";
import { join, sep } from "node:path";
import { fileURLToPath } from "node:url";
import type { PendingEntry } from "../types";
import { sandboxArticle, sandboxFbText, sandboxPost, sandboxTweet } from "./fixtures";
import { decodeHeaderValue, mailHeaders, startSmtpSink } from "./smtp-sink";

const SANDBOX_ROOT = process.env.SANDBOX_DIR ?? "/tmp/ig-sandbox";
const SANDBOX_BARE = `${SANDBOX_ROOT}.git`;
const IG_DIR = join(fileURLToPath(new URL(".", import.meta.url)), "..");
const ASTRO_DIR = join(IG_DIR, "..", "..");
const KEEP = process.argv.includes("--keep");

function cleanup(): void {
  if (KEEP) {
    console.log(`[sandbox] kept ${SANDBOX_ROOT} and ${SANDBOX_BARE}`);
    return;
  }
  rmSync(SANDBOX_ROOT, { recursive: true, force: true });
  rmSync(SANDBOX_BARE, { recursive: true, force: true });
}

function bootstrap(): number {
  rmSync(SANDBOX_ROOT, { recursive: true, force: true });
  rmSync(SANDBOX_BARE, { recursive: true, force: true });
  mkdirSync(SANDBOX_ROOT, { recursive: true });

  const repoRoot = execSync("git rev-parse --show-toplevel", { cwd: ASTRO_DIR }).toString().trim();
  console.log(`[sandbox] cloning ${repoRoot} into ${SANDBOX_ROOT} (local bare origin)`);
  execSync(`git clone --quiet --bare --local "${repoRoot}" "${SANDBOX_BARE}"`, { cwd: repoRoot });
  execSync(`git clone --quiet "${SANDBOX_BARE}" "${SANDBOX_ROOT}"`, { cwd: repoRoot });

  const cloneAstro = join(SANDBOX_ROOT, "urbanstyle", "astro");
  const cloneIg = join(cloneAstro, "scripts", "instagram-to-blog");

  // Current working tree (including uncommitted changes) wins over the clone.
  cpSync(IG_DIR, cloneIg, {
    recursive: true,
    force: true,
    filter: (src) =>
      !src.endsWith(".env") &&
      !src.endsWith(".pending.json") &&
      !src.endsWith(".deploy-manifest.json") &&
      !src.includes(".sandbox-mails") &&
      !src.includes(`${sep}node_modules${sep}`),
  });

  // Copy node_modules into the clone. A symlink breaks Vite's module cache
  // (realpath mismatch between importer and imported module).
  const realNodeModules = join(ASTRO_DIR, "node_modules");
  const cloneNodeModules = join(cloneAstro, "node_modules");
  if (existsSync(realNodeModules) && !existsSync(cloneNodeModules)) {
    console.log("[sandbox] copying node_modules into the clone (a few seconds)...");
    execSync(`cp -a "${realNodeModules}" "${cloneNodeModules}"`, { cwd: cloneAstro });
  }

  writeFileSync(
    join(cloneAstro, ".env"),
    [
      "DRY_RUN=1",
      "TELEGRAM_BOT_TOKEN=sandbox-token",
      "TELEGRAM_CHAT_ID=1",
      `NODE_BIN_DIR=${process.env.NODE_BIN_DIR ?? ""}`,
      "",
    ].join("\n"),
  );

  // SAFETY: gitSync must only ever push to the local bare repo.
  const origin = execSync("git remote get-url origin", { cwd: SANDBOX_ROOT }).toString().trim();
  if (!origin.startsWith(SANDBOX_BARE)) {
    console.error(`[sandbox] refusing to run: origin is ${origin}, expected ${SANDBOX_BARE}`);
    cleanup();
    return 1;
  }

  console.log("[sandbox] running fake pipeline inside the clone...\n");
  const res = spawnSync(process.execPath, [join(cloneIg, "sandbox", "run.ts"), "--in-clone"], {
    cwd: cloneAstro,
    stdio: "inherit",
    env: process.env,
  });
  const code = res.status ?? 1;
  console.log(code === 0 ? "\n[sandbox] OK" : `\n[sandbox] FAILED (exit ${code})`);
  cleanup();
  return code;
}

/** In-clone mode: everything happens inside /tmp with local fakes. */
async function runInClone(): Promise<number> {
  const cwd = process.cwd();
  if (!cwd.startsWith("/tmp/")) {
    console.error(`[sandbox] refusing to run in-clone outside /tmp (cwd=${cwd})`);
    return 1;
  }

  // Never let production credentials leak into the sandbox process.
  for (const key of Object.keys(process.env)) {
    if (/^(FTP_|BUFFER_|GBP_BUFFER_|FB_|IG_|GEMINI_|OPENCODE_|SMTP_|ALERT_EMAIL)/.test(key)) {
      delete process.env[key];
    }
  }
  process.env.DRY_RUN = "1";
  process.env.TELEGRAM_BOT_TOKEN = "sandbox-token";
  process.env.TELEGRAM_CHAT_ID = "1";
  // Dummy LLM keys: llm.ts constructs clients at import time. Calls fail with
  // auth errors later (translations are queued for retry), never reaching the network
  // with real credentials.
  process.env.OPENCODE_API_KEY = "sandbox";
  process.env.GEMINI_API_KEY = "sandbox";

  // Local image server for downloadImage (1x1 PNG placeholder).
  const png = Buffer.from(
    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==",
    "base64",
  );
  const imageServer = createServer((_req, res) => {
    res.writeHead(200, { "content-type": "image/png" });
    res.end(png);
  });
  await new Promise<void>((resolve) => imageServer.listen(0, "127.0.0.1", resolve));
  const imagePort = (imageServer.address() as AddressInfo).port;

  const sink = await startSmtpSink(0);
  process.env.SMTP_HOST = "127.0.0.1";
  process.env.SMTP_PORT = String(sink.port);
  process.env.SMTP_USER = "sandbox";
  process.env.SMTP_PASSWORD = "sandbox";
  process.env.ALERT_EMAIL = "sandbox@localhost";

  // Import the REAL pipeline modules only after the sandbox env is in place.
  const { preparePost, BLOG_ROOT } = await import("../content");
  const { loadState, saveState } = await import("../state");
  const { publishEntry, publishToX, publishToFb, publishToGbp, publishToLinkedIn } = await import("../bot");
  const { sendPublishReport } = await import("../report");

  sandboxPost.mediaUrl = `http://127.0.0.1:${imagePort}/header.png`;
  const prepared = preparePost(sandboxArticle, sandboxPost);
  const state = await loadState();
  const entry: PendingEntry = {
    id: sandboxPost.id,
    article: sandboxArticle,
    post: sandboxPost,
    prepared,
    status: "pending",
    feedback: null,
    createdAt: new Date().toISOString(),
    attempts: 1,
  };
  state.pending = [entry];
  await saveState(state);

  const telegram: string[] = [];
  const ctx = {
    chat: { id: 1 },
    reply: async (text: string) => {
      telegram.push(text);
      return { message_id: telegram.length };
    },
    api: {
      editMessageText: async (_chatId: number, _messageId: number, text: string) => {
        telegram.push(text);
      },
      editMessageReplyMarkup: async () => {},
    },
  };

  console.log("=== 1) blog publish (real MDX + real build + dry FTPS + sandbox git) ===");
  await publishEntry(entry, state, ctx as never);
  // runPostTranslations is fire-and-forget; give it a moment (no LLM keys → queued).
  await new Promise((resolve) => setTimeout(resolve, 1500));

  const publishedEntry = state.published[0];
  if (!publishedEntry) {
    console.error("\n[sandbox] FAILED: publishEntry did not publish (see build output above)");
    await sink.close();
    imageServer.close();
    return 1;
  }
  console.log(`[sandbox] state: ${state.published.length} published, slug=${publishedEntry.slug}`);

  console.log("\n=== 2) social publish (Buffer/Facebook dry-run) ===");
  publishedEntry.social.x = { status: "approved", tweet: sandboxTweet };
  publishedEntry.social.facebook = { status: "approved", tweet: sandboxFbText };
  const results = [
    await publishToX(publishedEntry, state),
    await publishToFb(publishedEntry, state),
    await publishToGbp(publishedEntry, state),
    await publishToLinkedIn(publishedEntry, state),
  ];
  // Same call the ▶️ Publicar en redes handler makes: ONE report email.
  await sendPublishReport(publishedEntry, results);
  await saveState(state);
  await new Promise((resolve) => setTimeout(resolve, 300));

  // ---- artifacts + report ----
  const mdx = join(BLOG_ROOT, publishedEntry.slug, "index.mdx");
  const built = join(cwd, "dist", "blog", publishedEntry.slug, "index.html");
  const mailsDir = join(cwd, ".sandbox-mails");
  mkdirSync(mailsDir, { recursive: true });
  sink.mails.forEach((mail, i) => {
    writeFileSync(join(mailsDir, `${String(i + 1).padStart(2, "0")}.eml`), mail.raw);
  });

  console.log("\n=== sandbox summary ===");
  console.log(`MDX written:      ${existsSync(mdx) ? "yes" : "NO"} (${mdx})`);
  console.log(`site built:       ${existsSync(built) ? "yes" : "NO"} (${built})`);
  console.log(`published:        ${state.published.length} post(s)`);
  console.log(
    `platform states:  x=${publishedEntry.social.x?.status} facebook=${publishedEntry.social.facebook?.status} gbp=${publishedEntry.social.gbp?.status} linkedin=${publishedEntry.social.linkedin?.status}`,
  );
  console.log(`emails captured:  ${sink.mails.length}`);
  for (const mail of sink.mails) {
    const headers = mailHeaders(mail.raw);
    console.log(`  - subject: ${decodeHeaderValue(headers.subject ?? "?")}`);
  }
  console.log(`mail files:       ${mailsDir}`);

  const emailCount = sink.mails.length;
  const expected = process.env.EXPECT_EMAILS ? Number(process.env.EXPECT_EMAILS) : null;
  await sink.close();
  imageServer.close();

  if (!existsSync(mdx) || !existsSync(built)) {
    console.error("[sandbox] FAILED: pipeline artifacts missing");
    return 1;
  }
  if (expected !== null && emailCount !== expected) {
    console.error(`[sandbox] FAILED: expected ${expected} email(s), captured ${emailCount}`);
    return 1;
  }
  return 0;
}

const exitCode = process.argv.includes("--in-clone") ? await runInClone() : bootstrap();
process.exit(exitCode);
