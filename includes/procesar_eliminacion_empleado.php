<?php
require_once '../config/database.php';

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../public/empleados.php?status=error_delete&msg=ID de empleado no válido.");
    exit();
}

$id_empleado = (int)$_GET['id'];
$status = 'error_delete';
$message = 'Error desconocido al eliminar el empleado.';
$delete_type = 'Eliminación Lógica'; // Por defecto

try {
    // 1. Verificar si el empleado tiene asignaciones activas
    $sql_check_active = "SELECT id FROM asignaciones WHERE id_empleado = ? AND estado_asignacion = 'Activa'";
    $stmt_active = $conexion->prepare($sql_check_active);
    $stmt_active->bind_param("i", $id_empleado);
    $stmt_active->execute();
    
    if ($stmt_active->get_result()->num_rows > 0) {
        // Si tiene asignaciones activas, no se puede eliminar.
        $message = "El empleado tiene una asignación de equipo activa y no puede ser eliminado.";
        $stmt_active->close();
        // Redirigir inmediatamente con error
        header("Location: ../public/empleados.php?status=error_delete&msg=" . urlencode($message));
        exit();
    }
    $stmt_active->close();


    // 2. Determinar si existe historial (asignaciones inactivas)
    $sql_check_history = "SELECT id FROM asignaciones WHERE id_empleado = ?";
    $stmt_history = $conexion->prepare($sql_check_history);
    $stmt_history->bind_param("i", $id_empleado);
    $stmt_history->execute();
    $has_history = ($stmt_history->get_result()->num_rows > 0);
    $stmt_history->close();


    if ($has_history) {
        // Eliminación Lógica: Marcar el empleado como Inactivo o 'De Baja'
        $sql_delete = "UPDATE empleados SET estado = 'Inactivo' WHERE id = ?";
        $delete_type = 'Inactivación';
    } else {
        // Eliminación Física: No hay historial que proteger
        $sql_delete = "DELETE FROM empleados WHERE id = ?";
        $delete_type = 'Eliminación Física';
    }
    
    $stmt_delete = $conexion->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_empleado);
    
    if ($stmt_delete->execute()) {
        $status = ($delete_type == 'Inactivación') ? 'success_logic_delete' : 'success_delete';
        $message = "Empleado procesado con éxito. ({$delete_type})";
    } else {
        throw new Exception("Error en la ejecución de la consulta: " . $stmt_delete->error);
    }
    $stmt_delete->close();

} catch (Exception $e) {
    $message = 'Fallo en el proceso de eliminación: ' . $e->getMessage();
}

$conexion->close();
header("Location: ../public/empleados.php?status={$status}&msg=" . urlencode($message));
exit();