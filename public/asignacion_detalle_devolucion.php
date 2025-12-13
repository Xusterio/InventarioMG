<?php
require_once '../templates/header.php';

$id_asignacion = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_asignacion) {
    header("Location: asignaciones.php");
    exit();
}

// Cargar todos los datos de la asignación y devolución
$sql = "SELECT 
            a.*,
            e.codigo_inventario, ma.nombre as marca_nombre, mo.nombre as modelo_nombre,
            emp.nombres, emp.apellidos
        FROM asignaciones a
        JOIN equipos e ON a.id_equipo = e.id
        JOIN empleados emp ON a.id_empleado = emp.id
        JOIN marcas ma ON e.id_marca = ma.id
        JOIN modelos mo ON e.id_modelo = mo.id
        WHERE a.id = ? AND a.estado_asignacion = 'Finalizada'";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_asignacion);
$stmt->execute();
$devolucion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$devolucion) {
    // Si no se encuentra o no está finalizada, redirigir
    header("Location: asignaciones.php");
    exit();
}

$imagenes_adjuntas = array_filter([
    $devolucion['imagen_devolucion_1'], 
    $devolucion['imagen_devolucion_2'], 
    $devolucion['imagen_devolucion_3']
]);
?>

<style>
    :root {
        --custom-primary: #3f51b5; 
        --custom-success: #4CAF50; 
        --custom-danger: #e53935; 
        --custom-secondary: #757575; 
        --custom-info: #00bcd4; 
    }
    .card { 
        border: 1px solid #e9ecef; 
        border-radius: 0.75rem; 
        box-shadow: 0 0.15rem 0.6rem rgba(0,0,0,0.08); 
    }
    .card-header { 
        background-color: #f8f9fa !important; 
        border-bottom: 1px solid #e3e6f0;
        font-weight: 600;
        color: var(--custom-primary);
    }
    .btn { 
        border-radius: 0.5rem; 
        font-weight: 600; 
    }
</style>


<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-file-earmark-check-fill me-2"></i> Detalle de Devolución #<?php echo $id_asignacion; ?>
        </h1>
        <a href="asignaciones.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver al Historial
        </a>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <i class="bi bi-info-circle-fill me-1"></i> Información de la Devolución
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 fw-bold text-muted">Empleado</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($devolucion['apellidos'] . ', ' . $devolucion['nombres']); ?></dd>
                        
                        <dt class="col-sm-4 fw-bold text-muted">Equipo</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($devolucion['codigo_inventario'] . ' - ' . $devolucion['marca_nombre'] . ' ' . $devolucion['modelo_nombre']); ?></dd>
                        
                        <dt class="col-sm-4 fw-bold text-muted">Fecha de Devolución</dt>
                        <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($devolucion['fecha_devolucion'])); ?></dd>
                    </dl>
                    
                    <hr class="my-3">
                    
                    <p class="mb-1 fw-bold text-muted">Observaciones registradas:</p>
                    <blockquote class="blockquote border-start border-3 border-primary ps-3 pt-1 pb-1 bg-light rounded">
                        <p class="mb-0 fst-italic small">
                            "<?php echo !empty($devolucion['observaciones_devolucion']) ? htmlspecialchars($devolucion['observaciones_devolucion']) : 'No se registraron observaciones.'; ?>"
                        </p>
                    </blockquote>
                    
                    <?php if (!empty($devolucion['acta_devolucion_path'])): ?>
                        <div class="mt-3">
                            <a href="../uploads/actas_devolucion/<?php echo htmlspecialchars($devolucion['acta_devolucion_path']); ?>" 
                               target="_blank" 
                               class="btn btn-sm btn-outline-primary"
                               title="Ver Acta de Devolución">
                                <i class="bi bi-file-earmark-pdf-fill me-2"></i>Ver Acta de Devolución
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <i class="bi bi-camera-fill me-1"></i> Evidencia Fotográfica
                </div>
                <div class="card-body">
                    <?php if (!empty($imagenes_adjuntas)): ?>
                        <div class="row g-3">
                            <?php foreach ($imagenes_adjuntas as $imagen): ?>
                                <div class="col-md-6 col-lg-6 mb-2">
                                    <a href="../uploads/devoluciones/<?php echo $id_asignacion . '/' . htmlspecialchars($imagen); ?>" target="_blank" title="Ver imagen completa">
                                        <img src="../uploads/devoluciones/<?php echo $id_asignacion . '/' . htmlspecialchars($imagen); ?>" class="img-fluid rounded shadow-sm" alt="Evidencia de devolución">
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No se adjuntaron imágenes para esta devolución.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>