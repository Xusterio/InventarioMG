<?php
require_once '../templates/header.php';

// Validar el ID
$id_empleado = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_empleado) {
    header("Location: empleados.php");
    exit();
}

// Lógica para procesar la ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determinar la sucursal a guardar: solo se toma del POST si es admin general
    $es_admin_general = ($_SESSION['user_sucursal_id'] === null);
    $id_sucursal_post = $_POST['id_sucursal'];

    if (!$es_admin_general) {
        // Si no es admin, se usa el valor original del empleado (si no fue enviado) para evitar manipulación
        // En este caso, asumimos que el campo oculto contiene el valor correcto.
    }
    
    $dni = $_POST['dni'];
    $nombres = $_POST['nombres'];
    $apellidos = $_POST['apellidos'];
    $id_cargo = $_POST['id_cargo'];
    $id_area = $_POST['id_area'];
    $estado = $_POST['estado'];

    $sql_update = "UPDATE empleados SET 
                    id_sucursal = ?, dni = ?, nombres = ?, apellidos = ?, 
                    id_cargo = ?, id_area = ?, estado = ?
                   WHERE id = ?";
    
    $stmt = $conexion->prepare($sql_update);
    $stmt->bind_param("isssiisi", 
        $id_sucursal_post, $dni, $nombres, $apellidos, $id_cargo, $id_area, $estado, $id_empleado);
    
    if ($stmt->execute()) {
        header("Location: empleados.php?status=success_edit");
        exit();
    } else {
        $error_message = "Error al actualizar el empleado: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

// Cargar datos del empleado a editar
$stmt_select = $conexion->prepare("SELECT * FROM empleados WHERE id = ?");
$stmt_select->bind_param("i", $id_empleado);
$stmt_select->execute();
$empleado = $stmt_select->get_result()->fetch_assoc();
$stmt_select->close();

if (!$empleado) {
    header("Location: empleados.php");
    exit();
}

// Cargar catálogos (incluyendo los que puedan estar inactivos si son los del empleado)
$es_admin_general = ($_SESSION['user_sucursal_id'] === null);
$sucursales = $conexion->query("SELECT * FROM sucursales WHERE estado = 'Activo' OR id = " . (int)$empleado['id_sucursal'] . " ORDER BY nombre");
$areas = $conexion->query("SELECT * FROM areas WHERE estado = 'Activo' OR id = " . (int)$empleado['id_area'] . " ORDER BY nombre");
$cargos_iniciales = $conexion->query("SELECT * FROM cargos WHERE id_area = " . (int)$empleado['id_area'] . " ORDER BY nombre");
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
    .card-header.bg-light { 
        background-color: #f8f9fa !important; 
        border-bottom: 1px solid #e3e6f0;
        font-weight: 600;
        color: var(--custom-primary);
    }
    .btn { 
        border-radius: 0.5rem; 
        font-weight: 600; 
    }
    .btn-primary { 
        background-color: var(--custom-primary) !important; 
        border-color: var(--custom-primary) !important;
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-pencil-square me-2"></i> Editar Empleado: <?php echo htmlspecialchars($empleado['nombres'] . ' ' . $empleado['apellidos']); ?>
        </h1>
        <a href="empleados.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver a Empleados
        </a>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-x-octagon-fill me-2"></i><?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light py-3">
            <h5 class="card-title mb-0">Datos del Empleado</h5>
        </div>
        <div class="card-body">
            <form action="empleado_editar.php?id=<?php echo $id_empleado; ?>" method="POST">
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <?php if ($es_admin_general): ?>
                            <label for="id_sucursal" class="form-label fw-bold">Sucursal <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_sucursal" required>
                                <?php $sucursales->data_seek(0); while($sucursal = $sucursales->fetch_assoc()): ?>
                                    <option value="<?php echo $sucursal['id']; ?>" <?php echo ($sucursal['id'] == $empleado['id_sucursal']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="id_sucursal" value="<?php echo $empleado['id_sucursal']; ?>">
                            <label class="form-label fw-bold">Sucursal</label>
                            <p class="text-muted small">
                                <?php 
                                    $sucursales->data_seek(0);
                                    while($s = $sucursales->fetch_assoc()):
                                        if ($s['id'] == $empleado['id_sucursal']) { echo htmlspecialchars($s['nombre']); break; }
                                    endwhile;
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">DNI <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="dni" required value="<?php echo htmlspecialchars($empleado['dni']); ?>" maxlength="8" pattern="\d{8}" title="Debe ser un DNI válido de 8 dígitos.">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nombres <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombres" required value="<?php echo htmlspecialchars($empleado['nombres']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Apellidos <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="apellidos" required value="<?php echo htmlspecialchars($empleado['apellidos']); ?>">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Área <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_area" id="selectArea" required>
                             <?php $areas->data_seek(0); while($area = $areas->fetch_assoc()): ?>
                                <option value="<?php echo $area['id']; ?>" <?php echo ($area['id'] == $empleado['id_area']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($area['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cargo <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_cargo" id="selectCargo" required>
                            <?php $cargos_iniciales->data_seek(0); while($cargo = $cargos_iniciales->fetch_assoc()): ?>
                                <option value="<?php echo $cargo['id']; ?>" <?php echo ($cargo['id'] == $empleado['id_cargo']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cargo['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Estado <span class="text-danger">*</span></label>
                        <select class="form-select" name="estado" required>
                            <option value="Activo" <?php echo ($empleado['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                            <option value="Inactivo" <?php echo ($empleado['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <hr class="my-4">
                <a href="empleados.php" class="btn btn-secondary me-2">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Guardar Cambios
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectArea = document.getElementById('selectArea');
    const selectCargo = document.getElementById('selectCargo');
    const originalCargoId = "<?php echo $empleado['id_cargo']; ?>";
    
    // Función para manejar la carga dinámica de cargos
    function loadCargos(idArea, idCargoSeleccionado) {
        selectCargo.innerHTML = '<option value="">Cargando...</option>';
        selectCargo.disabled = true;

        if (idArea) {
            // Se asume que ../includes/api.php?action=getCargos funciona correctamente
            fetch(`../includes/api.php?action=getCargos&id_area=${idArea}`)
                .then(response => response.json())
                .then(data => {
                    selectCargo.innerHTML = '<option value="">Seleccione...</option>';
                    selectCargo.disabled = false;
                    
                    if (data.length > 0) {
                        data.forEach(cargo => {
                            const option = document.createElement('option');
                            option.value = cargo.id;
                            option.textContent = cargo.nombre;
                            
                            // Pre-seleccionar el cargo si coincide con el cargo original
                            if (idCargoSeleccionado && cargo.id == idCargoSeleccionado) {
                                option.selected = true;
                            }
                            selectCargo.appendChild(option);
                        });
                    } else {
                        selectCargo.innerHTML = '<option value="">No hay cargos disponibles</option>';
                        selectCargo.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error al cargar cargos:', error);
                    selectCargo.innerHTML = '<option value="">Error al cargar cargos</option>';
                    selectCargo.disabled = true;
                });
        } else {
            selectCargo.innerHTML = '<option value="">Seleccione un área primero</option>';
            selectCargo.disabled = true;
        }
    }

    // Listener para el cambio de Área
    selectArea.addEventListener('change', function() {
        const idArea = this.value;
        // Al cambiar el área, no hay un cargo pre-seleccionado
        loadCargos(idArea, null);
    });
});
</script>

<?php require_once '../templates/footer.php'; ?>