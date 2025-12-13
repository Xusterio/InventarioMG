<?php
ob_start();
require_once '../templates/header.php';

// 1. Verificar si se proporcionó un ID de equipo
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: equipos.php");
    exit();
}

$id_equipo = (int)$_GET['id'];

// 2. Obtener información básica del equipo
$sql_equipo = "SELECT codigo_inventario, numero_serie FROM equipos WHERE id = ?";
$stmt_equipo = $conexion->prepare($sql_equipo);
$stmt_equipo->bind_param("i", $id_equipo);
$stmt_equipo->execute();
$result_equipo = $stmt_equipo->get_result();
$equipo_info = $result_equipo->fetch_assoc();
$stmt_equipo->close();

if (!$equipo_info) {
    echo "<div class='alert alert-danger'>Equipo no encontrado.</div>";
    require_once '../templates/footer.php';
    exit();
}

// 3. CONSULTA PARA OBTENER EL HISTORIAL COMBINADO (Asignaciones, Devoluciones y Bajas)
// Se usa UNION ALL para combinar tres conjuntos de resultados en una sola línea de tiempo.
$sql_historial = "
(
    -- 1. Registros de Asignación (Entrega)
    SELECT
        asi.fecha_entrega AS fecha,
        'Asignación' AS tipo_evento,
        CONCAT(emp.nombres, ' ', emp.apellidos) AS empleado_nombre,
        CONCAT('Asignado a: ', emp.nombres, ' ', emp.apellidos, ' (', a.nombre, ' - ', c.nombre, ')', 
                IF(asi.observaciones_entrega IS NOT NULL AND asi.observaciones_entrega != '', CONCAT('. Notas: ', asi.observaciones_entrega), '')) AS descripcion,
        asi.acta_firmada_path AS documento_path
    FROM asignaciones asi
    JOIN empleados emp ON asi.id_empleado = emp.id
    LEFT JOIN areas a ON emp.id_area = a.id
    LEFT JOIN cargos c ON emp.id_cargo = c.id
    WHERE asi.id_equipo = ?
)
UNION ALL
(
    -- 2. Registros de Devolución (Se extraen de las mismas asignaciones, pero usando fecha_devolucion)
    SELECT
        asi.fecha_devolucion AS fecha,
        'Devolución' AS tipo_evento,
        CONCAT(emp.nombres, ' ', emp.apellidos) AS empleado_nombre,
        CONCAT('Devuelto por: ', emp.nombres, ' ', emp.apellidos, 
               IF(asi.observaciones_devolucion IS NOT NULL AND asi.observaciones_devolucion != '', CONCAT('. Notas: ', asi.observaciones_devolucion), '')) AS descripcion,
        asi.acta_devolucion_path AS documento_path
    FROM asignaciones asi
    JOIN empleados emp ON asi.id_empleado = emp.id
    WHERE asi.id_equipo = ? AND asi.fecha_devolucion IS NOT NULL
)
UNION ALL
(
    -- 3. Registros de Bajas
    SELECT
        b.fecha_baja AS fecha,
        'Baja' AS tipo_evento,
        u.nombre AS empleado_nombre, -- Usamos el nombre del usuario responsable de la baja si existe
        CONCAT('Motivo: ', b.motivo, IF(b.descripcion_motivo IS NOT NULL AND b.descripcion_motivo != '', CONCAT('. Detalle: ', b.descripcion_motivo), '')) AS descripcion,
        NULL AS documento_path -- No hay documento asociado por defecto
    FROM bajas b
    LEFT JOIN usuarios u ON b.id_usuario_responsable = u.id
    WHERE b.id_equipo = ?
)
ORDER BY fecha DESC";

