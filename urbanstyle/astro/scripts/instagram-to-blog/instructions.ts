import { readFile, writeFile } from "node:fs/promises";
import { existsSync, mkdirSync } from "node:fs";
import { join } from "node:path";
import type { Instruction } from "./types";

const INSTRUCTIONS_FILE = join(import.meta.dirname, ".instructions.json");

interface InstructionsFile {
  global: Instruction[];
}

export async function loadInstructions(): Promise<Instruction[]> {
  if (!existsSync(INSTRUCTIONS_FILE)) return [];
  try {
    const raw = await readFile(INSTRUCTIONS_FILE, "utf8");
    const parsed = JSON.parse(raw) as InstructionsFile;
    return Array.isArray(parsed.global) ? parsed.global : [];
  } catch {
    return [];
  }
}

async function saveInstructions(instructions: Instruction[]): Promise<void> {
  const dir = join(import.meta.dirname);
  mkdirSync(dir, { recursive: true });
  await writeFile(INSTRUCTIONS_FILE, JSON.stringify({ global: instructions }, null, 2), "utf8");
}

export async function addInstruction(text: string, source: "feedback" | "manual" = "manual"): Promise<Instruction> {
  const instructions = await loadInstructions();
  const instruction: Instruction = {
    id: `inst_${Date.now().toString(36)}`,
    text: text.trim(),
    source,
    createdAt: new Date().toISOString(),
    timesApplied: 0,
  };
  instructions.push(instruction);
  await saveInstructions(instructions);
  return instruction;
}

export async function removeInstruction(id: string): Promise<boolean> {
  const instructions = await loadInstructions();
  const filtered = instructions.filter((i) => i.id !== id);
  if (filtered.length === instructions.length) return false;
  await saveInstructions(filtered);
  return true;
}

export async function markInstructionsApplied(ids: string[]): Promise<void> {
  const instructions = await loadInstructions();
  for (const instruction of instructions) {
    if (ids.includes(instruction.id)) instruction.timesApplied += 1;
  }
  await saveInstructions(instructions);
}

export function formatInstructions(instructions: Instruction[]): string {
  if (instructions.length === 0) return "";
  return instructions.map((i, idx) => `${idx + 1}. ${i.text}`).join("\n");
}
