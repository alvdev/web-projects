/**
 * LinkedIn publishing via Buffer — same SEPARATE Buffer workspace/key as GBP
 * (GBP_BUFFER_ACCESS_TOKEN). Channel: "urban-style-publicity" (service:
 * "linkedin", id 6a90c4c6ccaf649a672e0050, verified 2026-08-28).
 *
 * LinkedIn needs no metadata (unlike Facebook/Google): a plain text + optional
 * image post works. @mentions are stripped by the caller (stripMentions from
 * gbp.ts) — real LinkedIn mentions would require resolving org URNs
 * (AnnotationInputLinkedIn), left as future work.
 */

const API_URL = "https://api.buffer.com/graphql";

function token(): string {
  const t = process.env.GBP_BUFFER_ACCESS_TOKEN ?? "";
  if (!t) throw new Error("GBP_BUFFER_ACCESS_TOKEN not set");
  return t;
}

async function gql<T>(query: string, variables?: Record<string, unknown>): Promise<T> {
  const res = await fetch(API_URL, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${token()}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ query, variables }),
  });
  if (!res.ok) throw new Error(`Buffer API HTTP ${res.status}`);
  const data = (await res.json()) as { errors?: { message: string }[]; data?: T };
  if (data.errors?.length) {
    throw new Error(`Buffer API: ${data.errors.map((e) => e.message).join("; ")}`);
  }
  return data.data as T;
}

interface OrgQuery {
  account: { organizations: { id: string }[] };
}

interface ChannelsQuery {
  channels: { id: string; name: string; service: string }[];
}

interface CreatePostResult {
  createPost:
    | { post: { id: string; externalLink?: string } }
    | { message?: string };
}

/** Find the LinkedIn channel in the GBP Buffer workspace. */
export async function getLinkedInChannel(): Promise<{ id: string; name: string }> {
  const orgId =
    process.env.GBP_BUFFER_ORGANIZATION_ID ??
    (async () => {
      const org = await gql<OrgQuery>("{ account { organizations { id } } }");
      return org.account.organizations[0]?.id;
    })();

  const { channels } = await gql<ChannelsQuery>(
    "query Channels($orgId: OrganizationId!) { channels(input: { organizationId: $orgId }) { id name service } }",
    { orgId: await orgId },
  );
  const channel = channels.find((c) => c.service === "linkedin");
  if (!channel) throw new Error("No LinkedIn channel found in Buffer");
  return { id: channel.id, name: channel.name };
}

/**
 * Publish a LinkedIn post immediately (mode: shareNow) to the page channel.
 * If imageUrl is provided, Buffer fetches it server-side and attaches it.
 * LinkedIn requires no metadata.
 */
export async function createLinkedInPost(
  text: string,
  imageUrl?: string,
): Promise<{ id: string; externalLink?: string }> {
  const assets = imageUrl ? [{ image: { url: imageUrl } }] : [];
  const result = await gql<CreatePostResult>(
    `mutation CreatePost($input: CreatePostInput!) {
      createPost(input: $input) {
        ... on PostActionSuccess { post { id externalLink } }
        ... on InvalidInputError { message }
        ... on UnauthorizedError { message }
        ... on LimitReachedError { message }
        ... on UnexpectedError { message }
      }
    }`,
    {
      input: {
        channelId: (await getLinkedInChannel()).id,
        text,
        mode: "shareNow",
        schedulingType: "automatic",
        assets,
      },
    },
  );

  const payload = result.createPost;
  if ("post" in payload) {
    return { id: payload.post.id, externalLink: payload.post.externalLink };
  }
  throw new Error(`Buffer createPost failed: ${payload.message ?? "unknown error"}`);
}