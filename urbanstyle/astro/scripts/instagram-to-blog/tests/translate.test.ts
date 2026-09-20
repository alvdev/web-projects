import "./helpers/silence";
import { describe, expect, test } from "bun:test";
import { mkdir, mkdtemp, rm, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import { join } from "node:path";
import { findInvalidContentFrontmatter, frontmatterYamlError } from "../content";
import { validateTranslation } from "../translate";

// Exact corruption that broke the build on kv55: the Italian title/alt contain
// an unescaped apostrophe inside a single-quoted YAML scalar.
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

const escapedItalian = corruptItalian.replaceAll("l'evoluzione", "l''evoluzione");

describe("validateTranslation YAML parsing", () => {
  test("rejects the corrupt Italian frontmatter that broke the build", () => {
    const error = validateTranslation(corruptItalian, "blog");
    expect(error).not.toBeNull();
    expect(error).toContain("YAML");
  });

  test("accepts the same file once apostrophes are doubled", () => {
    expect(validateTranslation(escapedItalian, "blog")).toBeNull();
  });

  test("still rejects missing delimiters and fields", () => {
    expect(validateTranslation("no frontmatter", "blog")).toBe(
      "frontmatter opening delimiter missing",
    );
    expect(validateTranslation("---\ntitle: 'x'\n", "blog")).toBe(
      "frontmatter closing delimiter missing",
    );
    expect(validateTranslation("---\ntitle: 'x'\n---\n\nBody", "blog")).toContain(
      "frontmatter missing fields",
    );
  });
});

describe("frontmatterYamlError", () => {
  test("flags unescaped apostrophes with the parser message", () => {
    const error = frontmatterYamlError(corruptItalian);
    expect(error).not.toBeNull();
    expect(error).toContain("bad indentation");
  });

  test("returns null for valid YAML and for files without frontmatter", () => {
    expect(frontmatterYamlError(escapedItalian)).toBeNull();
    expect(frontmatterYamlError("# Just markdown\n\nno frontmatter here")).toBeNull();
  });
});

describe("findInvalidContentFrontmatter", () => {
  test("reports only the files with invalid frontmatter", async () => {
    const root = await mkdtemp(join(tmpdir(), "content-scan-"));
    try {
      await mkdir(join(root, "blog", "good"), { recursive: true });
      await mkdir(join(root, "blog", "bad"), { recursive: true });
      await mkdir(join(root, "services", "svc"), { recursive: true });
      await writeFile(join(root, "blog", "good", "index.mdx"), escapedItalian, "utf8");
      await writeFile(join(root, "blog", "bad", "index.mdx"), corruptItalian, "utf8");
      await writeFile(
        join(root, "services", "svc", "index.md"),
        "---\ntitle: 'Servicio'\norder: '1'\n---\n\nBody",
        "utf8",
      );

      const bad = await findInvalidContentFrontmatter(root);
      expect(bad).toHaveLength(1);
      expect(bad[0]!.file).toBe(join(root, "blog", "bad", "index.mdx"));
      expect(bad[0]!.error).toContain("bad indentation");
    } finally {
      await rm(root, { recursive: true, force: true });
    }
  });
});
