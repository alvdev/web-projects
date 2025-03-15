<?php
// Start output buffering
ob_start();

require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=utf-8');

try {
    // Validate required fields
    $required = ['name', 'email', 'phone'];
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
    }

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
        $mail->Host = "mail.cursohipnosis.com";
        $mail->SMTPAuth = true;
        $mail->Username = "info@cursohipnosis.com";
        $mail->Password = "=wCd14g~shDG";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom("info@cursohipnosis.com", "Formulario web");
        $mail->addAddress("info@cursohipnosis.com", "Destinatario");

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "Nuevo contacto de " . $submission['name'];

        $mail->Body = sprintf(
            "<p><strong>Nombre:</strong> %s</p>
            <p><strong>Email:</strong> %s</p>
            <p><strong>Teléfono:</strong> %s</p>
            <p><strong>Mensaje:</strong><br>%s</p>",
            $submission['name'],
            $submission['email'],
            $submission['phone'],
            nl2br($submission['message'])
        );

        $mail->AltBody = strip_tags($mail->Body);

        $mail->send();

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Tu reserva se ha enviado correctamente. Pronto nos pondremos en contacto contigo.'
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

// Final cleanup if not already exited
ob_end_clean();
