import { execSync, spawnSync } from "node:child_process";
import { existsSync } from "node:fs";
import { readdir, readFile, stat, writeFile } from "node:fs/promises";
import { createHash } from "node:crypto";
import { join, sep } from "node:path";
import * as tls from "node:tls";
import { Client } from "basic-ftp";

// Hosts whose TLS certificate is issued to the hosting provider (not the hostname).
// checkServerIdentity returns undefined for these, trusting the pinned host only.
const TRUSTED_FTP_HOSTS = new Set(["ftp.urbanstylepublicity.com"]);

function verifyFtpIdentity(hostname: string, cert: tls.PeerCertificate): Error | undefined {
  if (TRUSTED_FTP_HOSTS.has(hostname)) return undefined;
  return tls.checkServerIdentity(hostname, cert);
}

export const PROJECT_ROOT = join(import.meta.dirname, "..", "..");

const MANIFEST_FILE = join(import.meta.dirname, ".deploy-manifest.json");

interface Manifest {
  [relPath: string]: string; // relPath -> sha256 hex
}

async function loadManifest(): Promise<Manifest> {
  if (!existsSync(MANIFEST_FILE)) return {};
  try {
    return JSON.parse(await readFile(MANIFEST_FILE, "utf8")) as Manifest;
  } catch {
    return {};
  }
}

async function saveManifest(manifest: Manifest): Promise<void> {
  await writeFile(MANIFEST_FILE, JSON.stringify(manifest, null, 2), "utf8");
}

function nodeBinPath(): string | null {
  const envPath = process.env.NODE_BIN_DIR;
  if (envPath) return envPath;
  const candidate = "/home/alvdev/.nvm/versions/node/v22.23.2/bin";
  return existsSync(candidate) ? candidate : null;
}

export async function buildSite(): Promise<void> {
  console.log("[deploy] building site...");
  const nodeBin = nodeBinPath();
  const env = nodeBin
    ? { ...process.env, PATH: `${nodeBin}:${process.env.PATH ?? ""}` }
    : process.env;
  execSync("bun run build", { cwd: PROJECT_ROOT, stdio: "inherit", env });
  console.log("[deploy] build done");
}

interface LocalFile {
  path: string;
  size: number;
  mtimeMs: number;
  hash: string;
}

async function hashFile(path: string): Promise<string> {
  const content = await readFile(path);
  return createHash("sha256").update(content).digest("hex");
}

async function walkLocal(dir: string): Promise<Map<string, LocalFile>> {
  const out = new Map<string, LocalFile>();
  const entries = await readdir(dir, { withFileTypes: true });
  for (const entry of entries) {
    const full = join(dir, entry.name);
    if (entry.isDirectory()) {
      for (const [rel, info] of await walkLocal(full)) {
        out.set(entry.name + sep + rel, info);
      }
    } else if (entry.isFile()) {
      const s = await stat(full);
      const hash = await hashFile(full);
      out.set(entry.name, { path: full, size: s.size, mtimeMs: s.mtimeMs, hash });
    }
  }
  return out;
}

/**
 * Recursively remove a remote directory so a deleted post disappears from the
 * production site. Accepts either a site-relative path ("/blog/<slug>") or an
 * absolute remote path ("/urbanstylepublicity.com/blog/<slug>").
 */
export async function removeRemoteDir(remoteDir: string): Promise<void> {
  const host = process.env.FTP_HOST ?? "";
  const user = process.env.FTP_USER ?? "";
  const pass = process.env.FTP_PASSWORD ?? "";
  const remotePath = process.env.FTP_REMOTE_PATH ?? "";
  if (!host || !user || !pass) throw new Error("FTP env vars missing");

  // Normalize: if the caller passed a site-relative path, prepend the FTP root
  // (e.g. "/blog/x" -> "/urbanstylepublicity.com/blog/x"). This mirrors how
  // uploadDist builds remote paths.
  const normalized = remoteDir.startsWith(remotePath)
    ? remoteDir
    : `${remotePath}${remoteDir.startsWith("/") ? remoteDir : `/${remoteDir}`}`;

  const client = new Client();
  client.ftp.verbose = false;
  try {
    await client.access({
      host,
      user,
      password: pass,
      secure: true,
      secureOptions: { checkServerIdentity: verifyFtpIdentity },
    });
    try {
      await client.removeDir(normalized);
      console.log(`[deploy] removed remote dir ${normalized}`);
    } catch (err) {
      console.warn(`[deploy] removeDir ${normalized} failed (${(err as Error).message}) — may not exist`);
    }
  } finally {
    client.close();
  }
}

