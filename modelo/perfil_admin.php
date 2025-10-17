<?php
session_start();

// Verifico que el usuario esté logueado y sea admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../index.php"); // O la página que uses para login
    exit();
}

$conexion = new mysqli("localhost", "root", "", "recursosdb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensaje = "";

// Obtengo el ID del admin actual (por ejemplo desde sesión, o buscar por dni o legajo)
$dni_admin = $_SESSION['dni'] ?? null;
if (!$dni_admin) {
    die("No se pudo identificar al administrador.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nuevo_usuario = $conexion->real_escape_string(trim($_POST['usuario']));
    $clave_actual = $_POST['clave_actual'];
    $nueva_clave = $_POST['nueva_clave'];
    $confirmar_clave = $_POST['confirmar_clave'];

    // Busco los datos actuales para validar la clave actual
    $res = $conexion->query("SELECT clave FROM empleados WHERE dni = '$dni_admin'");
    if ($res->num_rows == 0) {
        die("Administrador no encontrado.");
    }
    $fila = $res->fetch_assoc();

    if (!password_verify($clave_actual, $fila['clave'])) {
        $mensaje = "La clave actual es incorrecta.";
    } else if ($nueva_clave != $confirmar_clave) {
        $mensaje = "Las nuevas contraseñas no coinciden.";
    } else {
        // Hasheo la nueva clave
        $clave_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);

        // Actualizo usuario (dni) y clave
        $sql = "UPDATE empleados SET dni='$nuevo_usuario', clave='$clave_hash' WHERE dni='$dni_admin'";
        if ($conexion->query($sql)) {
            $_SESSION['dni'] = $nuevo_usuario; // Actualizo sesión con nuevo usuario
            $mensaje = "Datos actualizados correctamente.";
        } else {
            $mensaje = "Error al actualizar: " . $conexion->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Perfil Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f8f8; padding: 30px; }
        form { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 10px; }
        input[type=text], input[type=password] { width: 100%; padding: 10px; margin: 10px 0; }
        input[type=submit] { background: #45b39d; color: white; border: none; padding: 10px; cursor: pointer; width: 100%; }
        input[type=submit]:hover { background: #37917e; }
        .mensaje { color: red; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>Modificar usuario y contraseña</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Nuevo usuario (dni o usuario):</label>
        <input type="text" name="usuario" value="<?= htmlspecialchars($_SESSION['dni']) ?>" required />

        <label>Clave actual:</label>
        <input type="password" name="clave_actual" required />

        <label>Nueva clave:</label>
        <input type="password" name="nueva_clave" required />

        <label>Confirmar nueva clave:</label>
        <input type="password" name="confirmar_clave" required />

        <input type="submit" value="Actualizar" />
        <br>
<input type="button" value="Volver al menú" onclick="window.location.href='../vista/admin.html';" style="background:#45b39d; color:white; border:none; padding:10px; cursor:pointer; width:100%; margin-top:10px;" />

    </form>
    <br>


</body>
</html>
