/**
 * Facebook Graph API client — direct page posting with the user's own Page
 * Access Token (real link preview via `link`, API-based post deletion).
 * Buffer remains the fallback. NOTE: real page MENTIONS are not possible via
 * any API path (investigated 2026-08-17): message_tags is ignored on create,
 * @[page-id] in the message is stripped, tags is blocked — Facebook only
 * allows mentioning other pages from its composer UI.
 */

const GRAPH_URL = "https://graph.facebook.com/v21.0";

function pageToken(): string {
  const t = process.env.FB_ACCESS_TOKEN ?? "";
  if (!t) throw new Error("FB_ACCESS_TOKEN not set");
  return t;
}

function pageId(): string {
  const id = process.env.FB_PAGE_ID ?? "";
  if (!id) throw new Error("FB_PAGE_ID not set");
  return id;
}

/**
 * Resolve a page username to its numeric id via the Graph API. Without the
 * "Page Public Content Access" feature (app review) this fails for pages the
 * app does not own — returns null then.
 */
export async function resolvePageId(username: string): Promise<string | null> {
  try {
    const res = await fetch(`${GRAPH_URL}/${encodeURIComponent(username)}?fields=id&access_token=${pageToken()}`, {
      signal: AbortSignal.timeout(20_000),
    });
    const data = (await res.json()) as { id?: string; error?: { message: string } };
    if (data.id) return data.id;
    if (data.error) console.warn(`[facebook] resolvePageId ${username}: ${data.error.message}`);
    return null;
  } catch (err) {
    console.warn(`[facebook] resolvePageId ${username} failed:`, (err as Error).message);
    return null;
  }
}

/**
 * Publish to the page feed. `link` gives an automatic link preview (og:image).
 * Returns the post external link (https://facebook.com/<pageid>_<postid>).
 */
export async function createFbPost(
  message: string,
  link: string,
): Promise<{ id: string; externalLink: string }> {
  const body = new URLSearchParams();
  body.set("message", message);
  body.set("link", link);

  const res = await fetch(`${GRAPH_URL}/${pageId()}/feed?access_token=${pageToken()}`, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body,
    signal: AbortSignal.timeout(30_000),
  });
  const data = (await res.json()) as { id?: string; error?: { message: string; code?: number } };
  if (data.error) {
    throw new Error(`Facebook API: ${data.error.message}`);
  }
  if (!data.id) throw new Error("Facebook API: no post id returned");
  const externalLink = `https://facebook.com/${data.id}`;
  return { id: data.id, externalLink };
}

/** Delete a post (works via the direct API — Buffer cannot delete). */
export async function deleteFbPost(postId: string): Promise<void> {
  const res = await fetch(`${GRAPH_URL}/${postId}?access_token=${pageToken()}`, { method: "DELETE" });
  const data = (await res.json()) as { success?: boolean; error?: { message: string } };
  if (data.error) throw new Error(`Facebook API delete: ${data.error.message}`);
}
