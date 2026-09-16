import nodemailer from "nodemailer";

function smtpConfig(): { host: string; port: number; user: string; pass: string; to: string } {
  return {
    host: process.env.SMTP_HOST ?? "",
    port: Number(process.env.SMTP_PORT ?? 587),
    user: process.env.SMTP_USER ?? "",
    pass: process.env.SMTP_PASSWORD ?? "",
    to: process.env.ALERT_EMAIL ?? "",
  };
}

export async function sendAlert(subject: string, body: string): Promise<void> {
  const { host, port, user, pass, to } = smtpConfig();
  if (!host || !user || !pass || !to) {
    console.warn("[mailer] SMTP env vars missing, skipping alert email");
    return;
  }
  const transporter = nodemailer.createTransport({
    host,
    port,
    secure: port === 465,
    auth: { user, pass },
  });
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
