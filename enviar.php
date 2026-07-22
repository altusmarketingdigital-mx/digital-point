<?php
// Configurar cabeceras de respuesta HTTP
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

// 1. Obtener datos desde cualquier fuente (POST, REQUEST o JSON)
$inputJSON = json_decode(file_get_contents('php://input'), true);
if (!is_array($inputJSON)) {
    $inputJSON = [];
}

function getParam($key, $json) {
    if (isset($_POST[$key]) && trim((string)$_POST[$key]) !== '') {
        return trim((string)$_POST[$key]);
    }
    if (isset($_REQUEST[$key]) && trim((string)$_REQUEST[$key]) !== '') {
        return trim((string)$_REQUEST[$key]);
    }
    if (isset($json[$key]) && trim((string)$json[$key]) !== '') {
        return trim((string)$json[$key]);
    }
    return '';
}

// Probar variantes de nombres de campo (mayúsculas y minúsculas)
$nombre   = getParam('Nombre', $inputJSON)   ?: getParam('nombre', $inputJSON)   ?: getParam('name', $inputJSON);
$contacto = getParam('Contacto', $inputJSON) ?: getParam('contacto', $inputJSON) ?: getParam('email', $inputJSON);
$mensaje  = getParam('Mensaje', $inputJSON)  ?: getParam('mensaje', $inputJSON)  ?: getParam('message', $inputJSON);

// 2. BLOQUEO ABSOLUTO: Si cualquier campo viene vacío o con solo espacios, NUNCA enviar correo
if (empty($nombre) || empty($contacto) || empty($mensaje) || strlen($nombre) === 0 || strlen($contacto) === 0 || strlen($mensaje) === 0) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Bloqueado: No se pueden enviar correos vacíos. Todos los campos son obligatorios."
    ]);
    exit();
}

// 3. Sanitización para prevenir inyecciones y problemas de formato
$nombreSanitizado   = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
$contactoSanitizado = htmlspecialchars($contacto, ENT_QUOTES, 'UTF-8');
$mensajeTextoPlano  = strip_tags($mensaje);
$mensajeHTML        = nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'));

// 4. Configurar destinatarios y asunto
$destinatario = "digitalpointmx1@gmail.com, ing.castro.95@gmail.com, facturacion@digitalpoint.mx";
$asunto = "¡Nuevo prospecto web: " . strip_tags($nombre) . "!";

// 5. Determinar dominio para la cabecera From
$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : 'digitalpoint.mx';
if (strpos($host, 'www.') === 0) {
    $host = substr($host, 4);
}

// 6. Construir mensaje en formato multipart/alternative (Texto Plano + HTML)
// Esto garantiza que el correo se lea correctamente en CUALQUIER cliente de correo (Gmail, Outlook, Webmail)
$boundary = "----=_NextPart_" . md5(time());

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"\r\n";
$headers .= "From: Digital Point Webmaster <webmaster@" . $host . ">\r\n";

$contactoLimpio = strip_tags($contacto);
if (filter_var($contactoLimpio, FILTER_VALIDATE_EMAIL)) {
    $headers .= "Reply-To: " . $contactoLimpio . "\r\n";
} else {
    $headers .= "Reply-To: no-reply@" . $host . "\r\n";
}

$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// Versión en Texto Plano
$cuerpoTexto  = "NUEVO MENSAJE DESDE EL SITIO WEB DIGITAL POINT\r\n";
$cuerpoTexto .= "---------------------------------------------\r\n\r\n";
$cuerpoTexto .= "Nombre completo: " . strip_tags($nombre) . "\r\n";
$cuerpoTexto .= "Correo o WhatsApp: " . $contactoLimpio . "\r\n\r\n";
$cuerpoTexto .= "Mensaje:\r\n" . $mensajeTextoPlano . "\r\n";

// Versión en HTML
$cuerpoHTML = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo prospecto web</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h2 style="color: #1f2937; margin-top: 0; border-bottom: 3px solid #facc15; padding-bottom: 10px;">¡Nuevo mensaje desde el sitio web!</h2>
        <p style="color: #4b5563; font-size: 15px;">Has recibido una nueva consulta desde el formulario de contacto de <strong>Digital Point</strong>:</p>
        
        <table style="width: 100%; margin-top: 20px; border-collapse: collapse;">
            <tr>
                <td style="padding: 12px; background-color: #f8fafc; font-weight: bold; width: 35%; color: #374151; border: 1px solid #e2e8f0;">Nombre completo:</td>
                <td style="padding: 12px; background-color: #ffffff; color: #111827; border: 1px solid #e2e8f0;">' . $nombreSanitizado . '</td>
            </tr>
            <tr>
                <td style="padding: 12px; background-color: #f8fafc; font-weight: bold; color: #374151; border: 1px solid #e2e8f0;">Correo o WhatsApp:</td>
                <td style="padding: 12px; background-color: #ffffff; color: #111827; border: 1px solid #e2e8f0;">' . $contactoSanitizado . '</td>
            </tr>
            <tr>
                <td style="padding: 12px; background-color: #f8fafc; font-weight: bold; vertical-align: top; color: #374151; border: 1px solid #e2e8f0;">Mensaje:</td>
                <td style="padding: 12px; background-color: #ffffff; color: #111827; border: 1px solid #e2e8f0;">' . $mensajeHTML . '</td>
            </tr>
        </table>
        
        <p style="margin-top: 25px; font-size: 12px; color: #9ca3af; text-align: center;">Este correo fue generado automáticamente desde el formulario web de Digital Point.</p>
    </div>
</body>
</html>
';

// Unir ambas versiones en la estructura MIME Multipart
$body  = "--" . $boundary . "\r\n";
$body .= "Content-Type: text/plain; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= $cuerpoTexto . "\r\n\r\n";
$body .= "--" . $boundary . "\r\n";
$body .= "Content-Type: text/html; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= $cuerpoHTML . "\r\n\r\n";
$body .= "--" . $boundary . "--";

// 7. Enviar correo
if (@mail($destinatario, $asunto, $body, $headers)) {
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Enviado con éxito"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al enviar el correo desde el servidor."]);
}
exit();
?>
