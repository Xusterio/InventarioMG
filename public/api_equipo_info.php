<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';

$id_equipo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_equipo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de equipo no válido']);
    exit();
}

// Obtener información completa del equipo según tu estructura de BD
$sql = "SELECT 
    e.id,
    e.codigo_inventario,
    e.caracteristicas,
    e.numero_serie,
    e.estado,
    e.tipo_adquisicion,
    e.fecha_adquisicion,
    e.proveedor,
    ma.nombre as marca,
    mo.nombre as modelo,
    t.nombre as tipo,
    s.nombre as sucursal,
    s.direccion as ubicacion
FROM equipos e
LEFT JOIN marcas ma ON e.id_marca = ma.id
LEFT JOIN modelos mo ON e.id_modelo = mo.id
LEFT JOIN tipos_equipo t ON e.id_tipo_equipo = t.id
LEFT JOIN sucursales s ON e.id_sucursal = s.id
WHERE e.id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_equipo);
$stmt->execute();
$result = $stmt->get_result();
$equipo = $result->fetch_assoc();

if ($equipo) {
    echo json_encode([
        'success' => true,
        'data' => $equipo,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Equipo no encontrado']);
}
?>