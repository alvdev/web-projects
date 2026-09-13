import { readFileSync, readdirSync, statSync } from "node:fs";
import { join } from "node:path";

const DIST = "dist";
const SITE = "https://urbanstylepublicity.com";

function* walk(dir: string): Generator<string> {
    for (const name of readdirSync(dir)) {
        const p = join(dir, name);
        if (statSync(p).isDirectory()) yield* walk(p);
        else if (p.endsWith(".html")) yield p;
    }
}

const ALT_RE = /<link rel="alternate" hreflang="([^"]+)" href="([^"]+)"/g;
const CANONICAL_RE = /<link rel="canonical" href="([^"]+)"/;

function urlToFile(url: string): string {
    const path = decodeURIComponent(new URL(url).pathname).replace(/^\//, "");
    return path === "" ? join(DIST, "index.html") : join(DIST, path, "index.html");
}

type PageInfo = {
    file: string;
    canonical: string;
    alternates: { lang: string; href: string }[];
};

const pages: PageInfo[] = [];
for (const file of walk(DIST)) {
    const html = readFileSync(file, "utf8");
    const alternates = [...html.matchAll(ALT_RE)].map(m => ({ lang: m[1], href: m[2] }));
    if (alternates.length === 0) continue;
    const canonical = html.match(CANONICAL_RE)?.[1] ?? "";
    pages.push({ file, canonical, alternates });
}

const byCanonical = new Map(pages.map(p => [p.canonical, p]));
let errors = 0;

for (const page of pages) {
    if (!page.canonical) {
        errors++;
        console.error(`✗ no canonical: ${page.file}`);
        continue;
    }
    for (const { lang, href } of page.alternates) {
        // target must be a built page
        const targetFile = urlToFile(href);
        let exists = false;
        try {
            exists = statSync(targetFile).isFile();
        } catch {
            exists = false;
        }
        if (!exists) {
            errors++;
            console.error(`✗ ${page.file}\n    → ${lang} target missing: ${href}`);
            continue;
        }
        if (lang === "x-default") continue;

        // reciprocity
        const target = byCanonical.get(href);
        if (!target) {
            errors++;
            console.error(`✗ ${page.file}\n    → ${lang} target has no hreflang block: ${href}`);
            continue;
        }
        if (!target.alternates.some(a => a.href === page.canonical)) {
            errors++;
            console.error(`✗ not reciprocal:\n    ${page.canonical} → ${href}\n    ${href} does not link back`);
        }
    }
}

console.log(`\nChecked ${pages.length} pages with hreflang blocks.`);
console.log(errors === 0 ? "✓ all hreflang alternates exist and are reciprocal" : `✗ ${errors} error(s)`);
if (errors > 0) process.exit(1);