/**
 * Upload a single file via curl. curl with --ftp-ssl is dramatically more
 * reliable against this server than basic-ftp: the server randomly drops
 * passive-mode data connections for some transfers, and curl's FTPS handling
 * (PASV + fresh TLS data session per transfer) does not hit the issue.
 * --ftp-create-dirs also creates missing remote directories automatically.
 */
function curlUpload(localPath: string, remoteUrl: string, user: string, pass: string): void {
  const res = spawnSync(
    "curl",
    [
      "-s",
      "-k",
      "--ftp-ssl",
      "--ftp-create-dirs",
      "-T",
      localPath,
      remoteUrl,
      "--user",
      `${user}:${pass}`,
    ],
    { timeout: 120_000 },
  );
  if (res.status !== 0) {
    const stderr = (res.stderr ?? "").toString().trim();
    const stdout = (res.stdout ?? "").toString().trim();
    throw new Error(`curl upload failed (exit ${res.status}): ${stderr || stdout || "unknown error"}`);
  }
}

export async function uploadDist(
  onProgress?: (uploaded: number, pending: number) => void,
): Promise<{ uploaded: number; skipped: number; total: number; pending: number }> {
  const host = process.env.FTP_HOST ?? "";
  const user = process.env.FTP_USER ?? "";
  const pass = process.env.FTP_PASSWORD ?? "";
  const remotePath = process.env.FTP_REMOTE_PATH ?? "";
  if (!host || !user || !pass || !remotePath) {
    throw new Error("FTP env vars missing");
  }

  const distPath = join(PROJECT_ROOT, "dist");
  const local = await walkLocal(distPath);
  const manifest = await loadManifest();

  console.log(`[deploy] hash-syncing ${distPath} -> ${remotePath} (via curl FTPS)`);

  let uploaded = 0;
  let skipped = 0;
  const start = Date.now();
  const MAX_ATTEMPTS = 5;

  const newManifest: Manifest = {};

  // Count pending files first so the progress message reports against the
  // real number to upload, not the total file count.
  let pendingCount = 0;
  for (const [rel, localInfo] of local) {
    if (manifest[rel] !== localInfo.hash) pendingCount++;
  }

  for (const [rel, localInfo] of local) {
    newManifest[rel] = localInfo.hash;

    if (manifest[rel] === localInfo.hash) {
      // Content unchanged since last successful upload — skip regardless of mtime.
      skipped++;
      continue;
    }

    const remoteUrl = `ftp://${host}/${remotePath}/${rel}`;

    let attempt = 0;
    for (;;) {
      attempt++;
      try {
        curlUpload(localInfo.path, remoteUrl, user, pass);
        break;
      } catch (err) {
        if (attempt >= MAX_ATTEMPTS) {
          throw new Error(`upload failed after ${MAX_ATTEMPTS} attempts: ${rel}: ${(err as Error).message}`);
        }
        const waitMs = 5000 * 2 ** (attempt - 1);
        console.warn(`[deploy] retry ${attempt}/${MAX_ATTEMPTS} for ${rel} in ${waitMs / 1000}s: ${(err as Error).message}`);
        await new Promise((r) => setTimeout(r, waitMs));
      }
    }
    uploaded++;
    if (uploaded % 25 === 0 || uploaded === pendingCount) {
      console.log(`[deploy] ${uploaded} files uploaded...`);
      onProgress?.(uploaded, pendingCount);
    }
  }

  // Only persist the manifest after a fully successful upload, so failed
  // uploads are retried next run.
  await saveManifest(newManifest);

  const elapsed = ((Date.now() - start) / 1000).toFixed(1);
  console.log(`[deploy] done in ${elapsed}s: ${uploaded} uploaded, ${skipped} unchanged`);

  return { uploaded, skipped, total: local.size, pending: pendingCount };
}
