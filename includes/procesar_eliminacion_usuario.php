<?php
require_once '../config/database.php';

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../public/gestion_usuarios.php?status=error_delete&msg=ID de usuario no válido.");
    exit();
}

$id_usuario = (int)$_GET['id'];
$status = 'error_delete';
$message = 'Error desconocido al eliminar el usuario.';

try {
    // Asumo que los usuarios no tienen dependencias críticas fuera de las tablas de usuarios y roles.
    // Si la tabla de usuarios está relacionada con otra data que no se debe eliminar (ej. logs), 
    // debería usar eliminación lógica (UPDATE usuarios SET estado = 'Inactivo').
    
    // Usaremos Eliminación Física simple
    $sql_delete = "DELETE FROM usuarios WHERE id = ?";
    $stmt_delete = $conexion->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_usuario);
    
    if ($stmt_delete->execute()) {
        if ($stmt_delete->affected_rows > 0) {
            $status = 'success_delete';
            $message = 'Usuario eliminado exitosamente.';
        } else {
            $message = 'Usuario no encontrado o ya eliminado.';
        }
    } else {
        throw new Exception("Error en la ejecución de la consulta: " . $stmt_delete->error);
    }
    $stmt_delete->close();

} catch (Exception $e) {
    // Si la eliminación física falla debido a restricciones de clave foránea (FK), 
    // intentamos una eliminación lógica para mantener la integridad (ej. si el usuario es un auditor en un log).
    
    // Intento de eliminación lógica si la física falla (opcional, pero más robusto)
    try {
        $sql_logic_delete = "UPDATE usuarios SET estado = 'Inactivo' WHERE id = ?";
        $stmt_logic = $conexion->prepare($sql_logic_delete);
        $stmt_logic->bind_param("i", $id_usuario);
        
        if ($stmt_logic->execute() && $stmt_logic->affected_rows > 0) {
            $status = 'success_logic_delete';
            $message = 'Usuario marcado como inactivo debido a historial o dependencias.';
        } else {
             $message = 'No se pudo eliminar ni inactivar el usuario.';
        }
        $stmt_logic->close();
        
    } catch (Exception $e2) {
        $message = 'Fallo en el proceso de eliminación: ' . $e->getMessage() . " | Error secundario: " . $e2->getMessage();
    }
}

$conexion->close();
header("Location: ../public/gestion_usuarios.php?status={$status}&msg=" . urlencode($message));
exit();