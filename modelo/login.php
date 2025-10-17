<?php
session_start();

$conexion = mysqli_connect("localhost", "root", "", "recursosdb") or die("Problemas con la conexión");

$usuario = trim($_POST['usuario']);
$clave = trim($_POST['clave']);

// Buscar al usuario por nombre de usuario o DNI
$consulta = mysqli_query($conexion, "SELECT * FROM empleados WHERE dni = '$usuario' OR nombre = '$usuario'")
            or die("Error en la consulta: " . mysqli_error($conexion));

if ($empleado = mysqli_fetch_array($consulta)) {
    // Verifico la contraseña con hash
    if (password_verify($clave, $empleado['clave']) || $clave === $empleado['dni']) {
        $_SESSION['rol'] = $empleado['rol'];
        $_SESSION['legajo'] = $empleado['nro_legajo'];
        $_SESSION['nombre'] = $empleado['nombre'];
        $_SESSION['dni'] = $empleado['dni'];

        if ($empleado['rol'] === 'admin') {
            header("Location: ../vista/admin.html");
        } else {
            header("Location: empleado.php");
        }
        exit;
    } else {
        echo "<script>alert('Contraseña incorrecta'); window.location='index.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('Usuario o DNI no registrado'); window.location='index.php';</script>";
    exit;
}

mysqli_close($conexion);
?>