$stmt_historial = $conexion->prepare($sql_historial);
// Vinculamos los tres parámetros id_equipo
$stmt_historial->bind_param("iii", $id_equipo, $id_equipo, $id_equipo);
$stmt_historial->execute();
$result_historial = $stmt_historial->get_result();
$historial = $result_historial->fetch_all(MYSQLI_ASSOC);
$stmt_historial->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial del Equipo - Sistema de Inventario</title>
    
    <style>
        :root {
            /* Colores Personalizados */
            --custom-primary: #3f51b5; /* Indigo/Deep Blue */
            --custom-success: #4CAF50; /* Green */
            --custom-danger: #e53935; /* Strong Red */
            --custom-secondary: #757575; /* Medium Gray */
            --custom-info: #00bcd4; /* Brighter Cyan */
        }
        /* Estilos de Tarjetas */
        .card { 
            border: 1px solid #e9ecef; 
            border-radius: 0.75rem; 
            box-shadow: 0 0.15rem 0.6rem rgba(0,0,0,0.08); 
            transition: all 0.3s;
        }
        .card:hover {
            box-shadow: 0 0.3rem 1rem rgba(0,0,0,0.1);
        }
        .card-header.bg-primary { background-color: var(--custom-primary) !important; color: white !important; border-bottom: none; }
        .card-header.bg-danger { background-color: var(--custom-danger) !important; color: white !important; border-bottom: none; }
        
        /* Estilos de la Línea de Tiempo */
        .timeline {
            border-left: 3px solid #dee2e6;
            border-bottom-right-radius: 4px;
            border-top-right-radius: 4px;
            background: #fff;
            margin: 0 auto;
            letter-spacing: 0.2px;
            position: relative;
            padding: 20px 0;
            list-style: none;
        }
        .timeline li {
            padding-left: 20px;
            padding-right: 20px;
            position: relative;
        }
        .timeline li::before {
            content: '';
            width: 10px;
            height: 10px;
            background: white;
            border: 2px solid;
            border-radius: 50%;
            position: absolute;
            left: -6.5px;
            top: 25px;
            z-index: 10;
        }
        /* Colores para los eventos en el timeline */
        .timeline li.asignación::before { border-color: var(--custom-success); }
        .timeline li.devolución::before { border-color: var(--custom-primary); }
        .timeline li.baja::before { border-color: var(--custom-danger); }

        .timeline li .time {
            display: block;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--custom-secondary);
        }
        .timeline li .desc {
            font-weight: 500;
            margin-top: 5px;
            margin-bottom: 5px; 
        }
        .timeline li a.documento {
            font-size: 0.85rem;
            color: var(--custom-info); 
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-history me-2"></i>
                        Historial del Equipo: 
                        <span class="text-primary"><?php echo htmlspecialchars($equipo_info['codigo_inventario']); ?></span>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="equipos.php" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>
                            Volver a Equipos
                        </a>
                        <a href="equipos_detalles.php?id=<?php echo $id_equipo; ?>" class="btn btn-info">
                            <i class="fas fa-info-circle me-1"></i>
                            Ver Detalles
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list-alt me-2"></i>
                                    Trazabilidad del Equipo
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-4">
                                    Código Patrimonial: **<?php echo htmlspecialchars($equipo_info['codigo_inventario']); ?>** | 
                                    N° Serie: **<?php echo htmlspecialchars($equipo_info['numero_serie']); ?>**
                                </p>
                                
                                <?php if (!empty($historial)): ?>
                                    <ul class="timeline">
                                        <?php foreach ($historial as $evento): 
                                            // Se usa 'fecha' para el orden, pero se asume que puede ser TIMESTAMP o DATE.
                                            // Se utiliza 'H:i' para mostrar la hora si está disponible.
                                            $fecha_formateada = date('d/m/Y H:i', strtotime($evento['fecha']));
                                            $clase_evento = strtolower(str_replace(' ', '', $evento['tipo_evento'])); // 'asignación', 'devolución', 'baja'
                                            $url_documento = null;
                                            $clase_badge = '';
                                            
                                            // Lógica para determinar el color del badge y la URL del documento
                                            switch($clase_evento) {
                                                case 'asignación':
                                                    $clase_badge = 'success';
                                                    if (!empty($evento['documento_path'])) {
                                                        // Ruta basada en el esquema: acta_firmada_path
                                                        $url_documento = "http://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . "/uploads/actas/" . $evento['documento_path'];
                                                    }
                                                    break;
                                                case 'devolución':
                                                    $clase_badge = 'primary';
                                                    if (!empty($evento['documento_path'])) {
                                                        // Ruta basada en el esquema: acta_devolucion_path
                                                        $url_documento = "http://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . "/uploads/actas_devolucion/" . $evento['documento_path'];
                                                    }
                                                    break;
                                                case 'baja':
                                                    $clase_badge = 'danger';
                                                    // Las bajas no tienen path de documento en esta consulta, no se genera URL
                                                    break;
                                            }
                                        ?>
                                        <li class="<?php echo $clase_evento; ?>">
                                            <span class="time"><i class="fas fa-calendar-alt me-1"></i> <?php echo $fecha_formateada; ?></span>
                                            <p class="desc">
                                                <span class="fw-bold text-uppercase me-2 text-<?php echo $clase_badge; ?>">
                                                    [<?php echo htmlspecialchars($evento['tipo_evento']); ?>]
                                                </span>
                                                <?php echo htmlspecialchars($evento['descripcion']); ?>
                                            </p>
                                            <?php if ($url_documento): ?>
                                                <a href="<?php echo htmlspecialchars($url_documento); ?>" target="_blank" class="documento">
                                                    <i class="fas fa-file-pdf me-1"></i> Ver Acta
                                                </a>
                                            <?php endif; ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No se encontró historial (asignaciones, devoluciones o bajas) para este equipo.
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php require_once '../templates/footer.php'; ?>
</body>
</html>