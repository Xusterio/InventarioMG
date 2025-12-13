<?php
ob_start(); // Buffer de salida para evitar errores de headers
require_once '../templates/header.php';

// --- Lógica para OBTENER EL COLAPSO ACTIVO DESDE LA URL ---
$active_collapse = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_FRAGMENT) ?? '');
$valid_collapse_ids = ['collapseSucursales', 'collapseAreas', 'collapseCargos', 'collapseTipos', 'collapseMarcas', 'collapseModelos'];
if (!in_array($active_collapse, $valid_collapse_ids)) {
    $active_collapse = 'collapseSucursales';
}

// Mapa de catálogos a tablas y a colapsos para simplificar la lógica
$catalogo_map = [
    'sucursal' => ['table' => 'sucursales', 'collapse' => 'collapseSucursales'], 
    'tipo' => ['table' => 'tipos_equipo', 'collapse' => 'collapseTipos'], 
    'marca' => ['table' => 'marcas', 'collapse' => 'collapseMarcas'], 
    'modelo' => ['table' => 'modelos', 'collapse' => 'collapseModelos'], 
    'area' => ['table' => 'areas', 'collapse' => 'collapseAreas'], 
    'cargo' => ['table' => 'cargos', 'collapse' => 'collapseCargos']
];

// --- Lógica para CAMBIAR ESTADO / ELIMINAR ---
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['type'])) {
    $id = (int)$_GET['id'];
    $type = $_GET['type'];
    $action = $_GET['action']; // 'deactivate', 'activate', o 'delete'
    
    if (array_key_exists($type, $catalogo_map)) {
        $table_name = $catalogo_map[$type]['table'];
        $redirect_hash = $catalogo_map[$type]['collapse'];
        $status_param = '';

        if ($action == 'delete') {
            // --- BORRADO FÍSICO ---
            $stmt = $conexion->prepare("DELETE FROM {$table_name} WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $status_param = 'success_delete';
            } else {
                // Error por clave foránea (FK)
                $status_param = 'error_delete_fk'; 
            }
        } else {
            // --- BAJA LÓGICA (Activar/Desactivar) ---
            $estado = ($action == 'deactivate') ? 'Inactivo' : 'Activo';
            $stmt = $conexion->prepare("UPDATE {$table_name} SET estado = ? WHERE id = ?");
            $stmt->bind_param("si", $estado, $id);
            if ($stmt->execute()) {
                $status_param = 'success_state';
            } else {
                $status_param = 'error_db';
            }
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }

        // Limpiar buffer y redirigir, manteniendo el colapso abierto
        ob_end_clean();
        header("Location: gestion_catalogos.php?status={$status_param}#{$redirect_hash}");
        exit();
    }
}

// --- Lógica para AÑADIR NUEVOS ELEMENTOS (Usando Post/Redirect/Get) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = null;
    $catalogo_key = isset($_POST['catalogo_sucursal']) ? 'sucursal' : ($_POST['catalogo'] ?? '');
    
    $redirect_hash = $catalogo_map[$catalogo_key]['collapse'] ?? '';
    
    if (isset($_POST['catalogo'])) {
        $catalogo = $_POST['catalogo'];
        $nombre = $_POST['nombre'];
        switch ($catalogo) {
            case 'tipo': $stmt = $conexion->prepare("INSERT INTO tipos_equipo (nombre) VALUES (?)"); $stmt->bind_param("s", $nombre); break;
            case 'marca': $stmt = $conexion->prepare("INSERT INTO marcas (nombre) VALUES (?)"); $stmt->bind_param("s", $nombre); break;
            case 'area': $stmt = $conexion->prepare("INSERT INTO areas (nombre) VALUES (?)"); $stmt->bind_param("s", $nombre); break;
            case 'modelo': $id_marca = $_POST['id_marca']; $stmt = $conexion->prepare("INSERT INTO modelos (id_marca, nombre) VALUES (?, ?)"); $stmt->bind_param("is", $id_marca, $nombre); break;
            case 'cargo': $id_area = $_POST['id_area']; $stmt = $conexion->prepare("INSERT INTO cargos (id_area, nombre) VALUES (?, ?)"); $stmt->bind_param("is", $id_area, $nombre); break;
        }
    } elseif (isset($_POST['catalogo_sucursal'])) {
        $nombre = $_POST['nombre_sucursal'];
        $direccion = $_POST['direccion_sucursal'];
        // Asumiendo que las sucursales/ambientes se crean con estado 'Activo' por defecto
        $estado = 'Activo'; 
        $stmt = $conexion->prepare("INSERT INTO sucursales (nombre, direccion, estado) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $direccion, $estado);
    }
    
    if ($stmt && $stmt->execute()) { 
        ob_end_clean();
        // Redirección con estado de éxito y hash para mantener el acordeón abierto
        header("Location: gestion_catalogos.php?status=success_add#{$redirect_hash}");
        exit();
    } elseif($stmt) { 
        ob_end_clean();
        // Redirección con estado de error y hash para mantener el acordeón abierto
        $error_message_param = strpos($stmt->error, 'Duplicate entry') !== false ? "error_duplicate" : "error_db";
        header("Location: gestion_catalogos.php?status={$error_message_param}#{$redirect_hash}");
        exit();
    }
    if ($stmt) $stmt->close();
}

