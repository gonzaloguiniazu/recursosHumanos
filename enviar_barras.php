<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    header("Location: index.php");
    exit;
}

require 'vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Conectar a la base de datos
$mysqli = new mysqli('localhost', 'root', '', 'recursosdb'); // Cambiar si tu DB tiene otros datos

if ($mysqli->connect_error) {
    die('Error de conexión: ' . $mysqli->connect_error);
}

// Buscar datos del empleado por su legajo
$nro_legajo = $_SESSION['legajo'];
$stmt = $mysqli->prepare("SELECT nombre, apellido, email FROM empleados WHERE nro_legajo = ?");
$stmt->bind_param("s", $nro_legajo);
$stmt->execute();
$stmt->bind_result($nombre, $apellido, $email);
$stmt->fetch();
$stmt->close();
$mysqli->close();

// Si no se encuentra el correo, abortar
if (empty($email)) {
    die('No se encontró el correo del empleado.');
}

// Generar código de barras como imagen
$generator = new BarcodeGeneratorPNG();
$barcodeData = $generator->getBarcode($nro_legajo, $generator::TYPE_CODE_128, 4, 100);
$barcodeBase64 = base64_encode($barcodeData);

// Configurar PHPMailer con Gmail
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'gonzaloguiniazu@gmail.com'; // <-- tu correo Gmail real
    $mail->Password = 'gcts ogqq atip fwwk'; // <-- contraseña generada en Google
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('gonzaloguioniazu@gmail.com', 'Recursos Humanos'); // Mismo que Username
    $mail->addAddress($email, "$nombre $apellido");

    $mail->isHTML(true);
    $mail->Subject = 'Tu código de barras para fichar';
    $mail->Body = "
        <p>Hola <strong>$nombre $apellido</strong>,</p>
        <p>Este es tu código de barras personal para fichar:</p>
        <img src='data:image/png;base64,$barcodeBase64' alt='Código de Barras'><br><br>
        <p>Te recomendamos guardar esta imagen en tu celular.</p>
        <p>Saludos,<br>RRHH</p>
    ";

    $mail->send();
    echo 'El correo fue enviado correctamente a ' . htmlspecialchars($email);
} catch (Exception $e) {
    echo 'Error al enviar el correo: ', $mail->ErrorInfo;
}
?>

