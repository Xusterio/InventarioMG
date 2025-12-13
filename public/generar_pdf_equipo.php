<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado. Por favor inicie sesión.");
}

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';
// Incluir FPDF
require_once __DIR__ . '/../fpdf/fpdf.php';
// Incluir librería QR (qrlib.php)
require_once __DIR__ . '/../phpqrcode/qrlib.php'; 

// --------------------------------------------------------
// --- CLASE FPDF CUSTOMIZADA CON ESTILO INSTITUCIONAL BLANCO ---
// --------------------------------------------------------
class PDF extends FPDF {
    // Definición de Colores (AJUSTAR ESTE VALOR AL RGB PRINCIPAL DE TU LOGO)
    const COLOR_PRIMARY = [30, 75, 110];    // Azul Institucional
    const COLOR_SECONDARY = [39, 174, 96]; // Verde (Usado para la sección de Asignación)
    const COLOR_TEXT_DARK = [51, 51, 51];   // Gris Oscuro para texto de datos
    const COLOR_TEXT_MUTED = [150, 150, 150]; // Gris claro para fechas/pie de página

    // Cabecera de página
    function Header() {
        // Asegura que el fondo del encabezado sea blanco
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 0, $this->GetPageWidth(), 35, 'F');
        
        // Asumiendo que el logo está en /public/img/Logo.png
        $logo_path = __DIR__ . '/img/Logo.png'; 
        
        // 1. Logo (Si existe)
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 10, 8, 20); // Posición y tamaño
        }
        
        // 2. Título principal
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(self::COLOR_PRIMARY[0], self::COLOR_PRIMARY[1], self::COLOR_PRIMARY[2]);
        $this->SetXY(40, 10); // Mover a la derecha del logo
        $this->Cell(150, 8, utf8_decode('FICHA TÉCNICA DEL EQUIPO'), 0, 1, 'C');
        
        // 3. Subtítulo de fecha
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(self::COLOR_TEXT_MUTED[0], self::COLOR_TEXT_MUTED[1], self::COLOR_TEXT_MUTED[2]);
        $this->SetX(40);
        $this->Cell(150, 5, utf8_decode('Sistema de Inventario - Generado el ' . date('d/m/Y H:i')), 0, 1, 'C');
        
        // 4. Línea divisoria de color institucional
        $this->SetDrawColor(self::COLOR_PRIMARY[0], self::COLOR_PRIMARY[1], self::COLOR_PRIMARY[2]);
        $this->SetLineWidth(0.8);
        $this->Line(10, 30, 200, 30);
        $this->Ln(8);
    }

    // Pie de página
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(self::COLOR_TEXT_MUTED[0], self::COLOR_TEXT_MUTED[1], self::COLOR_TEXT_MUTED[2]);
        
        $footer_text = utf8_decode('Documento generado automáticamente por el Sistema de Inventario | Página ') . $this->PageNo() . '/{nb}';
        $this->Cell(0, 10, $footer_text, 0, 0, 'R');
    }

    // Función para añadir títulos de sección (Barra de color)
    function AddSectionTitle($title, $color) {
        $this->SetFillColor($color[0], $color[1], $color[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(180, 7, utf8_decode('  ' . $title), 0, 1, 'L', true);
        $this->Ln(4);
    }

    // Función para añadir filas de información (Etiqueta en color, Valor en negro)
    function AddInfoRow($label, $value, $is_bold_value = false) {
        $label_width = 60;
        $row_height = 6;
        $current_x = $this->GetX();
        
        // 1. Imprimir Etiqueta (En color Primary)
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(self::COLOR_PRIMARY[0], self::COLOR_PRIMARY[1], self::COLOR_PRIMARY[2]);
        $this->Cell($label_width, $row_height, utf8_decode($label . ':'), 0, 0, 'L');
        
        // 2. Imprimir Valor
        if ($is_bold_value) {
            $this->SetFont('Arial', 'B', 11);
        } else {
            $this->SetFont('Arial', '', 10);
        }
        $this->SetTextColor(self::COLOR_TEXT_DARK[0], self::COLOR_TEXT_DARK[1], self::COLOR_TEXT_DARK[2]);
        $this->Cell(0, $row_height, utf8_decode($value), 0, 1, 'L');

        // Línea de separación sutil para cada fila
        $this->SetDrawColor(220, 220, 220); 
        $this->SetLineWidth(0.2);
        $this->Line($current_x, $this->GetY(), 200, $this->GetY());
    }
    
    // Función para añadir texto multilinea
    function AddMultiLineText($text) {
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(self::COLOR_TEXT_DARK[0], self::COLOR_TEXT_DARK[1], self::COLOR_TEXT_DARK[2]);
        $this->MultiCell(180, 6, utf8_decode($text), 0, 'J'); 
        $this->Ln(3);
    }

    public function CheckForNewPage($element_height) {
        if ($this->GetY() + $element_height > $this->PageBreakTrigger) {
            $this->AddPage();
        }
    }
}
// --------------------------------------------------------

// Verificar si se proporcionó un ID de equipo
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de equipo no proporcionado");
}

