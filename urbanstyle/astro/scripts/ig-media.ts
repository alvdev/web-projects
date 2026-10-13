#!/usr/bin/env bun
/**
 * Instagram media helper for service landing pages.
 *
 * Usage:
 *   bun scripts/ig-media.ts list [--match "regex"] [--type image|video|carousel] [--limit N]
 *   bun scripts/ig-media.ts download --slug <service> --ids id1,id2 [--dest images|carousel-<name>]
 *   bun scripts/ig-media.ts carousel --slug <service> --folder <name> --ids id1,id2 [--title T] [--subtitle S] [--description D]
 *
 * Reads IG_ACCESS_TOKEN from the environment (Bun loads .env automatically).
 * Download immediately after listing: Instagram CDN URLs expire.
 */

import { existsSync, mkdirSync, writeFileSync } from "node:fs";
import { join } from "node:path";
import { IG_API, TokenError, validateToken } from "./instagram-to-blog/token";

type IgChild = {
  id: string;
  media_type: string;
  media_url?: string | null;
  thumbnail_url?: string | null;
  alt_text?: string;
};

type IgItem = {
  id: string;
  caption?: string;
  alt_text?: string;
  media_type: string;
  media_url?: string | null;
  thumbnail_url?: string | null;
  permalink?: string;
  timestamp?: string;
  children?: { data: IgChild[] };
};

const FIELDS = [
  "id",
  "caption",
  "alt_text",
  "media_type",
  "media_url",
  "thumbnail_url",
  "permalink",
  "timestamp",
  "children{id,media_type,media_url,thumbnail_url,alt_text}",
].join(",");

function arg(name: string): string | undefined {
  const index = process.argv.indexOf(`--${name}`);
  return index !== -1 ? process.argv[index + 1] : undefined;
}

async function fetchAll(): Promise<IgItem[]> {
  const token = process.env.IG_ACCESS_TOKEN;
  if (!token) throw new TokenError("IG_ACCESS_TOKEN not set");

  let url: string | null = `${IG_API}/me/media?fields=${FIELDS}&limit=100&access_token=${token}`;
  const all: IgItem[] = [];

  for (let page = 0; page < 10 && url; page++) {
    const res = await fetch(url);
    if (!res.ok) {
      const body = (await res.json().catch(() => ({}))) as { error?: { message?: string } };
      throw new Error(`IG media fetch failed: ${body.error?.message ?? res.statusText}`);
    }
    const json = (await res.json()) as { data?: IgItem[]; paging?: { next?: string } };
    all.push(...(json.data ?? []));
    url = json.paging?.next ?? null;
  }

  return all;
}

