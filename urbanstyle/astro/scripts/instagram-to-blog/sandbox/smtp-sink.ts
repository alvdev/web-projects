/**
 * Minimal in-process SMTP sink for the sandbox. Accepts the AUTH PLAIN/LOGIN
 * handshake nodemailer performs against `auth: { user, pass }` transports and
 * captures every message in memory (plus writes .eml files via the runner).
 */
import { createServer, type Server, type Socket } from "node:net";

export interface CapturedMail {
  from: string;
  to: string;
  raw: string;
}

export interface SmtpSink {
  mails: CapturedMail[];
  port: number;
  close(): Promise<void>;
}

export async function startSmtpSink(port = 0): Promise<SmtpSink> {
  const mails: CapturedMail[] = [];

  const server: Server = createServer((conn: Socket) => {
    let buffer = "";
    let inData = false;
    let data = "";
    let from = "";
    let to = "";
    let authStage = 0; // 0 = none, 1 = awaiting username, 2 = awaiting password

    const write = (line: string): void => {
      conn.write(line);
    };

    const processBuffer = (): void => {
      for (;;) {
        if (authStage > 0) {
          const idx = buffer.indexOf("\r\n");
          if (idx === -1) return;
          buffer = buffer.slice(idx + 2);
          if (authStage === 1) {
            authStage = 2;
            write("334 UGFzc3dvcmQ6\r\n");
          } else {
            authStage = 0;
            write("235 Authentication successful\r\n");
          }
          continue;
        }
        if (inData) {
          const end = buffer.indexOf("\r\n.\r\n");
          if (end === -1) return;
          data += buffer.slice(0, end);
          buffer = buffer.slice(end + 5);
          inData = false;
          mails.push({ from, to, raw: data });
          write("250 Message accepted\r\n");
          continue;
        }
        const idx = buffer.indexOf("\r\n");
        if (idx === -1) return;
        const line = buffer.slice(0, idx);
        buffer = buffer.slice(idx + 2);
        const cmd = line.toUpperCase();
        if (cmd.startsWith("EHLO") || cmd.startsWith("HELO")) {
          write("250-sandbox\r\n250-AUTH PLAIN LOGIN\r\n250 SIZE 10485760\r\n");
        } else if (cmd.startsWith("AUTH PLAIN")) {
          write("235 Authentication successful\r\n");
        } else if (cmd === "AUTH LOGIN") {
          authStage = 1;
          write("334 VXNlcm5hbWU6\r\n");
        } else if (cmd.startsWith("MAIL FROM")) {
          from = line.slice(line.indexOf(":") + 1).trim();
          write("250 OK\r\n");
        } else if (cmd.startsWith("RCPT TO")) {
          to = line.slice(line.indexOf(":") + 1).trim();
          write("250 OK\r\n");
        } else if (cmd === "DATA") {
          inData = true;
          data = "";
          write("354 End data with <CR><LF>.<CR><LF>\r\n");
        } else if (cmd === "QUIT") {
          write("221 Bye\r\n");
          conn.end();
          return;
        } else {
          write("250 OK\r\n");
        }
      }
    };

    conn.on("data", (chunk) => {
      buffer += chunk.toString("utf8");
      processBuffer();
    });
    conn.on("error", () => {});
    write("220 sandbox ESMTP\r\n");
  });

  await new Promise<void>((resolve) => server.listen(port, "127.0.0.1", resolve));
  const address = server.address();
  const actualPort = typeof address === "object" && address ? address.port : port;
  return {
    mails,
    port: actualPort,
    close: () =>
      new Promise<void>((resolve) => {
        server.close(() => resolve());
      }),
  };
}

/** Decode RFC 2047 encoded-words (=?UTF-8?B?...?= / =?UTF-8?Q?...?=). */
export function decodeHeaderValue(value: string): string {
  return value.replace(/=\?UTF-8\?([BQ])\?([^?]*)\?=/gi, (_m, enc: string, text: string) => {
    const upper = enc.toUpperCase();
    if (upper === "B") return Buffer.from(text, "base64").toString("utf8");
    const bytes = text
      .replace(/_/g, " ")
      .replace(/=([0-9A-F]{2})/gi, (_x, hex: string) => String.fromCharCode(parseInt(hex, 16)));
    return Buffer.from(bytes, "binary").toString("utf8");
  });
}

export function mailHeaders(raw: string): Record<string, string> {
  const headerPart = raw.split("\r\n\r\n")[0] ?? "";
  const headers: Record<string, string> = {};
  let current = "";
  for (const line of headerPart.split("\r\n")) {
    if (/^\s/.test(line) && current) {
      headers[current] += ` ${line.trim()}`;
      continue;
    }
    const idx = line.indexOf(":");
    if (idx === -1) continue;
    current = line.slice(0, idx).toLowerCase();
    headers[current] = line.slice(idx + 1).trim();
  }
  return headers;
}

export function mailBody(raw: string): string {
  const idx = raw.indexOf("\r\n\r\n");
  return idx === -1 ? "" : raw.slice(idx + 4);
}
