import { readFile, writeFile } from "node:fs/promises";
import { existsSync } from "node:fs";
import { join } from "node:path";
import type { PendingState } from "./types";

export const STATE_FILE = join(import.meta.dirname, ".pending.json");

const emptyState: PendingState = { pending: [], skippedIds: [], published: [] };

let cache: PendingState | null = null;

export async function loadState(): Promise<PendingState> {
  if (cache) return cache;
  if (!existsSync(STATE_FILE)) {
    cache = structuredClone(emptyState);
    return cache;
  }
  try {
    const raw = await readFile(STATE_FILE, "utf8");
    const parsed = JSON.parse(raw) as PendingState;
    if (!Array.isArray(parsed.pending)) parsed.pending = [];
    if (!Array.isArray(parsed.skippedIds)) parsed.skippedIds = [];
    if (!Array.isArray(parsed.published)) parsed.published = [];
    cache = parsed;
    return parsed;
  } catch {
    cache = structuredClone(emptyState);
    return cache;
  }
}

export async function saveState(state: PendingState): Promise<void> {
  cache = state;
  await writeFile(STATE_FILE, JSON.stringify(state, null, 2), "utf8");
}

export function bumpCache(): void {
  cache = null;
}
