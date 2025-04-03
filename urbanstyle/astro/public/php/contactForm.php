<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start output buffering
ob_start();

header('Content-Type: application/json; charset=utf-8');

try {
    require __DIR__ . '/../vendor/autoload.php';
    // require_once 'dbRegistry.php';

    Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../')->load();

    // Validate required fields
    $required = ['name', 'email', 'message'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es requerido");
        }
    }

    // Sanitize inputs
    $submission = [
        'name' => filter_var($_POST['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
        'phone' => filter_var($_POST['phone'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'message' => filter_var($_POST['message'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)
    ];

    // Validate email format
    if (!filter_var($submission['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Correo electrónico inválido");
    }

    // Honeypot check
    if (!empty($_POST['website'])) {
        throw new Exception("Detectado como spam");
    }

    // Link spam check
    if (preg_match('/http|www/i', $submission['message'])) {
        throw new Exception("El mensaje no debe contener enlaces");
    };

    // Verify Turnstile captcha response
    $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';
    $turnstileSecret = getenv('TURNSTILE_SECRET'); // Replace with your Turnstile secret key

    // Make the verification request, using file_get_contents or cURL
    $verifyUrl = "https://challenges.cloudflare.com/turnstile/v0/siteverify?secret=" . urlencode($turnstileSecret) . "&response=" . urlencode($turnstileResponse);
    $data = [
        'secret'   => $turnstileSecret,
        'response' => $turnstileResponse,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $verifyUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $verifyResponse = curl_exec($ch);
    curl_close($ch);

    // file_put_contents('captcha-debug.log', $verifyResponse); // Save the response for debugging

    if ($verifyResponse === false) {
        throw new Exception("Error al verificar captcha");
    }
    $captchaData = json_decode($verifyResponse);
    if (!$captchaData->success) {
        throw new Exception("La verificación captcha ha fallado. Por favor, inténtalo de nuevo o reinicia el formulario.");
    }

    // Save submission data in SQLite. If enabled, reload page and success message is gone
    // saveSubmission($submission); 

    // Continue with sending email
    sendEmail($submission);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    ob_end_flush(); // Send output buffer
    exit;
}

function sendEmail($submission)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0; // CRITICAL: Keep debug disabled
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = getenv('SMTP_HOST');
        $mail->Username = getenv('SMTP_USER');
        $mail->Password = getenv('SMTP_PASSWORD');
        $mail->Port = getenv('SMTP_PORT');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        // Recipients
        $mail->setFrom(getenv('FROM_EMAIL'), getenv('FROM_NAME'));
        $mail->addAddress(getenv('TO_EMAIL'), getenv('TO_NAME'));

        // Fix capitalization
        $submission['name'] = mb_convert_case($submission['name'], MB_CASE_TITLE, 'UTF-8');

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Subject = "Consulta de " . html_entity_decode($submission['name'], ENT_QUOTES, 'UTF-8');
        $mail->addReplyTo($submission['email'], $submission['name']);

        $mail->Body = sprintf(
            "
<strong>Nombre:</strong> %s<br>
<strong>Email:</strong> %s<br>
<strong>Teléfono:</strong> %s<br><br>\n
<strong>Consulta:</strong><br>\n%s
",
            $submission['name'],
            $submission['email'],
            $submission['phone'],
            nl2br($submission['message'])
        );

        $mail->AltBody = html_entity_decode(strip_tags($mail->Body), ENT_QUOTES, 'UTF-8');

        $mail->send();

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Tu consulta se ha enviado correctamente. Te responderemos lo antes posible.'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al enviar el mensaje: ' . $e->getMessage()
        ]);
    }

    // Flush output buffer and exit
    ob_end_flush();
    exit;
}

// Should never reach here if sendEmail exits properly
ob_end_clean();
