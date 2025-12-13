<?php
require_once '../config/database.php';

// Iniciar sesión y validar permisos si es necesario
// Asumo que la validación de sesión y permisos se maneja al inicio.
// require_once '../templates/header_lite.php'; 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../public/asignaciones.php?status=error_delete&msg=ID de asignación no válido.");
    exit();
}

$id_asignacion = (int)$_GET['id'];
$status = 'error_delete';
$message = 'Error desconocido al procesar la eliminación.';

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Obtener el ID del equipo asociado a esta asignación
    $sql_get_equipo = "SELECT id_equipo FROM asignaciones WHERE id = ?";
    $stmt_get = $conexion->prepare($sql_get_equipo);
    $stmt_get->bind_param("i", $id_asignacion);
    $stmt_get->execute();
    $result_get = $stmt_get->get_result();
    
    if ($result_get->num_rows === 0) {
        throw new Exception("Asignación no encontrada.");
    }
    
    $row = $result_get->fetch_assoc();
    $id_equipo = $row['id_equipo'];
    $stmt_get->close();

    // 2. Eliminar la asignación (o marcar como inactiva si usa eliminación lógica)
    // Usaremos eliminación física ya que la asignación ya está en el historial (devoluciones)
    $sql_delete_asignacion = "DELETE FROM asignaciones WHERE id = ?";
    $stmt_delete = $conexion->prepare($sql_delete_asignacion);
    $stmt_delete->bind_param("i", $id_asignacion);
    
    if (!$stmt_delete->execute()) {
        throw new Exception("No se pudo eliminar el registro de asignación.");
    }
    $stmt_delete->close();

    // 3. Actualizar el estado del equipo a 'Disponible'
    $estado_disponible = 'Disponible';
    $sql_update_equipo = "UPDATE equipos SET estado = ? WHERE id = ?";
    $stmt_update = $conexion->prepare($sql_update_equipo);
    $stmt_update->bind_param("si", $estado_disponible, $id_equipo);

    if (!$stmt_update->execute()) {
        throw new Exception("No se pudo actualizar el estado del equipo.");
    }
    $stmt_update->close();

    $conexion->commit();
    $status = 'success_delete';
    $message = 'Asignación eliminada y equipo marcado como Disponible.';

} catch (Exception $e) {
    $conexion->rollback();
    $message = 'Error al eliminar la asignación: ' . $e->getMessage();
}

$conexion->close();
header("Location: ../public/asignaciones.php?status={$status}&msg=" . urlencode($message));
exit();