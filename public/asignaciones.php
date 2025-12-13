<?php
require_once '../templates/header.php';

// Verificar permisos de sucursal
$id_sucursal_usuario = $_SESSION['user_sucursal_id'];
$es_admin_general = ($id_sucursal_usuario === null);

// --- LÓGICA PARA CONSTRUIR LA CONSULTA SQL INICIAL (SIN FILTROS GET) ---
$sql_select = "SELECT 
    a.id AS id_asignacion, 
    e.id AS id_equipo,       /* CORREGIDO: ID de equipo explícito */
    emp.id AS id_empleado,   /* CORREGIDO: ID de empleado explícito */
    a.fecha_entrega, 
    a.fecha_devolucion, 
    a.estado_asignacion, 
    a.acta_firmada_path, 
    a.acta_devolucion_path,
    e.codigo_inventario, 
    ma.nombre as marca_nombre, 
    mo.nombre as modelo_nombre,
    emp.nombres, 
    emp.apellidos,
    s.nombre as sucursal_nombre
FROM asignaciones a
JOIN equipos e ON a.id_equipo = e.id
JOIN empleados emp ON a.id_empleado = emp.id
JOIN marcas ma ON e.id_marca = ma.id
JOIN modelos mo ON e.id_modelo = mo.id
JOIN sucursales s ON e.id_sucursal = s.id";

$where_clauses = [];

if ($id_sucursal_usuario !== null) {
    // Filtrar solo por la sucursal del usuario
    $where_clauses[] = "e.id_sucursal = " . (int)$id_sucursal_usuario;
}

