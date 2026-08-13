import { z } from "zod";

const RefreshResponseSchema = z.object({
  access_token: z.string(),
  expires_in: z.number(),
});

export const IG_API = "https://graph.instagram.com/v23.0";

export class TokenError extends Error {}

function getToken(): string {
  const token = process.env.IG_ACCESS_TOKEN;
  if (!token) throw new TokenError("IG_ACCESS_TOKEN not set");
  return token;
}

export async function validateToken(): Promise<{ id: string; username: string }> {
  const url = `${IG_API}/me?fields=id,username,account_type&access_token=${getToken()}`;
  const res = await fetch(url);
  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    throw new TokenError(
      `Instagram token invalid or expired: ${(body as { error?: { message?: string } }).error?.message ?? res.statusText}`,
    );
  }
  return res.json();
}

export async function refreshToken(): Promise<string> {
  const url = `${IG_API}/refresh_access_token?grant_type=ig_refresh_token&access_token=${getToken()}`;
  const res = await fetch(url);
  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    throw new TokenError(
      `Token refresh failed: ${(body as { error?: { message?: string } }).error?.message ?? res.statusText}`,
    );
  }
  const parsed = RefreshResponseSchema.parse(await res.json());
  return parsed.access_token;
}