// --- Cargar datos existentes para las tablas ---
$sucursales = $conexion->query("SELECT * FROM sucursales ORDER BY nombre");
$tipos = $conexion->query("SELECT * FROM tipos_equipo ORDER BY nombre");
$marcas = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
$modelos = $conexion->query("SELECT m.id, m.nombre, m.estado, ma.nombre as marca_nombre FROM modelos m JOIN marcas ma ON m.id_marca = ma.id ORDER BY ma.nombre, m.nombre");
$areas = $conexion->query("SELECT * FROM areas ORDER BY nombre");
$cargos = $conexion->query("SELECT c.id, c.nombre, c.estado, a.nombre AS area_nombre FROM cargos c JOIN areas a ON c.id_area = a.id ORDER BY a.nombre, c.nombre");
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-tags me-2 text-primary"></i> Gestión de Catálogos
        </h1>
    </div>

    <?php 
    // --- Mostrar Mensajes de Estado ---
    if (isset($_GET['status'])): ?>
        <div class="alert alert-dismissible fade show mt-3 
            <?php 
            if ($_GET['status'] === 'success_add' || $_GET['status'] === 'success_state' || $_GET['status'] === 'success_delete') {
                echo 'alert-success';
            } else {
                echo 'alert-danger';
            }
            ?>">
            <?php if ($_GET['status'] === 'success_add'): ?>
                <i class="bi bi-check-circle-fill me-2"></i>Elemento agregado correctamente.
            <?php elseif ($_GET['status'] === 'success_state'): ?>
                <i class="bi bi-check-circle-fill me-2"></i>Estado actualizado correctamente.
            <?php elseif ($_GET['status'] === 'success_delete'): ?>
                <i class="bi bi-check-circle-fill me-2"></i>Elemento eliminado permanentemente.
            <?php elseif ($_GET['status'] === 'error_delete_fk'): ?>
                <i class="bi bi-x-octagon-fill me-2"></i>Error al eliminar: El elemento está siendo utilizado en otros registros. (Clave Foránea)
            <?php elseif ($_GET['status'] === 'error_duplicate'): ?>
                <i class="bi bi-exclamation-triangle-fill me-2"></i>Error al agregar: Ya existe un elemento con este nombre.
            <?php elseif ($_GET['status'] === 'error_db'): ?>
                <i class="bi bi-x-octagon-fill me-2"></i>Error en la base de datos al realizar la operación.
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="bi bi-hdd-stack me-2"></i>Catálogos de Equipos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="accordionEquipos">

                        <div class="accordion-item shadow-sm mb-3">
                            <h2 class="accordion-header" id="headingTipos">
                                <button class="accordion-button fw-bold <?php echo $active_collapse === 'collapseTipos' ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTipos" aria-expanded="<?php echo $active_collapse === 'collapseTipos' ? 'true' : 'false'; ?>" aria-controls="collapseTipos">
                                    <i class="bi bi-pc-display me-2 text-success"></i> Tipos de Equipo
                                </button>
                            </h2>
                            <div id="collapseTipos" class="accordion-collapse collapse <?php echo $active_collapse === 'collapseTipos' ? 'show' : ''; ?>" aria-labelledby="headingTipos" data-bs-parent="#accordionEquipos">
                                <div class="accordion-body">
                                    <form method="POST" class="mb-4 border-bottom pb-3">
                                        <input type="hidden" name="catalogo" value="tipo">
                                        <label class="form-label small fw-bold">Nuevo Tipo <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej: Laptop, Monitor..." required>
                                            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-plus-circle me-1"></i> Agregar</button>
                                        </div>
                                    </form>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle">
                                            <tbody>
                                                <?php while($item = $tipos->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($item['nombre']); ?>
                                                        <span class="badge rounded-pill float-end <?php echo $item['estado'] == 'Activo' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $item['estado']; ?></span>
                                                    </td>
                                                    <td class="text-end align-middle" style="width: 170px;">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="catalogo_editar.php?id=<?php echo $item['id']; ?>&type=tipo" class="btn btn-outline-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                            
                                                            <a href="#" class="btn btn-outline-secondary" title="Ver Historial (No implementado)"><i class="bi bi-clock-history"></i></a>

                                                            <?php if ($item['estado'] == 'Activo'): ?>
                                                                <a href="?action=deactivate&id=<?php echo $item['id']; ?>&type=tipo#collapseTipos" class="btn btn-outline-secondary" title="Desactivar"><i class="bi bi-x-circle"></i></a>
                                                            <?php else: ?>
                                                                <a href="?action=activate&id=<?php echo $item['id']; ?>&type=tipo#collapseTipos" class="btn btn-outline-success" title="Activar"><i class="bi bi-check-circle"></i></a>
                                                            <?php endif; ?>
                                                                 
                                                            <button onclick="confirmDelete(<?php echo $item['id']; ?>, 'tipo', 'collapseTipos')" class="btn btn-outline-danger" title="Eliminar Permanentemente">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item shadow-sm mb-3">
                            <h2 class="accordion-header" id="headingMarcas">
                                <button class="accordion-button fw-bold <?php echo $active_collapse === 'collapseMarcas' ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMarcas" aria-expanded="<?php echo $active_collapse === 'collapseMarcas' ? 'true' : 'false'; ?>" aria-controls="collapseMarcas">
                                    <i class="bi bi-tag-fill me-2 text-danger"></i> Marcas
                                </button>
                            </h2>
                            <div id="collapseMarcas" class="accordion-collapse collapse <?php echo $active_collapse === 'collapseMarcas' ? 'show' : ''; ?>" aria-labelledby="headingMarcas" data-bs-parent="#accordionEquipos">
                                <div class="accordion-body">
                                    <form method="POST" class="mb-4 border-bottom pb-3">
                                        <input type="hidden" name="catalogo" value="marca">
                                        <label class="form-label small fw-bold">Nueva Marca <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej: Dell, HP..." required>
                                            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-plus-circle me-1"></i> Agregar</button>
                                        </div>
                                    </form>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle">
                                            <tbody>
                                                <?php $marcas->data_seek(0); while($item = $marcas->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($item['nombre']); ?>
                                                        <span class="badge rounded-pill float-end <?php echo $item['estado'] == 'Activo' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $item['estado']; ?></span>
                                                    </td>
                                                    <td class="text-end align-middle" style="width: 170px;">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="catalogo_editar.php?id=<?php echo $item['id']; ?>&type=marca" class="btn btn-outline-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                            
                                                            <a href="#" class="btn btn-outline-secondary" title="Ver Historial (No implementado)"><i class="bi bi-clock-history"></i></a>

                                                            <?php if ($item['estado'] == 'Activo'): ?>
                                                                <a href="?action=deactivate&id=<?php echo $item['id']; ?>&type=marca#collapseMarcas" class="btn btn-outline-secondary" title="Desactivar"><i class="bi bi-x-circle"></i></a>
                                                            <?php else: ?>
                                                                <a href="?action=activate&id=<?php echo $item['id']; ?>&type=marca#collapseMarcas" class="btn btn-outline-success" title="Activar"><i class="bi bi-check-circle"></i></a>
                                                            <?php endif; ?>
                                                             
                                                            <button onclick="confirmDelete(<?php echo $item['id']; ?>, 'marca', 'collapseMarcas')" class="btn btn-outline-danger" title="Eliminar Permanentemente">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item shadow-sm mb-3">
                            <h2 class="accordion-header" id="headingModelos">
                                <button class="accordion-button fw-bold <?php echo $active_collapse === 'collapseModelos' ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModelos" aria-expanded="<?php echo $active_collapse === 'collapseModelos' ? 'true' : 'false'; ?>" aria-controls="collapseModelos">
                                    <i class="bi bi-gear-fill me-2 text-secondary"></i> Modelos
                                </button>
                            </h2>
                            <div id="collapseModelos" class="accordion-collapse collapse <?php echo $active_collapse === 'collapseModelos' ? 'show' : ''; ?>" aria-labelledby="headingModelos" data-bs-parent="#accordionEquipos">
                                <div class="accordion-body">
                                    <form method="POST" class="mb-4 border-bottom pb-3">
                                        <input type="hidden" name="catalogo" value="modelo">
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold">Marca <span class="text-danger">*</span></label>
                                            <select name="id_marca" class="form-select form-select-sm" required>
                                                <option value="">Selecciona una marca *</option>
                                                <?php $marcas->data_seek(0); while($marca = $marcas->fetch_assoc()): ?>
                                                <option value="<?php echo $marca['id']; ?>"><?php echo htmlspecialchars($marca['nombre']); ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <label class="form-label small fw-bold">Nuevo Modelo <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre del modelo..." required>
                                            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-plus-circle me-1"></i> Agregar</button>
                                        </div>
                                    </form>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle">
                                            <tbody>
                                                <?php while($item = $modelos->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($item['marca_nombre']); ?></strong> - <?php echo htmlspecialchars($item['nombre']); ?>
                                                        <span class="badge rounded-pill float-end <?php echo $item['estado'] == 'Activo' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $item['estado']; ?></span>
                                                    </td>
                                                    <td class="text-end align-middle" style="width: 170px;">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="catalogo_editar.php?id=<?php echo $item['id']; ?>&type=modelo" class="btn btn-outline-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                            
                                                            <a href="#" class="btn btn-outline-secondary" title="Ver Historial (No implementado)"><i class="bi bi-clock-history"></i></a>

                                                            <?php if ($item['estado'] == 'Activo'): ?>
                                                                <a href="?action=deactivate&id=<?php echo $item['id']; ?>&type=modelo#collapseModelos" class="btn btn-outline-secondary" title="Desactivar"><i class="bi bi-x-circle"></i></a>
                                                            <?php else: ?>
                                                                <a href="?action=activate&id=<?php echo $item['id']; ?>&type=modelo#collapseModelos" class="btn btn-outline-success" title="Activar"><i class="bi bi-check-circle"></i></a>
                                                            <?php endif; ?>
                                                             
                                                            <button onclick="confirmDelete(<?php echo $item['id']; ?>, 'modelo', 'collapseModelos')" class="btn btn-outline-danger" title="Eliminar Permanentemente">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 text-info">
                        <i class="bi bi-people-fill me-2"></i>Catálogos de Empleados y Ubicación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="accordionOtros">

                        <div class="accordion-item shadow-sm mb-3">
                            <h2 class="accordion-header" id="headingSucursales">
                                <button class="accordion-button fw-bold <?php echo $active_collapse === 'collapseSucursales' ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSucursales" aria-expanded="<?php echo $active_collapse === 'collapseSucursales' ? 'true' : 'false'; ?>" aria-controls="collapseSucursales">
                                    <i class="bi bi-shop me-2 text-primary"></i> Ambiente (Sucursales)
                                </button>
                            </h2>
                            <div id="collapseSucursales" class="accordion-collapse collapse <?php echo $active_collapse === 'collapseSucursales' ? 'show' : ''; ?>" aria-labelledby="headingSucursales" data-bs-parent="#accordionOtros">
                                <div class="accordion-body">
                                    <form method="POST" class="mb-4 border-bottom pb-3">
                                        <input type="hidden" name="catalogo_sucursal" value="1">
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold">Area <span class="text-danger">*</span></label>
                                            <input type="text" name="nombre_sucursal" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold">Lugar</label>
                                            <textarea name="direccion_sucursal" class="form-control form-control-sm" rows="1"></textarea>
                                        </div>
                                        <button class="btn btn-primary btn-sm mt-2" type="submit"><i class="bi bi-plus-circle me-1"></i> Agregar</button>
                                    </form>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle">
                                            <tbody>
                                                <?php while ($item = $sucursales->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($item['nombre']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($item['direccion']); ?></small>
                                                        <span class="badge rounded-pill float-end <?php echo $item['estado'] == 'Activo' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $item['estado']; ?></span>
                                                    </td>
                                                    <td class="text-end align-middle" style="width: 170px;">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="catalogo_editar.php?id=<?php echo $item['id']; ?>&type=sucursal" class="btn btn-outline-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                            
                                                            <a href="#" class="btn btn-outline-secondary" title="Ver Historial (No implementado)"><i class="bi bi-clock-history"></i></a>

                                                            <?php if ($item['estado'] == 'Activo'): ?>
                                                                <a href="?action=deactivate&id=<?php echo $item['id']; ?>&type=sucursal#collapseSucursales" class="btn btn-outline-secondary" title="Desactivar"><i class="bi bi-x-circle"></i></a>
                                                            <?php else: ?>
                                                                <a href="?action=activate&id=<?php echo $item['id']; ?>&type=sucursal#collapseSucursales" class="btn btn-outline-success" title="Activar"><i class="bi bi-check-circle"></i></a>
                                                            <?php endif; ?>
                                                             
                                                            <button onclick="confirmDelete(<?php echo $item['id']; ?>, 'sucursal', 'collapseSucursales')" class="btn btn-outline-danger" title="Eliminar Permanentemente">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item shadow-sm mb-3">
                            <h2 class="accordion-header" id="headingAreas">
                                <button class="accordion-button fw-bold <?php echo $active_collapse === 'collapseAreas' ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAreas" aria-expanded="<?php echo $active_collapse === 'collapseAreas' ? 'true' : 'false'; ?>" aria-controls="collapseAreas">
                                    <i class="bi bi-diagram-3-fill me-2 text-info"></i> Áreas
                                </button>
                            </h2>
                            <div id="collapseAreas" class="accordion-collapse collapse <?php echo $active_collapse === 'collapseAreas' ? 'show' : ''; ?>" aria-labelledby="headingAreas" data-bs-parent="#accordionOtros">
                                <div class="accordion-body">
                                    <form method="POST" class="mb-4 border-bottom pb-3">
                                        <input type="hidden" name="catalogo" value="area">
                                        <label class="form-label small fw-bold">Nueva Área <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre del área..." required>
                                            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-plus-circle me-1"></i> Agregar</button>
                                        </div>
                                    </form>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle">
                                            <tbody>
                                                <?php $areas->data_seek(0); while ($item = $areas->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($item['nombre']); ?>
                                                        <span class="badge rounded-pill float-end <?php echo $item['estado'] == 'Activo' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $item['estado']; ?></span>
                                                    </td>
                                                    <td class="text-end align-middle" style="width: 170px;">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="catalogo_editar.php?id=<?php echo $item['id']; ?>&type=area" class="btn btn-outline-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                            
                                                            <a href="#" class="btn btn-outline-secondary" title="Ver Historial (No implementado)"><i class="bi bi-clock-history"></i></a>

                                                            <?php if ($item['estado'] == 'Activo'): ?>
                                                                <a href="?action=deactivate&id=<?php echo $item['id']; ?>&type=area#collapseAreas" class="btn btn-outline-secondary" title="Desactivar"><i class="bi bi-x-circle"></i></a>
                                                            <?php else: ?>
                                                                <a href="?action=activate&id=<?php echo $item['id']; ?>&type=area#collapseAreas" class="btn btn-outline-success" title="Activar"><i class="bi bi-check-circle"></i></a>
                                                            <?php endif; ?>
                                                             
                                                            <button onclick="confirmDelete(<?php echo $item['id']; ?>, 'area', 'collapseAreas')" class="btn btn-outline-danger" title="Eliminar Permanentemente">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item shadow-sm mb-3">
                            <h2 class="accordion-header" id="headingCargos">
                                <button class="accordion-button fw-bold <?php echo $active_collapse === 'collapseCargos' ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCargos" aria-expanded="<?php echo $active_collapse === 'collapseCargos' ? 'true' : 'false'; ?>" aria-controls="collapseCargos">
                                    <i class="bi bi-briefcase-fill me-2 text-warning"></i> Cargos (por Área)
                                </button>
                            </h2>
                            <div id="collapseCargos" class="accordion-collapse collapse <?php echo $active_collapse === 'collapseCargos' ? 'show' : ''; ?>" aria-labelledby="headingCargos" data-bs-parent="#accordionOtros">
                                <div class="accordion-body">
                                    <form method="POST" class="mb-4 border-bottom pb-3">
                                        <input type="hidden" name="catalogo" value="cargo">
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold">Área <span class="text-danger">*</span></label>
                                            <select name="id_area" class="form-select form-select-sm" required>
                                                <option value="">Selecciona un área *</option>
                                                <?php $areas->data_seek(0); while($area = $areas->fetch_assoc()): ?>
                                                <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['nombre']); ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <label class="form-label small fw-bold">Nuevo Cargo <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre del cargo..." required>
                                            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-plus-circle me-1"></i> Agregar</button>
                                        </div>
                                    </form>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle">
                                            <tbody>
                                                <?php while ($item = $cargos->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($item['area_nombre']); ?></strong> - <?php echo htmlspecialchars($item['nombre']); ?>
                                                        <span class="badge rounded-pill float-end <?php echo $item['estado'] == 'Activo' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $item['estado']; ?></span>
                                                    </td>
                                                    <td class="text-end align-middle" style="width: 170px;">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="catalogo_editar.php?id=<?php echo $item['id']; ?>&type=cargo" class="btn btn-outline-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                            
                                                            <a href="#" class="btn btn-outline-secondary" title="Ver Historial (No implementado)"><i class="bi bi-clock-history"></i></a>

                                                            <?php if ($item['estado'] == 'Activo'): ?>
                                                                <a href="?action=deactivate&id=<?php echo $item['id']; ?>&type=cargo#collapseCargos" class="btn btn-outline-secondary" title="Desactivar"><i class="bi bi-x-circle"></i></a>
                                                            <?php else: ?>
                                                                <a href="?action=activate&id=<?php echo $item['id']; ?>&type=cargo#collapseCargos" class="btn btn-outline-success" title="Activar"><i class="bi bi-check-circle"></i></a>
                                                            <?php endif; ?>
                                                             
                                                            <button onclick="confirmDelete(<?php echo $item['id']; ?>, 'cargo', 'collapseCargos')" class="btn btn-outline-danger" title="Eliminar Permanentemente">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Pide confirmación y redirige para ejecutar la eliminación física de un registro.
     * Esta acción es irreversible.
     * @param {number} id - ID del registro a eliminar.
     * @param {string} type - Tipo de catálogo (e.g., 'tipo', 'marca', 'area').
     * @param {string} collapseId - ID del acordeón para mantenerlo abierto después de la acción.
     */
    function confirmDelete(id, type, collapseId) {
        if (confirm(`ADVERTENCIA: ¿Estás seguro de que quieres ELIMINAR PERMANENTEMENTE el registro ID ${id} del catálogo ${type}? 
Esta acción NO se puede deshacer y puede causar errores si el registro está en uso (Clave Foránea).`)) {
            // Construye la URL para el borrado físico
            window.location.href = `?action=delete&id=${id}&type=${type}#${collapseId}`;
        }
    }
</script>

<?php 
ob_end_flush();
require_once '../templates/footer.php'; 
?>