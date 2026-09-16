import "./helpers/silence";
import { afterAll, beforeEach, describe, expect, mock, test } from "bun:test";

interface MailMessage {
  from?: string;
  to?: string;
  subject?: string;
  text?: string;
}

interface TransportOptions {
  host: string;
  port: number;
  secure: boolean;
  auth: { user: string; pass: string };
}

const sendMailCalls: MailMessage[] = [];
const transportOptions: TransportOptions[] = [];
let sendMailImpl: (msg: MailMessage) => Promise<unknown> = async () => ({ accepted: ["test"] });

mock.module("nodemailer", () => ({
  default: {
    createTransport: (opts: TransportOptions) => {
      transportOptions.push(opts);
      return {
        sendMail: (msg: MailMessage) => {
          sendMailCalls.push(msg);
          return sendMailImpl(msg);
        },
      };
    },
  },
}));

const { sendAlert } = await import("../mailer");

const SMTP_KEYS = ["SMTP_HOST", "SMTP_PORT", "SMTP_USER", "SMTP_PASSWORD", "ALERT_EMAIL"] as const;
const savedEnv: Record<string, string | undefined> = {};
for (const key of SMTP_KEYS) savedEnv[key] = process.env[key];

function setSmtpEnv(port = "587"): void {
  process.env.SMTP_HOST = "smtp.test";
  process.env.SMTP_PORT = port;
  process.env.SMTP_USER = "user@test";
  process.env.SMTP_PASSWORD = "secret";
  process.env.ALERT_EMAIL = "alerts@test";
}

beforeEach(() => {
  sendMailCalls.length = 0;
  transportOptions.length = 0;
  sendMailImpl = async () => ({ accepted: ["test"] });
  for (const key of SMTP_KEYS) delete process.env[key];
});

afterAll(() => {
  for (const key of SMTP_KEYS) {
    if (savedEnv[key] === undefined) delete process.env[key];
    else process.env[key] = savedEnv[key];
  }
});

describe("sendAlert", () => {
  test("skips silently when SMTP env vars are missing", async () => {
    await sendAlert("subject", "body");
    expect(sendMailCalls.length).toBe(0);
    expect(transportOptions.length).toBe(0);
  });

  test("creates a plain transport and sends the alert when configured", async () => {
    setSmtpEnv("587");
    await sendAlert("[Urban Sync] Test", "body text");
    expect(transportOptions.length).toBe(1);
    expect(transportOptions[0]).toEqual({
      host: "smtp.test",
      port: 587,
      secure: false,
      auth: { user: "user@test", pass: "secret" },
    });
    expect(sendMailCalls.length).toBe(1);
    expect(sendMailCalls[0]).toEqual({
      from: '"Urban Style Sync" <user@test>',
      to: "alerts@test",
      subject: "[Urban Sync] Test",
      text: "body text",
    });
  });

  test("uses implicit TLS on port 465", async () => {
    setSmtpEnv("465");
    await sendAlert("s", "b");
    expect(transportOptions[0]?.secure).toBe(true);
  });

  test("swallows transport errors instead of throwing", async () => {
    setSmtpEnv();
    sendMailImpl = async () => {
      throw new Error("SMTP down");
    };
    await expect(sendAlert("s", "b")).resolves.toBeUndefined();
    expect(sendMailCalls.length).toBe(1);
  });
});
