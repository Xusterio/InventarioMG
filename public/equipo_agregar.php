<?php
ob_start(); // Buffer para evitar errores de headers
require_once '../templates/header.php';

// Lógica para procesar el formulario de inserción
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que todos los campos requeridos estén presentes
    if (!isset($_POST['id_modelo']) || empty($_POST['id_modelo'])) {
        $error_message = "El campo Modelo es requerido.";
    } else {
        // Asignación de variables
        $id_sucursal_post = ($_SESSION['user_sucursal_id'] === null) ? $_POST['id_sucursal'] : $_SESSION['user_sucursal_id'];
        $id_tipo_equipo = $_POST['id_tipo_equipo'];
        $id_marca = $_POST['id_marca'];
        $id_modelo = $_POST['id_modelo'];
        $codigo_inventario = $_POST['codigo_inventario'];
        $numero_serie = $_POST['numero_serie'];
        
        $tipo_adquisicion = 'Propio'; 
        $fecha_adquisicion = !empty($_POST['fecha_adquisicion']) ? $_POST['fecha_adquisicion'] : null;
        
        $proveedor_select = $_POST['proveedor_select'];
        $proveedor_otro = isset($_POST['proveedor_otro']) ? trim($_POST['proveedor_otro']) : null;
        $proveedor_final = ($proveedor_select === 'OTRO') ? $proveedor_otro : $proveedor_select;
        
        if ($proveedor_select === 'OTRO' && empty($proveedor_final)) {
             $error_message = "Debe especificar el nombre del proveedor si selecciona 'OTRO'.";
        }

        if (!isset($error_message)) {
            $caracteristicas = $_POST['caracteristicas'];
            $observaciones = $_POST['observaciones'];

            $sql_insert = "INSERT INTO equipos (id_sucursal, codigo_inventario, id_tipo_equipo, id_marca, id_modelo, numero_serie, tipo_adquisicion, caracteristicas, observaciones, fecha_adquisicion, proveedor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conexion->prepare($sql_insert);
            $stmt->bind_param("isiiissssss", $id_sucursal_post, $codigo_inventario, $id_tipo_equipo, $id_marca, $id_modelo, $numero_serie, $tipo_adquisicion, $caracteristicas, $observaciones, $fecha_adquisicion, $proveedor_final);
            
            if ($stmt->execute()) {
                ob_end_clean(); 
                // MODIFICACIÓN: Redirige a sí mismo con parámetro de éxito
                header("Location: equipo_agregar.php?status=success_add");
                exit();
            } else {
                $error_message = "Error al agregar el equipo: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Cargar catálogos
$tipos = $conexion->query("SELECT * FROM tipos_equipo WHERE estado = 'Activo' ORDER BY nombre");
$marcas = $conexion->query("SELECT * FROM marcas WHERE estado = 'Activo' ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Equipo - Sistema de Inventario</title>
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
            transition: all 0.3s;
        }
        .btn { 
            border-radius: 0.5rem; 
            font-weight: 600; 
            transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
        }
        .btn-primary { 
            background-color: var(--custom-primary) !important; 
            border-color: var(--custom-primary) !important;
        }
        .btn-primary:hover {
            background-color: #303f9f !important; 
            border-color: #303f9f !important;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        .form-container {
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-plus-circle-fill me-2"></i>
            Registrar Nuevo Equipo
        </h1>
        <a href="equipos.php" class="btn btn-secondary">
            <i class="bi bi-list-ul me-2"></i>Ver Listado Completo
        </a>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'success_add'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <strong>¡Éxito!</strong> El equipo se registró correctamente. Puedes continuar registrando más equipos.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><i class="bi bi-x-octagon-fill me-2"></i><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body form-container">

            <form action="equipo_agregar.php" method="POST" id="equipoForm">
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
                                    <option value="<?php echo $sucursal['id']; ?>"><?php echo htmlspecialchars($sucursal['nombre']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="codigo_inventario" class="form-label">Código Patrimonial<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="codigo_inventario" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="numero_serie" class="form-label">Número de Serie <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="numero_serie" required>
                        </div>
                    <?php else: // Usuario de Sucursal ?>
                        <div class="col-md-6 mb-3">
                            <label for="codigo_inventario" class="form-label">Código Patrimonial <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="codigo_inventario" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="numero_serie" class="form-label">Número de Serie <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="numero_serie" required>
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
                                <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nombre']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Marca <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_marca" id="selectMarca" required>
                            <option value="">Seleccione...</option>
                            <?php $marcas->data_seek(0); while($marca = $marcas->fetch_assoc()): ?>
                                <option value="<?php echo $marca['id']; ?>"><?php echo htmlspecialchars($marca['nombre']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Modelo <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_modelo" id="selectModelo" required>
                            <option value="">Seleccione una marca primero</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="fecha_adquisicion" class="form-label">Fecha de Adquisición</label>
                        <input type="date" class="form-control" name="fecha_adquisicion">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="proveedor_select" class="form-label">Proveedor</label>
                        <select class="form-select" name="proveedor_select" id="selectProveedor" onchange="toggleOtroProveedor()">
                            <option value="">Seleccione un proveedor...</option>
                            <option value="DREMO">DREMO</option>
                            <option value="UGEL">UGEL</option>
                            <option value="OTRO">OTRO</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3" id="otroProveedorField" style="display: none;">
                    <div class="col-md-6 offset-md-6">
                        <label for="proveedor_otro" class="form-label">Especifique el Proveedor</label>
                        <input type="text" class="form-control" name="proveedor_otro" id="inputProveedorOtro">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Características</label>
                        <textarea class="form-control" name="caracteristicas" rows="2"></textarea>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control" name="observaciones" rows="3"></textarea>
                </div>
                
                <hr class="my-4">
                <div class="d-flex justify-content-end">
                    <a href="equipos.php" class="btn btn-secondary me-2">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Registrar Equipo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('selectMarca').addEventListener('change', function() {
    const idMarca = this.value;
    const selectModelo = document.getElementById('selectModelo');
    selectModelo.innerHTML = '<option value="">Cargando...</option>';

    if (idMarca) {
        fetch(`../includes/api.php?action=getModelos&id_marca=${idMarca}`)
            .then(response => response.json())
            .then(data => {
                selectModelo.innerHTML = '<option value="">Seleccione un modelo...</option>';
                if (data.length > 0) {
                    data.forEach(modelo => {
                        const option = new Option(modelo.nombre, modelo.id);
                        selectModelo.add(option);
                    });
                } else {
                    selectModelo.innerHTML = '<option value="">No hay modelos activos</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                selectModelo.innerHTML = '<option value="">Error al cargar</option>';
            });
    } else {
        selectModelo.innerHTML = '<option value="">Seleccione una marca primero</option>';
    }
});

function toggleOtroProveedor() {
    const selectProveedor = document.getElementById('selectProveedor');
    const otroProveedorField = document.getElementById('otroProveedorField');
    const inputProveedorOtro = document.getElementById('inputProveedorOtro');

    if (selectProveedor.value === 'OTRO') {
        otroProveedorField.style.display = 'flex';
        inputProveedorOtro.setAttribute('required', 'required');
    } else {
        otroProveedorField.style.display = 'none';
        inputProveedorOtro.removeAttribute('required');
        inputProveedorOtro.value = '';
    }
}

document.getElementById('equipoForm').addEventListener('submit', function(e) {
    const selectModelo = document.getElementById('selectModelo');
    const selectProveedor = document.getElementById('selectProveedor');
    const inputProveedorOtro = document.getElementById('inputProveedorOtro');

    if (!selectModelo.value) {
        e.preventDefault();
        alert('Por favor, seleccione un modelo.');
        return;
    }
    
    if (selectProveedor.value === 'OTRO' && inputProveedorOtro.value.trim() === '') {
        e.preventDefault();
        alert('Debe especificar el nombre del proveedor.');
        return;
    }
});
</script>

<?php 
require_once '../templates/footer.php';
ob_end_flush();
?>