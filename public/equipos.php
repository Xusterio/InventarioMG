<?php
ob_start(); // Iniciar buffer de salida
require_once '../templates/header.php';

// Verificar permisos de sucursal
$id_sucursal_usuario = $_SESSION['user_sucursal_id'];
$es_admin_general = ($id_sucursal_usuario === null);

// Construir filtro de sucursal si no es admin
$filtro_sucursal_sql = "";
if (!$es_admin_general) {
    $filtro_sucursal_sql = " AND e.id_sucursal = " . (int)$id_sucursal_usuario;
}

// ------------------------------------------------------------------
// LA LÓGICA DE EXPORTACIÓN A EXCEL EN PHP HA SIDO ELIMINADA.
// ------------------------------------------------------------------

// Consulta para obtener equipos - Necesario para poblar la tabla HTML
$sql = "SELECT 
    e.id,
    e.codigo_inventario,
    e.caracteristicas,
    e.estado,
    e.numero_serie,
    e.fecha_registro,
    e.id_sucursal,
    e.id_tipo_equipo,
    e.id_marca,
    ma.nombre as marca_nombre,
    mo.nombre as modelo_nombre,
    t.nombre as tipo_nombre,
    s.nombre as sucursal_nombre
FROM equipos e
LEFT JOIN marcas ma ON e.id_marca = ma.id
LEFT JOIN modelos mo ON e.id_modelo = mo.id
LEFT JOIN tipos_equipo t ON e.id_tipo_equipo = t.id
LEFT JOIN sucursales s ON e.id_sucursal = s.id
WHERE 1=1 {$filtro_sucursal_sql}
ORDER BY e.fecha_registro DESC"; 

$equipos = $conexion->query($sql);

