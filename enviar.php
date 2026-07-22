<?php
// Recibir datos del formulario
$nombre = isset($_POST['Nombre']) ? $_POST['Nombre'] : '';
$contacto = isset($_POST['Contacto']) ? $_POST['Contacto'] : '';
$mensaje = isset($_POST['Mensaje']) ? $_POST['Mensaje'] : '';

// Configurar el correo de destino
$destinatario = "digitalpointmx1@gmail.com, ing.castro.95@gmail.com, facturacion@digitalpoint.mx";
$asunto = "¡Nuevo prospecto web: " . $nombre . "!";

// Crear el cuerpo del correo
$cuerpo = "Has recibido un nuevo mensaje desde el sitio web de Digital Point.\n\n";
$cuerpo .= "Nombre: " . $nombre . "\n";
$cuerpo .= "Correo o WhatsApp: " . $contacto . "\n\n";
$cuerpo .= "Mensaje:\n" . $mensaje . "\n";

// Cabeceras básicas para evitar que caiga en Spam y soportar acentos
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/plain; charset=utf-8\r\n";
$headers .= "From: webmaster@" . $_SERVER['HTTP_HOST'] . "\r\n";
$headers .= "Reply-To: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Enviar correo
if(mail($destinatario, $asunto, $cuerpo, $headers)) {
    http_response_code(200);
    echo "Enviado con éxito";
} else {
    http_response_code(500);
    echo "Error al enviar";
}
exit();
?>
