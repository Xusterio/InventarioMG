<?php
require_once '../templates/header.php';

// Solo los administradores pueden acceder
if ($_SESSION['user_rol'] !== 'Administrador') {
    echo "<div class='alert alert-danger'>Acceso denegado.</div>";
    require_once '../templates/footer.php';
    exit();
}
$usuarios = $conexion->query("SELECT u.id, u.nombre, u.email, r.nombre_rol, s.nombre as sucursal_nombre FROM usuarios u LEFT JOIN usuario_roles ur ON u.id = ur.id_usuario LEFT JOIN roles r ON ur.id_rol = r.id LEFT JOIN sucursales s ON u.id_sucursal = s.id ORDER BY u.nombre");
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-person-circle me-2 text-primary"></i> Gestión de Usuarios y Roles
        </h1>
        <a href="usuario_agregar.php" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-2"></i>Registrar Nuevo Usuario
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-columns-reverse me-2"></i> Usuarios del Sistema
            </h5>
            <div class="text-muted small">
                </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Sucursal</th>
                            <th width="100">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($usuario['nombre_rol'] ?? 'Sin rol'); ?></span></td>
                                <td><?php echo htmlspecialchars($usuario['sucursal_nombre'] ?? 'Todas'); ?></td>
                                <td>
                                    <a href="usuario_editar.php?id=<?php echo $usuario['id']; ?>" class="btn btn-outline-warning btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>