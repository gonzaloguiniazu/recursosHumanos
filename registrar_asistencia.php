<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "recursosdb"); // <-- ajustá usuario, contraseña y nombre de BD
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Verificar si se recibió el código (nro_legajo)
if (isset($_POST['codigo'])) {
    date_default_timezone_set('America/Argentina/Buenos_Aires'); // Asegura la hora local
    $nro_legajo = $conexion->real_escape_string($_POST['codigo']);
    $fechaHoy = date('Y-m-d');
    $horaActual = date('H:i:s');

    // Verificar si el nro_legajo existe en la tabla empleados
    $consultaLegajo = "SELECT * FROM empleados WHERE nro_legajo = '$nro_legajo'";
    $resultadoLegajo = $conexion->query($consultaLegajo);

    if ($resultadoLegajo->num_rows == 0) {
        // Legajo no encontrado
        echo "Número de legajo no válido.";
    } else {
        // Verificar si ya hay un registro de asistencia para hoy
        $consulta = "SELECT * FROM asistencia WHERE nro_legajo = '$nro_legajo' AND fecha = '$fechaHoy'";
        $resultado = $conexion->query($consulta);

        if ($resultado->num_rows > 0) {
            // Ya hay un registro, actualizar hora de salida
            $sql = "UPDATE asistencia SET horaSalida = NOW() WHERE nro_legajo = '$nro_legajo' AND fecha = '$fechaHoy'";
            if ($conexion->query($sql)) {
                echo "Salida registrada correctamente a las $horaActual.";
            } else {
                echo "Error al registrar salida: " . $conexion->error;
            }
        } else {
            // No hay registro previo, insertar nueva entrada
            $sql = "INSERT INTO asistencia (nro_legajo, horaEntrada, fecha) VALUES ('$nro_legajo', NOW(), '$fechaHoy')";
            if ($conexion->query($sql)) {
                echo "Entrada registrada correctamente a las $horaActual.";
            } else {
                echo "Error al registrar entrada: " . $conexion->error;
            }
        }
    }
} else {
    echo "No se recibió ningún código.";
}

// Cerrar la conexión
$conexion->close();
?>
