<?php
require_once '../config/database.php';

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../public/equipos.php?status=error_delete&msg=ID de equipo no válido.");
    exit();
}

$id_equipo = (int)$_GET['id'];
$status = 'error_delete';
$message = 'Error desconocido al eliminar el equipo.';
$delete_type = 'Eliminación Lógica'; // Por defecto

try {
    // 1. Verificar si el equipo tiene asignaciones activas (ya validado en equipos.php, pero por seguridad)
    $sql_check_active = "SELECT id FROM asignaciones WHERE id_equipo = ? AND estado_asignacion = 'Activa'";
    $stmt_active = $conexion->prepare($sql_check_active);
    $stmt_active->bind_param("i", $id_equipo);
    $stmt_active->execute();
    
    if ($stmt_active->get_result()->num_rows > 0) {
        // Esto no debería suceder si el botón está deshabilitado, pero es un seguro.
        $message = "El equipo no puede ser eliminado porque tiene una asignación activa.";
        $stmt_active->close();
        header("Location: ../public/equipos.php?status=error_delete&msg=" . urlencode($message));
        exit();
    }
    $stmt_active->close();


    // 2. Determinar si existe historial de asignaciones (inactivas)
    $sql_check_history = "SELECT id FROM asignaciones WHERE id_equipo = ?";
    $stmt_history = $conexion->prepare($sql_check_history);
    $stmt_history->bind_param("i", $id_equipo);
    $stmt_history->execute();
    $has_history = ($stmt_history->get_result()->num_rows > 0);
    $stmt_history->close();


    if ($has_history) {
        // Eliminación Lógica: Marcar el equipo como 'De Baja'
        $sql_delete = "UPDATE equipos SET estado = 'De Baja' WHERE id = ?";
        $delete_type = 'Inactivación/De Baja';
    } else {
        // Eliminación Física: No hay historial que proteger
        $sql_delete = "DELETE FROM equipos WHERE id = ?";
        $delete_type = 'Eliminación Física';
    }
    
    $stmt_delete = $conexion->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_equipo);
    
    if ($stmt_delete->execute()) {
        $status = ($delete_type == 'Inactivación/De Baja') ? 'success_logic_delete' : 'success_delete';
        $message = "Equipo procesado con éxito. ({$delete_type})";
    } else {
        throw new Exception("Error en la ejecución de la consulta: " . $stmt_delete->error);
    }
    $stmt_delete->close();

} catch (Exception $e) {
    $message = 'Fallo en el proceso de eliminación: ' . $e->getMessage();
}

$conexion->close();
header("Location: ../public/equipos.php?status={$status}&msg=" . urlencode($message));
exit();