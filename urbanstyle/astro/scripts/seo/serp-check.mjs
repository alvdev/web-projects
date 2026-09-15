#!/usr/bin/env node
/**
 * Google SERP checker (Camoufox + optional residential proxy).
 *
 * Usage:
 *   node scripts/seo/serp-check.mjs [--query "pegada de carteles"] [--uule "<uule>"] [--hl es] [--gl ES]
 *
 * Reads the SERP at a fixed Google location (UULE) and reports the ORGANIC
 * results only (ads are counted separately). Appends one row per run to
 * seo/reports/serp-history.csv.
 */
import { launchOptions } from "camoufox-js";
import { firefox } from "playwright-core";
import { appendFile, mkdir } from "node:fs/promises";
import { existsSync } from "node:fs";
import { join } from "node:path";

const args = process.argv.slice(2);
const arg = (name, def) => {
  const i = args.indexOf(`--${name}`);
  return i >= 0 && args[i + 1] ? args[i + 1] : def;
};

const query = arg("query", "pegada de carteles");
const hl = arg("hl", "es");
const gl = arg("gl", "ES");
const uule = arg(
  "uule",
  "a+cm9sZToxCnByb2R1Y2VyOjEyCnByb3ZlbmFuY2U6Ngp0aW1lc3RhbXA6MTc4OTQ4MTQ3NzIzOTAwMApsYXRsbmd7CmxhdGl0dWRlX2U3OjQwMzkyNTQ1OApsb25naXR1ZGVfZTc6LTM3MDIyOTQyCn0KcmFkaXVzOjkzMDAw",
);
const proxyUrl = process.env.SERP_PROXY || "";
const targetDomain = arg("target", "urbanstylepublicity.com");

const url = `https://www.google.com/search?q=${encodeURIComponent(query)}&hl=${hl}&gl=${gl}&ie=utf-8&oe=utf-8&pws=0&uule=${uule}`;

function buildProxy(u) {
  if (!u) return undefined;
  const p = new URL(u);
  return { server: p.origin, username: p.username, password: p.password };
}

const browser = await firefox.launch(
  await launchOptions({
    headless: true,
    os: "linux",
    humanize: true,
    enable_cache: false,
    proxy: buildProxy(proxyUrl),
  }),
);

const context = await browser.newContext({
  viewport: null,
  locale: hl.startsWith("es") ? "es-ES" : "en-US",
});
const page = await context.newPage();

try {
  await page.goto(url, { waitUntil: "domcontentloaded", timeout: 120000 });
  await page.waitForTimeout(9000);

  const initial = await page.evaluate(() => document.body.innerText);
  if (initial.includes("Antes de continuar") || page.url().includes("consent")) {
    try {
      await page.getByRole("button", { name: /Aceptar todo|Accept all/i }).click({ timeout: 6000 });
      await page.waitForTimeout(4000);
      await page.goto(url, { waitUntil: "domcontentloaded", timeout: 120000 });
      await page.waitForTimeout(6000);
    } catch {
      // results are usually rendered even behind the consent sheet
    }
  }

  const data = await page.evaluate(() => {
    const rows = [];
    for (const h of document.querySelectorAll("h3")) {
      let ad = false;
      let n = h;
      for (let i = 0; i < 10 && n; i++) {
        if (n.getAttribute && n.getAttribute("data-text-ad") === "1") {
          ad = true;
          break;
        }
        n = n.parentElement;
      }
      let cite = "";
      let m = h;
      for (let i = 0; i < 6 && m; i++) {
        const c = m.querySelector ? m.querySelector("cite") : null;
        if (c && c.innerText.trim()) {
          cite = c.innerText.trim();
          break;
        }
        m = m.parentElement;
      }
      rows.push({ title: h.innerText.trim(), cite, ad, top: Math.round(h.getBoundingClientRect().top) });
    }
    const ads = [...document.querySelectorAll('div[data-text-ad="1"]')].map(d =>
      (d.innerText || "").split("\n").slice(0, 3).join(" | "),
    );
    const body = document.body.innerText;
    return {
      rows,
      ads,
      hasAI: /Vista general creada con IA|Resumen de IA|Vista general de IA/i.test(body),
    };
  });

  const organic = data.rows
    .filter(r => !r.ad)
    .sort((a, b) => a.top - b.top)
    .filter((r, i, arr) => i === 0 || r.cite !== arr[i - 1].cite || r.title !== arr[i - 1].title);

  const clean = (s) => s.replace(/^https?:\/\//, "").replace(/^www\./, "").split(/[›/]/)[0].trim();
  const top = organic.slice(0, 10).map(r => clean(r.cite) || "?");
  const ourIndex = organic.findIndex(r => (r.cite + r.title).toLowerCase().includes(targetDomain));

  const now = new Date().toISOString().replace("T", " ").slice(0, 19);
  console.log(`SERP ${now} | q="${query}" | ads: ${data.ads.length} | AI overview: ${data.hasAI ? "yes" : "no"}`);
  console.log(`gsc: ${data.ads.map(a => a.split("|")[0].trim()).join(" / ") || "-"}`);
  organic.slice(0, 10).forEach((r, i) => {
    const mark = (r.cite + r.title).toLowerCase().includes(targetDomain) ? "  <<< TARGET" : "";
    console.log(`${String(i + 1).padStart(2)}. ${(clean(r.cite) || "?").padEnd(28)} | ${r.title.slice(0, 62)}${mark}`);
  });
  console.log(`OUR ORGANIC POSITION: ${ourIndex >= 0 ? ourIndex + 1 : "not in top 10"}`);

  await mkdir("seo/reports", { recursive: true });
  const csv = ["date", "query", "ads", "ai_overview", "position", "top10"].join(",") + "\n";
  const row =
    [
      now,
      `"${query}"`,
      data.ads.length,
      data.hasAI ? "yes" : "no",
      ourIndex >= 0 ? ourIndex + 1 : 0,
      `"${top.join(" > ")}"`,
    ].join(",") + "\n";
  const file = join("seo", "reports", "serp-history.csv");
  await appendFile(file, existsSync(file) ? row : csv + row);
  console.log(`logged -> ${file}`);
} finally {
  await browser.close();
}
