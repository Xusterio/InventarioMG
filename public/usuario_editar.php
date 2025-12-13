<?php
require_once '../templates/header.php';

// Solo los administradores pueden acceder
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Administrador') {
    echo "<div class='alert alert-danger'>Acceso denegado.</div>";
    require_once '../templates/footer.php';
    exit();
}

$id_usuario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_usuario) {
    header("Location: gestion_usuarios.php");
    exit();
}

// Lógica para ACTUALIZAR el usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $id_rol = $_POST['id_rol'];
    $id_sucursal = !empty($_POST['id_sucursal']) ? $_POST['id_sucursal'] : null;
    $activo = $_POST['activo'];
    
    $conexion->begin_transaction();
    try {
        // 1. Actualizar tabla de usuarios
        $stmt_user = $conexion->prepare("UPDATE usuarios SET nombre = ?, email = ?, id_sucursal = ?, activo = ? WHERE id = ?");
        $stmt_user->bind_param("ssiii", $nombre, $email, $id_sucursal, $activo, $id_usuario);
        $stmt_user->execute();

        // 2. Actualizar tabla de roles (eliminar anterior e insertar nuevo para simplicidad)
        $stmt_del_role = $conexion->prepare("DELETE FROM usuario_roles WHERE id_usuario = ?");
        $stmt_del_role->bind_param("i", $id_usuario);
        $stmt_del_role->execute();

        $stmt_add_role = $conexion->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, ?)");
        $stmt_add_role->bind_param("ii", $id_usuario, $id_rol);
        $stmt_add_role->execute();

        $conexion->commit();
        header("Location: gestion_usuarios.php?status=success_edit");
        exit();

    } catch (mysqli_sql_exception $exception) {
        $conexion->rollback();
        $error_message = "Error al actualizar el usuario: " . $exception->getMessage();
    }
}

// Cargar datos del usuario a editar
$stmt = $conexion->prepare("SELECT u.*, ur.id_rol FROM usuarios u LEFT JOIN usuario_roles ur ON u.id = ur.id_usuario WHERE u.id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: gestion_usuarios.php");
    exit();
}

// Cargar catálogos
$roles = $conexion->query("SELECT * FROM roles ORDER BY nombre_rol");
$sucursales = $conexion->query("SELECT * FROM sucursales WHERE estado = 'Activo' ORDER BY nombre");
?>

<h1 class="h2 mb-4">Editar Usuario</h1>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="id_rol" class="form-label">Rol <span class="text-danger">*</span></label>
            <select class="form-select" name="id_rol" required>
                <?php while ($rol = $roles->fetch_assoc()): ?>
                    <option value="<?php echo $rol['id']; ?>" <?php if($rol['id'] == $usuario['id_rol']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="id_sucursal" class="form-label">Sucursal</label>
            <select class="form-select" name="id_sucursal">
                <option value="">General (Todas las sucursales)</option>
                <?php while ($sucursal = $sucursales->fetch_assoc()): ?>
                    <option value="<?php echo $sucursal['id']; ?>" <?php if($sucursal['id'] == $usuario['id_sucursal']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($sucursal['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="activo" class="form-label">Estado <span class="text-danger">*</span></label>
            <select class="form-select" name="activo" required>
                <option value="1" <?php if($usuario['activo'] == 1) echo 'selected'; ?>>Activo</option>
                <option value="0" <?php if($usuario['activo'] == 0) echo 'selected'; ?>>Inactivo</option>
            </select>
        </div>
    </div>
    <div class="alert alert-info mt-3">
        La contraseña solo puede ser cambiada por el propio usuario o mediante una función de "recuperar contraseña".
    </div>

    <hr class="my-4">
    <a href="gestion_usuarios.php" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
</form>

<?php require_once '../templates/footer.php'; ?>