if (!empty($where_clauses)) {
    $sql_select .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql_select .= " ORDER BY a.estado_asignacion ASC, a.fecha_entrega DESC";

// Ejecutar la consulta
$resultado = $conexion->query($sql_select); 

// Cargar catálogos para los dropdowns de filtros

// CORRECCIÓN: Se aliasa equipos como 'e' y se selecciona explícitamente e.id para evitar ambigüedad con marcas.id
$equipos_query = "SELECT e.id, e.codigo_inventario, ma.nombre as marca_nombre FROM equipos e JOIN marcas ma ON e.id_marca = ma.id WHERE e.estado = 'Asignado' OR e.estado = 'Disponible' ORDER BY e.codigo_inventario";
$equipos = $conexion->query($equipos_query);

// Otras consultas de catálogos
$sucursales = $conexion->query("SELECT id, nombre FROM sucursales WHERE estado = 'Activo' ORDER BY nombre");
$empleados = $conexion->query("SELECT id, nombres, apellidos FROM empleados WHERE estado = 'Activo' ORDER BY apellidos");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asignaciones</title>
    
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
        .card:hover {
            box-shadow: 0 0.3rem 1rem rgba(0,0,0,0.1);
        }
        .card-header.bg-light { 
            background-color: #f8f9fa !important; 
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
        }
        .btn { 
            border-radius: 0.5rem; 
            font-weight: 600; 
        }
        .btn-primary, .badge.bg-primary { 
            background-color: var(--custom-primary) !important; 
            border-color: var(--custom-primary) !important;
        }
        .badge.bg-success { background-color: var(--custom-success) !important; }
        .badge.bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
        .badge.bg-danger { background-color: var(--custom-danger) !important; }
        
        #tablaAsignacionesPrincipal thead th {
            font-weight: 600;
            color: var(--custom-secondary);
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-arrow-right-circle-fill me-2"></i> Gestión de Asignaciones
        </h1>
        <div class="btn-group">
            <a href="asignacion_agregar.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Nueva Asignación
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
            <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i>Asignación creada correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php elseif ($_GET['status'] === 'success_devolved'): ?>
            <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i>Equipo devuelto correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php elseif ($_GET['status'] === 'success_acta'): ?>
            <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i>Acta subida correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php elseif ($_GET['status'] === 'error'): ?>
            <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-x-octagon-fill me-2"></i>Error en la operación. Consulte los detalles.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="searchInput" 
                               placeholder="Buscar Empleado o Código de Equipo..." 
                               onkeyup="aplicarFiltrosCombinados()">
                    </div>
                </div>
                
                <?php if ($es_admin_general): ?>
                <div class="col-md-4">
                    <select class="form-select" id="filterSucursal" onchange="aplicarFiltrosCombinados()">
                        <option value="">Todas las sucursales</option>
                        <?php $sucursales->data_seek(0); while($s = $sucursales->fetch_assoc()) { echo "<option value='".htmlspecialchars($s['nombre'])."'>".htmlspecialchars($s['nombre'])."</option>"; } ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="<?php echo $es_admin_general ? 'col-md-4' : 'col-md-3'; ?>">
                    <select class="form-select" id="filterEmpleado" onchange="aplicarFiltrosCombinados()">
                        <option value="">Todos los empleados</option>
                        <?php $empleados->data_seek(0); while($e = $empleados->fetch_assoc()) { echo "<option value='".htmlspecialchars($e['apellidos'].', '.$e['nombres'])."'>".htmlspecialchars($e['apellidos'].', '.$e['nombres'])."</option>"; } ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <select class="form-select" id="filterEstado" onchange="aplicarFiltrosCombinados()">
                        <option value="">Todos los estados</option>
                        <option value="Activa">Activa</option>
                        <option value="Finalizada">Finalizada</option>
                    </select>
                </div>
            
                <div class="col-md-3">
                    <select class="form-select" id="filterEquipo" onchange="aplicarFiltrosCombinados()">
                        <option value="">Todos los equipos</option>
                        <?php $equipos->data_seek(0); while($eq = $equipos->fetch_assoc()) { echo "<option value='".htmlspecialchars($eq['codigo_inventario'])."'>".htmlspecialchars($eq['codigo_inventario'])."</option>"; } ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Fecha Desde" onkeyup="aplicarFiltrosCombinados()" id="filterFechaDesde">
                </div>
                
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Fecha Hasta" onkeyup="aplicarFiltrosCombinados()" id="filterFechaHasta">
                </div>
                
                <div class="col-md-3">
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
                <i class="bi bi-table me-2"></i>
                Historial de Asignaciones
            </h5>
            <div class="text-muted small">
                <span id="contadorAsignaciones"><?php echo $resultado->num_rows ?? 0; ?></span> asignaciones
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaAsignacionesPrincipal" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <?php if ($es_admin_general): ?>
                                <th>Sucursal</th>
                            <?php endif; ?>
                            <th>Empleado</th>
                            <th>Equipo (Cód. Inv.)</th>
                            <th>Fecha Entrega</th>
                            <th>Fecha Devolución</th>
                            <th>Estado</th>
                            <th>Acta Entrega</th>
                            <th>Acta Devolución</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado && $resultado->num_rows > 0): ?>
                            <?php while ($asignacion = $resultado->fetch_assoc()): ?>
                                <tr>
                                    <?php if ($es_admin_general): ?>
                                        <td><?php echo htmlspecialchars($asignacion['sucursal_nombre']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($asignacion['apellidos'] . ', ' . $asignacion['nombres']); ?></td>
                                    <td><?php echo htmlspecialchars($asignacion['codigo_inventario'] . ' (' . $asignacion['marca_nombre'] . ')'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($asignacion['fecha_entrega'])); ?></td>
                                    <td><?php echo $asignacion['fecha_devolucion'] ? date('d/m/Y', strtotime($asignacion['fecha_devolucion'])) : '<span class="text-muted">---</span>';?></td>
                                    <td><span class="badge rounded-pill <?php echo $asignacion['estado_asignacion'] === 'Activa' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo htmlspecialchars($asignacion['estado_asignacion']); ?></span></td>
                                    <td>
                                        <?php if ($asignacion['acta_firmada_path']): ?>
                                            <a href="../uploads/actas/<?php echo htmlspecialchars($asignacion['acta_firmada_path']); ?>" target="_blank" class="btn btn-info btn-sm text-dark" title="Ver Acta"><i class="bi bi-file-earmark-pdf-fill"></i></a>
                                        <?php else: ?>
                                            <a href="asignacion_subir_acta.php?id=<?php echo $asignacion['id_asignacion']; ?>" class="btn btn-outline-primary btn-sm" title="Subir Acta"><i class="bi bi-upload"></i></a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($asignacion['estado_asignacion'] === 'Finalizada'): ?>
                                            <?php if ($asignacion['acta_devolucion_path']): ?>
                                                <a href="../uploads/actas_devolucion/<?php echo htmlspecialchars($asignacion['acta_devolucion_path']); ?>" target="_blank" class="btn btn-info btn-sm text-dark" title="Ver Acta"><i class="bi bi-file-earmark-pdf-fill"></i></a>
                                            <?php else: ?>
                                                <a href="asignacion_subir_acta_devolucion.php?id=<?php echo $asignacion['id_asignacion']; ?>" class="btn btn-outline-danger btn-sm" title="Subir Acta"><i class="bi bi-upload"></i></a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="generar_acta.php?id_asignacion=<?php echo $asignacion['id_asignacion']; ?>" target="_blank" class="btn btn-outline-secondary" title="Imprimir Acta Entrega"><i class="bi bi-printer"></i></a>
                                            <?php if ($asignacion['estado_asignacion'] === 'Activa'): ?>
                                                <a href="asignacion_devolver.php?id=<?php echo $asignacion['id_asignacion']; ?>" class="btn btn-danger" title="Registrar Devolución"><i class="bi bi-arrow-return-left"></i></a>
                                            <?php else: ?>
                                                <a href="asignacion_detalle_devolucion.php?id=<?php echo $asignacion['id_asignacion']; ?>" class="btn btn-primary" title="Ver Detalle"><i class="bi bi-eye"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="sinResultados">
                                <td colspan="<?php echo ($es_admin_general ? '9' : '8'); ?>" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-box-seam display-4 d-block mb-2"></i>
                                        No se encontraron asignaciones
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

<script>
// --- LÓGICA DE DATATABLES Y FILTRADO (UNIFICADA) ---
$(document).ready(function() {
    
    const esAdminGeneral = <?php echo $es_admin_general ? 'true' : 'false'; ?>;
    
    // Índices de columnas visibles para filtrado/ordenación
    // [Sucursal (0)], Empleado (1), Equipo (2), F. Entrega (3), F. Devolución (4), Estado (5), Acta E. (6), Acta D. (7), Acciones (8)
    let columnaSucursalIndex = 0; 
    let columnaEmpleadoIndex = esAdminGeneral ? 1 : 0;
    let columnaEquipoIndex = esAdminGeneral ? 2 : 1;
    let columnaFechaEntregaIndex = esAdminGeneral ? 3 : 2;
    let columnaEstadoIndex = esAdminGeneral ? 5 : 4;
    let columnaAccionesIndex = esAdminGeneral ? 8 : 7; 

    // 1. Inicializar DataTables con Buttons
    const tablaAsignaciones = $('#tablaAsignacionesPrincipal').DataTable({
        "dom": 'r<"table-responsive"t><"d-flex justify-content-between"ip>',
        "paging": true,
        "pageLength": 10,
        "searching": true, 
        "info": true,
        "order": [[ columnaEstadoIndex, 'asc' ]], 
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        "columnDefs": [
            { "orderable": false, "targets": [columnaAccionesIndex, columnaAccionesIndex - 1, columnaAccionesIndex - 2] }, // Actas y Acciones no ordenables
        ],
        "buttons": [ 
            { extend: 'excelHtml5', text: 'Excel', title: 'Reporte Asignaciones', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'csvHtml5', text: 'CSV', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'pdfHtml5', text: 'PDF', title: 'Reporte Asignaciones', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'print', text: 'Imprimir', exportOptions: { columns: ':not(:last-child)' } }
        ],
        "drawCallback": function( settings ) {
            const rowCount = this.api().rows( { filter: 'applied' } ).nodes().length;
            
            const info = tablaAsignaciones.page.info();
            document.getElementById('contadorAsignaciones').textContent = info.recordsDisplay;

            // Lógica para mostrar u ocultar el mensaje "No se encontraron datos"
            if (rowCount === 0) {
                if ($('#tablaAsignacionesPrincipal tbody').find('#sinResultados').length === 0) {
                     const colSpanCount = tablaAsignaciones.columns(':visible').count(); 
                     $('#tablaAsignacionesPrincipal tbody').append('<tr id="sinResultados"><td colspan="' + colSpanCount + '" class="text-center py-4"><div class="text-muted"><i class="bi bi-box-seam display-4 d-block mb-2"></i>No se encontraron asignaciones</div></td></tr>');
                }
            } else {
                $('#sinResultados').remove();
            }
        }
    });

    // Función principal de filtrado que combina la búsqueda global con los filtros de columna
    window.aplicarFiltrosCombinados = function() {
        // 1. Aplicar filtro de búsqueda global (searchInput)
        const searchTerm = document.getElementById('searchInput').value;
        tablaAsignaciones.search(searchTerm).draw();
        
        // 2. Aplicar filtros de columna (SELECTs)
        
        // Estado
        const estado = document.getElementById('filterEstado').value;
        tablaAsignaciones.column(columnaEstadoIndex).search(estado === '' ? '' : '^'+estado+'$', true, false).draw();
        
        // Sucursal (Solo si es admin general)
        if (esAdminGeneral) {
            const sucursalNombre = document.getElementById('filterSucursal').value;
            tablaAsignaciones.column(columnaSucursalIndex).search(sucursalNombre === '' ? '' : '^'+sucursalNombre+'$', true, false).draw();
        }
        
        // Empleado
        const empleadoNombre = document.getElementById('filterEmpleado').value;
        tablaAsignaciones.column(columnaEmpleadoIndex).search(empleadoNombre === '' ? '' : '^'+empleadoNombre+'$', true, false).draw();

        // Equipo (Código)
        const equipoCodigo = document.getElementById('filterEquipo').value;
        // La búsqueda debe ser más flexible ya que la columna contiene "CÓDIGO (Marca)"
        tablaAsignaciones.column(columnaEquipoIndex).search(equipoCodigo, false, true).draw();
        
        // Las búsquedas por fecha (filterFechaDesde/Hasta) deben ser manejadas en DataTables, 
        // pero requieren plugins de rango de fecha. Por simplicidad, se deja la búsqueda a nivel global
        // y se usa el valor directamente en el filtro principal.
    };


    // 3. Implementar la función de Limpiar Filtros
    window.limpiarFiltros = function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterEstado').value = '';
        document.getElementById('filterSucursal').value = '';
        document.getElementById('filterEmpleado').value = '';
        document.getElementById('filterEquipo').value = '';
        document.getElementById('filterFechaDesde').value = '';
        document.getElementById('filterFechaHasta').value = '';
        
        // Limpiar búsqueda global y de columnas
        tablaAsignaciones.search('').columns().search('').draw();
    }

    // 4. Conectar los botones de exportación
    
    $('#exportExcelClientSide').on('click', function(e) { e.preventDefault(); tablaAsignaciones.button('.buttons-excel').trigger(); });
    $('#exportCSVClientSide').on('click', function(e) { e.preventDefault(); tablaAsignaciones.button('.buttons-csv').trigger(); });
    $('#exportPDFClientSide').on('click', function(e) { e.preventDefault(); tablaAsignaciones.button('.buttons-pdf').trigger(); });
    $('#exportPrintClientSide').on('click', function(e) { e.preventDefault(); tablaAsignaciones.button('.buttons-print').trigger(); });

    // Disparar filtros al cargar la página para inicializar el estado
    aplicarFiltrosCombinados();
});
</script>

<?php require_once '../templates/footer.php'; ?>