// Cargar catálogos para los dropdowns de filtros
$sucursales = $conexion->query("SELECT id, nombre FROM sucursales WHERE estado = 'Activo' ORDER BY nombre");
$tipos = $conexion->query("SELECT id, nombre FROM tipos_equipo WHERE estado = 'Activo' ORDER BY nombre");
$marcas = $conexion->query("SELECT id, nombre FROM marcas WHERE estado = 'Activo' ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Equipos</title>
    
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
        #tablaEquiposPrincipal thead th {
            font-weight: 600;
            color: var(--custom-secondary);
        }
        #tablaEquiposPrincipal tbody tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-list-columns-reverse me-2"></i> Gestión de Equipos
        </h1>
        <div class="btn-group">
            <a href="equipo_agregar.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Agregar Equipo
            </a>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                <span class="visually-hidden">Exportar</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" id="exportExcelClientSide"><i class="bi bi-file-earmark-excel me-2"></i>Exportar a Excel</a></li>
                <li><a class="dropdown-item" href="#" onclick="exportarPDF()"><i class="bi bi-file-earmark-pdf me-2"></i>Exportar a PDF</a></li>
                <li><a class="dropdown-item" href="#" onclick="exportarCSV()"><i class="bi bi-file-earmark-text me-2"></i>Exportar a CSV</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" onclick="imprimirTabla()"><i class="bi bi-printer me-2"></i>Imprimir</a></li>
            </ul>
        </div>
    </div>

    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'success_add'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>Equipo agregado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['status'] === 'success_edit'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>Equipo actualizado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['status'] === 'success_delete' || $_GET['status'] === 'success_logic_delete'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($_GET['msg'] ?? 'Equipo eliminado/marcado correctamente.'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['status'] === 'error_delete'): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-x-octagon-fill me-2"></i>Error al eliminar equipo. <?php echo htmlspecialchars($_GET['msg'] ?? 'Error desconocido.'); ?>
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
                               placeholder="Buscar por Código de Inventario o Número de Serie...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="filterEstado">
                        <option value="">Todos los estados</option>
                        <option value="Disponible">Disponible</option>
                        <option value="Asignado">Asignado</option>
                        <option value="En Reparacion">En Reparación</option>
                        <option value="De Baja">De Baja</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="filterSucursal">
                        <option value="">Todas las sucursales</option>
                        <?php
                        $sucursales->data_seek(0);
                        while ($sucursal = $sucursales->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($sucursal['nombre']); ?>"><?php echo htmlspecialchars($sucursal['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <select class="form-select" id="filterTipo">
                        <option value="">Todos los tipos</option>
                        <?php
                        $tipos->data_seek(0);
                        while ($tipo = $tipos->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($tipo['nombre']); ?>"><?php echo htmlspecialchars($tipo['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="filterMarca">
                        <option value="">Todas las marcas</option>
                        <?php
                        $marcas->data_seek(0);
                        while ($marca = $marcas->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($marca['nombre']); ?>"><?php echo htmlspecialchars($marca['nombre']); ?></option>
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
                <i class="bi bi-laptop me-2"></i>
                Inventario de Equipos
            </h5>
            <div class="text-muted small">
                <span id="contadorEquipos"><?php echo $equipos->num_rows; ?></span> equipos encontrados
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaEquiposPrincipal" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <?php if ($id_sucursal_usuario === null): ?>
                                <th>Sucursal</th>
                            <?php endif; ?>
                            <th>Código</th>
                            <th>Tipo</th> 
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>N° Serie</th>
                            <th>Estado</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaEquipos">
                        <?php if ($equipos->num_rows > 0): ?>
                            <?php while ($equipo = $equipos->fetch_assoc()): ?>
                            <tr class="fila-equipo">
                                
                                <?php 
                                    if ($id_sucursal_usuario === null): 
                                ?>
                                    <td><?php echo htmlspecialchars($equipo['sucursal_nombre']); ?></td>
                                <?php endif; ?>
                                <td class="fw-bold">
                                    <?php echo htmlspecialchars($equipo['codigo_inventario']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($equipo['tipo_nombre']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($equipo['marca_nombre']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($equipo['modelo_nombre']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($equipo['numero_serie']); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'bg-secondary';
                                    if ($equipo['estado'] == 'Disponible') $badge_class = 'bg-success';
                                    if ($equipo['estado'] == 'Asignado') $badge_class = 'bg-primary'; 
                                    if ($equipo['estado'] == 'En Reparacion') $badge_class = 'bg-warning text-dark';
                                    if ($equipo['estado'] == 'De Baja') $badge_class = 'bg-danger';
                                    ?>
                                    <span class="badge rounded-pill <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($equipo['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="equipos_detalles.php?id=<?php echo $equipo['id']; ?>" 
                                           class="btn btn-outline-info" title="Ver Detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <a href="equipo_editar.php?id=<?php echo $equipo['id']; ?>" 
                                           class="btn btn-outline-warning" title="Editar Equipo">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <a href="historial_equipo.php?id=<?php echo $equipo['id']; ?>" 
                                           class="btn btn-outline-secondary" title="Ver Historial de Edición">
                                            <i class="bi bi-clock-history"></i>
                                        </a>

                                        <a href="#" 
                                           onclick="confirmarEliminacionEquipo(
                                                            <?php echo $equipo['id']; ?>, 
                                                            '<?php echo htmlspecialchars($equipo['codigo_inventario']); ?>',
                                                            '<?php echo $equipo['estado']; ?>'
                                                        )"
                                           class="btn btn-outline-danger" 
                                           title="Eliminar Equipo"
                                           <?php echo ($equipo['estado'] == 'Asignado') ? 'disabled' : ''; ?>>
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="sinResultados">
                                <td colspan="<?php echo ($id_sucursal_usuario === null ? '8' : '7'); ?>" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-laptop display-4 d-block mb-2"></i>
                                        No se encontraron equipos registrados
                                        <div class="mt-3">
                                            <a href="equipo_agregar.php" class="btn btn-primary">
                                                <i class="bi bi-plus-circle me-2"></i>Registrar Primer Equipo
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

<div class="modal fade" id="modalEliminacionEquipo" tabindex="-1" aria-labelledby="modalEliminacionEquipoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalEliminacionEquipoLabel"><i class="bi bi-trash me-2"></i> Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea **eliminar** el equipo con Código: <strong id="equipoCodigoAEliminar"></strong>?</p>
                <div id="advertenciaEliminacion"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a id="btnConfirmarEliminacionEquipo" class="btn btn-danger" href="#">Eliminar Definitivamente</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    const esAdminGeneral = <?php echo $es_admin_general ? 'true' : 'false'; ?>;
    
    // ÍNDICES DE COLUMNAS VISIBLES (Total de columnas visibles: Admin: 8, No Admin: 7)
    let columnaTipoIndex = esAdminGeneral ? 2 : 1; 
    let columnaMarcaIndex = esAdminGeneral ? 3 : 2;
    let columnaEstadoIndex = esAdminGeneral ? 6 : 5;
    let columnaAccionesIndex = esAdminGeneral ? 7 : 6;
    let columnaSucursalIndex = 0; 

    // 1. Inicializar DataTables con Buttons
    const tablaEquipos = $('#tablaEquiposPrincipal').DataTable({
        "dom": 'r<"table-responsive"t><"d-flex justify-content-between"ip>',
        "paging": true,
        "pageLength": 10,
        "searching": true, 
        "info": true,
        "order": [[ columnaEstadoIndex, 'asc' ]], 
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        "columnDefs": [
            { "orderable": false, "targets": columnaAccionesIndex }
        ],
        "buttons": [ 
            { 
                extend: 'excelHtml5', 
                text: 'Excel', 
                title: 'Inventario Equipos - <?php echo date('Y-m-d'); ?>',
                exportOptions: {
                    columns: ':visible',
                    columns: ':not(:last-child)' 
                }
            },
            { extend: 'csvHtml5', text: 'CSV', exportOptions: { columns: ':not(:last-child)' } },
            { 
                extend: 'pdfHtml5', 
                text: 'PDF', 
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
            
            const info = tablaEquipos.page.info();
            document.getElementById('contadorEquipos').textContent = info.recordsDisplay;

            // Lógica para mostrar u ocultar el mensaje "No se encontraron datos"
            if (rowCount === 0) {
                if ($('#tablaEquiposPrincipal tbody').find('#sinResultados').length === 0) {
                     const colSpanCount = tablaEquipos.columns(':visible').count(); 
                     $('#tablaEquiposPrincipal tbody').append('<tr id="sinResultados"><td colspan="' + colSpanCount + '" class="text-center py-4"><div class="text-muted"><i class="bi bi-laptop display-4 d-block mb-2"></i>No se encontraron equipos registrados</div></td></tr>');
                }
            } else {
                $('#sinResultados').remove();
            }
        }
    });

    // 2. Conectar el campo de búsqueda principal (searchInput) al filtro global
    $('#searchInput').on('keyup', function() {
        tablaEquipos.search(this.value).draw();
    });

    // 3. Conectar los selects de filtro a los filtros de columna (APUNTAN A NOMBRES)
    
    // Columna: ESTADO
    $('#filterEstado').on('change', function() {
        const estado = this.value;
        tablaEquipos.column(columnaEstadoIndex).search(estado === '' ? '' : '^'+estado+'$', true, false).draw();
    });

    // Columna: SUCURSAL (Solo si es admin general)
    if (esAdminGeneral) {
        $('#filterSucursal').on('change', function() {
            const sucursalNombre = this.value;
            tablaEquipos.column(columnaSucursalIndex).search(sucursalNombre === '' ? '' : '^'+sucursalNombre+'$', true, false).draw();
        });
    }

    // Columna: TIPO (Apunta a la columna de Tipo Nombre)
    $('#filterTipo').on('change', function() {
        const tipoNombre = this.value;
        tablaEquipos.column(columnaTipoIndex).search(tipoNombre === '' ? '' : '^'+tipoNombre+'$', true, false).draw();
    });

    // Columna: MARCA (Apunta a la columna de Marca Nombre)
    $('#filterMarca').on('change', function() {
        const marcaNombre = this.value;
        tablaEquipos.column(columnaMarcaIndex).search(marcaNombre === '' ? '' : '^'+marcaNombre+'$', true, false).draw();
    });

    // 4. Implementar la función de Limpiar Filtros
    window.limpiarFiltros = function() {
        $('#searchInput').val('').trigger('keyup');
        $('#filterEstado').val('').trigger('change');
        $('#filterSucursal').val('').trigger('change');
        $('#filterTipo').val('').val('').trigger('change');
        $('#filterMarca').val('').val('').trigger('change');
        // Limpiar búsqueda global y de columnas
        tablaEquipos.search('').columns().search('').draw();
    }

    // 5. Conectar los botones de exportación a DataTables Buttons y la función de PDF personalizada
    
    $('#exportExcelClientSide').on('click', function(e) { 
        e.preventDefault();
        tablaEquipos.button('.buttons-excel').trigger(); 
    });
    
    window.exportarCSV = function() {
        tablaEquipos.button('.buttons-csv').trigger();
    }
    
    // FUNCIÓN PARA GENERAR PDF PERSONALIZADO (con logo y filtros)
    window.exportarPDF = function() {
        // Recolectar los valores de los filtros (Nombres de las categorías)
        const estado = document.getElementById('filterEstado').value;
        const sucursal = document.getElementById('filterSucursal').value;
        const tipo = document.getElementById('filterTipo').value;
        const marca = document.getElementById('filterMarca').value;
        // Obtener el valor de búsqueda global
        const busqueda = document.getElementById('searchInput').value;

        // URL al script de servidor que creará el PDF
        let url = 'exportar_equipos_pdf_custom.php?';
        url += 'estado=' + encodeURIComponent(estado);
        url += '&sucursal=' + encodeURIComponent(sucursal);
        url += '&tipo=' + encodeURIComponent(tipo);
        url += '&marca=' + encodeURIComponent(marca);
        url += '&busqueda=' + encodeURIComponent(busqueda);
        
        window.open(url, '_blank');
    }

    window.imprimirTabla = function() {
        tablaEquipos.button('.buttons-print').trigger();
    }
});

// FUNCIÓN DE MODAL DE ELIMINACIÓN (Se mantiene intacta)
function confirmarEliminacionEquipo(id, codigo, estado) {
    const modalTitle = document.getElementById('modalEliminacionEquipoLabel');
    const advertenciaDiv = document.getElementById('advertenciaEliminacion');
    const btnConfirmar = document.getElementById('btnConfirmarEliminacionEquipo');
    
    if (estado === 'Asignado') {
        modalTitle.textContent = "❌ Error al Eliminar Equipo";
        advertenciaDiv.innerHTML = '<p class="text-danger fw-bold">No se puede eliminar un equipo con estado "Asignado". Finalice la asignación primero.</p>';
        btnConfirmar.style.display = 'none';
        
    } else {
        modalTitle.textContent = "Confirmar Eliminación";
        document.getElementById('equipoCodigoAEliminar').textContent = codigo;
        btnConfirmar.style.display = 'inline-block';
        
        advertenciaDiv.innerHTML = `
            <p class="text-info">
                Si este equipo tiene historial de asignaciones, será marcado como **De Baja** en lugar de ser eliminado permanentemente para preservar la data histórica.
            </p>
            <p class="text-danger fw-bold">
                ¡Esta acción es irreversible!
            </p>
        `;
        
        const deleteUrl = '../includes/procesar_eliminacion_equipo.php?id=' + id;
        btnConfirmar.href = deleteUrl;
    }

    const modalElement = document.getElementById('modalEliminacionEquipo');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

</script>

<?php 
ob_end_flush(); 
require_once '../templates/footer.php'; 
?>