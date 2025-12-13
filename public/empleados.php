<?php
require_once '../templates/header.php';

// --- LÓGICA PARA CONSTRUIR LA CONSULTA SQL INICIAL (SIN FILTROS GET) ---
// Se busca obtener toda la data relevante para la tabla, la filtración se hará en el cliente.
$id_sucursal_usuario = $_SESSION['user_sucursal_id'];
$es_admin_general = ($id_sucursal_usuario === null);

$sql_select = "SELECT 
    emp.id, 
    emp.dni, 
    emp.nombres, 
    emp.apellidos, 
    emp.estado,
    c.nombre AS cargo_nombre,
    a.nombre AS area_nombre,
    s.nombre AS sucursal_nombre,
    s.id AS sucursal_id,
    a.id AS area_id,
    c.id AS cargo_id
FROM empleados emp
LEFT JOIN sucursales s ON emp.id_sucursal = s.id
LEFT JOIN areas a ON emp.id_area = a.id
LEFT JOIN cargos c ON emp.id_cargo = c.id";

$where_clauses = [];

if ($id_sucursal_usuario !== null) {
    // Si no es admin general, solo ve empleados de su sucursal
    $where_clauses[] = "emp.id_sucursal = " . (int)$id_sucursal_usuario;
}

if (!empty($where_clauses)) {
    $sql_select .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql_select .= " ORDER BY emp.apellidos, emp.nombres";

// Ejecutar la consulta
$resultado = $conexion->query($sql_select);


// Cargar catálogos para los dropdowns de filtros
$sucursales = $conexion->query("SELECT id, nombre FROM sucursales WHERE estado = 'Activo' ORDER BY nombre");
$areas = $conexion->query("SELECT id, nombre FROM areas WHERE estado = 'Activo' ORDER BY nombre");
$cargos = $conexion->query("SELECT id, nombre FROM cargos WHERE estado = 'Activo' ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados</title>
    
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
        /* Encabezados de tarjeta personalizados */
        .card-header.bg-light { 
            background-color: #f8f9fa !important; 
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
        }

        /* Estilos de Botones */
        .btn { 
            border-radius: 0.5rem; 
            font-weight: 600; 
            transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
        }
        /* Color primario para botones y badges 'Asignado' */
        .btn-primary, .badge.bg-primary { 
            background-color: var(--custom-primary) !important; 
            border-color: var(--custom-primary) !important;
        }
        /* Color de éxito para badges 'Disponible' */
        .badge.bg-success { background-color: var(--custom-success) !important; }
        /* Color de advertencia para badges 'En Reparacion' */
        .badge.bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
        /* Color de peligro para botones de eliminar y badges 'De Baja' */
        .btn-danger, .badge.bg-danger { background-color: var(--custom-danger) !important; border-color: var(--custom-danger) !important; }
        /* Color info para botones de ver detalle */
        .btn-outline-info { color: var(--custom-info); border-color: var(--custom-info); }
        .btn-outline-info:hover { background-color: var(--custom-info); color: white; }
        
        /* Estilos específicos de la tabla */
        #tablaEmpleadosPrincipal thead th {
            font-weight: 600;
            color: var(--custom-secondary);
        }
        #tablaEmpleadosPrincipal tbody tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-people-fill me-2"></i> Gestión de Empleados
        </h1>
        <div class="btn-group">
            <a href="empleado_agregar.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Agregar Empleado
            </a>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                <span class="visually-hidden">Exportar</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" id="exportExcelClientSide"><i class="bi bi-file-earmark-excel me-2"></i>Exportar a Excel</a></li>
                <li><a class="dropdown-item" href="#" id="exportPDFClientSide"><i class="bi bi-file-earmark-pdf me-2"></i>Exportar a PDF</a></li>
                <li><a class="dropdown-item" href="#" id="exportCSVClientSide"><i class="bi bi-file-earmark-text me-2"></i>Exportar a CSV</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" id="exportPrintClientSide"><i class="bi bi-printer me-2"></i>Imprimir</a></li>
            </ul>
        </div>
    </div>

    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'success_add'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>Empleado agregado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['status'] === 'success_edit'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>Empleado actualizado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['status'] === 'success_delete' || $_GET['status'] === 'success_logic_delete'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($_GET['msg'] ?? 'Empleado eliminado/marcado correctamente.'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['status'] === 'error_delete'): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-x-octagon-fill me-2"></i>Error al eliminar empleado. <?php echo htmlspecialchars($_GET['msg'] ?? 'Error desconocido.'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="searchInput" 
                               placeholder="Buscar por DNI, Apellidos o Nombres..." 
                               onkeyup="aplicarFiltrosCombinados()">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="filterEstado" onchange="aplicarFiltrosCombinados()">
                        <option value="">Todos los estados</option>
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="filterSucursal" onchange="aplicarFiltrosCombinados()">
                        <option value="">Todas las sucursales</option>
                        <?php
                        $sucursales->data_seek(0);
                        while ($s = $sucursales->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($s['nombre']); ?>"><?php echo htmlspecialchars($s['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <select class="form-select" id="filterArea" onchange="aplicarFiltrosCombinados()">
                        <option value="">Todas las áreas</option>
                        <?php
                        $areas->data_seek(0);
                        while ($a = $areas->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($a['nombre']); ?>"><?php echo htmlspecialchars($a['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="filterCargo" onchange="aplicarFiltrosCombinados()">
                        <option value="">Todos los cargos</option>
                        <?php
                        $cargos->data_seek(0);
                        while ($c = $cargos->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($c['nombre']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="limpiarFiltros()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-people-fill me-2"></i>
                Listado de Empleados
            </h5>
            <div class="text-muted small">
                <span id="contadorEmpleados"><?php echo $resultado->num_rows; ?></span> empleados encontrados
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaEmpleadosPrincipal" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <?php if ($id_sucursal_usuario === null): ?>
                                <th>Sucursal</th>
                            <?php endif; ?>
                            <th>DNI</th>
                            <th>Apellidos y Nombres</th>
                            <th>Cargo</th>
                            <th>Área</th>
                            <th>Estado</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaEmpleados">
                        <?php if ($resultado->num_rows > 0) : ?>
                            <?php while ($empleado = $resultado->fetch_assoc()) : ?>
                                <tr class="fila-empleado">
                                    <?php if ($id_sucursal_usuario === null) : ?>
                                        <td><?php echo htmlspecialchars($empleado['sucursal_nombre']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($empleado['dni']); ?></td>
                                    <td class="fw-bold">
                                        <?php echo htmlspecialchars($empleado['apellidos'] . ', ' . $empleado['nombres']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($empleado['cargo_nombre'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['area_nombre'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = $empleado['estado'] === 'Activo' ? 'bg-success' : 'bg-danger';
                                        ?>
                                        <span class="badge rounded-pill <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($empleado['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="empleado_editar.php?id=<?php echo $empleado['id']; ?>" 
                                               class="btn btn-outline-warning" title="Editar Empleado">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="empleado_detalles.php?id=<?php echo $empleado['id']; ?>" 
                                               class="btn btn-outline-info" title="Ver Detalles">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="#" 
                                               onclick="confirmarEliminacionEmpleado(
                                                                <?php echo $empleado['id']; ?>, 
                                                                '<?php echo htmlspecialchars($empleado['apellidos'] . ', ' . $empleado['nombres']); ?>',
                                                                '<?php echo $empleado['estado']; ?>'
                                                            )"
                                               class="btn btn-outline-danger" 
                                               title="Eliminar Empleado">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr id="sinResultados">
                                <td colspan="<?php echo ($id_sucursal_usuario === null ? '7' : '6'); ?>" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-people display-4 d-block mb-2"></i>
                                        No se encontraron empleados registrados
                                        <div class="mt-3">
                                            <a href="empleado_agregar.php" class="btn btn-primary">
                                                <i class="bi bi-plus-circle me-2"></i>Registrar Primer Empleado
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEliminacionEmpleado" tabindex="-1" aria-labelledby="modalEliminacionEmpleadoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalEliminacionEmpleadoLabel"><i class="bi bi-trash me-2"></i> Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea **eliminar** al empleado: <strong id="empleadoNombreAEliminar"></strong>?</p>
                <div id="advertenciaEliminacion"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a id="btnConfirmarEliminacionEmpleado" class="btn btn-danger" href="#">Eliminar Definitivamente</a>
            </div>
        </div>
    </div>
</div>

<script>
// --- FUNCIONES DE MODAL Y UTILIDAD ---

function confirmarEliminacionEmpleado(id, nombre, estado) {
    const modalTitle = document.getElementById('modalEliminacionEmpleadoLabel');
    const advertenciaDiv = document.getElementById('advertenciaEliminacion');
    const btnConfirmar = document.getElementById('btnConfirmarEliminacionEmpleado');
    
    document.getElementById('empleadoNombreAEliminar').textContent = nombre;
    btnConfirmar.style.display = 'inline-block';
    
    // Si el empleado está activo, se advierte que será marcado como Inactivo
    if (estado === 'Activo') {
        modalTitle.textContent = "Confirmar Inactivación";
        advertenciaDiv.innerHTML = `
            <p class="text-warning fw-bold">
                ¡Advertencia! Este empleado está **Activo**. Si tiene equipos asignados, será marcado como **Inactivo** y sus equipos deberán ser devueltos o reasignados.
            </p>
        `;
    } else {
        modalTitle.textContent = "Confirmar Eliminación";
        advertenciaDiv.innerHTML = `
            <p class="text-danger fw-bold">
                ¡Esta acción es irreversible y debe usarse con precaución!
            </p>
        `;
    }
    
    const deleteUrl = '../includes/procesar_eliminacion_empleado.php?id=' + id;
    btnConfirmar.href = deleteUrl;

    const modalElement = document.getElementById('modalEliminacionEmpleado');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

// --- LÓGICA DE DATATABLES Y FILTRADO (COPIADA DE EQUIPOS.PHP) ---

$(document).ready(function() {
    
    const esAdminGeneral = <?php echo $es_admin_general ? 'true' : 'false'; ?>;
    
    // ÍNDICES DE COLUMNAS VISIBLES (Total de columnas visibles: Admin: 7, No Admin: 6)
    // Estructura visible: [Sucursal (0)], DNI (1), Apellidos y Nombres (2), Cargo (3), Área (4), Estado (5), Acciones (6)
    let columnaDNIIndex = esAdminGeneral ? 1 : 0;
    let columnaNombreIndex = esAdminGeneral ? 2 : 1;
    let columnaCargoIndex = esAdminGeneral ? 3 : 2; 
    let columnaAreaIndex = esAdminGeneral ? 4 : 3;
    let columnaEstadoIndex = esAdminGeneral ? 5 : 4;
    let columnaAccionesIndex = esAdminGeneral ? 6 : 5;
    let columnaSucursalIndex = 0; 

    // 1. Inicializar DataTables con Buttons
    const tablaEmpleados = $('#tablaEmpleadosPrincipal').DataTable({
        "dom": 'r<"table-responsive"t><"d-flex justify-content-between"ip>',
        "paging": true,
        "pageLength": 10,
        "searching": true, 
        "info": true,
        "order": [[ columnaNombreIndex, 'asc' ]], 
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        "columnDefs": [
            { "orderable": false, "targets": columnaAccionesIndex }
        ],
        "buttons": [ 
            { 
                extend: 'excelHtml5', 
                text: 'Excel', 
                title: 'Reporte Empleados - <?php echo date('Y-m-d'); ?>',
                exportOptions: {
                    columns: ':visible',
                    columns: ':not(:last-child)' 
                }
            },
            { extend: 'csvHtml5', text: 'CSV', exportOptions: { columns: ':not(:last-child)' } },
            { 
                extend: 'pdfHtml5', 
                text: 'PDF', 
                title: 'Reporte Empleados - <?php echo date('Y-m-d'); ?>',
                exportOptions: { columns: ':not(:last-child)' }
            },
            { 
                extend: 'print', 
                text: 'Imprimir', 
                exportOptions: { columns: ':not(:last-child)' }
            }
        ],
        "drawCallback": function( settings ) {
            const rowCount = this.api().rows( { filter: 'applied' } ).nodes().length;
            
            const info = tablaEmpleados.page.info();
            document.getElementById('contadorEmpleados').textContent = info.recordsDisplay;

            // Lógica para mostrar u ocultar el mensaje "No se encontraron datos"
            if (rowCount === 0) {
                if ($('#tablaEmpleadosPrincipal tbody').find('#sinResultados').length === 0) {
                     const colSpanCount = tablaEmpleados.columns(':visible').count(); 
                     $('#tablaEmpleadosPrincipal tbody').append('<tr id="sinResultados"><td colspan="' + colSpanCount + '" class="text-center py-4"><div class="text-muted"><i class="bi bi-people display-4 d-block mb-2"></i>No se encontraron empleados registrados</div></td></tr>');
                }
            } else {
                $('#sinResultados').remove();
            }
        }
    });

    // Función principal de filtrado combinada con el input de búsqueda global
    window.aplicarFiltrosCombinados = function() {
        // 1. Aplicar filtro de búsqueda global
        const searchTerm = document.getElementById('searchInput').value;
        tablaEmpleados.search(searchTerm).draw();

        // 2. Aplicar filtros de columnas con selects
        
        // Estado
        const estado = document.getElementById('filterEstado').value;
        tablaEmpleados.column(columnaEstadoIndex).search(estado === '' ? '' : '^'+estado+'$', true, false).draw();
        
        // Sucursal (Solo si es admin general)
        if (esAdminGeneral) {
            const sucursalNombre = document.getElementById('filterSucursal').value;
            tablaEmpleados.column(columnaSucursalIndex).search(sucursalNombre === '' ? '' : '^'+sucursalNombre+'$', true, false).draw();
        }
        
        // Área
        const areaNombre = document.getElementById('filterArea').value;
        tablaEmpleados.column(columnaAreaIndex).search(areaNombre === '' ? '' : '^'+areaNombre+'$', true, false).draw();
        
        // Cargo
        const cargoNombre = document.getElementById('filterCargo').value;
        tablaEmpleados.column(columnaCargoIndex).search(cargoNombre === '' ? '' : '^'+cargoNombre+'$', true, false).draw();
    };


    // 4. Implementar la función de Limpiar Filtros
    window.limpiarFiltros = function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterEstado').value = '';
        document.getElementById('filterSucursal').value = '';
        document.getElementById('filterArea').value = '';
        document.getElementById('filterCargo').value = '';
        
        // Limpiar búsqueda global y de columnas
        tablaEmpleados.search('').columns().search('').draw();
    }

    // 5. Conectar los botones de exportación a DataTables Buttons
    
    // Conectar Excel
    $('#exportExcelClientSide').on('click', function(e) { 
        e.preventDefault();
        tablaEmpleados.button('.buttons-excel').trigger(); 
    });
    
    // Conectar CSV
    window.exportarCSV = function() {
        tablaEmpleados.button('.buttons-csv').trigger();
    }
    $('#exportCSVClientSide').on('click', function(e) {
        e.preventDefault();
        window.exportarCSV();
    });

    // Conectar PDF
    window.exportarPDF = function() {
        tablaEmpleados.button('.buttons-pdf').trigger();
    }
    $('#exportPDFClientSide').on('click', function(e) {
        e.preventDefault();
        window.exportarPDF();
    });

    // Conectar Imprimir
    window.imprimirTabla = function() {
        tablaEmpleados.button('.buttons-print').trigger();
    }
    $('#exportPrintClientSide').on('click', function(e) {
        e.preventDefault();
        window.imprimirTabla();
    });

    // Disparar filtros al cargar la página para inicializar el estado
    // Se dispara el DataTables draw para aplicar la busqueda inicial (si la hay) y actualizar el contador
    aplicarFiltrosCombinados();
});
</script>

<?php require_once '../templates/footer.php'; ?>