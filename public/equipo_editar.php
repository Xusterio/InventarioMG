<?php
ob_start(); // Buffer para evitar errores de headers
require_once '../templates/header.php';

// 1. Verificar si se proporcionó un ID de equipo
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: equipos.php");
    exit();
}

$id_equipo = (int)$_GET['id'];
$error_message = null;

// 2. Lógica para procesar la ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que todos los campos requeridos estén presentes
    if (!isset($_POST['id_modelo']) || empty($_POST['id_modelo'])) {
        $error_message = "El campo Modelo es requerido.";
    } else {
        // Asignación de variables
        // La sucursal solo se puede cambiar si el usuario es Admin General
        $id_sucursal_post = ($_SESSION['user_sucursal_id'] === null) ? $_POST['id_sucursal'] : $_POST['current_id_sucursal'];
        $id_tipo_equipo = $_POST['id_tipo_equipo'];
        $id_marca = $_POST['id_marca'];
        $id_modelo = $_POST['id_modelo'];
        $codigo_inventario = $_POST['codigo_inventario'];
        $numero_serie = $_POST['numero_serie'];
        
        // --- Cambios solicitados: Adquisición eliminada, Proveedor y Fecha ajustados ---
        
        // La columna tipo_adquisicion es requerida en la DB, se establece un valor por defecto.
        $tipo_adquisicion = 'Propio'; 
        
        // La fecha viene en formato YYYY-MM-DD (input type="date")
        $fecha_adquisicion = !empty($_POST['fecha_adquisicion']) ? $_POST['fecha_adquisicion'] : null;
        
        // Lógica para el proveedor condicional
        $proveedor_select = $_POST['proveedor_select'];
        $proveedor_otro = isset($_POST['proveedor_otro']) ? trim($_POST['proveedor_otro']) : null;
        
        // Determinar el valor final del proveedor
        $proveedor_final = ($proveedor_select === 'OTRO') ? $proveedor_otro : $proveedor_select;
        
        // Si se seleccionó OTRO pero no se escribió nada, se considera error
        if ($proveedor_select === 'OTRO' && empty($proveedor_final)) {
             $error_message = "Debe especificar el nombre del proveedor si selecciona 'OTRO'.";
        }

        // Si no hay error, se procede con la actualización
        if (!isset($error_message)) {
            $caracteristicas = $_POST['caracteristicas'];
            $observaciones = $_POST['observaciones'];

            $sql_update = "UPDATE equipos SET 
                id_sucursal = ?, 
                codigo_inventario = ?, 
                id_tipo_equipo = ?, 
                id_marca = ?, 
                id_modelo = ?, 
                numero_serie = ?, 
                tipo_adquisicion = ?,
                caracteristicas = ?, 
                observaciones = ?, 
                fecha_adquisicion = ?, 
                proveedor = ?
            WHERE id = ?";
            
            $stmt = $conexion->prepare($sql_update);
            // El formato de bind_param: isiiissssssi (11 string/integer + 1 id_equipo al final)
            $stmt->bind_param("isiiissssssi", 
                $id_sucursal_post, 
                $codigo_inventario, 
                $id_tipo_equipo, 
                $id_marca, 
                $id_modelo, 
                $numero_serie, 
                $tipo_adquisicion,
                $caracteristicas, 
                $observaciones, 
                $fecha_adquisicion, 
                $proveedor_final,
                $id_equipo
            );
            
            if ($stmt->execute()) {
                ob_end_clean(); // Limpiar buffer antes del header
                header("Location: equipos.php?status=success_edit");
                exit();
            } else {
                $error_message = "Error al actualizar el equipo: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// 3. Obtener datos actuales del equipo para rellenar el formulario
$sql_equipo = "SELECT * FROM equipos WHERE id = ?";
$stmt_equipo = $conexion->prepare($sql_equipo);
$stmt_equipo->bind_param("i", $id_equipo);
$stmt_equipo->execute();
$result_equipo = $stmt_equipo->get_result();
$equipo = $result_equipo->fetch_assoc();
$stmt_equipo->close();

if (!$equipo) {
    echo "<div class='alert alert-danger'>Equipo no encontrado.</div>";
    require_once '../templates/footer.php';
    exit();
}

// 4. Cargar catálogos para los menús desplegables
$tipos = $conexion->query("SELECT * FROM tipos_equipo WHERE estado = 'Activo' ORDER BY nombre");
$marcas = $conexion->query("SELECT * FROM marcas WHERE estado = 'Activo' ORDER BY nombre");

// Lógica para preseleccionar proveedor condicional
$proveedores_fijos = ['DREMO', 'UGEL'];
$proveedor_seleccionado = 'OTRO';
$proveedor_otro_valor = $equipo['proveedor'];

if (in_array($equipo['proveedor'], $proveedores_fijos)) {
    $proveedor_seleccionado = $equipo['proveedor'];
    $proveedor_otro_valor = '';
}
// Si el proveedor no es nulo/vacío y no está en los fijos, es 'OTRO' y su valor es el proveedor.

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo - Sistema de Inventario</title>
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
        /* Estilos de Botones */
        .btn { 
            border-radius: 0.5rem; 
            font-weight: 600; 
            transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
        }
        /* Color primario para botones */
        .btn-primary { 
            background-color: var(--custom-primary) !important; 
            border-color: var(--custom-primary) !important;
        }
        .btn-primary:hover {
            background-color: #303f9f !important; 
            border-color: #303f9f !important;
        }
        /* Etiquetas de formulario más prominentes */
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        /* Contenedor principal con padding consistente */
        .form-container {
            padding: 20px;
        }
        /* Línea divisoria más sutil */
        .my-4 {
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-pencil-square me-2"></i>
            Editar Equipo: <?php echo htmlspecialchars($equipo['codigo_inventario']); ?>
        </h1>
        <a href="equipos.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver a Equipos
        </a>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><i class="bi bi-x-octagon-fill me-2"></i><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body form-container">

            <form action="equipo_editar.php?id=<?php echo $id_equipo; ?>" method="POST" id="equipoForm">
                <input type="hidden" name="current_id_sucursal" value="<?php echo htmlspecialchars($equipo['id_sucursal']); ?>">
                <p class="text-muted small mb-4">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>

                <div class="row mb-3">
                    <?php if ($_SESSION['user_sucursal_id'] === null): // Admin General ?>
                        <div class="col-md-4 mb-3">
                            <label for="id_sucursal" class="form-label">Sucursal <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_sucursal" required>
                                <option value="">Seleccione...</option>
                                <?php 
                                $sucursales = $conexion->query("SELECT * FROM sucursales WHERE estado = 'Activo' ORDER BY nombre");
                                while($sucursal = $sucursales->fetch_assoc()): ?>
                                    <option value="<?php echo $sucursal['id']; ?>" <?php echo ($equipo['id_sucursal'] == $sucursal['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="codigo_inventario" class="form-label">Código Patrimonial<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="codigo_inventario" value="<?php echo htmlspecialchars($equipo['codigo_inventario']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="numero_serie" class="form-label">Número de Serie <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="numero_serie" value="<?php echo htmlspecialchars($equipo['numero_serie']); ?>" required>
                        </div>
                    <?php else: // Usuario de Sucursal (Sin selector de sucursal) ?>
                        <div class="col-md-6 mb-3">
                            <label for="codigo_inventario" class="form-label">Código Patrimonial <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="codigo_inventario" value="<?php echo htmlspecialchars($equipo['codigo_inventario']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="numero_serie" class="form-label">Número de Serie <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="numero_serie" value="<?php echo htmlspecialchars($equipo['numero_serie']); ?>" required>
                        </div>
                    <?php endif; ?>
                </div>

                <hr class="my-4">

                <div class="row mb-3">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tipo de Equipo <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_tipo_equipo" required>
                            <option value="">Seleccione...</option>
                            <?php $tipos->data_seek(0); while($tipo = $tipos->fetch_assoc()): ?>
                                <option value="<?php echo $tipo['id']; ?>" <?php echo ($equipo['id_tipo_equipo'] == $tipo['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipo['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Marca <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_marca" id="selectMarca" required>
                            <option value="">Seleccione...</option>
                            <?php $marcas->data_seek(0); while($marca = $marcas->fetch_assoc()): ?>
                                <option value="<?php echo $marca['id']; ?>" <?php echo ($equipo['id_marca'] == $marca['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($marca['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Modelo <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_modelo" id="selectModelo" required>
                            <option value="<?php echo htmlspecialchars($equipo['id_modelo']); ?>" selected>
                                Cargando modelo actual...
                            </option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="fecha_adquisicion" class="form-label">Fecha de Adquisición</label>
                        <input type="date" class="form-control" name="fecha_adquisicion" value="<?php echo htmlspecialchars($equipo['fecha_adquisicion']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="proveedor_select" class="form-label">Proveedor</label>
                        <select class="form-select" name="proveedor_select" id="selectProveedor" onchange="toggleOtroProveedor()">
                            <option value="" <?php echo (empty($equipo['proveedor']) || !in_array($equipo['proveedor'], $proveedores_fijos) && $equipo['proveedor'] !== null && $proveedor_seleccionado !== 'OTRO') ? 'selected' : ''; ?>>
                                Seleccione un proveedor...
                            </option>
                            <option value="DREMO" <?php echo ($proveedor_seleccionado === 'DREMO') ? 'selected' : ''; ?>>DREMO</option>
                            <option value="UGEL" <?php echo ($proveedor_seleccionado === 'UGEL') ? 'selected' : ''; ?>>UGEL</option>
                            <option value="OTRO" <?php echo ($proveedor_seleccionado === 'OTRO' || (!in_array($equipo['proveedor'], $proveedores_fijos) && !empty($equipo['proveedor']))) ? 'selected' : ''; ?>>OTRO</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3" id="otroProveedorField" style="display: none;">
                    <div class="col-md-6 offset-md-6">
                        <label for="proveedor_otro" class="form-label">Especifique el Proveedor</label>
                        <input type="text" class="form-control" name="proveedor_otro" id="inputProveedorOtro" value="<?php echo htmlspecialchars($proveedor_otro_valor); ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Características</label>
                        <textarea class="form-control" name="caracteristicas" rows="2"><?php echo htmlspecialchars($equipo['caracteristicas']); ?></textarea>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control" name="observaciones" rows="3"><?php echo htmlspecialchars($equipo['observaciones']); ?></textarea>
                </div>
                
                <hr class="my-4">
                <a href="equipos.php" class="btn btn-secondary me-2">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Actualizar Equipo
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// --- Variables de inicialización ---
const idMarcaInicial = '<?php echo htmlspecialchars($equipo['id_marca']); ?>';
const idModeloInicial = '<?php echo htmlspecialchars($equipo['id_modelo']); ?>';

// --- Funciones JS ---

// Función para mostrar el campo de "Otro Proveedor"
function toggleOtroProveedor() {
    const selectProveedor = document.getElementById('selectProveedor');
    const otroProveedorField = document.getElementById('otroProveedorField');
    const inputProveedorOtro = document.getElementById('inputProveedorOtro');
    
    // Si se selecciona OTRO o si la opción seleccionada no tiene valor (proveedor vacío en DB)
    const isOtro = selectProveedor.value === 'OTRO';
    const isVacio = selectProveedor.value === '';

    if (isOtro) {
        otroProveedorField.style.display = 'flex';
        inputProveedorOtro.setAttribute('required', 'required');
    } else {
        otroProveedorField.style.display = 'none';
        inputProveedorOtro.removeAttribute('required');
        
        // Limpiar el campo si ya no es necesario, pero solo si no es la carga inicial
        if (!isVacio) {
            inputProveedorOtro.value = '';
        }
    }
}

// Función para cargar modelos al cambiar la marca
function cargarModelos(idMarca, idModeloActual = null) {
    const selectModelo = document.getElementById('selectModelo');
    
    selectModelo.innerHTML = '<option value="">Cargando modelos...</option>';
    selectModelo.disabled = false;

    if (idMarca) {
        fetch(`../includes/api.php?action=getModelos&id_marca=${idMarca}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error de red o del servidor.');
                }
                return response.json();
            })
            .then(data => {
                selectModelo.innerHTML = '<option value="">Seleccione un modelo...</option>';
                if (data.length > 0) {
                    data.forEach(modelo => {
                        const isSelected = (idModeloActual && modelo.id == idModeloActual) ? 'selected' : '';
                        const option = new Option(modelo.nombre, modelo.id, false, isSelected);
                        selectModelo.add(option);
                    });
                } else {
                    selectModelo.innerHTML = '<option value="">No hay modelos activos para esta marca</option>';
                }
            })
            .catch(error => {
                console.error('Error al cargar los modelos:', error);
                selectModelo.innerHTML = '<option value="">Error al cargar modelos</option>';
            });
    } else {
        selectModelo.innerHTML = '<option value="">Seleccione una marca primero</option>';
    }
}

// --- Event Listeners ---

// 1. Cargar modelos cuando se cambia la marca
document.getElementById('selectMarca').addEventListener('change', function() {
    // Cuando el usuario cambia la marca, no preseleccionamos nada.
    cargarModelos(this.value);
});

// 2. Validación del formulario antes de enviar
document.getElementById('equipoForm').addEventListener('submit', function(e) {
    const selectModelo = document.getElementById('selectModelo');
    const selectProveedor = document.getElementById('selectProveedor');
    const inputProveedorOtro = document.getElementById('inputProveedorOtro');

    if (!selectModelo.value) {
        e.preventDefault();
        alert('Por favor, seleccione un modelo antes de enviar el formulario.');
        selectModelo.focus();
        return;
    }
    
    // Validación para el campo "OTRO" del proveedor
    if (selectProveedor.value === 'OTRO' && inputProveedorOtro.value.trim() === '') {
        e.preventDefault();
        alert('Debe especificar el nombre del proveedor si selecciona "OTRO".');
        inputProveedorOtro.focus();
        return;
    }
});

// 3. Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // A. Cargar modelos con el ID de modelo actual para preseleccionar
    if (idMarcaInicial) {
        cargarModelos(idMarcaInicial, idModeloInicial);
    }
    // B. Inicializar la visualización del campo "Otro Proveedor"
    toggleOtroProveedor(); 
});
</script>

<?php require_once '../templates/footer.php'; ?>
</body>
</html>