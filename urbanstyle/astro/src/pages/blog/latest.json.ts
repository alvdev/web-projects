import { getCollection } from "astro:content";
import { parsePublishDate } from "@/utils/parseDate";

export async function GET() {
    const posts = (await getCollection("posts"))
        .sort((a, b) =>
            parsePublishDate(b.data.pubDate).getTime() - parsePublishDate(a.data.pubDate).getTime(),
        )
        .slice(0, 5)
        .map((p) => ({
            title: p.data.title,
            url: import.meta.env.BASE_URL + "blog/" + (p.data.slug || p.id),
            pubDate: p.data.pubDate,
        }));

    return new Response(JSON.stringify(posts), {
        headers: { "Content-Type": "application/json; charset=utf-8" },
    });
}
