/**
 * Sandbox support. When DRY_RUN=1 the external side effects (FTPS upload,
 * Buffer/Facebook publishing) are skipped and fake links are returned, so the
 * full pipeline can run end-to-end in a disposable clone without touching
 * production. Default-off: unset in production.
 */
export function isDryRun(): boolean {
  return process.env.DRY_RUN === "1";
}

export function dryRunLog(what: string): void {
  console.log(`[dry-run] ${what}`);
}

export const DRY_RUN_LINK = "https://dry-run.invalid";