function cleanText(text: string, max = 200): string {
  return text
    .replace(/#[\p{L}\p{N}_]+/gu, "")
    .replace(/[*_`]|\.{3,}/g, "")
    .replace(/\s+/g, " ")
    .trim()
    .slice(0, max);
}

function altFor(item: { alt_text?: string; caption?: string }, fallback: string): string {
  const alt = (item.alt_text ?? "").trim();
  if (alt) return cleanText(alt, 160);
  const caption = cleanText(item.caption ?? "", 120);
  return caption || fallback;
}

function extFrom(url: string): string {
  const match = url.split("?")[0].match(/\.(jpe?g|png|webp|mp4)$/i);
  return match ? match[1].toLowerCase().replace("jpeg", "jpg") : "jpg";
}

async function download(url: string, path: string): Promise<number> {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`download failed ${res.status} ${url.slice(0, 60)}...`);
  const buffer = Buffer.from(await res.arrayBuffer());
  writeFileSync(path, buffer);
  return buffer.byteLength;
}

function mediaUrl(item: { media_type?: string; media_url?: string | null; thumbnail_url?: string | null }): string | null {
  if (item.media_type === "VIDEO") return item.thumbnail_url ?? item.media_url ?? null;
  return item.media_url ?? item.thumbnail_url ?? null;
}

async function cmdList(): Promise<void> {
  const match = arg("match") ? new RegExp(arg("match")!, "i") : null;
  const type = arg("type")?.toLowerCase();
  const limit = Number(arg("limit") ?? 40);

  const posts = await fetchAll();
  const selected = posts.filter((post) => {
    if (match && !match.test(post.caption ?? "")) return false;
    if (type === "image" && post.media_type !== "IMAGE") return false;
    if (type === "video" && post.media_type !== "VIDEO") return false;
    if (type === "carousel" && post.media_type !== "CAROUSEL_ALBUM") return false;
    return true;
  });

  console.log(`# ${selected.length} matches (of ${posts.length} posts)\n`);
  for (const post of selected.slice(0, limit)) {
    const date = (post.timestamp ?? "").slice(0, 10);
    const caption = cleanText(post.caption ?? "", 130);
    console.log(`${post.id}\t${date}\t${post.media_type}\t${post.permalink ?? ""}\t${caption}`);
  }
}

async function downloadSelection(
  slug: string,
  ids: string[],
  dest: string,
  allChildren: boolean,
  maxChildren = 0,
): Promise<{ files: string[]; alts: string[]; descriptions: string[] }> {
  const dir = join("src/content/services", slug, dest);
  mkdirSync(dir, { recursive: true });

  const posts = await fetchAll();
  const files: string[] = [];
  const alts: string[] = [];
  const descriptions: string[] = [];

  for (const id of ids) {
    const post = posts.find((item) => item.id === id);
    if (!post) {
      console.warn(`!! post ${id} not found`);
      continue;
    }

    const children = post.children?.data ?? [];
    const sources: { url: string; alt: string; desc: string; key: string }[] = [];

    if (post.media_type === "CAROUSEL_ALBUM" && children.length > 0) {
      const limit = maxChildren > 0 ? maxChildren : children.length;
      const use = allChildren ? children.slice(0, limit) : children.slice(0, 1);
      use.forEach((child, index) => {
        const url = mediaUrl(child);
        if (!url) return;
        sources.push({
          url,
          alt: altFor(child, `${slug} - campaña de Urban Style Publicity`),
          desc: cleanText(post.caption ?? "", 220),
          key: `${id}-${index + 1}`,
        });
      });
    } else {
      const url = mediaUrl(post);
      if (!url) {
        console.warn(`!! post ${id} has no downloadable media`);
        continue;
      }
      sources.push({
        url,
        alt: altFor(post, `${slug} - campaña de Urban Style Publicity`),
        desc: cleanText(post.caption ?? "", 220),
        key: id,
      });
    }

    for (const source of sources) {
      const filename = `ig-${source.key}.${extFrom(source.url)}`;
      const path = join(dir, filename);
      if (existsSync(path)) {
        files.push(filename);
        alts.push(source.alt);
        descriptions.push(source.desc);
        continue;
      }
      const bytes = await download(source.url, path);
      console.log(`✓ ${path} (${Math.round(bytes / 1024)} KB)`);
      files.push(filename);
      alts.push(source.alt);
      descriptions.push(source.desc);
    }
  }

  return { files, alts, descriptions };
}

async function cmdDownload(): Promise<void> {
  const slug = arg("slug");
  const ids = (arg("ids") ?? "").split(",").map((s) => s.trim()).filter(Boolean);
  const carousel = arg("carousel");
  const dest = carousel ? `carousel-${carousel}` : "images";
  if (!slug || ids.length === 0) {
    console.error("usage: bun scripts/ig-media.ts download --slug <service> --ids id1,id2 [--dest carousel-<name>]");
    process.exit(1);
  }

  const carouselFolder = carousel ? dest : null;
  const maxChildren = Number(arg("max-children") ?? 0);
  const result = await downloadSelection(slug, ids, carouselFolder ?? dest, Boolean(carousel), maxChildren);
  if (carousel) {
    const md = buildCarouselMd(carousel, result.files, result.alts, result.descriptions);
    const mdPath = join("src/content/services", slug, dest, `${dest}.md`);
    writeFileSync(mdPath, md);
    console.log(`✓ ${mdPath}`);
  }
}

function buildCarouselMd(folder: string, files: string[], alts: string[], descriptions: string[]): string {
  const titleSuffix = folder.replace(/-/g, " ");
  const images = files
    .map((file, index) => {
      const alt = alts[index] || `${titleSuffix} - Urban Style Publicity`;
      const desc = descriptions[index] || alt;
      return `  - src: ${file}\n    desc: "${desc.replace(/"/g, "'")}"\n    alt: "${alt.replace(/"/g, "'")}"`;
    })
    .join("\n");

  return `---
title: "${titleSuffix}"
subtitle: "Campañas reales de Urban Style Publicity"
description: "Una selección de imágenes de nuestras campañas."
enabledTitles: false
images:
${images}
---
`;
}

async function main(): Promise<void> {
  const command = process.argv[2] ?? "list";

  // Ensure the token still works before doing anything else (refresh is manual).
  try {
    const me = await validateToken();
    if (command === "list" || command === "download" || command === "carousel") {
      console.error(`# @${me.username}`);
    }
  } catch (error) {
    console.error(`Token problem: ${(error as Error).message}`);
    process.exit(1);
  }

  if (command === "list") return cmdList();
  if (command === "download" || command === "carousel") {
    if (command === "carousel" && !arg("carousel")) {
      // Allow `carousel` as an alias for download + --carousel <folder>
      const folder = arg("folder") ?? arg("carousel");
      if (folder) {
        // inject into argv for arg()
        process.argv.push("--carousel", folder);
      }
    }
    return cmdDownload();
  }

  console.error("unknown command. Use: list | download | carousel");
  process.exit(1);
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
