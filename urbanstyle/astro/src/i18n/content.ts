import { getCollection } from "astro:content";
import { languages, type Locale } from "./config";
import { entryMatchesLocale } from "./ui";

export async function getPostLocaleVariants(slug: string): Promise<Locale[]> {
    const posts = await getCollection("posts");
    return (Object.keys(languages) as Locale[]).filter(locale =>
        posts.some(entry => entryMatchesLocale(entry, locale) && entry.id.split("/").pop() === slug),
    );
}
