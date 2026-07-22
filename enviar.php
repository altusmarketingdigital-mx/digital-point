<?php
// Permitir solicitudes desde el mismo origen / CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: text/html; charset=UTF-8");

// 1. Obtener datos independientemente de la forma en que se envíen (POST, REQUEST o JSON body)
$inputJSON = json_decode(file_get_contents('php://input'), true);
if (!is_array($inputJSON)) {
    $inputJSON = [];
}

function getParam($key, $json) {
    if (isset($_POST[$key]) && trim($_POST[$key]) !== '') {
        return trim($_POST[$key]);
    }
    if (isset($_REQUEST[$key]) && trim($_REQUEST[$key]) !== '') {
        return trim($_REQUEST[$key]);
    }
    if (isset($json[$key]) && trim($json[$key]) !== '') {
        return trim($json[$key]);
    }
    return '';
}

// Probar variantes de nombres de campos en mayúsculas / minúsculas
$nombre   = getParam('Nombre', $inputJSON)   ?: getParam('nombre', $inputJSON)   ?: getParam('name', $inputJSON);
$contacto = getParam('Contacto', $inputJSON) ?: getParam('contacto', $inputJSON) ?: getParam('email', $inputJSON);
$mensaje  = getParam('Mensaje', $inputJSON)  ?: getParam('mensaje', $inputJSON)  ?: getParam('message', $inputJSON);

// Sanitización
$nombreSanitizado   = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
$contactoSanitizado = htmlspecialchars($contacto, ENT_QUOTES, 'UTF-8');
$mensajeSanitizado  = nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'));

// 2. VALIDACIÓN: Si falta algún campo obligatorio, NO enviar correo en blanco
if (empty($nombre) || empty($contacto) || empty($mensaje)) {
    http_response_code(400);
    echo "Error: Todos los campos son obligatorios y no pueden estar vacíos.";
    exit();
}

// 3. Configurar destinatarios y asunto
$destinatario = "digitalpointmx1@gmail.com, ing.castro.95@gmail.com, facturacion@digitalpoint.mx";
$asunto = "¡Nuevo prospecto web: " . strip_tags($nombre) . "!";

// 4. Crear cuerpo del correo en HTML estilizado
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
                <td style="padding: 12px; background-color: #ffffff; color: #111827; border: 1px solid #e2e8f0;">' . $mensajeSanitizado . '</td>
            </tr>
        </table>
        
        <p style="margin-top: 25px; font-size: 12px; color: #9ca3af; text-align: center;">Este correo fue generado automáticamente desde el formulario web de Digital Point.</p>
    </div>
</body>
</html>
';

// 5. Configurar cabeceras del correo con \r\n según estándar RFC 5322
$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : 'digitalpoint.mx';
if (strpos($host, 'www.') === 0) {
    $host = substr($host, 4);
}

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=utf-8\r\n";
$headers .= "From: Digital Point Webmaster <webmaster@" . $host . ">\r\n";

// Si el contacto incluye un correo válido, configurarlo en Reply-To
$contactoLimpio = strip_tags($contacto);
if (filter_var($contactoLimpio, FILTER_VALIDATE_EMAIL)) {
    $headers .= "Reply-To: " . $contactoLimpio . "\r\n";
} else {
    $headers .= "Reply-To: no-reply@" . $host . "\r\n";
}

$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// 6. Enviar correo
if (@mail($destinatario, $asunto, $cuerpoHTML, $headers)) {
    http_response_code(200);
    echo "Enviado con éxito";
} else {
    http_response_code(500);
    echo "Error al enviar el correo desde el servidor.";
}
exit();
?>