$id_equipo = (int)$_GET['id'];

// --- OBTENCIÓN DE DATOS DEL EQUIPO (Consulta SQL) ---
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

$stmt->close();
$conexion->close();


// --------------------------------------------------------
// --- GENERACIÓN DEL PDF CON FPDF ---
// --------------------------------------------------------

$pdf = new PDF();
$pdf->AliasNbPages(); 
$pdf->AddPage();


// --- INFORMACIÓN BÁSICA DEL EQUIPO ---
$pdf->AddSectionTitle('INFORMACIÓN GENERAL DEL EQUIPO', PDF::COLOR_PRIMARY);

$pdf->AddInfoRow('Código de Inventario', $equipo['codigo_inventario'], true);
$pdf->AddInfoRow('Número de Serie', $equipo['numero_serie'] ?: 'No especificado');
$pdf->AddInfoRow('Tipo de Equipo', $equipo['tipo_equipo_nombre'] ?: 'No especificado');
$pdf->AddInfoRow('Marca', $equipo['marca_nombre'] ?: 'No especificado');
$pdf->AddInfoRow('Modelo', $equipo['modelo_nombre'] ?: 'No especificado');
$pdf->AddInfoRow('Estado', $equipo['estado'] ?: 'No especificado');
$pdf->AddInfoRow('Sucursal', $equipo['sucursal_nombre'] ?: 'No especificado');
$pdf->AddInfoRow('Tipo de Adquisición', $equipo['tipo_adquisicion'] ?: 'No especificado');
$pdf->Ln(5);


// --- INFORMACIÓN DE ADQUISICIÓN ---
$pdf->AddSectionTitle('INFORMACIÓN DE ADQUISICIÓN', PDF::COLOR_PRIMARY);
$fecha_adquisicion = ($equipo['fecha_adquisicion'] && $equipo['fecha_adquisicion'] != '0000-00-00') ? date('d/m/Y', strtotime($equipo['fecha_adquisicion'])) : 'No especificado';

$pdf->AddInfoRow('Fecha de Adquisición', $fecha_adquisicion);
$pdf->AddInfoRow('Proveedor', $equipo['proveedor'] ?: 'No especificado');
$pdf->Ln(5);


// --- INFORMACIÓN DE ASIGNACIÓN ---
$pdf->AddSectionTitle('ASIGNADO A', PDF::COLOR_SECONDARY);
if ($equipo['empleado_nombres']) {
    $pdf->AddInfoRow('Empleado', $equipo['empleado_nombres'] . ' ' . $equipo['empleado_apellidos'], true);
    $pdf->AddInfoRow('DNI', $equipo['empleado_dni'] ?: 'No especificado');
    $pdf->AddInfoRow('Área', $equipo['area_nombre'] ?: 'No especificado');
    $pdf->AddInfoRow('Cargo', $equipo['cargo_nombre'] ?: 'No especificado');
} else {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(231, 76, 60);
    $pdf->Cell(0, 6, utf8_decode('⚠️ Este equipo no tiene una asignación activa.'), 0, 1, 'C');
}
$pdf->Ln(5);


// --- CARACTERÍSTICAS ---
if (!empty($equipo['caracteristicas'])) {
    $pdf->AddSectionTitle('CARACTERÍSTICAS', PDF::COLOR_PRIMARY); 
    $pdf->AddMultiLineText($equipo['caracteristicas']);
    $pdf->Ln(5);
}

