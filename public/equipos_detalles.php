<?php
ob_start();
require_once '../templates/header.php';

// Verificar si se proporcionó un ID de equipo
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: equipos.php");
    exit();
}

$id_equipo = (int)$_GET['id'];

// Obtener información completa del equipo (CONSULTA CORREGIDA)
$sql_equipo = "SELECT 
    e.*,
    s.nombre as sucursal_nombre,
    te.nombre as tipo_equipo_nombre,
    m.nombre as marca_nombre,
    mo.nombre as modelo_nombre,
    a.nombre as area_nombre,
    c.nombre as cargo_nombre,
    emp.nombres as empleado_nombres,
    emp.apellidos as empleado_apellidos,
    emp.dni as empleado_dni
FROM equipos e
LEFT JOIN sucursales s ON e.id_sucursal = s.id
LEFT JOIN tipos_equipo te ON e.id_tipo_equipo = te.id
LEFT JOIN marcas m ON e.id_marca = m.id
LEFT JOIN modelos mo ON e.id_modelo = mo.id
LEFT JOIN asignaciones asi ON e.id = asi.id_equipo AND asi.estado_asignacion = 'Activa'
LEFT JOIN empleados emp ON asi.id_empleado = emp.id
LEFT JOIN cargos c ON emp.id_cargo = c.id
LEFT JOIN areas a ON emp.id_area = a.id
WHERE e.id = ?";

$stmt = $conexion->prepare($sql_equipo);
$stmt->bind_param("i", $id_equipo);
$stmt->execute();
$result = $stmt->get_result();
$equipo = $result->fetch_assoc();

