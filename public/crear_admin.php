<?php
require_once '../config/database.php';

$nombre = 'Admin';
$email = 'admin@tuempresa.com';
$password_plano = 'admin123'; // Cambia esta contraseña
$id_rol = 1; // 1 = Administrador

// Hashear la contraseña de forma segura
$password_hash = password_hash($password_plano, PASSWORD_DEFAULT);

// Iniciar transacción
$conexion->begin_transaction();
try {
    // Insertar usuario
    $stmt_user = $conexion->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
    $stmt_user->bind_param("sss", $nombre, $email, $password_hash);
    $stmt_user->execute();
    $id_usuario = $stmt_user->insert_id;
    $stmt_user->close();

    // Asignar rol
    $stmt_role = $conexion->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, ?)");
    $stmt_role->bind_param("ii", $id_usuario, $id_rol);
    $stmt_role->execute();
    $stmt_role->close();

    // Confirmar transacción
    $conexion->commit();
    echo "¡Usuario administrador creado con éxito! <br>Email: {$email} <br>Password: {$password_plano} <br><b>YA PUEDES BORRAR ESTE ARCHIVO.</b>";

} catch (mysqli_sql_exception $exception) {
    $conexion->rollback();
    echo "Error al crear el usuario: " . $exception->getMessage();
}
?>