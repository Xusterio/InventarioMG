<?php
ob_start();
require_once '../templates/header.php';

// Mapa de catálogos para simplificar la lógica
$catalogo_map = [
    'sucursal' => ['table' => 'sucursales', 'tab' => 'tab-sucursales'], 
    'tipo'     => ['table' => 'tipos_equipo', 'tab' => 'tab-tipos'], 
    'marca'    => ['table' => 'marcas', 'tab' => 'tab-marcas'], 
    'modelo'   => ['table' => 'modelos', 'tab' => 'tab-modelos'], 
    'area'     => ['table' => 'areas', 'tab' => 'tab-areas'], 
    'cargo'    => ['table' => 'cargos', 'tab' => 'tab-cargos']
];

// Lógica de Acciones (Eliminar/Estado)
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['type'])) {
    $id = (int)$_GET['id'];
    $type = $_GET['type'];
    $action = $_GET['action'];
    
    if (array_key_exists($type, $catalogo_map)) {
        $table_name = $catalogo_map[$type]['table'];
        $active_tab = $catalogo_map[$type]['tab'];
        $status_param = '';

        if ($action == 'delete') {
            $stmt = $conexion->prepare("DELETE FROM {$table_name} WHERE id = ?");
            $stmt->bind_param("i", $id);
            $status_param = $stmt->execute() ? 'success_delete' : 'error_delete_fk';
        } else {
            $estado = ($action == 'deactivate') ? 'Inactivo' : 'Activo';
            $stmt = $conexion->prepare("UPDATE {$table_name} SET estado = ? WHERE id = ?");
            $stmt->bind_param("si", $estado, $id);
            $status_param = $stmt->execute() ? 'success_state' : 'error_db';
        }
        
        header("Location: gestion_catalogos.php?status={$status_param}&active_tab={$active_tab}");
        exit();
    }
}

// Lógica de Inserción (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catalogo_key = isset($_POST['catalogo_sucursal']) ? 'sucursal' : ($_POST['catalogo'] ?? '');
    $active_tab = $catalogo_map[$catalogo_key]['tab'] ?? 'tab-sucursales';
    $stmt = null;

    if (isset($_POST['catalogo'])) {
        $nombre = $_POST['nombre'];
        switch ($_POST['catalogo']) {
            case 'tipo':   $stmt = $conexion->prepare("INSERT INTO tipos_equipo (nombre) VALUES (?)"); $stmt->bind_param("s", $nombre); break;
            case 'marca':  $stmt = $conexion->prepare("INSERT INTO marcas (nombre) VALUES (?)"); $stmt->bind_param("s", $nombre); break;
            case 'area':   $stmt = $conexion->prepare("INSERT INTO areas (nombre) VALUES (?)"); $stmt->bind_param("s", $nombre); break;
            case 'modelo': $stmt = $conexion->prepare("INSERT INTO modelos (id_marca, nombre) VALUES (?, ?)"); $stmt->bind_param("is", $_POST['id_marca'], $nombre); break;
            case 'cargo':  $stmt = $conexion->prepare("INSERT INTO cargos (id_area, nombre) VALUES (?, ?)"); $stmt->bind_param("is", $_POST['id_area'], $nombre); break;
        }
    } elseif (isset($_POST['catalogo_sucursal'])) {
        $stmt = $conexion->prepare("INSERT INTO sucursales (nombre, direccion, estado) VALUES (?, ?, 'Activo')");
        $stmt->bind_param("ss", $_POST['nombre_sucursal'], $_POST['direccion_sucursal']);
    }

    if ($stmt && $stmt->execute()) {
        header("Location: gestion_catalogos.php?status=success_add&active_tab={$active_tab}");
    } else {
        $err = ($stmt && strpos($stmt->error, 'Duplicate') !== false) ? 'error_duplicate' : 'error_db';
        header("Location: gestion_catalogos.php?status={$err}&active_tab={$active_tab}");
    }
    exit();
}

