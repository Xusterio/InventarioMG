<?php
require_once '../templates/header.php';

$codigo_o_serie = trim(filter_input(INPUT_GET, 'codigo', FILTER_SANITIZE_STRING));
$equipo = null;
$error_message = null;

if ($codigo_o_serie) {
    // Consulta para buscar el equipo por código de inventario o número de serie
    $sql = "SELECT 
        e.id, 
        e.codigo_inventario, 
        e.numero_serie, 
        e.estado,
        te.nombre AS tipo_equipo,
        m.nombre AS marca,
        mo.nombre AS modelo,
        s.nombre AS sucursal,
        emp.nombres,
        emp.apellidos,
        emp.dni,
        asi.fecha_asignacion
    FROM equipos e
    LEFT JOIN tipos_equipo te ON e.id_tipo_equipo = te.id
    LEFT JOIN marcas m ON e.id_marca = m.id
    LEFT JOIN modelos mo ON e.id_modelo = mo.id
    LEFT JOIN sucursales s ON e.id_sucursal = s.id
    LEFT JOIN asignaciones asi ON e.id = asi.id_equipo AND asi.estado_asignacion = 'Activa'
    LEFT JOIN empleados emp ON asi.id_empleado = emp.id
    WHERE e.codigo_inventario = ? OR e.numero_serie = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $codigo_o_serie, $codigo_o_serie);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $equipo = $result->fetch_assoc();
    } else {
        $error_message = "No se encontró ningún equipo con el código o serie proporcionado.";
    }
    $stmt->close();
} else {
    $error_message = "Por favor, ingrese un Código de Inventario o Número de Serie para verificar.";
}

// Determinar colores de estado
$badge_class = 'bg-secondary';
if ($equipo) {
    if ($equipo['estado'] == 'Disponible') $badge_class = 'bg-success';
    if ($equipo['estado'] == 'Asignado') $badge_class = 'bg-primary';
    if ($equipo['estado'] == 'En Reparacion') $badge_class = 'bg-warning text-dark';
    if ($equipo['estado'] == 'De Baja') $badge_class = 'bg-danger';
}
?>

<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h1 class="h3 mb-0"><i class="bi bi-qr-code me-2"></i> Verificación Rápida de Equipo</h1>
        </div>
        <div class="card-body">
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php elseif ($equipo): ?>
                
                <h4 class="mb-3">Equipo Encontrado: <?php echo htmlspecialchars($equipo['codigo_inventario']); ?></h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><strong>Tipo:</strong> <?php echo htmlspecialchars($equipo['tipo_equipo']); ?></li>
                            <li><strong>Marca / Modelo:</strong> <?php echo htmlspecialchars($equipo['marca'] . ' / ' . $equipo['modelo']); ?></li>
                            <li><strong>N° Serie:</strong> <?php echo htmlspecialchars($equipo['numero_serie']); ?></li>
                            <li><strong>Ubicación:</strong> <?php echo htmlspecialchars($equipo['sucursal']); ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6 text-center">
                        <div class="p-3 rounded shadow-sm">
                            <p class="mb-1"><strong>ESTADO ACTUAL</strong></p>
                            <span class="badge display-4 <?php echo $badge_class; ?> p-3 rounded-pill">
                                <?php echo htmlspecialchars($equipo['estado']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <hr>

                <?php if ($equipo['estado'] == 'Asignado'): ?>
                    <div class="alert alert-info mt-4">
                        <h5>Detalles de Asignación Activa</h5>
                        <ul class="list-unstyled">
                            <li><strong>Asignado a:</strong> <?php echo htmlspecialchars($equipo['nombres'] . ' ' . $equipo['apellidos']); ?></li>
                            <li><strong>DNI:</strong> <?php echo htmlspecialchars($equipo['dni']); ?></li>
                            <li><strong>Fecha de Asignación:</strong> <?php echo date('d/m/Y', strtotime($equipo['fecha_asignacion'])); ?></li>
                        </ul>
                        <a href="equipos_detalles.php?id=<?php echo $equipo['id']; ?>" class="btn btn-sm btn-primary">Ver Detalles Completos</a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

            <div class="mt-4">
                <form method="GET" action="verificar_equipo.php">
                    <div class="input-group">
                        <input type="text" class="form-control" name="codigo" placeholder="Escanear o ingresar Código/Serie..." required value="<?php echo htmlspecialchars($codigo_o_serie); ?>">
                        <button class="btn btn-outline-primary" type="submit">Verificar</button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>