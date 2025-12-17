<?php
ob_start();
require_once '../templates/header.php';

// Solo los administradores pueden acceder
if ($_SESSION['user_rol'] !== 'Administrador') {
    echo "<div class='alert alert-danger'>Acceso denegado.</div>";
    require_once '../templates/footer.php';
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error_message = '';
$success_message = '';

if (!$id) {
    header("Location: gestion_usuarios.php");
    exit();
}

// Lógica de procesamiento del formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $id_rol = filter_input(INPUT_POST, 'id_rol', FILTER_VALIDATE_INT);
    $id_sucursal = filter_input(INPUT_POST, 'id_sucursal', FILTER_VALIDATE_INT) ?? null;

    if (empty($nombre) || empty($email) || !$id_rol) {
        $error_message = "Todos los campos obligatorios (Nombre, Email, Rol) deben ser llenados.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "El formato del email no es válido.";
    } else {
        $conexion->begin_transaction();
        $is_ok = false;

        try {
            // 1. Actualizar datos en la tabla 'usuarios'
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, email = ?, id_sucursal = ? WHERE id = ?");
            $stmt->bind_param("ssii", $nombre, $email, $id_sucursal, $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar el usuario: " . $stmt->error);
            }
            $stmt->close();
            
            // 2. Actualizar el rol en la tabla 'usuario_roles'
            // Primero, eliminar el rol actual
            $stmt_delete_rol = $conexion->prepare("DELETE FROM usuario_roles WHERE id_usuario = ?");
            $stmt_delete_rol->bind_param("i", $id);
            $stmt_delete_rol->execute();
            $stmt_delete_rol->close();
            
            // Luego, insertar el nuevo rol
            $stmt_insert_rol = $conexion->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, ?)");
            $stmt_insert_rol->bind_param("ii", $id, $id_rol);
            
            if (!$stmt_insert_rol->execute()) {
                throw new Exception("Error al reasignar el rol: " . $stmt_insert_rol->error);
            }
            $stmt_insert_rol->close();
            
            // 3. Commit de la transacción
            $conexion->commit();
            $is_ok = true;
            
        } catch (Exception $e) {
            $conexion->rollback();
            $error_message = "Error en la base de datos: " . $e->getMessage();
            if (strpos($error_message, 'Duplicate entry') !== false) {
                 $error_message = "El email '{$email}' ya está registrado por otro usuario.";
            }
        }
        
        if ($is_ok) {
            // Redirigir a la página principal de gestión de usuarios con mensaje de éxito
            ob_end_clean();
            header("Location: gestion_usuarios.php?status=success_edit");
            exit();
        }
    }
}

// Cargar datos del usuario
// CORRECCIÓN: Se cambia r.id_rol por ur.id_rol (que es el campo en la tabla intermedia) 
// o simplemente r.id si quieres el ID de la tabla de roles.
$stmt_usuario = $conexion->prepare("SELECT u.id, u.nombre, u.email, u.id_sucursal, ur.id_rol 
                                    FROM usuarios u 
                                    LEFT JOIN usuario_roles ur ON u.id = ur.id_usuario 
                                    LEFT JOIN roles r ON ur.id_rol = r.id
                                    WHERE u.id = ?");
$stmt_usuario->bind_param("i", $id);
$stmt_usuario->execute();
$usuario = $stmt_usuario->get_result()->fetch_assoc();
$stmt_usuario->close();

if (!$usuario) {
    ob_end_clean();
    header("Location: gestion_usuarios.php");
    exit();
}

// Cargar roles y sucursales
$roles = $conexion->query("SELECT id, nombre_rol FROM roles ORDER BY nombre_rol");
$sucursales = $conexion->query("SELECT id, nombre FROM sucursales WHERE estado = 'Activo' ORDER BY nombre");

$default_rol_id = $usuario['id_rol'] ?? null;
$default_sucursal_id = $usuario['id_sucursal'] ?? null;

// Si hubo error de POST, usar los valores del POST
$current_nombre = htmlspecialchars($_POST['nombre'] ?? $usuario['nombre']);
$current_email = htmlspecialchars($_POST['email'] ?? $usuario['email']);
$current_rol_id = $_POST['id_rol'] ?? $default_rol_id;
$current_sucursal_id = $_POST['id_sucursal'] ?? $default_sucursal_id;

?>

<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-person-badge me-2 text-primary"></i> Editar Usuario
        </h1>
        <a href="gestion_usuarios.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver a Usuarios
        </a>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 text-primary">
                        Editando: <?php echo $current_nombre; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required value="<?php echo $current_nombre; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo $current_email; ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_rol" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_rol" name="id_rol" required>
                                    <option value="">Seleccione un Rol</option>
                                    <?php while ($rol = $roles->fetch_assoc()): ?>
                                        <option value="<?php echo $rol['id']; ?>" <?php echo ($current_rol_id == $rol['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_sucursal" class="form-label">Sucursal (Opcional)</label>
                                <select class="form-select" id="id_sucursal" name="id_sucursal">
                                    <option value="">Todas (Admin General)</option>
                                    <?php $sucursales->data_seek(0); while ($sucursal = $sucursales->fetch_assoc()): ?>
                                        <option value="<?php echo $sucursal['id']; ?>" <?php echo ($current_sucursal_id == $sucursal['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted">Solo se aplica si el rol lo requiere.</small>
                            </div>
                        </div>

                        <div class="alert alert-info small" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            Para cambiar la contraseña, el usuario debe usar la opción **"Restablecer Contraseña"** en el menú superior derecho.
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-end">
                            <a href="gestion_usuarios.php" class="btn btn-secondary me-2"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
ob_end_flush();
require_once '../templates/footer.php'; 
?>