// Carga de datos
$sucursales = $conexion->query("SELECT * FROM sucursales ORDER BY nombre");
$tipos = $conexion->query("SELECT * FROM tipos_equipo ORDER BY nombre");
$marcas = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
$modelos = $conexion->query("SELECT m.*, ma.nombre as marca_nombre FROM modelos m JOIN marcas ma ON m.id_marca = ma.id ORDER BY ma.nombre, m.nombre");
$areas = $conexion->query("SELECT * FROM areas ORDER BY nombre");
$cargos = $conexion->query("SELECT c.*, a.nombre AS area_nombre FROM cargos c JOIN areas a ON c.id_area = a.id ORDER BY a.nombre, c.nombre");

$active_tab = $_GET['active_tab'] ?? 'tab-sucursales';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 fw-bold text-dark mb-1"><i class="bi bi-folder2-open text-primary me-2"></i>Centro de Configuración</h1>
            <p class="text-muted small">Administra las sucursales, áreas y especificaciones de equipos.</p>
        </div>
    </div>

    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-dismissible fade show shadow-sm border-0 mb-4 <?php echo (strpos($_GET['status'], 'success') !== false) ? 'alert-success' : 'alert-danger'; ?>">
            <i class="bi <?php echo (strpos($_GET['status'], 'success') !== false) ? 'bi-check-circle-fill' : 'bi-exclamation-octagon-fill'; ?> me-2"></i>
            <?php 
                $msgs = [
                    'success_add' => 'Registro agregado con éxito.',
                    'success_state' => 'Estado actualizado.',
                    'success_delete' => 'Registro eliminado permanentemente.',
                    'error_delete_fk' => 'No se puede eliminar: El registro está en uso en otra tabla.',
                    'error_duplicate' => 'El nombre ya existe en este catálogo.',
                    'error_db' => 'Error crítico en la base de datos.'
                ];
                echo $msgs[$_GET['status']] ?? 'Operación finalizada.';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="row g-0">
            <div class="col-md-3 bg-light border-end">
                <div class="nav flex-column nav-pills p-3" id="v-pills-tab" role="tablist">
                    <div class="text-uppercase small fw-bold text-muted mb-3 px-3">Estructura Organizativa</div>
                    <button class="nav-link text-start mb-2 <?php echo $active_tab == 'tab-sucursales' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-sucursales" type="button">
                        <i class="bi bi-building me-2"></i> Sucursales
                    </button>
                    <button class="nav-link text-start mb-2 <?php echo $active_tab == 'tab-areas' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-areas" type="button">
                        <i class="bi bi-diagram-3 me-2"></i> Áreas
                    </button>
                    <button class="nav-link text-start mb-4 <?php echo $active_tab == 'tab-cargos' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-cargos" type="button">
                        <i class="bi bi-person-badge me-2"></i> Cargos
                    </button>

                    <div class="text-uppercase small fw-bold text-muted mb-3 px-3">Inventario</div>
                    <button class="nav-link text-start mb-2 <?php echo $active_tab == 'tab-tipos' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-tipos" type="button">
                        <i class="bi bi-laptop me-2"></i> Tipos de Equipo
                    </button>
                    <button class="nav-link text-start mb-2 <?php echo $active_tab == 'tab-marcas' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-marcas" type="button">
                        <i class="bi bi-bookmark-star me-2"></i> Marcas
                    </button>
                    <button class="nav-link text-start mb-2 <?php echo $active_tab == 'tab-modelos' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-modelos" type="button">
                        <i class="bi bi-cpu me-2"></i> Modelos
                    </button>
                </div>
            </div>

            <div class="col-md-9 bg-white">
                <div class="tab-content p-4" id="v-pills-tabContent">
                    
                    <div class="tab-pane fade <?php echo $active_tab == 'tab-sucursales' ? 'show active' : ''; ?>" id="tab-sucursales">
                        <?php renderFormHeader("Sucursal (Ambiente)", "sucursal_form", "POST", true); ?>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6"><input type="text" name="nombre_sucursal" class="form-control form-control-sm" placeholder="Nombre (Ej: Oficina Central)" required></div>
                                <div class="col-md-6"><input type="text" name="direccion_sucursal" class="form-control form-control-sm" placeholder="Dirección / Referencia"></div>
                                <input type="hidden" name="catalogo_sucursal" value="1">
                            </div>
                        <?php renderFormFooter(); ?>
                        <?php renderTable($sucursales, 'sucursal', ['Nombre', 'Dirección'], ['nombre', 'direccion']); ?>
                    </div>

                    <div class="tab-pane fade <?php echo $active_tab == 'tab-areas' ? 'show active' : ''; ?>" id="tab-areas">
                        <?php renderSimpleForm("area", "Nueva Área", "Ej: Contabilidad, TI..."); ?>
                        <?php renderTable($areas, 'area', ['Nombre del Área'], ['nombre']); ?>
                    </div>

                    <div class="tab-pane fade <?php echo $active_tab == 'tab-cargos' ? 'show active' : ''; ?>" id="tab-cargos">
                        <?php renderFormHeader("Cargo", "cargo_form"); ?>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <select name="id_area" class="form-select form-select-sm" required>
                                        <option value="">Seleccionar Área...</option>
                                        <?php $areas->data_seek(0); while($a = $areas->fetch_assoc()) echo "<option value='{$a['id']}'>{$a['nombre']}</option>"; ?>
                                    </select>
                                </div>
                                <div class="col-md-6"><input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre del Cargo" required></div>
                                <input type="hidden" name="catalogo" value="cargo">
                            </div>
                        <?php renderFormFooter(); ?>
                        <?php renderTable($cargos, 'cargo', ['Área', 'Cargo'], ['area_nombre', 'nombre']); ?>
                    </div>

                    <div class="tab-pane fade <?php echo $active_tab == 'tab-tipos' ? 'show active' : ''; ?>" id="tab-tipos">
                        <?php renderSimpleForm("tipo", "Tipo de Equipo", "Ej: Laptop, Impresora..."); ?>
                        <?php renderTable($tipos, 'tipo', ['Categoría'], ['nombre']); ?>
                    </div>

                    <div class="tab-pane fade <?php echo $active_tab == 'tab-marcas' ? 'show active' : ''; ?>" id="tab-marcas">
                        <?php renderSimpleForm("marca", "Nueva Marca", "Ej: Dell, Lenovo..."); ?>
                        <?php renderTable($marcas, 'marca', ['Nombre de Marca'], ['nombre']); ?>
                    </div>

                    <div class="tab-pane fade <?php echo $active_tab == 'tab-modelos' ? 'show active' : ''; ?>" id="tab-modelos">
                        <?php renderFormHeader("Modelo", "modelo_form"); ?>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <select name="id_marca" class="form-select form-select-sm" required>
                                        <option value="">Seleccionar Marca...</option>
                                        <?php $marcas->data_seek(0); while($m = $marcas->fetch_assoc()) echo "<option value='{$m['id']}'>{$m['nombre']}</option>"; ?>
                                    </select>
                                </div>
                                <div class="col-md-6"><input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre del Modelo" required></div>
                                <input type="hidden" name="catalogo" value="modelo">
                            </div>
                        <?php renderFormFooter(); ?>
                        <?php renderTable($modelos, 'modelo', ['Marca', 'Modelo'], ['marca_nombre', 'nombre']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.5rem;">
            <div class="modal-body text-center p-5">
                <div class="mb-4">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 5rem; color: #F4AC05;"></i>
                </div>
                <h2 class="fw-bold mb-3" style="color: #0F1A2D;">¿Confirmar eliminación?</h2>
                <p class="text-muted mb-4 lead">Estás a punto de borrar este registro permanentemente. Esta acción no se puede deshacer.</p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-light btn-lg px-4 fw-bold text-secondary" data-bs-dismiss="modal">CANCELAR</button>
                    <a id="btnConfirmarEliminar" href="#" class="btn btn-danger btn-lg px-4 fw-bold">ELIMINAR AHORA</a>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center bg-light rounded-bottom-4 py-3">
                <span class="small text-muted text-uppercase fw-bold letter-spacing-1">Sistema de Inventario TI</span>
            </div>
        </div>
    </div>
</div>

<?php
// FUNCIONES AUXILIARES
function renderFormHeader($titulo, $id, $method = "POST") {
    echo "
    <div class='mb-4 bg-light p-3 rounded border border-dashed'>
        <h6 class='fw-bold mb-3 text-secondary'><i class='bi bi-plus-circle me-2'></i>Agregar $titulo</h6>
        <form method='$method' id='$id'>";
}

function renderFormFooter() {
    echo "<button type='submit' class='btn btn-primary btn-sm px-4'>Guardar Registro</button></form></div>";
}

function renderSimpleForm($cat, $label, $placeholder) {
    renderFormHeader($label, $cat."_form");
    echo "
    <div class='mb-3'>
        <input type='text' name='nombre' class='form-control form-control-sm' placeholder='$placeholder' required>
        <input type='hidden' name='catalogo' value='$cat'>
    </div>";
    renderFormFooter();
}

function renderTable($result, $type, $headers, $fields) {
    echo "<div class='table-responsive' style='max-height:400px;'>
    <table class='table table-hover table-sm align-middle small border'>
        <thead class='table-light'><tr>";
    foreach($headers as $h) echo "<th>$h</th>";
    echo "<th>Estado</th><th class='text-end'>Acciones</th></tr></thead><tbody>";
    while($row = $result->fetch_assoc()) {
        $badge = ($row['estado'] == 'Activo') ? 'bg-success' : 'bg-danger';
        echo "<tr>";
        foreach($fields as $f) echo "<td>".htmlspecialchars($row[$f])."</td>";
        echo "<td><span class='badge $badge rounded-pill'>{$row['estado']}</span></td>
        <td class='text-end'>
            <div class='btn-group btn-group-sm'>
                <a href='catalogo_editar.php?id={$row['id']}&type=$type' class='btn btn-outline-warning' title='Editar'><i class='bi bi-pencil'></i></a>";
        if($row['estado'] == 'Activo') {
            echo "<a href='?action=deactivate&id={$row['id']}&type=$type' class='btn btn-outline-secondary' title='Desactivar'><i class='bi bi-toggle-on'></i></a>";
        } else {
            echo "<a href='?action=activate&id={$row['id']}&type=$type' class='btn btn-outline-success' title='Activar'><i class='bi bi-toggle-off'></i></a>";
        }
        // CAMBIO: Ahora llama a abrirModal en lugar del confirm nativo
        echo "<button type='button' onclick='abrirModal({$row['id']}, \"$type\")' class='btn btn-outline-danger' title='Eliminar'><i class='bi bi-trash'></i></button>
            </div>
        </td></tr>";
    }
    echo "</tbody></table></div>";
}
?>

<script>
// Función para abrir el modal personalizado
function abrirModal(id, type) {
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    const linkEliminar = document.getElementById('btnConfirmarEliminar');
    
    // Configuramos la URL de eliminación final
    linkEliminar.href = `?action=delete&id=${id}&type=${type}`;
    
    // Mostramos el modal
    modal.show();
}
</script>

<style>
.nav-pills .nav-link { color: #555; font-weight: 500; border-radius: 0.5rem; transition: 0.3s; }
.nav-pills .nav-link:hover { background-color: #f8f9fa; }
.nav-pills .nav-link.active { background-color: #0F1A2D; color: white !important; box-shadow: 0 4px 10px rgba(15,26,45,0.3); }
.border-dashed { border-style: dashed !important; border-width: 2px !important; }
.table th { font-size: 0.7rem; text-transform: uppercase; color: #6c757d; letter-spacing: 0.8px; padding: 10px; }
.letter-spacing-1 { letter-spacing: 1.5px; }
</style>

<?php 
require_once '../templates/footer.php';
ob_end_flush();
?>