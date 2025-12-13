<?php
session_start();

// Rutas a las librerías y configuración
require_once '../config/database.php';
require_once '../fpdf/fpdf.php';

// Definición de la clase FPDF personalizada
class PDF extends FPDF
{
    protected $id_sucursal_usuario;
    protected $es_admin_general;

    function __construct($orientation='P', $unit='mm', $size='A4', $id_sucursal_usuario)
    {
        parent::__construct($orientation, $unit, $size);
        $this->id_sucursal_usuario = $id_sucursal_usuario;
        $this->es_admin_general = ($id_sucursal_usuario === null);
        $this->SetTitle(iconv('UTF-8', 'windows-1252', 'Inventario de Equipos'));
        $this->SetAuthor('Sistema de Inventario');
    }

    // Encabezado con Logo y Título
    function Header()
    {
        // 1. Logo (Asumo que la ruta es correcta relativa a este script en /public/)
        $logo_path = 'img/Logo.png'; 
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 10, 8, 30); // X=10, Y=8, Ancho=30
        }

        // 2. Título del Reporte
        $this->SetFont('Arial', 'B', 14);
        $this->SetX(45); // Mover X después del logo
        $titulo = 'INVENTARIO DE EQUIPOS DE T.I.';
        $subtitulo = 'I.E.E. ALMIRANTE MIGUEL GRAU SEMINARIO';
        
        $this->Cell(130, 7, iconv('UTF-8', 'windows-1252', $titulo), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetX(45);
        $this->Cell(130, 5, iconv('UTF-8', 'windows-1252', $subtitulo), 0, 1, 'C');
        
        // 3. Fecha de Generación
        $this->SetFont('Arial', '', 8);
        $this->SetXY(180, 10); // Derecha superior
        $this->Cell(20, 5, 'Fecha: ' . date('d/m/Y'), 0, 1, 'R');
        
        // 4. Línea divisoria y espacio extra
        
        // CORRECCIÓN CLAVE: Dibuja la línea divisoria más abajo (Y=40)
        $line_y = 40;
        $this->SetLineWidth(0.5);
        $this->Line(10, $line_y, 200, $line_y); 
        
        // Mover Y a donde debe empezar la cabecera de la tabla (42mm), dejando espacio.
        $this->SetY($line_y + 2); 
        
        // Cabecera de la Tabla (Empieza en Y=42)
        $this->SetFillColor(63, 81, 181); // Azul oscuro (RGB: 3f51b5)
        $this->SetTextColor(255, 255, 255); // Blanco
        $this->SetFont('Arial', 'B', 8); 
        $h = 6; 
        
        // Definir anchos de columna (Ajustados para el nuevo diseño de tabla separada)
        $w = [];
        if ($this->es_admin_general) {
            $w[] = 25; // Sucursal
        }
        $w[] = 25; // Código Inv.
        $w[] = 25; // Tipo
        $w[] = 25; // Marca
        $w[] = 25; // Modelo
        $w[] = 30; // N° Serie
        $w[] = 15; // Estado
        $w[] = 25; // Proveedor
        
        // Títulos de columnas
        $header_titles = [];
        if ($this->es_admin_general) {
            $header_titles[] = 'SUCURSAL';
        }
        $header_titles[] = 'CÓDIGO INV.';
        $header_titles[] = 'TIPO';
        $header_titles[] = 'MARCA';
        $header_titles[] = 'MODELO';
        $header_titles[] = 'N° SERIE';
        $header_titles[] = 'ESTADO';
        $header_titles[] = 'PROVEEDOR'; 
        
        // Ajustar el ancho para que la suma sea 190mm (margen de 10mm a cada lado)
        $total_w = array_sum($w);
        if ($total_w != 190) {
            // Si la suma no es 190, ajustamos la última columna para rellenar el espacio restante (si existe)
            $w[count($w) - 1] += (190 - $total_w);
        }

        // Imprimir la cabecera
        for ($i = 0; $i < count($header_titles); $i++) {
            $this->Cell($w[$i], $h, iconv('UTF-8', 'windows-1252', $header_titles[$i]), 1, 0, 'C', true);
        }
        $this->Ln();

        // Restaurar colores y fuentes
        $this->SetTextColor(0);
        $this->SetDrawColor(100, 100, 100);
        $this->SetFillColor(240, 240, 240);
        $this->SetFont('Arial', '', 8);
    }

    // Pie de página
    function Footer()
    {
        // Posición: a 1.5 cm del final
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, iconv('UTF-8', 'windows-1252', 'Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    // Función para colorear la fila según el estado
    function RowColor($estado) {
        switch ($estado) {
            case 'Disponible':
                $this->SetFillColor(212, 237, 218); // Verde claro (d4edda)
                break;
            case 'Asignado':
                $this->SetFillColor(204, 229, 255); // Azul claro (cce5ff)
                break;
            case 'En Reparacion':
                $this->SetFillColor(255, 243, 205); // Amarillo claro (fff3cd)
                break;
            case 'De Baja':
                $this->SetFillColor(248, 215, 218); // Rojo claro (f8d7da)
                break;
            default:
                $this->SetFillColor(255, 255, 255); // Blanco
                break;
        }
    }
}

// --------------------
// 4. Lógica principal
// --------------------

// Se utiliza la conexión previamente definida en database.php
global $conexion;
$id_sucursal_usuario = $_SESSION['user_sucursal_id'] ?? null;
$es_admin_general = ($id_sucursal_usuario === null);

// Construir filtro de sucursal si no es admin
$filtro_sucursal_sql = "";
if (!$es_admin_general) {
    $filtro_sucursal_sql = " AND e.id_sucursal = " . (int)$id_sucursal_usuario;
}

// Preparar los filtros adicionales recibidos por GET (Nombres/Texto)
$filtro_estado = $_GET['estado'] ?? '';
$filtro_sucursal_nombre = $_GET['sucursal'] ?? '';
$filtro_tipo_nombre = $_GET['tipo'] ?? '';
$filtro_marca_nombre = $_GET['marca'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

$where_clauses = [];
$params = [];
$types = "";

if (!empty($filtro_estado)) {
    $where_clauses[] = "e.estado = ?"; $types .= "s"; $params[] = $filtro_estado;
}
if ($es_admin_general && !empty($filtro_sucursal_nombre)) {
    $where_clauses[] = "s.nombre = ?"; $types .= "s"; $params[] = $filtro_sucursal_nombre;
}
if (!empty($filtro_tipo_nombre)) {
    $where_clauses[] = "te.nombre = ?"; $types .= "s"; $params[] = $filtro_tipo_nombre;
}
if (!empty($filtro_marca_nombre)) {
    $where_clauses[] = "m.nombre = ?"; $types .= "s"; $params[] = $filtro_marca_nombre;
}
if (!empty($filtro_busqueda)) {
    // Buscar en código de inventario o número de serie
    $where_clauses[] = "(e.codigo_inventario LIKE ? OR e.numero_serie LIKE ?)";
    $types .= "ss";
    $like_busqueda = "%" . $filtro_busqueda . "%";
    array_push($params, $like_busqueda, $like_busqueda);
}

$sql_data = "SELECT 
    e.codigo_inventario, 
    te.nombre AS tipo_equipo, 
    m.nombre AS marca, 
    mo.nombre AS modelo, 
    e.numero_serie, 
    e.estado,
    s.nombre AS sucursal,
    e.proveedor
FROM equipos e
JOIN tipos_equipo te ON e.id_tipo_equipo = te.id
JOIN marcas m ON e.id_marca = m.id
JOIN modelos mo ON e.id_modelo = mo.id
JOIN sucursales s ON e.id_sucursal = s.id
WHERE 1=1 {$filtro_sucursal_sql}";

if (!empty($where_clauses)) {
    $sql_data .= " AND " . implode(" AND ", $where_clauses);
}

$sql_data .= " ORDER BY e.codigo_inventario";

// Usar prepared statements para la seguridad
$stmt = $conexion->prepare($sql_data);

if (!empty($params)) {
    // Manejar bind_param de forma dinámica
    $bind_params = array_merge([$types], $params);
    $stmt->bind_param(...$bind_params);
}
$stmt->execute();
$resultado = $stmt->get_result();


// 4.2. Generación del PDF
$pdf = new PDF('P', 'mm', 'A4', $id_sucursal_usuario);
$pdf->AliasNbPages();
$pdf->AddPage();

// Anchos de columna (Ajustados para el nuevo diseño, deben coincidir con Header)
$w = [];
if ($es_admin_general) {
    $w[] = 25; // Sucursal
}
$w[] = 25; // Código Inv.
$w[] = 25; // Tipo
$w[] = 25; // Marca
$w[] = 25; // Modelo
$w[] = 30; // N° Serie
$w[] = 15; // Estado
$w[] = 25; // Proveedor

// Ajustar el ancho para que la suma sea 190mm (margen de 10mm a cada lado)
$total_w = array_sum($w);
if ($total_w != 190) {
    $w[count($w) - 1] += (190 - $total_w);
}

$h = 6; // Altura de la fila

if ($resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $fill = true; // Usar relleno para el color de fondo

        // Aplicar color de fondo basado en el estado
        $pdf->RowColor($row['estado']);

        // Columnas
        $col_data = [];
        if ($es_admin_general) {
            $col_data[] = iconv('UTF-8', 'windows-1252', $row['sucursal']);
        }
        $col_data[] = iconv('UTF-8', 'windows-1252', $row['codigo_inventario']);
        $col_data[] = iconv('UTF-8', 'windows-1252', $row['tipo_equipo']);
        $col_data[] = iconv('UTF-8', 'windows-1252', $row['marca']);
        $col_data[] = iconv('UTF-8', 'windows-1252', $row['modelo']);
        $col_data[] = iconv('UTF-8', 'windows-1252', $row['numero_serie']);
        $col_data[] = iconv('UTF-8', 'windows-1252', $row['estado']);
        $col_data[] = iconv('UTF-8', 'windows-1252', $row['proveedor']); // Añadir Proveedor

        for ($i = 0; $i < count($col_data); $i++) {
            // Cell(ancho, alto, texto, borde, ln, alineación, relleno, link)
            $pdf->Cell($w[$i], $h, $col_data[$i], 1, 0, 'L', $fill);
        }
        $pdf->Ln();
    }
} else {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell(array_sum($w), $h, iconv('UTF-8', 'windows-1252', 'No se encontraron equipos con los filtros seleccionados.'), 1, 1, 'C', true);
}

$pdf->Output('I', 'reporte_equipos_' . date('Ymd_His') . '.pdf');
$conexion->close();

?>