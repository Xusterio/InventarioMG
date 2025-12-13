<?php
session_start();
require_once '../config/database.php';
require_once '../fpdf/fpdf.php';

if (!isset($_GET['id_asignacion'])) {
    die("Error: ID de asignación no especificado.");
}

$id_asignacion = (int)$_GET['id_asignacion'];

// Consulta para obtener todos los datos necesarios para el acta
$sql = "SELECT 
            a.fecha_devolucion, a.observaciones_devolucion,
            e.codigo_inventario, e.numero_serie,
            t.nombre AS tipo_nombre,
            ma.nombre as marca_nombre, mo.nombre as modelo_nombre,
            emp.dni, emp.nombres, emp.apellidos,
            c.nombre AS cargo_nombre
        FROM asignaciones a
        JOIN equipos e ON a.id_equipo = e.id
        JOIN empleados emp ON a.id_empleado = emp.id
        JOIN tipos_equipo t ON e.id_tipo_equipo = t.id
        JOIN marcas ma ON e.id_marca = ma.id
        JOIN modelos mo ON e.id_modelo = mo.id
        LEFT JOIN cargos c ON emp.id_cargo = c.id
        WHERE a.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_asignacion);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$datos) {
    die("Error: Asignación no encontrada.");
}

$usuario_ti_recibe = $_SESSION['user_nombre'];

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, utf8_decode('Acta de Devolución de Equipo'), 0, 1, 'C');
        $this->Ln(10);
    }
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 10, 'Fecha de Devolucion: ' . date('d/m/Y H:i', strtotime($datos['fecha_devolucion'])), 0, 1, 'R');
$pdf->Ln(5);

// --- INICIO: SECCIÓN DE DATOS DEL EQUIPO (AÑADIDA) ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Datos del Equipo Devuelto', 1, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(45, 10, utf8_decode('Código de Inventario:'), 1, 0);
$pdf->Cell(0, 10, utf8_decode($datos['codigo_inventario']), 1, 1);
$pdf->Cell(45, 10, 'Tipo de Equipo:', 1, 0);
$pdf->Cell(0, 10, utf8_decode($datos['tipo_nombre']), 1, 1);
$pdf->Cell(45, 10, 'Marca y Modelo:', 1, 0);
$pdf->Cell(0, 10, utf8_decode($datos['marca_nombre'] . ' ' . $datos['modelo_nombre']), 1, 1);
$pdf->Cell(45, 10, utf8_decode('Número de Serie:'), 1, 0);
$pdf->Cell(0, 10, utf8_decode($datos['numero_serie']), 1, 1);
$pdf->Ln(10);
// --- FIN: SECCIÓN DE DATOS DEL EQUIPO ---

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, utf8_decode('Detalles de la Devolución'), 1, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(45, 8, 'Equipo Devuelto por:', 1, 0);
$pdf->Cell(0, 8, utf8_decode($datos['apellidos'] . ', ' . $datos['nombres']), 1, 1);
$pdf->Cell(45, 8, 'Equipo Recibido por:', 1, 0);
$pdf->Cell(0, 8, utf8_decode($usuario_ti_recibe), 1, 1);
$pdf->Cell(45, 8, 'Observaciones:', 'TLB');
$pdf->MultiCell(0, 8, utf8_decode($datos['observaciones_devolucion']), 'TRB');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 5, utf8_decode("Se deja constancia de que el equipo ha sido devuelto por el empleado y recibido por el área de TI en la fecha y con las observaciones indicadas. Ambas partes firman en señal de conformidad."), 0, 'J');

// --- SECCIÓN DE EVIDENCIA FOTOGRÁFICA ---
$stmt_img = $conexion->prepare("SELECT imagen_devolucion_1, imagen_devolucion_2, imagen_devolucion_3 FROM asignaciones WHERE id = ?");
$stmt_img->bind_param("i", $id_asignacion);
$stmt_img->execute();
$imagenes = $stmt_img->get_result()->fetch_assoc();
$stmt_img->close();
$imagenes_adjuntas = array_filter([$imagenes['imagen_devolucion_1'] ?? null, $imagenes['imagen_devolucion_2'] ?? null, $imagenes['imagen_devolucion_3'] ?? null]);
if (!empty($imagenes_adjuntas)) {
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, utf8_decode('Evidencia Fotográfica Adjunta'), 0, 1);
    $pdf->SetFont('Arial', '', 9);
    $pdf->MultiCell(0, 5, utf8_decode("Se adjuntan " . count($imagenes_adjuntas) . " imagen(es) como evidencia del estado del equipo al momento de la devolución. Estos archivos se encuentran almacenados en el sistema."), 0, 'J');
}

$pdf->Ln(15);

$pdf->Cell(95, 10, '_________________________', 0, 0, 'C');
$pdf->Cell(95, 10, '_________________________', 0, 1, 'C');
$pdf->Cell(95, 10, 'Firma del Empleado', 0, 0, 'C');
$pdf->Cell(95, 10, 'Recibido por (TI)', 0, 1, 'C');

$pdf->Output('I', 'Acta_Devolucion_' . $datos['codigo_inventario'] . '.pdf');