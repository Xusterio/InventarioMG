<?php
ob_start(); // Buffer de salida para evitar errores de headers
require_once '../templates/header.php';

// --- LÓGICA DE PROCESAMIENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // La sucursal es tomada del POST solo si el usuario es Admin General (id_sucursal_id es null)
    $id_sucursal_post = ($_SESSION['user_sucursal_id'] === null) ? $_POST['id_sucursal'] : $_SESSION['user_sucursal_id'];
    $dni = $_POST['dni'];
    $nombres = $_POST['nombres'];
    $apellidos = $_POST['apellidos'];
    $id_cargo = $_POST['id_cargo'];
    $id_area = $_POST['id_area'];
    
    // Validar que se haya seleccionado una sucursal si es admin general
    if ($id_sucursal_post === null) {
         $error_message = "Error: Debe seleccionar una sucursal.";
    } else {
        $sql_insert = "INSERT INTO empleados (id_sucursal, dni, nombres, apellidos, id_cargo, id_area) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql_insert);
        $stmt->bind_param("isssii", $id_sucursal_post, $dni, $nombres, $apellidos, $id_cargo, $id_area);
        
        if ($stmt->execute()) { 
            // Limpiar buffer y redirigir
            ob_end_clean();
            header("Location: empleados.php?status=success_add"); 
            exit(); 
        } else { 
            $error_message = "Error al agregar el empleado: " . htmlspecialchars($stmt->error); 
        }
        $stmt->close();
    }
}
// Cargar catálogos para los formularios
$areas = $conexion->query("SELECT * FROM areas WHERE estado = 'Activo' ORDER BY nombre");
$sucursales = $conexion->query("SELECT * FROM sucursales WHERE estado = 'Activo' ORDER BY nombre");
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
            <i class="bi bi-person-plus-fill me-2"></i> Registrar Nuevo Empleado
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
            <h5 class="card-title mb-0">Datos Personales y Ubicación</h5>
        </div>
        <div class="card-body">
            <form action="empleado_agregar.php" method="POST">
                <?php if ($_SESSION['user_sucursal_id'] === null): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="id_sucursal" class="form-label fw-bold">Sucursal <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_sucursal" id="id_sucursal" required>
                                <option value="">Seleccione...</option>
                                <?php $sucursales->data_seek(0); while($sucursal = $sucursales->fetch_assoc()): ?>
                                <option value="<?php echo $sucursal['id']; ?>"><?php echo htmlspecialchars($sucursal['nombre']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">DNI <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="dni" required maxlength="8" pattern="\d{8}" title="Debe ser un DNI válido de 8 dígitos.">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nombres <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombres" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Apellidos <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="apellidos" required>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Área <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_area" id="selectArea" required>
                            <option value="">Seleccione un área...</option>
                            <?php while($area = $areas->fetch_assoc()): ?>
                            <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['nombre']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cargo <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_cargo" id="selectCargo" required disabled>
                            <option value="">Seleccione un área primero</option>
                        </select>
                    </div>
                </div>
                
                <hr class="my-4">
                <a href="empleados.php" class="btn btn-secondary me-2">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Registrar Empleado
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('selectArea').addEventListener('change', function() {
    const idArea = this.value;
    const selectCargo = document.getElementById('selectCargo');
    selectCargo.innerHTML = '<option value="">Cargando...</option>';
    selectCargo.disabled = true;
    
    if (idArea) {
        // La URL de la API debe ser ajustada si es necesario, pero se mantiene la estructura:
        fetch(`../includes/api.php?action=getCargos&id_area=${idArea}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                selectCargo.innerHTML = '<option value="">Seleccione...</option>';
                if (data.length > 0) {
                    data.forEach(cargo => {
                        const option = new Option(cargo.nombre, cargo.id);
                        selectCargo.add(option);
                    });
                    selectCargo.disabled = false;
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
});
</script>

<?php require_once '../templates/footer.php'; ?>