if (!$equipo) {
    echo "<div class='alert alert-danger'>Equipo no encontrado.</div>";
    require_once '../templates/footer.php';
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Equipo - Sistema de Inventario</title>
    <style>
        /* 🎨 Mejoras de Interfaz (CSS interno a petición del usuario) */
        :root {
            /* Colores Personalizados para la interfaz */
            --custom-primary: #3f51b5; /* Indigo/Deep Blue - para Información */
            --custom-success: #4CAF50; /* Green - para Asignación/Disponible */
            --custom-danger: #e53935; /* Strong Red - para PDF/De Baja */
            --custom-secondary: #757575; /* Medium Gray - para Etiquetas/Estado por defecto */
            --custom-info: #00bcd4; /* Brighter Cyan - para En Reparación */
        }

        /* Estilos de Tarjetas (Mejoras de Interfaz) */
        .card { 
            /* Borde más sutil y mejor sombra */
            border: 1px solid #e9ecef; 
            border-radius: 0.75rem; 
            box-shadow: 0 0.15rem 0.6rem rgba(0,0,0,0.08); 
            transition: all 0.3s;
        }
        .card:hover {
            box-shadow: 0 0.3rem 1rem rgba(0,0,0,0.1);
        }

        /* Sobreescritura de colores de encabezados de tarjeta para equipos_detalles.php */
        .card-header.bg-primary { background-color: var(--custom-primary) !important; color: white !important; border-bottom: none; }
        .card-header.bg-success { background-color: var(--custom-success) !important; color: white !important; border-bottom: none; }
        .card-header.bg-secondary { background-color: var(--custom-secondary) !important; color: white !important; border-bottom: none; }

        /* Estilos de Etiquetas de Información */
        .info-label {
            font-weight: 600 !important; /* Etiqueta más prominente */
            color: var(--custom-secondary) !important; 
        }

        /* Estilos de Botones */
        .btn { 
            border-radius: 0.5rem; 
            font-weight: 600; 
            padding: 0.5rem 1rem;
            transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
        }
        .btn-danger {
            background-color: var(--custom-danger);
            border-color: var(--custom-danger);
        }
        .btn-danger:hover {
            background-color: #c62828; /* Hover más oscuro */
            border-color: #b71c1c;
        }

        /* Estilos para Badges/Estados */
        .badge.bg-success { background-color: var(--custom-success) !important; }
        .badge.bg-warning { background-color: #ffc107 !important; color: #212529 !important; } /* Amarillo con texto oscuro */
        .badge.bg-danger { background-color: var(--custom-danger) !important; }
        .badge.bg-info { background-color: var(--custom-info) !important; color: #212529 !important; } /* Cian con texto oscuro */
        .badge.bg-secondary { background-color: var(--custom-secondary) !important; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-desktop me-2"></i>
                        Detalles del Equipo
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="equipos.php" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>
                            Volver
                        </a>
                        <a href="generar_pdf_equipo.php?id=<?php echo $id_equipo; ?>" target="_blank" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-1"></i>
                            Generar Ficha Técnica (PDF)
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Información del Equipo
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <span class="info-label">Código Patrimonial:</span>
                                        <p class="fs-5 fw-bold"><?php echo htmlspecialchars($equipo['codigo_inventario']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span class="info-label">Número de Serie:</span>
                                        <p class="fs-5"><?php echo htmlspecialchars($equipo['numero_serie']); ?></p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <span class="info-label">Tipo de Equipo:</span>
                                        <p><?php echo htmlspecialchars($equipo['tipo_equipo_nombre']); ?></p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <span class="info-label">Marca:</span>
                                        <p><?php echo htmlspecialchars($equipo['marca_nombre']); ?></p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <span class="info-label">Modelo:</span>
                                        <p><?php echo htmlspecialchars($equipo['modelo_nombre']); ?></p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <span class="info-label">Sucursal:</span>
                                        <p><?php echo htmlspecialchars($equipo['sucursal_nombre']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span class="info-label">Tipo de Adquisición:</span>
                                        <p><?php echo htmlspecialchars($equipo['tipo_adquisicion']); ?></p>
                                    </div>
                                </div>

                                <?php if ($equipo['fecha_adquisicion']): ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <span class="info-label">Fecha de Adquisición:</span>
                                        <p><?php echo date('d/m/Y', strtotime($equipo['fecha_adquisicion'])); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span class="info-label">Proveedor:</span>
                                        <p><?php echo htmlspecialchars($equipo['proveedor']); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($equipo['caracteristicas'])): ?>
                                <div class="mb-3">
                                    <span class="info-label">Características:</span>
                                    <p><?php echo nl2br(htmlspecialchars($equipo['caracteristicas'])); ?></p>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($equipo['observaciones'])): ?>
                                <div class="mb-3">
                                    <span class="info-label">Observaciones:</span>
                                    <p><?php echo nl2br(htmlspecialchars($equipo['observaciones'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($equipo['empleado_nombres']): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user me-2"></i>
                                    Asignado a
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <span class="info-label">Empleado:</span>
                                        <p class="fs-5"><?php echo htmlspecialchars($equipo['empleado_nombres'] . ' ' . $equipo['empleado_apellidos']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span class="info-label">DNI:</span>
                                        <p><?php echo htmlspecialchars($equipo['empleado_dni']); ?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <span class="info-label">Área:</span>
                                        <p><?php echo htmlspecialchars($equipo['area_nombre']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span class="info-label">Cargo:</span>
                                        <p><?php echo htmlspecialchars($equipo['cargo_nombre']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Este equipo no está asignado a ningún empleado.
                        </div>
                        <?php endif; ?>
                        
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tag me-2"></i>
                                    Estado del Equipo
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <?php 
                                $badge_class = '';
                                switch($equipo['estado']) {
                                    case 'Disponible': $badge_class = 'bg-success'; break;
                                    case 'Asignado': $badge_class = 'bg-warning text-dark'; break;
                                    case 'En Reparacion': $badge_class = 'bg-info text-dark'; break;
                                    case 'De Baja': $badge_class = 'bg-danger'; break;
                                    default: $badge_class = 'bg-secondary';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?> fs-5 p-2"><?php echo htmlspecialchars($equipo['estado']); ?></span>
                                <p class="mt-2 mb-0 text-muted">
                                    <small>Fecha registro: <?php echo date('d/m/Y H:i', strtotime($equipo['fecha_registro'])); ?></small>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </main>
        </div>
    </div>

    <?php require_once '../templates/footer.php'; ?>
</body>
</html>