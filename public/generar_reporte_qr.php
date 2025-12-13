<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../config/database.php';

// Verificar si se proporcionó un ID de equipo
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de equipo no proporcionado");
}

$id_equipo = (int)$_GET['id'];

// Obtener información completa del equipo
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
    die("Equipo no encontrado");
}

// Generar URL para el QR
$url_equipo = "http://" . $_SERVER['HTTP_HOST'] . "/InventarioMG/public/equipos_detalles.php?id=" . $id_equipo;

// Generar código QR usando API gratuita
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($url_equipo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Equipo - <?php echo htmlspecialchars($equipo['codigo_inventario']); ?></title>
    <style>
        /* Estilos para impresión/PDF y móviles */
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            .container { width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .qr-section { page-break-inside: avoid; }
        }
        
        @media screen and (max-width: 768px) {
            body { margin: 10px; }
            .container { padding: 15px; }
            .section { padding: 10px; }
            .header h1 { font-size: 20px; }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
            line-height: 1.4;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
        }
        
        .header .subtitle {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .section {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            page-break-inside: avoid;
            background: #fff;
        }
        
        .section-title {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 12px 15px;
            margin: -15px -15px 15px -15px;
            border-radius: 8px 8px 0 0;
            font-weight: bold;
            font-size: 16px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-table td {
            padding: 10px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .info-table td:first-child {
            font-weight: bold;
            width: 35%;
            color: #2c3e50;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            display: inline-block;
        }
        
        .badge-success { background: #27ae60; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .badge-info { background: #3498db; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
        
        .text-muted { color: #7f8c8d; }
        
        .qr-section {
            text-align: center;
            padding: 25px;
            border: 3px solid #2c3e50;
            margin: 20px 0;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .qr-code {
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
            display: block;
            border: 3px solid #2c3e50;
            border-radius: 10px;
            padding: 10px;
            background: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .scan-instructions {
            background: #e8f4fd;
            border: 2px solid #3498db;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .scan-instructions h4 {
            color: #2c3e50;
            margin-top: 0;
            text-align: center;
        }
        
        .scan-instructions ol {
            margin: 15px 0;
            padding-left: 20px;
        }
        
        .scan-instructions li {
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .mobile-warning {
            display: none;
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        
        .qr-success {
            color: #27ae60;
            font-weight: bold;
            font-size: 18px;
            margin: 10px 0;
        }
        
        @media screen and (max-width: 768px) {
            .mobile-warning {
                display: block;
            }
            .qr-code {
                width: 180px;
                height: 180px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Advertencia móvil -->
        <div class="mobile-warning">
            📱 <strong>ESCANEABLE DESDE CELULAR</strong> - Usa cualquier app de cámara para escanear el código QR
        </div>

        <!-- Botones de acción -->
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button onclick="window.print()" class="btn-print">
                🖨️ Imprimir / Guardar como PDF
            </button>
            <button onclick="window.close()" class="btn-print" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                ❌ Cerrar Ventana
            </button>
        </div>

        <!-- Encabezado -->
        <div class="header">
            <h1>INFORMACIÓN DEL EQUIPO</h1>
            <div class="subtitle">Sistema de Inventario - Generado el <?php echo date('d/m/Y H:i'); ?></div>
        </div>

        <!-- SECCIÓN QR (LO MÁS IMPORTANTE) -->
        <div class="section qr-section">
            <div class="section-title">📱 ESCANEAR CON CELULAR</div>
            
            <div class="qr-success">¡CÓDIGO QR LISTO PARA ESCANEAR!</div>
            
            <img src="<?php echo $qr_url; ?>" alt="Código QR para escanear" class="qr-code">
            <p style="font-size: 16px; font-weight: bold; margin: 10px 0;">
                Escanea este código QR con la cámara de tu celular
            </p>
            
            <div class="scan-instructions">
                <h4>📋 INSTRUCCIONES PARA ESCANEAR</h4>
                <ol>
                    <li><strong>Abre la cámara de tu celular</strong></li>
                    <li><strong>Enfoca el código QR</strong> (mantén estable)</li>
                    <li><strong>Toca la notificación</strong> que aparece en pantalla</li>
                    <li><strong>Serás dirigido automáticamente</strong> a la información actualizada del equipo</li>
                </ol>
                <p style="text-align: center; margin: 0; font-style: italic;">
                    ⚡ Funciona con cualquier celular moderno (iOS/Android)
                </p>
            </div>
            
            <div style="background: #2c3e50; color: white; padding: 10px; border-radius: 5px; margin-top: 15px;">
                <strong>Código: <?php echo htmlspecialchars($equipo['codigo_inventario']); ?></strong><br>
                <small>URL: <?php echo $url_equipo; ?></small>
            </div>
        </div>

        <!-- Información básica del equipo -->
        <div class="section">
            <div class="section-title">📋 DATOS DEL EQUIPO</div>
            <table class="info-table">
                <tr>
                    <td>Código Patrimonial:</td>
                    <td><strong style="font-size: 16px; color: #2c3e50;"><?php echo htmlspecialchars($equipo['codigo_inventario']); ?></strong></td>
                </tr>
                <tr>
                    <td>Número de Serie:</td>
                    <td><?php echo htmlspecialchars($equipo['numero_serie'] ?: 'No especificado'); ?></td>
                </tr>
                <tr>
                    <td>Tipo de Equipo:</td>
                    <td><?php echo htmlspecialchars($equipo['tipo_equipo_nombre'] ?: 'No especificado'); ?></td>
                </tr>
                <tr>
                    <td>Marca - Modelo:</td>
                    <td><?php echo htmlspecialchars($equipo['marca_nombre'] ?: 'No especificado'); ?> - <?php echo htmlspecialchars($equipo['modelo_nombre'] ?: 'No especificado'); ?></td>
                </tr>
                <tr>
                    <td>Sucursal:</td>
                    <td><?php echo htmlspecialchars($equipo['sucursal_nombre'] ?: 'No especificado'); ?></td>
                </tr>
            </table>
        </div>

        <!-- Estado y Asignación -->
        <div class="section">
            <div class="section-title">🏷️ ESTADO Y UBICACIÓN</div>
            <table class="info-table">
                <tr>
                    <td>Estado:</td>
                    <td>
                        <?php 
                        $badge_class = '';
                        switch($equipo['estado']) {
                            case 'Disponible': $badge_class = 'badge-success'; break;
                            case 'Asignado': $badge_class = 'badge-warning'; break;
                            case 'En Reparacion': $badge_class = 'badge-info'; break;
                            case 'De Baja': $badge_class = 'badge-danger'; break;
                            default: $badge_class = 'badge-info';
                        }
                        ?>
                        <span class="badge <?php echo $badge_class; ?>">
                            <?php echo htmlspecialchars($equipo['estado']); ?>
                        </span>
                    </td>
                </tr>
                <?php if ($equipo['empleado_nombres']): ?>
                <tr>
                    <td>Asignado a:</td>
                    <td><strong><?php echo htmlspecialchars($equipo['empleado_nombres'] . ' ' . $equipo['empleado_apellidos']); ?></strong></td>
                </tr>
                <tr>
                    <td>DNI:</td>
                    <td><?php echo htmlspecialchars($equipo['empleado_dni']); ?></td>
                </tr>
                <tr>
                    <td>Área - Cargo:</td>
                    <td><?php echo htmlspecialchars($equipo['area_nombre'] ?: 'No especificado'); ?> - <?php echo htmlspecialchars($equipo['cargo_nombre'] ?: 'No especificado'); ?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td>Asignación:</td>
                    <td><span style="color: #e74c3c; font-style: italic;">⚠️ No asignado</span></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <p class="text-muted">
                📄 Documento generado automáticamente por el Sistema de Inventario<br>
                🕒 Fecha de generación: <?php echo date('d/m/Y H:i'); ?><br>
                📱 <strong>ESCANEA EL CÓDIGO QR PARA INFORMACIÓN ACTUALIZADA EN TIEMPO REAL</strong>
            </p>
        </div>
    </div>

    <script>
        // Detectar si es móvil y mostrar instrucciones
        function esMovil() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }
        
        if (esMovil()) {
            document.querySelector('.mobile-warning').style.display = 'block';
            
            // En móvil, mostrar alerta de cómo escanear
            setTimeout(() => {
                if (confirm('¿Quieres aprender a escanear el código QR?\n\n1. Abre la cámara\n2. Enfoca el código\n3. Toca la notificación')) {
                    // El usuario quiere instrucciones
                }
            }, 1000);
        }
        
        // Mostrar mensaje de éxito
        console.log('✅ Código QR generado correctamente');
        console.log('📱 URL para escanear:', '<?php echo $url_equipo; ?>');
    </script>
</body>
</html>

<?php
// Cerrar conexión
$stmt->close();
$conexion->close();
?>