// --- OBSERVACIONES ---
if (!empty($equipo['observaciones'])) {
    $pdf->AddSectionTitle('OBSERVACIONES', PDF::COLOR_PRIMARY); 
    $pdf->AddMultiLineText($equipo['observaciones']);
    $pdf->Ln(5);
}


// --------------------------------------------------------
// --- CÓDIGO QR AL FINAL ---
// --------------------------------------------------------

$pdf->AddSectionTitle('CÓDIGO QR Y ENLACE DE DETALLES', PDF::COLOR_PRIMARY);

// 1. Preparar datos completos para el contenido del QR
$qr_data_content = "FICHA TÉCNICA - " . ($equipo['codigo_inventario'] ?: 'N/A') . "\n";
$qr_data_content .= "Tipo: " . ($equipo['tipo_equipo_nombre'] ?: 'N/A') . "\n";
$qr_data_content .= "Marca: " . ($equipo['marca_nombre'] ?: 'N/A') . " / Modelo: " . ($equipo['modelo_nombre'] ?: 'N/A') . "\n";
$qr_data_content .= "Serie: " . ($equipo['numero_serie'] ?: 'N/A') . "\n";
$qr_data_content .= "Estado: " . ($equipo['estado'] ?: 'N/A') . "\n";
$base_url = "http://" . $_SERVER['HTTP_HOST'] . str_replace("/public/generar_pdf_equipo.php", "", $_SERVER['PHP_SELF']);
$qr_data_content .= "URL Detalles: " . $base_url . "/public/equipos_detalles.php?id=" . $id_equipo;

$qr_generated = false;
$temp_dir = __DIR__ . '/temp_qr/';
$file_path = '';

if (!file_exists($temp_dir)) {
    @mkdir($temp_dir, 0777, true); 
}

$file_name = 'qr_' . $equipo['codigo_inventario'] . '_' . time() . '.png';
$file_path = $temp_dir . $file_name;

if (is_writable($temp_dir) && @file_exists(__DIR__ . '/../phpqrcode/qrlib.php')) {
    QRcode::png($qr_data_content, $file_path, QR_ECLEVEL_H, 8, 2); 
    $qr_generated = file_exists($file_path);
} 

// Dibujar el QR
$qr_size_pdf = 50;
$pdf->CheckForNewPage($qr_size_pdf + 15); 

$pdf->SetY($pdf->GetY() + 5);

if ($qr_generated && file_exists($file_path)) {
    $page_width = $pdf->GetPageWidth();
    $qr_x_pos = ($page_width - $qr_size_pdf) / 2; 

    // Posicionar imagen QR centrada
    $pdf->Image($file_path, $qr_x_pos, $pdf->GetY(), $qr_size_pdf, $qr_size_pdf, 'PNG'); 
    
    $pdf->SetY($pdf->GetY() + $qr_size_pdf + 4); 
    
    // Código de Inventario bajo el QR (Centrado)
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(PDF::COLOR_PRIMARY[0], PDF::COLOR_PRIMARY[1], PDF::COLOR_PRIMARY[2]);
    $pdf->Cell(0, 5, utf8_decode('CÓDIGO: ' . $equipo['codigo_inventario']), 0, 1, 'C');
    
    // Instrucción (Centrado)
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(PDF::COLOR_TEXT_MUTED[0], PDF::COLOR_TEXT_MUTED[1], PDF::COLOR_TEXT_MUTED[2]);
    $pdf->Cell(0, 4, utf8_decode('Escanee para obtener todos los detalles técnicos y actualizados del equipo.'), 0, 1, 'C');

} else {
    // Mensaje de error si falla la generación
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(231, 76, 60); 
    $pdf->Cell(0, 10, utf8_decode('ERROR: NO SE PUDO GENERAR EL CÓDIGO QR. REVISE PERMISOS DE LA CARPETA temp_qr.'), 0, 1, 'C');
}


// --- SALIDA DEL PDF ---
$pdf->Output('I', 'Ficha_Tecnica_' . $equipo['codigo_inventario'] . '.pdf');

// --------------------------------------------------------
// --- LIMPIEZA DEL ARCHIVO TEMPORAL ---
// --------------------------------------------------------
if ($qr_generated && file_exists($file_path)) {
    @unlink($file_path);
}
?>