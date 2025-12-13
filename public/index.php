<?php require_once '../templates/header.php'; ?>

    <h1 class="h2">Dashboard</h1>
    
    <?php
    // --- FILTRO POR SUCURSAL PARA LAS ESTADÍSTICAS ---
    $id_sucursal_usuario = $_SESSION['user_sucursal_id'];
    $es_admin_general = ($id_sucursal_usuario === null);
    $filtro_sucursal_sql = "";
    $filtro_sucursal_sql_con_and = "";

    if (!$es_admin_general) {
        $filtro_sucursal_sql = " WHERE id_sucursal = " . (int)$id_sucursal_usuario;
        $filtro_sucursal_sql_con_and = " AND id_sucursal = " . (int)$id_sucursal_usuario;
    }

    // --- DATOS PARA GRÁFICO 1: EQUIPOS DISPONIBLES POR TIPO ---
    $sql_chart_equipos_tipo = "SELECT 
                                te.nombre as tipo_equipo, COUNT(e.id) as cantidad
                               FROM equipos e
                               JOIN tipos_equipo te ON e.id_tipo_equipo = te.id
                               WHERE e.estado = 'Disponible' " . str_replace('WHERE', 'AND', $filtro_sucursal_sql) . "
                               GROUP BY te.nombre ORDER BY cantidad DESC";
    $resultado_chart_equipos_tipo = $conexion->query($sql_chart_equipos_tipo);
    $equipos_tipo_labels = [];
    $equipos_tipo_data = [];
    if ($resultado_chart_equipos_tipo) {
        while ($fila = $resultado_chart_equipos_tipo->fetch_assoc()) {
            $equipos_tipo_labels[] = $fila['tipo_equipo'];
            $equipos_tipo_data[] = $fila['cantidad'];
        }
    }

    // --- DATOS PARA GRÁFICO 2: EQUIPOS Y EMPLEADOS POR SUCURSAL (SOLO ADMIN GENERAL) ---
    $sucursal_chart_labels = [];
    $sucursal_equipos_data = [];
    $sucursal_empleados_data = [];
    if ($es_admin_general) {
        $sql_chart_sucursal = "SELECT 
                                    s.nombre AS sucursal_nombre,
                                    (SELECT COUNT(*) FROM equipos eq WHERE eq.id_sucursal = s.id) as total_equipos,
                                    (SELECT COUNT(*) FROM empleados emp WHERE emp.id_sucursal = s.id AND emp.estado = 'Activo') as total_empleados
                                FROM sucursales s
                                WHERE s.estado = 'Activo'
                                ORDER BY s.nombre";
        $resultado_chart_sucursal = $conexion->query($sql_chart_sucursal);
        if ($resultado_chart_sucursal) {
            while ($fila = $resultado_chart_sucursal->fetch_assoc()) {
                $sucursal_chart_labels[] = $fila['sucursal_nombre'];
                $sucursal_equipos_data[] = $fila['total_equipos'];
                $sucursal_empleados_data[] = $fila['total_empleados'];
            }
        }
    }
    ?>

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Total de Equipos</h5>
                            <?php $total_equipos = $conexion->query("SELECT COUNT(id) AS total FROM equipos" . $filtro_sucursal_sql)->fetch_assoc()['total']; ?>
                            <p class="card-text fs-2 fw-bold text-dark"><?php echo $total_equipos; ?></p>
                        </div>
                        <i class="bi bi-hdd-stack display-4 text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Equipos Asignados</h5>
                            <?php $total_asignados = $conexion->query("SELECT COUNT(id) AS total FROM equipos WHERE estado = 'Asignado'" . $filtro_sucursal_sql_con_and)->fetch_assoc()['total']; ?>
                            <p class="card-text fs-2 fw-bold text-dark"><?php echo $total_asignados; ?></p>
                        </div>
                        <i class="bi bi-person-check display-4 text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Equipos Disponibles</h5>
                            <?php $total_disponibles = $conexion->query("SELECT COUNT(id) AS total FROM equipos WHERE estado = 'Disponible'" . $filtro_sucursal_sql_con_and)->fetch_assoc()['total']; ?>
                            <p class="card-text fs-2 fw-bold text-dark"><?php echo $total_disponibles; ?></p>
                        </div>
                        <i class="bi bi-box-seam display-4 text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-lg-5 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white border-0 fw-bold">Equipos Disponibles por Tipo</div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <?php if (!empty($equipos_tipo_data)): ?>
                        <canvas id="equiposPorTipoChart"></canvas>
                    <?php else: ?>
                        <p class="text-muted">No hay datos para mostrar en el gráfico.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($es_admin_general): ?>
        <div class="col-lg-7 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white border-0 fw-bold">Equipos y Empleados por Sucursal</div>
                <div class="card-body d-flex justify-content-center align-items-center">
                     <?php if (!empty($sucursal_chart_labels)): ?>
                        <canvas id="sucursalChart"></canvas>
                    <?php else: ?>
                        <p class="text-muted">No hay datos de sucursales para mostrar.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Gráfico de Dona: Equipos por Tipo
    const ctxEquiposTipo = document.getElementById('equiposPorTipoChart');
    if (ctxEquiposTipo) {
        new Chart(ctxEquiposTipo, {
            type: 'doughnut', 
            data: { 
                labels: <?php echo json_encode($equipos_tipo_labels); ?>, 
                datasets: [{ 
                    label: 'Cantidad', 
                    data: <?php echo json_encode($equipos_tipo_data); ?>, 
                    backgroundColor: ['rgba(54, 162, 235, 0.8)','rgba(255, 206, 86, 0.8)','rgba(75, 192, 192, 0.8)','rgba(153, 102, 255, 0.8)','rgba(255, 159, 64, 0.8)'], 
                    borderWidth: 2 
                }] 
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
        });
    }

    // Gráfico de Barras: Equipos y Empleados por Sucursal
    const ctxSucursal = document.getElementById('sucursalChart');
    if (ctxSucursal) {
        new Chart(ctxSucursal, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($sucursal_chart_labels); ?>,
                datasets: [
                    {
                        label: 'Nº de Equipos',
                        data: <?php echo json_encode($sucursal_equipos_data); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Nº de Empleados',
                        data: <?php echo json_encode($sucursal_empleados_data); ?>,
                        backgroundColor: 'rgba(255, 159, 64, 0.7)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { precision: 0 } 
                    } 
                },
                plugins: { 
                    legend: { position: 'top' } 
                }
            }
        });
    }
});
</script>

<?php require_once '../templates/footer.php'; ?>