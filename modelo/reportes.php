<?php
$conexion = new mysqli("localhost", "root", "", "recursosdb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Configura la zona horaria una vez al principio del script para toda la aplicación
date_default_timezone_set("America/Argentina/Buenos_Aires");

// Detectar la sección activa según lo enviado por POST
$seccion_activa = ''; // por defecto
$desdeAsistencias = '';
$hastaAsistencias = '';
$desdeInasistencias = date("Y-m-01"); // Valores por defecto para inasistencias
$hastaInasistencias = date("Y-m-d"); // Valores por defecto para inasistencias
$desdeTarde = '';
$hastaTarde = '';
$desdeExtra = '';
$hastaExtra = '';
$buscar = ''; // Variable para la búsqueda por legajo/apellido

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Lógica para detectar la sección activa y asignar valores
    if (isset($_POST['filtrar_asistencias']) || isset($_POST['exportar_asistencias'])) {
        $seccion_activa = 'asistencias';
        $desdeAsistencias = $_POST['desde'] ?? '';
        $hastaAsistencias = $_POST['hasta'] ?? '';
    } elseif (isset($_POST['filtrar_inasistencias']) || isset($_POST['exportar_inasistencias'])) {
        $seccion_activa = 'inasistencias';
        $desdeInasistencias = $_POST['desde_inasistencias'] ?? date("Y-m-01");
        $hastaInasistencias = $_POST['hasta_inasistencias'] ?? date("Y-m-d");
    } elseif (isset($_POST['filtrar_tardanzas']) || isset($_POST['exportar_tardanzas'])) {
        $seccion_activa = 'tardanzas';
        $desdeTarde = $_POST['desde_tarde'] ?? '';
        $hastaTarde = $_POST['hasta_tarde'] ?? '';
    } elseif (isset($_POST['filtrar_extras']) || isset($_POST['exportar_extras'])) {
        $seccion_activa = 'extras';
        $desdeExtra = $_POST['desde_extra'] ?? '';
        $hastaExtra = $_POST['hasta_extra'] ?? '';
    } elseif (isset($_POST['buscar_empleado'])) { // Cambiado para claridad
        $seccion_activa = 'buscar';
        $buscar = $_POST['buscar'] ?? '';
    }

    // --- Lógica de Exportación a Excel ---
    if (isset($_POST['exportar_asistencias'])) {
        $desdeAsistencias = $_POST['desde'] ?? '';
        $hastaAsistencias = $_POST['hasta'] ?? '';

        $stmt = $conexion->prepare("
            SELECT a.fecha, a.horaEntrada, a.horaSalida, e.nro_legajo
            FROM asistencia a
            JOIN empleados e ON a.id_empleados = e.id_empleados
            WHERE a.fecha BETWEEN ? AND ? AND e.rol != 'administrador'
        ");
        $stmt->bind_param("ss", $desdeAsistencias, $hastaAsistencias);
        $stmt->execute();
        $consultaRango = $stmt->get_result();

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=asistencias.xls");
        echo "Legajo\tFecha\tHora Entrada\tHora Salida\n";
        while($fila = $consultaRango->fetch_assoc()) {
            echo "{$fila['nro_legajo']}\t{$fila['fecha']}\t{$fila['horaEntrada']}\t{$fila['horaSalida']}\n";
        }
        $stmt->close();
        exit;
    }

    if (isset($_POST['exportar_inasistencias'])) {
        $desdeInasistencias = $_POST['desde_inasistencias'] ?? date("Y-m-01");
        $hastaInasistencias = $_POST['hasta_inasistencias'] ?? date("Y-m-d");
        
        $fechasLaborales = obtenerFechasLaborales($desdeInasistencias, $hastaInasistencias);
        $empleados_stmt = $conexion->prepare("SELECT id_empleados, nro_legajo, nombre FROM empleados WHERE rol != 'administrador'");
        $empleados_stmt->execute();
        $empleados_result = $empleados_stmt->get_result();

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=inasistencias.xls");
        echo "Legajo\tNombre\tFecha de inasistencia\n";

        while ($emp = $empleados_result->fetch_assoc()) {
            $id = $emp['id_empleados'];
            $legajo = $emp['nro_legajo'];
            $nombre = $emp['nombre'];
            foreach ($fechasLaborales as $fecha) {
                $stmt_inasistencia = $conexion->prepare("SELECT 1 FROM asistencia WHERE id_empleados = ? AND fecha = ?");
                $stmt_inasistencia->bind_param("is", $id, $fecha);
                $stmt_inasistencia->execute();
                $queryResult = $stmt_inasistencia->get_result();
                if ($queryResult->num_rows == 0) {
                    echo "$legajo\t$nombre\t$fecha\n";
                }
                $stmt_inasistencia->close();
            }
        }
        $empleados_stmt->close();
        exit;
    }

    if (isset($_POST['exportar_tardanzas'])) {
        $desdeTarde = $_POST['desde_tarde'] ?? '';
        $hastaTarde = $_POST['hasta_tarde'] ?? '';

        $config_stmt = $conexion->query("SELECT hora_limite_entrada FROM horario LIMIT 1");
        $config = $config_stmt->fetch_assoc();
        $horaLimiteEntrada = $config['hora_limite_entrada'];

        $stmt = $conexion->prepare("
            SELECT a.fecha, a.horaEntrada, e.nro_legajo, e.nombre
            FROM asistencia a
            JOIN empleados e ON a.id_empleados = e.id_empleados
            WHERE a.horaEntrada > ? AND a.fecha BETWEEN ? AND ? AND e.rol != 'administrador'
        ");
        $stmt->bind_param("sss", $horaLimiteEntrada, $desdeTarde, $hastaTarde);
        $stmt->execute();
        $consultaTarde = $stmt->get_result();

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=tardanzas.xls");
        echo "Legajo\tNombre\tFecha\tHora Entrada\n";
        while ($fila = $consultaTarde->fetch_assoc()) {
            echo "{$fila['nro_legajo']}\t{$fila['nombre']}\t{$fila['fecha']}\t{$fila['horaEntrada']}\n";
        }
        $stmt->close();
        exit;
    }

    if (isset($_POST['exportar_extras'])) {
        $desdeExtra = $_POST['desde_extra'] ?? '';
        $hastaExtra = $_POST['hasta_extra'] ?? '';

        $config_stmt = $conexion->query("SELECT hora_limite_salida FROM horario LIMIT 1");
        $config = $config_stmt->fetch_assoc();
        $horarioSalidaOficial = $config['hora_limite_salida'];

        $stmt = $conexion->prepare("
            SELECT a.fecha, a.horaSalida, e.nro_legajo, e.nombre
            FROM asistencia a
            JOIN empleados e ON a.id_empleados = e.id_empleados
            WHERE a.horaSalida > ? AND a.fecha BETWEEN ? AND ? AND e.rol != 'administrador'
        ");
        $stmt->bind_param("sss", $horarioSalidaOficial, $desdeExtra, $hastaExtra);
        $stmt->execute();
        $consultaExtras = $stmt->get_result();

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=horas_extras.xls");
        echo "Legajo\tNombre\tFecha\tHora Salida\n";
        while ($fila = $consultaExtras->fetch_assoc()) {
            echo "{$fila['nro_legajo']}\t{$fila['nombre']}\t{$fila['fecha']}\t{$fila['horaSalida']}\n";
        }
        $stmt->close();
        exit;
    }
}

// Función para obtener fechas laborales
function obtenerFechasLaborales($desde, $hasta) {
    $fechas = [];
    $hoy = date("Y-m-d");
    $fechaInicio = strtotime($desde);
    $fechaFin = strtotime(min($hasta, $hoy)); // Nunca más allá de hoy

    for ($i = $fechaInicio; $i <= $fechaFin; $i += 86400) {
        $fecha = date("Y-m-d", $i);
        $diaSemana = date("N", $i);
        if ($diaSemana < 6) { // Lunes a viernes (1 a 5)
            $fechas[] = $fecha;
        }
    }
    return $fechas;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Reportes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        header { background-color: #00838f; color: white; padding: 20px; text-align: center; }
        nav { background-color: #e0f2f1; padding: 15px; text-align: center; }
        nav button {
            margin: 10px;
            padding: 10px 20px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
            border: none;
            background-color: #a3e4d7;
        }
        section { padding: 30px; display: none; }
        section.active { display: block; }
        h2 { color: #00695c; }
        .volver { margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

    <header>
        <h1>PANEL DE REPORTES</h1>
    </header>

    <nav>
        <button onclick="mostrarSeccion('asistencias')"> VER ASISTENCIAS</button>
        <button onclick="mostrarSeccion('inasistencias')"> VER INASISTENCIAS</button>
        <button onclick="mostrarSeccion('tardanzas')"> LLEGADAS TARDE</button>
        <button onclick="mostrarSeccion('extras')"> HORAS EXTRAS</button>
        <button onclick="mostrarSeccion('buscar')"> BUSCAR POR LEGAJO / APELLIDO</button>
        <button onclick="window.location.href='../vista/admin.html'"> VOLVER</button>
    </nav>

    <main>
        <section id="asistencias" class="<?= $seccion_activa === 'asistencias' ? 'active' : '' ?>">
            <h2>📈 Asistencias por rango de fechas</h2>
            <form method="POST" action="">
                Desde: <input type="date" name="desde" value="<?= htmlspecialchars($desdeAsistencias) ?>" required>
                Hasta: <input type="date" name="hasta" value="<?= htmlspecialchars($hastaAsistencias) ?>" required>
                <button type="submit" name="filtrar_asistencias">Filtrar</button>
                <br>
                <?php
                if ($seccion_activa === 'asistencias' && !empty($desdeAsistencias) && !empty($hastaAsistencias)) {
                    $stmt = $conexion->prepare("
                        SELECT a.fecha, a.horaEntrada, a.horaSalida, e.nro_legajo
                        FROM asistencia a
                        JOIN empleados e ON a.id_empleados = e.id_empleados
                        WHERE a.fecha BETWEEN ? AND ? AND e.rol != 'admin'
                    ");
                    $stmt->bind_param("ss", $desdeAsistencias, $hastaAsistencias);
                    $stmt->execute();
                    $consultaRango = $stmt->get_result();

                    echo "<table border='1'><tr><th>Legajo</th><th>Fecha</th><th>Hora Entrada</th><th>Hora Salida</th></tr>";
                    while($fila = $consultaRango->fetch_assoc()) {
                        echo "<tr><td>{$fila['nro_legajo']}</td><td>{$fila['fecha']}</td><td>{$fila['horaEntrada']}</td><td>{$fila['horaSalida']}</td></tr>";
                    }
                    echo "</table>";
                    $stmt->close();
                }
                ?>
                <br>
                <button type="submit" name="exportar_asistencias" class="export">Exportar a Excel</button>
            </form>
        </section>

        <section id="inasistencias" class="<?= $seccion_activa === 'inasistencias' ? 'active' : '' ?>">
            <h2>❌ Inasistencias por rango de fechas</h2>
            <form method="POST" action="">
                Desde: <input type="date" name="desde_inasistencias" value="<?= htmlspecialchars($desdeInasistencias) ?>" required>
                Hasta: <input type="date" name="hasta_inasistencias" value="<?= htmlspecialchars($hastaInasistencias) ?>" required>
                <button type="submit" name="filtrar_inasistencias">Filtrar</button>
                <br>
                <?php
                if ($seccion_activa === 'inasistencias') {
                    $fechasLaborales = obtenerFechasLaborales($desdeInasistencias, $hastaInasistencias);

                    $empleados_stmt = $conexion->prepare("SELECT id_empleados, nro_legajo, nombre FROM empleados WHERE rol != 'admin'");
                    $empleados_stmt->execute();
                    $empleados_result = $empleados_stmt->get_result();

                    echo "<table border='1'>";
                    echo "<tr><th>Legajo</th><th>Nombre</th><th>Fecha de inasistencia</th></tr>";

                    while ($emp = $empleados_result->fetch_assoc()) {
                        $id = $emp['id_empleados'];
                        $legajo = $emp['nro_legajo'];
                        $nombre = $emp['nombre'];
                        foreach ($fechasLaborales as $fecha) {
                            $stmt_inasistencia = $conexion->prepare("SELECT 1 FROM asistencia WHERE id_empleados = ? AND fecha = ?");
                            $stmt_inasistencia->bind_param("is", $id, $fecha);
                            $stmt_inasistencia->execute();
                            $queryResult = $stmt_inasistencia->get_result();
                            if ($queryResult->num_rows == 0) {
                                echo "<tr><td>$legajo</td><td>$nombre</td><td>$fecha</td></tr>";
                            }
                            $stmt_inasistencia->close();
                        }
                    }
                    echo "</table>";
                    $empleados_stmt->close();
                }
                ?>
                <br>
                <button type="submit" name="exportar_inasistencias" class="export">Exportar a Excel</button>
            </form>
        </section>

        <section id="tardanzas" class="<?= $seccion_activa === 'tardanzas' ? 'active' : '' ?>">
            <h2>⏰ Llegadas tarde</h2>
            <form method="POST" action="">
                Desde: <input type="date" name="desde_tarde" value="<?= htmlspecialchars($desdeTarde) ?>" required>
                Hasta: <input type="date" name="hasta_tarde" value="<?= htmlspecialchars($hastaTarde) ?>" required>
                <button type="submit" name="filtrar_tardanzas">Filtrar</button>
                <br>
                <?php
                if ($seccion_activa === 'tardanzas' && !empty($desdeTarde) && !empty($hastaTarde)) {
                    $config_stmt = $conexion->query("SELECT hora_limite_entrada FROM horario LIMIT 1");
                    $config = $config_stmt->fetch_assoc();
                    $horaLimiteEntrada = $config['hora_limite_entrada'];
                    $config_stmt->close();

                    $stmt = $conexion->prepare("
                        SELECT a.fecha, a.horaEntrada, e.nro_legajo, e.nombre
                        FROM asistencia a
                        JOIN empleados e ON a.id_empleados = e.id_empleados
                        WHERE a.horaEntrada > ? AND a.fecha BETWEEN ? AND ? AND e.rol != 'admin'
                    ");
                    $stmt->bind_param("sss", $horaLimiteEntrada, $desdeTarde, $hastaTarde);
                    $stmt->execute();
                    $consultaTarde = $stmt->get_result();

                    echo "<table border='1'><tr><th>Legajo</th><th>Nombre</th><th>Fecha</th><th>Hora Entrada</th></tr>";
                    while ($fila = $consultaTarde->fetch_assoc()) {
                        echo "<tr><td>{$fila['nro_legajo']}</td><td>{$fila['nombre']}</td><td>{$fila['fecha']}</td><td>{$fila['horaEntrada']}</td></tr>";
                    }
                    echo "</table>";
                    $stmt->close();
                }
                ?>
                <br>
                <button type="submit" name="exportar_tardanzas">Exportar a Excel</button>
            </form>
        </section>

        <section id="extras" class="<?= $seccion_activa === 'extras' ? 'active' : '' ?>">
            <h2>🕒 Horas Extras</h2>
            <form method="POST" action="">
                Desde: <input type="date" name="desde_extra" value="<?= htmlspecialchars($desdeExtra) ?>" required>
                Hasta: <input type="date" name="hasta_extra" value="<?= htmlspecialchars($hastaExtra) ?>" required>
                <button type="submit" name="filtrar_extras">Filtrar</button>
                <br>
                <?php
                if ($seccion_activa === 'extras' && !empty($desdeExtra) && !empty($hastaExtra)) {
                    $config_stmt = $conexion->query("SELECT hora_limite_salida FROM horario LIMIT 1");
                    $config = $config_stmt->fetch_assoc();
                    $horarioSalidaOficial = $config['hora_limite_salida'];
                    $config_stmt->close();

                    $stmt = $conexion->prepare("
                        SELECT a.fecha, a.horaSalida, e.nro_legajo, e.nombre
                        FROM asistencia a
                        JOIN empleados e ON a.id_empleados = e.id_empleados
                        WHERE a.horaSalida > ? AND a.fecha BETWEEN ? AND ? AND e.rol != 'admin'
                    ");
                    $stmt->bind_param("sss", $horarioSalidaOficial, $desdeExtra, $hastaExtra);
                    $stmt->execute();
                    $consultaExtras = $stmt->get_result();

                    echo "<table border='1'><tr><th>Legajo</th><th>Nombre</th><th>Fecha</th><th>Hora Salida</th></tr>";
                    while ($fila = $consultaExtras->fetch_assoc()) {
                        echo "<tr><td>{$fila['nro_legajo']}</td><td>{$fila['nombre']}</td><td>{$fila['fecha']}</td><td>{$fila['horaSalida']}</td></tr>";
                    }
                    echo "</table>";
                    $stmt->close();
                }
                ?>
                <br>
                <button type="submit" name="exportar_extras">Exportar a Excel</button>
            </form>
        </section>

        <section id="buscar" class="<?= $seccion_activa === 'buscar' ? 'active' : '' ?>">
            <h2>🔍 Buscar por legajo o apellido</h2>
            <form method="POST" action="">
                Buscar: <input type="text" name="buscar" value="<?= htmlspecialchars($buscar) ?>">
                <button type="submit" name="buscar_empleado">Buscar</button>
            </form>
            <?php
            if ($seccion_activa === 'buscar' && !empty($buscar)) {
                $searchTerm = '%' . $buscar . '%'; // Para la búsqueda LIKE

                $stmt = $conexion->prepare("
                    SELECT e.apellido, e.nro_legajo, a.fecha, a.horaEntrada, a.horaSalida
                    FROM empleados e
                    LEFT JOIN asistencia a ON a.id_empleados = e.id_empleados
                    WHERE (e.apellido LIKE ? OR e.nro_legajo = ?) AND e.rol != 'admin'
                    ORDER BY a.fecha DESC
                ");
                $stmt->bind_param("ss", $searchTerm, $buscar); // 'ss' porque nro_legajo puede ser una cadena
                $stmt->execute();
                $consultaBuscar = $stmt->get_result();

                if ($consultaBuscar->num_rows > 0) {
                    echo "<table border='1'><tr><th>Apellido</th><th>Legajo</th><th>Fecha</th><th>Entrada</th><th>Salida</th></tr>";
                    while($fila = $consultaBuscar->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($fila['apellido']) . "</td>";
                        echo "<td>" . htmlspecialchars($fila['nro_legajo']) . "</td>";
                        echo "<td>" . ($fila['fecha'] ?? '-') . "</td>";
                        echo "<td>" . ($fila['horaEntrada'] ?? '-') . "</td>";
                        echo "<td>" . ($fila['horaSalida'] ?? '-') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No se encontraron resultados para <strong>" . htmlspecialchars($buscar) . "</strong>.</p>";
                }
                $stmt->close();
            }
            ?>
        </section>
    </main>

    <script src="../controlador/mostrarSeccion.js"></script>

</body>
</html>