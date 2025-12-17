<?php
require_once '../templates/header.php';

$id_sucursal_usuario = $_SESSION['user_sucursal_id'];
$es_admin_general = ($id_sucursal_usuario === null);

// 1. CARGA DE DATOS: Ahora cargamos TODOS los equipos disponibles del inventario global
$empleados = [];
$equipos = [];

// Empleados: Se mantienen filtrados por sucursal si no es admin, o se cargan vía JS si lo es
if (!$es_admin_general) {
    $filtro_sucursal_sql = " AND id_sucursal = " . (int)$id_sucursal_usuario;
    $empleados_q = $conexion->query("SELECT id, nombres, apellidos FROM empleados WHERE estado = 'Activo' {$filtro_sucursal_sql} ORDER BY apellidos");
    if($empleados_q) $empleados = $empleados_q->fetch_all(MYSQLI_ASSOC);
}

// Equipos: CARGA GLOBAL (Se quita el filtro de sucursal para mostrar todo el inventario)
$equipos_q = $conexion->query("SELECT e.id, e.codigo_inventario, ma.nombre as marca_nombre, mo.nombre as modelo_nombre, s.nombre as sucursal_nombre 
                               FROM equipos e 
                               JOIN marcas ma ON e.id_marca = ma.id 
                               JOIN modelos mo ON e.id_modelo = mo.id 
                               JOIN sucursales s ON e.id_sucursal = s.id
                               WHERE e.estado = 'Disponible' 
                               ORDER BY e.codigo_inventario ASC");
if($equipos_q) $equipos = $equipos_q->fetch_all(MYSQLI_ASSOC);
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<h1 class="h2 mb-4">Asignar Equipo a Empleado</h1>

<form action="../includes/procesar_asignacion.php" method="POST">

    <?php if ($es_admin_general): ?>
    <div class="mb-3">
        <label for="selectSucursal" class="form-label">Sucursal del Empleado <span class="text-danger">*</span></label>
        <select class="form-select select2" id="selectSucursal" name="id_sucursal" required>
            <option value="" selected>-- Selecciona la sucursal del empleado --</option>
            <?php 
            $sucursales = $conexion->query("SELECT * FROM sucursales WHERE estado = 'Activo' ORDER BY nombre");
            while ($sucursal = $sucursales->fetch_assoc()): ?>
                <option value="<?php echo $sucursal['id']; ?>"><?php echo htmlspecialchars($sucursal['nombre']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="mb-3">
        <label for="selectEmpleado" class="form-label">Seleccionar Empleado <span class="text-danger">*</span></label>
        <select class="form-select select2" id="selectEmpleado" name="id_empleado" required <?php if ($es_admin_general) echo 'disabled'; ?>>
            <option value="" selected>-- <?php echo $es_admin_general ? 'Primero selecciona una sucursal' : 'Empleados en tu sucursal'; ?> --</option>
            <?php foreach ($empleados as $empleado): ?>
                <option value="<?php echo $empleado['id']; ?>"><?php echo htmlspecialchars($empleado['apellidos'] . ', ' . $empleado['nombres']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="selectEquipo" class="form-label">Seleccionar Equipo (Inventario Global) <span class="text-danger">*</span></label>
        <select class="form-select select2" id="selectEquipo" name="id_equipo" required>
            <option value="" selected>-- Buscar por código, marca o modelo --</option>
             <?php foreach ($equipos as $equipo): ?>
                <option value="<?php echo $equipo['id']; ?>">
                    <?php echo htmlspecialchars($equipo['codigo_inventario'] . ' - ' . $equipo['marca_nombre'] . ' ' . $equipo['modelo_nombre'] . ' (Ubicación: ' . $equipo['sucursal_nombre'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">Puedes buscar cualquier equipo disponible en todo el inventario.</div>
    </div>

    <div class="mb-3">
        <label for="observaciones_entrega" class="form-label">Observaciones de la Entrega</label>
        <textarea class="form-control" name="observaciones_entrega" rows="3" placeholder="Ej: Se entrega con cargador y maletín."></textarea>
    </div>

    <hr class="my-4">
    <a href="asignaciones.php" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Asignar Equipo</button>
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Inicializar buscadores Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Lógica para cargar empleados dinámicamente si es admin general
    const selectSucursal = $('#selectSucursal');
    const selectEmpleado = $('#selectEmpleado');

    if (selectSucursal.length) {
        selectSucursal.on('change', function() {
            const idSucursal = $(this).val();
            
            selectEmpleado.empty().append('<option value="">Cargando...</option>').prop('disabled', true);

            if (!idSucursal) {
                selectEmpleado.append('<option value="">-- Selecciona una sucursal --</option>');
                return;
            }

            fetch(`../includes/api.php?action=getEmpleadosPorSucursal&id_sucursal=${idSucursal}`)
                .then(response => response.json())
                .then(data => {
                    selectEmpleado.empty().append('<option value="">-- Seleccionar Empleado --</option>');
                    if (data.length > 0) {
                        data.forEach(empleado => {
                            const option = new Option(`${empleado.apellidos}, ${empleado.nombres}`, empleado.id);
                            selectEmpleado.append(option);
                        });
                        selectEmpleado.prop('disabled', false);
                    } else {
                        selectEmpleado.append('<option value="">-- No hay empleados en esta sucursal --</option>');
                    }
                    // Actualizar el buscador Select2 después de cambiar las opciones
                    selectEmpleado.trigger('change');
                });
        });
    }
});
</script>

<?php require_once '../templates/footer.php'; ?>