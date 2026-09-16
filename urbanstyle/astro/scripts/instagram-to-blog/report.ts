/**
 * Publish report: ONE email per ▶️ "Publicar en redes" attempt with the whole
 * publishing process — blog URL, per-platform status, published links and
 * errors. Replaces the old per-platform success/error emails.
 */
import { sendAlert } from "./mailer";
import type { PublishPlatform, PublishResult, PublishedEntry } from "./types";

const PLATFORM_ORDER: PublishPlatform[] = ["x", "facebook", "gbp", "linkedin"];

const PLATFORM_LABELS: Record<PublishPlatform, string> = {
  x: "X",
  facebook: "Facebook",
  gbp: "Google",
  linkedin: "LinkedIn",
};

export function buildPublishReport(
  published: PublishedEntry,
  results: PublishResult[],
): { subject: string; body: string } {
  const byPlatform = new Map(results.map((r) => [r.platform, r]));
  const anyFailed = results.some((r) => !r.ok);
  const subject = `${anyFailed ? "[Urban Sync] Publicado con fallos" : "[Urban Sync] Publicado"}: ${published.title}`;

  const lines: string[] = [
    `Título: ${published.title}`,
    `Blog: https://urbanstylepublicity.com/blog/${published.slug}`,
    `Fecha: ${published.publishedAt}`,
    "",
  ];

  for (const platform of PLATFORM_ORDER) {
    const label = PLATFORM_LABELS[platform];
    const result = byPlatform.get(platform);

    if (result) {
      if (result.status === "published") {
        lines.push(`${label}: ✅ publicado${result.link ? ` — ${result.link}` : " (sin enlace)"}`);
      } else {
        lines.push(`${label}: ❌ error — ${result.error ?? "error desconocido"}`);
      }
      continue;
    }

    const state = published.social[platform];
    if (state?.status === "published") {
      lines.push(`${label}: ✅ ya publicado${state.link ? ` — ${state.link}` : ""}`);
    } else {
      lines.push(`${label}: ⏭ no intentado`);
    }
  }

  return { subject, body: lines.join("\n") };
}

/** Send the single publish report. No-op when no platform was attempted. */
export async function sendPublishReport(
  published: PublishedEntry,
  results: PublishResult[],
): Promise<void> {
  if (results.length === 0) return;
  const { subject, body } = buildPublishReport(published, results);
  await sendAlert(subject, body);
}
