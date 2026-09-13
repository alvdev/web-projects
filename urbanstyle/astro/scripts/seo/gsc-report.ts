import { google } from "googleapis";
import { mkdir, writeFile } from "node:fs/promises";
import { join } from "node:path";

const keyFile = process.env.GSC_KEY_FILE;
if (!keyFile) {
 console.error("GSC_KEY_FILE is not set (see .env)");
 process.exit(1);
}

const auth = new google.auth.GoogleAuth({
 keyFile,
 scopes: ["https://www.googleapis.com/auth/webmasters.readonly"],
});

const sc = google.searchconsole({ version: "v1", auth });

const REPORTS_DIR = "seo/reports";

type ApiRow = {
 keys?: string[] | null;
 clicks?: number | null;
 impressions?: number | null;
 ctr?: number | null;
 position?: number | null;
};

function fmt(n: number | null | undefined, digits = 0): string {
 if (n === null || n === undefined) return "";
 return n.toFixed(digits);
}

function toCsv(header: string[], rows: string[][]): string {
 return [header, ...rows].map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(",")).join("\n");
}

function isoDaysAgo(days: number): string {
 const d = new Date();
 d.setDate(d.getDate() - days);
 return d.toISOString().slice(0, 10);
}

async function listSites() {
 const res = await sc.sites.list({});
 const entries = res.data.siteEntry ?? [];
 if (entries.length === 0) {
  console.log("No properties visible to this service account.");
  return;
 }
 for (const e of entries) {
  console.log(`${e.siteUrl}  [${e.permissionLevel}]`);
 }
}

async function query(options: {
 siteUrl: string;
 startDate: string;
 endDate: string;
 dimensions: string[];
 rowLimit?: number;
 queryFilter?: string;
 pageFilter?: string;
}): Promise<ApiRow[]> {
 const { siteUrl, startDate, endDate, dimensions, rowLimit = 1000, queryFilter, pageFilter } = options;
 const filters: Record<string, unknown>[] = [];
 if (queryFilter) filters.push({ dimension: "query", operator: "equals", expression: queryFilter });
 if (pageFilter) filters.push({ dimension: "page", operator: "equals", expression: pageFilter });
 const res = await sc.searchanalytics.query({
  siteUrl,
  requestBody: {
   startDate,
   endDate,
   dimensions,
   rowLimit,
   dataState: "all",
   ...(filters.length ? { dimensionFilterGroups: [{ filters }] } : {}),
  },
 });
 return (res.data.rows ?? []) as ApiRow[];
}

async function saveCsv(name: string, header: string[], rows: string[][]) {
 await mkdir(REPORTS_DIR, { recursive: true });
 const file = join(REPORTS_DIR, name);
 await writeFile(file, toCsv(header, rows), "utf8");
 console.log(`  wrote ${file} (${rows.length} rows)`);
}

function printRows(rows: ApiRow[], keys: string[]) {
 for (const r of rows) {
  const k = (r.keys ?? []).join(" | ");
  console.log(
   `  ${k.padEnd(70)} clicks=${fmt(r.clicks)} impr=${fmt(r.impressions)} ctr=${fmt((r.ctr ?? 0) * 100, 1)}% pos=${fmt(r.position, 1)}`,
  );
 }
}

