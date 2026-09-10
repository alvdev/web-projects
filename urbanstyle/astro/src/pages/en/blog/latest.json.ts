import { getCollection } from "astro:content";
import { parsePublishDate } from "@/utils/parseDate";
import { entryMatchesLocale, type Locale } from "@/i18n/ui";

// Spanish (default locale) latest posts. Localized variants live under
// src/pages/{en,it,fr,pt}/blog/latest.json.ts with a different LOCALE.
const LOCALE: Locale = "en";

export async function GET() {
    const prefix = import.meta.env.BASE_URL + (LOCALE === "es" ? "" : `${LOCALE}/`);
    const posts = (await getCollection("posts"))
        .filter((e) => entryMatchesLocale(e, LOCALE))
        .sort((a, b) =>
            parsePublishDate(b.data.pubDate).getTime() - parsePublishDate(a.data.pubDate).getTime(),
        )
        .slice(0, 5)
        .map((p) => ({
            title: p.data.title,
            url: prefix + "blog/" + p.id.split("/").pop(),
            pubDate: p.data.pubDate,
        }));

    return new Response(JSON.stringify(posts), {
        headers: { "Content-Type": "application/json; charset=utf-8" },
    });
}