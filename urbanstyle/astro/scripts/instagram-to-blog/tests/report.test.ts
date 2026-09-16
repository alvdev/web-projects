import "./helpers/silence";
import { describe, expect, test } from "bun:test";
import { buildPublishReport } from "../report";
import type { PublishResult, PublishedEntry } from "../types";

function published(overrides: Partial<PublishedEntry> = {}): PublishedEntry {
  return {
    id: "100",
    slug: "test-post",
    title: "Test Post",
    publishedAt: "2026-09-01T10:00:00.000Z",
    social: {},
    ...overrides,
  };
}

function result(overrides: Partial<PublishResult> & Pick<PublishResult, "platform">): PublishResult {
  return { ok: true, status: "published", line: "", ...overrides };
}

describe("buildPublishReport", () => {
  test("lists every attempted platform with its published link", () => {
    const report = buildPublishReport(published(), [
      result({ platform: "x", link: "https://x.com/a/1" }),
      result({ platform: "facebook", link: "https://facebook.com/1_2" }),
      result({ platform: "gbp", link: "https://gbp.test/3" }),
      result({ platform: "linkedin", link: "https://linkedin.test/4" }),
    ]);

    expect(report.subject).toBe("[Urban Sync] Publicado: Test Post");
    expect(report.body).toContain("Título: Test Post");
    expect(report.body).toContain("Blog: https://urbanstylepublicity.com/blog/test-post");
    expect(report.body).toContain("X: ✅ publicado — https://x.com/a/1");
    expect(report.body).toContain("Facebook: ✅ publicado — https://facebook.com/1_2");
    expect(report.body).toContain("Google: ✅ publicado — https://gbp.test/3");
    expect(report.body).toContain("LinkedIn: ✅ publicado — https://linkedin.test/4");
  });

  test("flags failures in the subject and marks unattempted platforms", () => {
    const report = buildPublishReport(published(), [
      result({ platform: "x", ok: false, status: "failed", error: "Buffer caído" }),
    ]);

    expect(report.subject).toBe("[Urban Sync] Publicado con fallos: Test Post");
    expect(report.body).toContain("X: ❌ error — Buffer caído");
    expect(report.body).toContain("Facebook: ⏭ no intentado");
    expect(report.body).toContain("Google: ⏭ no intentado");
    expect(report.body).toContain("LinkedIn: ⏭ no intentado");
  });

  test("shows previously published platforms with their stored links on retries", () => {
    const report = buildPublishReport(
      published({
        social: {
          x: { status: "published", link: "https://x.com/old/status/9" },
          facebook: { status: "published" },
        },
      }),
      [result({ platform: "gbp", link: "https://gbp.test/3" })],
    );

    expect(report.body).toContain("X: ✅ ya publicado — https://x.com/old/status/9");
    expect(report.body).toContain("Facebook: ✅ ya publicado");
    expect(report.body).toContain("Google: ✅ publicado — https://gbp.test/3");
    expect(report.body).toContain("LinkedIn: ⏭ no intentado");
  });

  test("notes 'sin enlace' when a platform returns no URL", () => {
    const report = buildPublishReport(published(), [result({ platform: "x" })]);
    expect(report.body).toContain("X: ✅ publicado (sin enlace)");
  });

  test("a failed platform without an error message falls back to a generic label", () => {
    const report = buildPublishReport(published(), [
      result({ platform: "gbp", ok: false, status: "failed" }),
    ]);
    expect(report.body).toContain("Google: ❌ error — error desconocido");
  });
});