async function baseline(siteUrl: string) {
 const today = new Date().toISOString().slice(0, 10);
 const longStart = isoDaysAgo(480);
 console.log(`\nProperty: ${siteUrl}`);
 console.log(`Long range: ${longStart} .. ${today}`);
 console.log("(GSC API returns up to 16 months; last 2-3 days are incomplete)\n");

 console.log("== 1. Head query 'pegada de carteles' — position trend by date ==");
 const trend = await query({ siteUrl, startDate: longStart, endDate: today, dimensions: ["date"], queryFilter: "pegada de carteles" });
 printRows(trend.slice(-21), ["date"]); // last 3 weeks
 await saveCsv("head-query-trend.csv", ["date", "clicks", "impressions", "ctr", "position"], trend.map(r => [r.keys?.[0] ?? "", fmt(r.clicks), fmt(r.impressions), fmt((r.ctr ?? 0) * 100, 2), fmt(r.position, 2)]));

 console.log("\n== 2. Head query — pages competing for it (full range) ==");
 const pages = await query({ siteUrl, startDate: longStart, endDate: today, dimensions: ["page"], queryFilter: "pegada de carteles", rowLimit: 100 });
 printRows(pages, ["page"]);
 await saveCsv("head-query-pages.csv", ["page", "clicks", "impressions", "ctr", "position"], pages.map(r => [r.keys?.[0] ?? "", fmt(r.clicks), fmt(r.impressions), fmt((r.ctr ?? 0) * 100, 2), fmt(r.position, 2)]));

 console.log("\n== 3. Head query — recent 28d vs previous 28d by page ==");
 const recentStart = isoDaysAgo(28);
 const prevStart = isoDaysAgo(56);
 const prevEnd = isoDaysAgo(29);
 for (const [label, start, end] of [
  ["previous-28d", prevStart, prevEnd],
  ["recent-28d", recentStart, today],
 ] as const) {
  const rows = await query({ siteUrl, startDate: start, endDate: end, dimensions: ["page"], queryFilter: "pegada de carteles", rowLimit: 100 });
  console.log(`  -- ${label} (${start}..${end})`);
  printRows(rows, ["page"]);
  await saveCsv(`head-query-pages-${label}.csv`, ["page", "clicks", "impressions", "ctr", "position"], rows.map(r => [r.keys?.[0] ?? "", fmt(r.clicks), fmt(r.impressions), fmt((r.ctr ?? 0) * 100, 2), fmt(r.position, 2)]));
 }

 console.log("\n== 4. Sep 1-9 vs Sep 10-13 (i18n deploy on Sep 10) — queries + pages ==");
 for (const [label, start, end] of [
  ["before-sep10", "2026-09-01", "2026-09-09"],
  ["after-sep10", "2026-09-10", today],
 ] as const) {
  const q = await query({ siteUrl, startDate: start, endDate: end, dimensions: ["query"], rowLimit: 200 });
  const p = await query({ siteUrl, startDate: start, endDate: end, dimensions: ["page"], rowLimit: 200 });
  console.log(`  -- ${label} (${start}..${end}): ${q.length} queries, ${p.length} pages`);
  await saveCsv(`queries-${label}.csv`, ["query", "clicks", "impressions", "ctr", "position"], q.map(r => [r.keys?.[0] ?? "", fmt(r.clicks), fmt(r.impressions), fmt((r.ctr ?? 0) * 100, 2), fmt(r.position, 2)]));
  await saveCsv(`pages-${label}.csv`, ["page", "clicks", "impressions", "ctr", "position"], p.map(r => [r.keys?.[0] ?? "", fmt(r.clicks), fmt(r.impressions), fmt((r.ctr ?? 0) * 100, 2), fmt(r.position, 2)]));
 }

 console.log("\n== 5. Top 50 queries (recent 28d) ==");
 const top = await query({ siteUrl, startDate: recentStart, endDate: today, dimensions: ["query"], rowLimit: 50 });
 printRows(top, ["query"]);
 await saveCsv("top-queries-28d.csv", ["query", "clicks", "impressions", "ctr", "position"], top.map(r => [r.keys?.[0] ?? "", fmt(r.clicks), fmt(r.impressions), fmt((r.ctr ?? 0) * 100, 2), fmt(r.position, 2)]));

 console.log("\nDone. CSVs in " + REPORTS_DIR);
}

const [cmd = "help", ...args] = process.argv.slice(2);
const siteUrl = process.env.GSC_SITE_URL ?? "";

switch (cmd) {
 case "sites":
  await listSites();
  break;
 case "baseline":
  if (!siteUrl) {
   console.error("GSC_SITE_URL is not set. Run `sites` first, then add it to .env");
   process.exit(1);
  }
  await baseline(siteUrl);
  break;
 case "query": {
  if (!siteUrl) {
   console.error("GSC_SITE_URL is not set");
   process.exit(1);
  }
  const text = args[0];
  const start = args[1] ?? isoDaysAgo(90);
  const end = args[2] ?? new Date().toISOString().slice(0, 10);
  const rows = await query({ siteUrl, startDate: start, endDate: end, dimensions: ["query", "page"], queryFilter: text, rowLimit: 500 });
  printRows(rows, ["query", "page"]);
  break;
 }
 case "trend": {
  if (!siteUrl) {
   console.error("GSC_SITE_URL is not set");
   process.exit(1);
  }
  const text = args[0] === "-" ? undefined : args[0];
  const page = args[1];
  const start = args[2] ?? isoDaysAgo(120);
  const end = args[3] ?? new Date().toISOString().slice(0, 10);
  const rows = await query({ siteUrl, startDate: start, endDate: end, dimensions: ["date"], queryFilter: text, pageFilter: page, rowLimit: 500 });
  printRows(rows, ["date"]);
  break;
 }
 default:
  console.log(`Usage:
  bun scripts/seo/gsc-report.ts sites
  bun scripts/seo/gsc-report.ts baseline
  bun scripts/seo/gsc-report.ts query "pegada de carteles" [start] [end]
  bun scripts/seo/gsc-report.ts trend "pegada de carteles" "https://urbanstylepublicity.com/" [start] [end]`);
}
