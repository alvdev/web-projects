import "./helpers/silence";
import { describe, expect, test } from "bun:test";
import { formatBuildFailure } from "../deploy";

describe("formatBuildFailure", () => {
  test("keeps only the tail of the build log", () => {
    const lines = Array.from({ length: 50 }, (_, i) => `line ${i + 1}`);
    expect(formatBuildFailure(lines.join("\n"), 3)).toBe("line 48\nline 49\nline 50");
  });

  test("handles short output and trims surrounding whitespace", () => {
    expect(formatBuildFailure("boom", 30)).toBe("boom");
    expect(formatBuildFailure("a\nb\n", 30)).toBe("a\nb");
  });
});
