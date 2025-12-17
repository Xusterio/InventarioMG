<?php
ob_start();
require_once '../templates/header.php';

// Solo los administradores pueden acceder
if ($_SESSION['user_rol'] !== 'Administrador') {
    echo "<div class='alert alert-danger'>Acceso denegado.</div>";
    require_once '../templates/footer.php';
    exit();
}

// Lógica de procesamiento del formulario
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $id_rol = filter_input(INPUT_POST, 'id_rol', FILTER_VALIDATE_INT);
    
    // Manejo de Sucursal
    $id_sucursal_raw = $_POST['id_sucursal'];
    $id_sucursal = (empty($id_sucursal_raw)) ? null : (int)$id_sucursal_raw;

    // Validación básica
    if (empty($nombre) || empty($email) || empty($password) || !$id_rol) {
        $error_message = "Todos los campos obligatorios deben ser llenados.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "El formato del email no es válido.";
    } else {
        // Encriptar contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $conexion->begin_transaction();
        $is_ok = false;

        try {
            // 1. Insertar el usuario en la tabla 'usuarios'
            // CORRECCIÓN: Se agrega la columna 'activo' y se cambia el bind_param a "sssi" 
            // para que la contraseña se trate como String (s) y no como Integer (i).
            $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password, id_sucursal, activo) VALUES (?, ?, ?, ?, 1)");
            
            // "sssi" significa: nombre (string), email (string), password (string), id_sucursal (integer/null)
            $stmt->bind_param("sssi", $nombre, $email, $hashed_password, $id_sucursal);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al insertar el usuario: " . $stmt->error);
            }
            $user_id = $stmt->insert_id;
            $stmt->close();
            
            // 2. Insertar el rol en la tabla 'usuario_roles'
            $stmt_rol = $conexion->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, ?)");
            $stmt_rol->bind_param("ii", $user_id, $id_rol);
            
            if (!$stmt_rol->execute()) {
                throw new Exception("Error al asignar el rol: " . $stmt_rol->error);
            }
            $stmt_rol->close();
            
            $conexion->commit();
            $is_ok = true;
            
        } catch (Exception $e) {
            $conexion->rollback();
            $error_message = "Error en la base de datos: " . $e->getMessage();
            if (strpos($error_message, 'Duplicate entry') !== false) {
                 $error_message = "El email '{$email}' ya está registrado.";
            }
        }
        
        if ($is_ok) {
            ob_end_clean();
            header("Location: gestion_usuarios.php?status=success_add");
            exit();
        }
    }
}

// Cargar roles y sucursales para el formulario
$roles = $conexion->query("SELECT id, nombre_rol FROM roles ORDER BY nombre_rol");
$sucursales = $conexion->query("SELECT id, nombre FROM sucursales WHERE estado = 'Activo' ORDER BY nombre");
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-person-plus-fill me-2 text-primary"></i> Registrar Nuevo Usuario
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
                    <h5 class="card-title mb-0 text-primary">Formulario de Registro</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_rol" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_rol" name="id_rol" required>
                                    <option value="">Seleccione un Rol</option>
                                    <?php while ($rol = $roles->fetch_assoc()): ?>
                                        <option value="<?php echo $rol['id']; ?>" <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == $rol['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_sucursal" class="form-label">Sucursal (Opcional)</label>
                                <select class="form-select" id="id_sucursal" name="id_sucursal">
                                    <option value="">Todas (Admin General)</option>
                                    <?php while ($sucursal = $sucursales->fetch_assoc()): ?>
                                        <option value="<?php echo $sucursal['id']; ?>" <?php echo (isset($_POST['id_sucursal']) && $_POST['id_sucursal'] == $sucursal['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted">Si selecciona "Todas", tendrá acceso global.</small>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-end">
                            <a href="gestion_usuarios.php" class="btn btn-secondary me-2"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Registrar Usuario</button>
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