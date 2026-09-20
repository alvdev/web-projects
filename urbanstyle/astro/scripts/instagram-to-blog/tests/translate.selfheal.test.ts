import "./helpers/silence";
import { describe, expect, mock, test } from "bun:test";
import { mkdir, mkdtemp, readFile, rm, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import { join } from "node:path";
import { fileURLToPath } from "node:url";

const ROOT = join(fileURLToPath(new URL(".", import.meta.url)), "..");

// Same corruption that broke the kv55 build.
const corruptItalian = `---
title: 'Portiamo l'evoluzione di Aristocrazy nelle strade di Madrid'
description: 'Ti raccontiamo come abbiamo eseguito la campagna.'
cover:
  url: './header.jpg'
  alt: 'Portiamo l'evoluzione di Aristocrazy'
  objectPosition: 'bottom'
taxonomy:
  categories: ['Pubblicità esterna']
  tags: ['affissione manifesti']
pubDate: '06-10-2025 09:43'
updatedDate: '19-09-2026 18:45'
---

Body text.
`;

const validItalian = corruptItalian.replaceAll("l'evoluzione", "l''evoluzione");

let translateCalls = 0;
mock.module(join(ROOT, "llm.ts"), () => ({
  translateFileContent: async (): Promise<string> => {
    translateCalls++;
    return validItalian;
  },
}));

const { translatePostBySlug } = await import("../translate");

async function makePost(root: string, slug: string): Promise<void> {
  await mkdir(join(root, slug), { recursive: true });
  await writeFile(
    join(root, slug, "index.mdx"),
    "---\ntitle: 'Post original'\n---\n\nBody original",
    "utf8",
  );
}

describe("translatePostBySlug self-healing", () => {
  test("quarantines and regenerates a locale file with invalid YAML frontmatter", async () => {
    const root = await mkdtemp(join(tmpdir(), "translate-heal-"));
    try {
      const slug = "test-post";
      await makePost(root, slug);
      await mkdir(join(root, "it", slug), { recursive: true });
      await writeFile(join(root, "it", slug, "index.mdx"), corruptItalian, "utf8");

      translateCalls = 0;
      const written = await translatePostBySlug(slug, ["it"], root);

      expect(written).toHaveLength(1);
      expect(translateCalls).toBeGreaterThan(0);
      const healed = await readFile(join(root, "it", slug, "index.mdx"), "utf8");
      expect(healed).toContain("l''evoluzione");
      const quarantined = await readFile(join(root, "it", slug, "index.mdx.invalid.bak"), "utf8");
      expect(quarantined).toContain("l'evoluzione");
    } finally {
      await rm(root, { recursive: true, force: true });
    }
  });

  test("skips a valid existing locale file without calling the LLM", async () => {
    const root = await mkdtemp(join(tmpdir(), "translate-skip-"));
    try {
      const slug = "test-post";
      await makePost(root, slug);
      await mkdir(join(root, "it", slug), { recursive: true });
      await writeFile(join(root, "it", slug, "index.mdx"), validItalian, "utf8");

      translateCalls = 0;
      const written = await translatePostBySlug(slug, ["it"], root);

      expect(written).toHaveLength(0);
      expect(translateCalls).toBe(0);
    } finally {
      await rm(root, { recursive: true, force: true });
    }
  });
});
