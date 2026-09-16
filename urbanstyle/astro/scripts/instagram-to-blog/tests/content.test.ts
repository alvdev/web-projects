import "./helpers/silence";
import { describe, expect, test } from "bun:test";
import {
  BLOG_ROOT,
  TRANSLATION_LOCALES,
  buildMdx,
  preparePost,
  stripSlugFrontmatter,
  validateTitleEnding,
} from "../content";
import type { LlmArticle, NewPost } from "../types";

const article: LlmArticle = {
  title: "Concierto de Verano en Madrid",
  description: "Una noche de música en directo en la capital.",
  content: "## La gira\n\nEl artista presentó «Nuevo Álbum» ante miles de fans.",
  tags: ["música", "directo"],
  category: "Conciertos",
};

const post: NewPost = {
  id: "123",
  caption: "cartel del concierto",
  mediaUrl: "https://example.test/img.jpg",
  timestamp: "2026-06-15T18:30:00.000Z",
  mediaType: "IMAGE",
};

describe("validateTitleEnding", () => {
  test("flags a title ending in a dangling preposition", () => {
    expect(validateTitleEnding("Una gira para")).toBe("para");
    expect(validateTitleEnding("Carteles de")).toBe("de");
  });

  test("returns null for a complete title", () => {
    expect(validateTitleEnding("Concierto en Madrid")).toBeNull();
    expect(validateTitleEnding("")).toBeNull();
  });
});

describe("preparePost", () => {
  test("builds slug, paths and pubDate from the article and post", () => {
    const prepared = preparePost(article, post);
    expect(prepared.slug).toBe("concierto-de-verano-en-madrid");
    expect(prepared.basePath).toBe(`${BLOG_ROOT}/concierto-de-verano-en-madrid`);
    expect(prepared.mdxPath).toBe(`${prepared.basePath}/index.mdx`);
    expect(prepared.imagePath).toBe(`${prepared.basePath}/header.jpg`);
    expect(prepared.media_url).toBe(post.mediaUrl);
    expect(prepared.igMediaId).toBe("123");
    const d = new Date(post.timestamp);
    const pad = (n: number) => String(n).padStart(2, "0");
    expect(prepared.pubDate).toBe(
      `${pad(d.getDate())}-${pad(d.getMonth() + 1)}-${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`,
    );
    expect(prepared.objectPosition).toBeUndefined();
  });

  test("converts guillemets to RAE double quotes (italic in content)", () => {
    const prepared = preparePost(article, post);
    expect(prepared.title).not.toContain("«");
    expect(prepared.content).toContain('*"Nuevo Álbum"*');
  });

  test("unescapes backslash-escaped quotes from the LLM", () => {
    const prepared = preparePost({ ...article, title: 'Lo que dijo \\"el artista\\"' }, post);
    expect(prepared.title).toContain('"el artista"');
  });

  test("prefixes the description when it starts like the title", () => {
    const prepared = preparePost(
      { ...article, description: "Concierto de Verano en Madrid fue único" },
      post,
    );
    expect(prepared.description).toBe("Explora cómo concierto de Verano en Madrid fue único");
  });

  test("strips accents and punctuation from the slug", () => {
    const prepared = preparePost({ ...article, title: "¡Música, Baile y Acción!" }, post);
    expect(prepared.slug).toBe("musica-baile-y-accion");
  });

  test("drops a dangling article when truncating a long title", () => {
    const longTitle = "Carteles pegados por toda la ciudad de Madrid durante la campaña de verano para";
    const prepared = preparePost({ ...article, title: longTitle }, post);
    expect(prepared.slug.length).toBeLessThanOrEqual(100);
    expect(prepared.slug.endsWith("para")).toBe(false);
  });
});

describe("buildMdx", () => {
  test("renders frontmatter with defaults and escaped quotes", () => {
    const prepared = preparePost({ ...article, title: "El tour de O'Connor" }, post);
    const mdx = buildMdx(prepared);
    expect(mdx.startsWith("---\n")).toBe(true);
    expect(mdx).toContain("title: 'El tour de O''Connor'");
    expect(mdx).toContain("objectPosition: 'center'");
    expect(mdx).toContain("slug: 'el-tour-de-oconnor'");
    expect(mdx).toContain("categories: ['Conciertos']");
    expect(mdx).toContain("tags: ['música', 'directo']");
    expect(mdx).toContain("![El tour de O'Connor](./header.jpg)");
    expect(mdx).toContain(prepared.content);
  });

  test("respects an explicit objectPosition", () => {
    const prepared = { ...preparePost(article, post), objectPosition: "top" as const };
    expect(buildMdx(prepared)).toContain("objectPosition: 'top'");
  });
});

describe("stripSlugFrontmatter", () => {
  test("removes only the slug line from frontmatter", () => {
    const file = "---\ntitle: 'T'\nslug: 'test-post'\npubDate: 'x'\n---\n\nBody";
    const out = stripSlugFrontmatter(file);
    expect(out).not.toContain("slug:");
    expect(out).toContain("title: 'T'");
    expect(out).toContain("pubDate: 'x'");
    expect(out).toContain("Body");
  });
});

test("TRANSLATION_LOCALES covers en/it/fr/pt", () => {
  expect([...TRANSLATION_LOCALES]).toEqual(["en", "it", "fr", "pt"]);
});
