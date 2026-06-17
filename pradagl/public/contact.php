<?php

$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
}
require $autoload;

use PHPMailer\PHPMailer\PHPMailer;

$dotenv = Dotenv\Dotenv::createUnsafeMutable([__DIR__, __DIR__ . '/..']);
$dotenv->safeLoad();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$company = trim($_POST['company'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');
$turnstileToken = $_POST['cf-turnstile-response'] ?? '';

$emailOk = (bool) preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email);
$phoneOk = (bool) preg_match('/^[+0-9\s().\-]{6,}$/', $phone);

if (strlen($name) < 2) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio.']); exit; }
if (strlen($company) < 2) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'La empresa es obligatoria.']); exit; }
if (!$emailOk) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Email no válido.']); exit; }
if (!$phoneOk) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Teléfono no válido.']); exit; }
if (!$turnstileToken) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Falta la verificación anti-bot.']); exit; }

$secret = getenv('TURNSTILE_SECRET');
if (!$secret) {
    error_log('TURNSTILE_SECRET env var is missing — set it in .env or the server environment.');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor. Inténtalo de nuevo más tarde.']);
    exit;
}

$turnstileData = null;
$turnstileResponse = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query(['secret' => $secret, 'response' => $turnstileToken]),
        'timeout' => 10,
    ],
]));
if ($turnstileResponse === false && function_exists('curl_init')) {
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $turnstileToken]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $turnstileResponse = curl_exec($ch);
    curl_close($ch);
}
if ($turnstileResponse === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno: no se pudo verificar el captcha.']);
    exit;
}
$turnstileData = json_decode($turnstileResponse, true);
if (empty($turnstileData['success'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Verificación anti-bot falló.']);
    exit;
}

$smtpHost = getenv('SMTP_HOST');
$smtpPort = (int) (getenv('SMTP_PORT') ?: '465');
$smtpUser = getenv('SMTP_USER');
$smtpPass = getenv('SMTP_PASS');
$smtpFrom = getenv('SMTP_FROM');
$smtpTo = getenv('SMTP_TO');

$missing = array_filter(
    ['SMTP_HOST' => $smtpHost, 'SMTP_USER' => $smtpUser, 'SMTP_PASS' => $smtpPass, 'SMTP_FROM' => $smtpFrom, 'SMTP_TO' => $smtpTo],
    fn($v) => empty($v)
);
if ($missing) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server misconfigured: missing env vars ' . implode(', ', array_keys($missing))]);
    exit;
}

$secure = strtolower(getenv('SMTP_SECURE') ?: 'true') !== 'false'
    ? PHPMailer::ENCRYPTION_SMTPS
    : PHPMailer::ENCRYPTION_STARTTLS;

$hostname = $_SERVER['HTTP_HOST'] ?? 'verificado';
$date = date('c');

function escapeHtml(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$text = "Nuevo contacto desde el formulario web\n\n"
    . "Nombre:    $name\n"
    . "Empresa:   $company\n"
    . "Email:     $email\n"
    . "Teléfono:  $phone\n"
    . ($message ? "Mensaje:\n$message\n\n" : "\n")
    . "Turnstile: $hostname\n"
    . "Fecha:     $date\n";

$logo = '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="#e63946" stroke-width="2.5" stroke-linejoin="round">
  <path d="M3 17 L12 5 L21 17 Z"/><circle cx="12" cy="20" r="1.6" fill="#e63946" stroke="none"/>
</svg>';

$rows = '
      <tr>
        <td style="padding:10px 16px 10px 0;color:#64748b;font-size:13px;white-space:nowrap;vertical-align:top;border-bottom:1px solid #f1f5f9;">Nombre</td>
        <td style="padding:10px 0;color:#0a1f44;font-weight:600;font-size:14px;border-bottom:1px solid #f1f5f9;">' . escapeHtml($name) . '</td>
      </tr>
      <tr>
        <td style="padding:10px 16px 10px 0;color:#64748b;font-size:13px;white-space:nowrap;vertical-align:top;border-bottom:1px solid #f1f5f9;">Empresa</td>
        <td style="padding:10px 0;color:#0a1f44;font-weight:600;font-size:14px;border-bottom:1px solid #f1f5f9;">' . escapeHtml($company) . '</td>
      </tr>
      <tr>
        <td style="padding:10px 16px 10px 0;color:#64748b;font-size:13px;white-space:nowrap;vertical-align:top;border-bottom:1px solid #f1f5f9;">Email</td>
        <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;"><a href="mailto:' . escapeHtml($email) . '" style="color:#e63946;text-decoration:none;font-weight:600;font-size:14px;">' . escapeHtml($email) . '</a></td>
      </tr>
      <tr>
        <td style="padding:10px 16px 10px 0;color:#64748b;font-size:13px;white-space:nowrap;vertical-align:top;border-bottom:1px solid #f1f5f9;">Teléfono</td>
        <td style="padding:10px 0;color:#0a1f44;font-weight:600;font-size:14px;border-bottom:1px solid #f1f5f9;">' . escapeHtml($phone) . '</td>
      </tr>' . ($message ? '
      <tr>
        <td style="padding:10px 16px 10px 0;color:#64748b;font-size:13px;white-space:nowrap;vertical-align:top;border-bottom:1px solid #f1f5f9;">Mensaje</td>
        <td style="padding:10px 0;color:#0a1f44;font-size:14px;white-space:pre-wrap;border-bottom:1px solid #f1f5f9;">' . escapeHtml($message) . '</td>
      </tr>' : '');

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:system-ui,-apple-system,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr><td align="center" style="padding:32px 16px;">
      <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;">
        <tr>
          <td style="background:#0a1f44;border-radius:12px 12px 0 0;padding:28px 32px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="vertical-align:middle;">
                  <span style="display:inline-flex;align-items:center;gap:10px;color:#fff;font-size:20px;font-weight:700;">
                    {$logo}
                    Prada <span style="color:#e63946;">GL</span>
                  </span>
                </td>
                <td style="text-align:right;vertical-align:middle;">
                  <span style="color:#fbbf24;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;background:rgba(255,255,255,0.1);padding:4px 10px;border-radius:4px;">Nuevo contacto</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="background:#ffffff;padding:32px;border-radius:0 0 12px 12px;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <p style="margin:0 0 20px;color:#0a1f44;font-size:16px;font-weight:600;">Te han escrito desde el formulario web</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
              {$rows}
            </table>
            <p style="margin:24px 0 0;color:#94a3b8;font-size:11px;padding-top:16px;">
              Turnstile: {$hostname} · {$date}
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->Port = $smtpPort;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = $secure;
    $mail->CharSet = 'UTF-8';

    if (preg_match('/^(.+?)<(.+@.+)>$/', $smtpFrom, $m)) {
        $mail->setFrom(trim($m[2]), trim($m[1]));
    } else {
        $mail->setFrom($smtpFrom);
    }
    $mail->addAddress($smtpTo);
    $mail->addReplyTo($email, $name);
    $mail->Subject = "Nuevo contacto · $company";
    $mail->Body = $html;
    $mail->isHTML(true);
    $mail->AltBody = $text;
    $mail->send();

    echo json_encode(['ok' => true, 'message' => 'Mensaje enviado']);
} catch (Exception $e) {
    $msg = $mail->ErrorInfo ?: $e->getMessage();
    error_log("PHPMailer error: $msg");
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'SMTP send failed: ' . $msg]);
}
