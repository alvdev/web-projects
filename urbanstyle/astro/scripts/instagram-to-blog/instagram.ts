import { IgMediaResponseSchema, type IgPost, type NewPost } from "./types";
import { IG_API } from "./token";

const MAX_PAGES = 10;
const PAGE_LIMIT = 100;

function cleanCaption(caption: string | undefined | null): string {
  if (!caption) return "";
  return caption.replace(/[-\u2014]{3,}\n\n/g, "");
}

export async function fetchAllPosts(token: string): Promise<IgPost[]> {
  const fields = "id,caption,alt_text,media_type,media_url,permalink,thumbnail_url,timestamp";
  let nextUrl: string | null = `${IG_API}/me/media?fields=${fields}&limit=${PAGE_LIMIT}&access_token=${token}`;
  const seen = new Set<string>();
  const all: IgPost[] = [];
  let pageCount = 0;

  while (nextUrl && pageCount < MAX_PAGES) {
    const res = await fetch(nextUrl);
    if (!res.ok) {
      throw new Error(`Instagram media fetch failed: ${res.status} ${res.statusText}`);
    }
    const parsed = IgMediaResponseSchema.parse(await res.json());

    if (!parsed.data || parsed.data.length === 0) break;

    const newIds = parsed.data.map((p) => p.id);
    if (newIds.every((id) => seen.has(id))) break;

    for (const post of parsed.data) {
      if (!seen.has(post.id)) {
        seen.add(post.id);
        all.push(post);
      }
    }

    nextUrl = parsed.paging?.next ?? null;
    pageCount++;
  }

  return all;
}

export function filterNewPosts(posts: IgPost[], stopId: string | null): NewPost[] {
  const result: NewPost[] = [];

  for (const post of posts) {
    if (stopId && post.id === stopId) break;

    const mediaUrl = post.media_url ?? post.thumbnail_url;
    if (!mediaUrl) continue;

    result.push({
      id: post.id,
      caption: cleanCaption(post.caption),
      mediaUrl,
      timestamp: post.timestamp,
      mediaType: post.media_type,
    });
  }

  return result;
}
