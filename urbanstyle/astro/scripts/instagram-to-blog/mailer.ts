import nodemailer from "nodemailer";

const host = process.env.SMTP_HOST ?? "";
const port = Number(process.env.SMTP_PORT ?? 587);
const user = process.env.SMTP_USER ?? "";
const pass = process.env.SMTP_PASSWORD ?? "";
const to = process.env.ALERT_EMAIL ?? "";

const transporter = nodemailer.createTransport({
  host,
  port,
  secure: port === 465,
  auth: { user, pass },
});

export async function sendAlert(subject: string, body: string): Promise<void> {
  if (!host || !user || !pass || !to) {
    console.warn("[mailer] SMTP env vars missing, skipping alert email");
    return;
  }
  try {
    await transporter.sendMail({
      from: `"Urban Style Sync" <${user}>`,
      to,
      subject,
      text: body,
    });
    console.log(`[mailer] alert sent: ${subject}`);
  } catch (err) {
    console.error("[mailer] failed to send email:", err);
  }
}
