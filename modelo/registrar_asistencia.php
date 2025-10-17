<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "recursosdb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if (isset($_POST['codigo'])) {
    date_default_timezone_set('America/Argentina/Buenos_Aires');
    $nro_legajo = $conexion->real_escape_string($_POST['codigo']);
    $fechaHoy = date('Y-m-d');
    $horaActual = date('H:i:s');
    //$horaLimite = "18:30:00";
    // Obtener el horario límite desde la base
    $configQuery = "SELECT hora_limite_entrada, hora_limite_salida FROM horario LIMIT 1";
    $configResult = $conexion->query($configQuery);
    $config = $configResult->fetch_assoc();
    $horaLimiteEntrada = $config['hora_limite_entrada'];
    $horaLimiteSalida = $config['hora_limite_salida'];
    $mensajeTardanza = "";
    $llegoTarde = 0;

    if ($horaActual > $horaLimiteEntrada) {
        $mensajeTardanza = " ATENCIÓN: El empleado llegó tarde.";
        $llegoTarde = 1;
    }

    // Buscar id_empleados a partir del nro_legajo
    $consultaLegajo = "SELECT id_empleados FROM empleados WHERE nro_legajo = '$nro_legajo'";
    $resultadoLegajo = $conexion->query($consultaLegajo);

    if ($resultadoLegajo->num_rows == 0) {
        echo "Número de legajo no válido.";
    } else {
        $row = $resultadoLegajo->fetch_assoc();
        $idEmpleado = $row['id_empleados'];

        // Verificar si ya hay un registro de asistencia para hoy usando id_empleados
        $consulta = "SELECT * FROM asistencia WHERE id_empleados = '$idEmpleado' AND fecha = '$fechaHoy'";
        $resultado = $conexion->query($consulta);

        if ($resultado->num_rows > 0) {
            // Ya hay un registro: actualizar hora de salida
            $sql = "UPDATE asistencia SET horaSalida = NOW() WHERE id_empleados = '$idEmpleado' AND fecha = '$fechaHoy'";
            if ($conexion->query($sql)) {
                echo "Salida registrada correctamente a las $horaActual.";
            } else {
                echo "Error al registrar salida: " . $conexion->error;
            }
        } else {
            // No hay registro previo: insertar nueva entrada con llego_tarde
            $sql = "INSERT INTO asistencia (id_empleados, horaEntrada, fecha, llego_tarde) 
                    VALUES ('$idEmpleado', NOW(), '$fechaHoy', '$llegoTarde')";
            if ($conexion->query($sql)) {
                echo "Entrada registrada correctamente a las $horaActual." . $mensajeTardanza;
            } else {
                echo "Error al registrar entrada: " . $conexion->error;
            }
        }
    }
} else {
    echo "No se recibió ningún código.";
}

$conexion->close();
?>