<?php
ob_start(); // Buffer de salida para evitar errores de headers
require_once '../templates/header.php';

// Solo los administradores pueden acceder
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Administrador') {
    echo "<div class='alert alert-danger'>Acceso denegado.</div>";
    require_once '../templates/footer.php';
    exit();
}

// Lógica para crear un nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password_plano = $_POST['password'];
    $id_rol = $_POST['id_rol'];
    $id_sucursal = !empty($_POST['id_sucursal']) ? $_POST['id_sucursal'] : null;

    if (empty($password_plano)) {
        $error_message = "La contraseña no puede estar vacía.";
    } else {
        $password_hash = password_hash($password_plano, PASSWORD_DEFAULT);

        $conexion->begin_transaction();
        try {
            // 1. Insertar en la tabla 'usuarios'
            $stmt_user = $conexion->prepare("INSERT INTO usuarios (nombre, email, password, id_sucursal) VALUES (?, ?, ?, ?)");
            $stmt_user->bind_param("sssi", $nombre, $email, $password_hash, $id_sucursal);
            $stmt_user->execute();
            $id_usuario_nuevo = $stmt_user->insert_id; // Obtener el ID del usuario recién creado

            // 2. Insertar en la tabla 'usuario_roles'
            $stmt_role = $conexion->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, ?)");
            $stmt_role->bind_param("ii", $id_usuario_nuevo, $id_rol);
            $stmt_role->execute();

            $conexion->commit();
            
            // Limpiar buffer y redirigir
            ob_end_clean();
            header("Location: gestion_usuarios.php?status=success_add");
            exit();

        } catch (mysqli_sql_exception $exception) {
            $conexion->rollback();
            $error_message = "Error al crear el usuario: " . $exception->getMessage();
        }
    }
}

// Cargar catálogos para los menús
$roles = $conexion->query("SELECT * FROM roles ORDER BY nombre_rol");
$sucursales = $conexion->query("SELECT * FROM sucursales WHERE estado = 'Activo' ORDER BY nombre");
?>

<h1 class="h2 mb-4">Registrar Nuevo Usuario</h1>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nombre" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="password" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="id_rol" class="form-label">Rol <span class="text-danger">*</span></label>
            <select class="form-select" name="id_rol" required>
                <option value="">Seleccione un rol...</option>
                <?php while ($rol = $roles->fetch_assoc()): ?>
                    <option value="<?php echo $rol['id']; ?>"><?php echo htmlspecialchars($rol['nombre_rol']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="id_sucursal" class="form-label">Ambiente</label>
            <select class="form-select" name="id_sucursal">
                <option value="">General</option>
                <?php while ($sucursal = $sucursales->fetch_assoc()): ?>
                    <option value="<?php echo $sucursal['id']; ?>"><?php echo htmlspecialchars($sucursal['nombre']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <hr class="my-4">
    <a href="gestion_usuarios.php" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">Crear Usuario</button>
</form>

<?php require_once '../templates/footer.php'; ?>