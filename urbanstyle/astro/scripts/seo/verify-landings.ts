#!/usr/bin/env bun
/**
 * Verifies the 13 service landings in dist/.
 *
 * Usage: bun scripts/seo/verify-landings.ts
 */

import { existsSync, readFileSync, readdirSync } from "node:fs";
import { join } from "node:path";

const SERVICES = [
  "street-marketing",
  "instalacion-lonas-publicitarias",
  "publicidad-exterior-digital",
  "reparto-flyers",
  "diseño-web",
  "diseño-grafico",
  "vinilos-lonas-rotulacion",
  "graffitis",
  "impresion-digital",
  "impresion-offset",
  "locales-universidades",
  "video-fotografia",
  "acciones-publicitarias-especiales",
];

const CAROUSELS: Record<string, string[]> = {
  "street-marketing": ["carousel-street-marketing"],
  "instalacion-lonas-publicitarias": ["carousel-lonas"],
  "vinilos-lonas-rotulacion": ["carousel-vinilos"],
  graffitis: ["carousel-graffitis"],
  "impresion-digital": ["carousel-impresion-digital"],
  "locales-universidades": ["carousel-locales-universidades"],
  "acciones-publicitarias-especiales": ["carousel-acciones"],
};

let failures = 0;

function fail(message: string): void {
  failures++;
  console.error(`  ✗ ${message}`);
}

function pass(message: string): void {
  console.log(`  ✓ ${message}`);
}

for (const service of SERVICES) {
  const htmlPath = join("dist", service, "index.html");
  console.log(`\n== ${service}`);

  if (!existsSync(htmlPath)) {
    fail(`missing ${htmlPath}`);
    continue;
  }

  const html = readFileSync(htmlPath, "utf8");
  const text = html
    .replace(/<script[\s\S]*?<\/script>/g, " ")
    .replace(/<style[\s\S]*?<\/style>/g, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/&[a-z]+;/g, " ")
    .replace(/\s+/g, " ")
    .trim();
  const words = text.split(" ").length;

  if (words < 700) fail(`only ${words} words`); else pass(`${words} words`);

  const h2 = (html.match(/<h2/g) ?? []).length;
  if (h2 < 2) fail(`only ${h2} H2 sections`); else pass(`${h2} H2 sections`);

  const faqs = (html.match(/setActiveAccordion\(id\)/g) ?? []).length;
  if (faqs < 3) fail(`FAQ questions found: ${faqs}`); else pass(`FAQ present (${faqs} questions)`);

  if (!/<meta name="description" content="[^"]{40,}"/.test(html)) fail("missing meta description");

  const serviceLinks = new Set(
    [...html.matchAll(/href="\/([^"\/]+)\/"/gi)]
      .map((m) => m[1])
      .filter((slug) => SERVICES.includes(slug) && slug !== service),
  );
  if (serviceLinks.size < 2) fail(`only ${serviceLinks.size} internal service links`);
  else pass(`${serviceLinks.size} internal service links`);

  for (const folder of CAROUSELS[service] ?? []) {
    const carouselMd = join("src/content/services", service, folder, `${folder}.md`);
    if (!existsSync(carouselMd)) {
      fail(`missing carousel ${carouselMd}`);
      continue;
    }
    const md = readFileSync(carouselMd, "utf8");
    const images = [...md.matchAll(/src:\s*(.+)/g)].map((m) => m[1].trim());
    const missing = images.filter((file) => !existsSync(join("src/content/services", service, folder, file)));
    if (missing.length > 0) fail(`carousel images missing: ${missing.join(", ")}`);
    else pass(`carousel OK (${images.length} images)`);
  }

  const imageRefs = [...readFileSync(join("src/content/services", service, "index.mdx"), "utf8").matchAll(/\.\/images\/([^'")\s]+)/g)];
  const missingImages = imageRefs
    .map((m) => m[1])
    .filter((file) => !existsSync(join("src/content/services", service, "images", file)));
  if (missingImages.length > 0) fail(`missing body images: ${missingImages.join(", ")}`);
}

console.log(`\n${failures === 0 ? "All landings verified ✓" : `${failures} issue(s) found`}`);
process.exit(failures === 0 ? 0 : 1);
