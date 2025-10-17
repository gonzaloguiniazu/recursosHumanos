<?php
session_start();

// Verifica que sea un administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "recursosdb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensaje = "";

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entrada = $_POST['entrada'];
    $salida = $_POST['salida'];

    $sql = "UPDATE horario SET hora_limite_entrada = '$entrada', hora_limite_salida = '$salida' WHERE id = 1";
    if ($conexion->query($sql)) {
        $mensaje = "✅ Horarios actualizados correctamente.";
    } else {
        $mensaje = "❌ Error al actualizar: " . $conexion->error;
    }
}

// Obtener valores actuales
$consulta = "SELECT hora_limite_entrada, hora_limite_salida FROM horario WHERE id = 1";
$resultado = $conexion->query($consulta);
$config = $resultado->fetch_assoc();

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Horarios</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #F0F8F8;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 30px;
        }

        h2 {
            color: #117864;
        }

        form {
            background-color: #ffffff;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px #ccc;
            width: 300px;
        }

        label {
            display: block;
            margin-top: 15px;
            color: #0B5345;
            font-weight: bold;
        }

        input[type="time"] {
            width: 100%;
            padding: 10px;
            font-size: 1em;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            margin-top: 20px;
            padding: 10px;
            width: 100%;
            background-color: #117864;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
        }

        button:hover {
            background-color: #0E6655;
        }

        .mensaje {
            margin-top: 20px;
            font-size: 1em;
            color: #0B5345;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>Modificar Horarios</h2>

    <form method="POST" action="">
        <label for="entrada">Hora límite de entrada:</label>
        <input type="time" name="entrada" required value="<?= $config['hora_limite_entrada'] ?>">

        <label for="salida">Hora límite de salida:</label>
        <input type="time" name="salida" required value="<?= $config['hora_limite_salida'] ?>">

        <button type="submit">Guardar Cambios</button>

        <button type="button" onclick="window.location.href='lector_codigos.php'">Volver al escáner</button>

    </form>

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje"><?= $mensaje ?></div>
    <?php endif; ?>
</body>
</html>
