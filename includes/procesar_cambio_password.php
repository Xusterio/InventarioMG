<?php
session_start();
require_once '../config/database.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validar coincidencia de nuevas contraseñas
    if ($new_password !== $confirm_password) {
        header("Location: ../public/cambiar_password.php?error=match");
        exit();
    }

    // 2. Obtener hash actual
    $stmt = $conexion->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    // 3. Verificar contraseña actual y actualizar
    if ($usuario && password_verify($current_password, $usuario['password'])) {
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_hashed_password, $user_id);

        if ($update_stmt->execute()) {
            // Éxito: Redirige con parámetro status=success
            header("Location: ../public/cambiar_password.php?status=success");
        } else {
            header("Location: ../public/cambiar_password.php?error=db");
        }
        $update_stmt->close();
    } else {
        header("Location: ../public/cambiar_password.php?error=current");
    }
    $stmt->close();
    exit();
}