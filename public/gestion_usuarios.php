<?php
ob_start();
require_once '../templates/header.php';

// Verificar si el usuario tiene permiso (solo Administradores)
if ($_SESSION['user_rol'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

// Cargar la lista de usuarios
$sql = "SELECT u.id, u.nombre, u.email, u.activo, u.fecha_creacion, r.nombre_rol, s.nombre as sucursal_nombre
        FROM usuarios u
        LEFT JOIN usuario_roles ur ON u.id = ur.id_usuario
        LEFT JOIN roles r ON ur.id_rol = r.id
        LEFT JOIN sucursales s ON u.id_sucursal = s.id
        ORDER BY u.fecha_creacion DESC";
$resultado = $conexion->query($sql);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="bi bi-people-fill text-primary me-2"></i>Gestión de Usuarios
            </h1>
            <p class="text-muted small">Administra los accesos y roles del personal al sistema.</p>
        </div>
        <a href="usuario_agregar.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-person-plus-fill me-2"></i>Nuevo Usuario
        </a>
    </div>

    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-dismissible fade show shadow-sm border-0 mb-4 <?php echo (strpos($_GET['status'], 'success') !== false) ? 'alert-success' : 'alert-danger'; ?>">
            <i class="bi <?php echo (strpos($_GET['status'], 'success') !== false) ? 'bi-check-circle-fill' : 'bi-exclamation-octagon-fill'; ?> me-2"></i>
            <?php 
                $msgs = [
                    'success_add' => 'Usuario registrado correctamente.',
                    'success_edit' => 'Datos actualizados con éxito.',
                    'success_delete' => 'El usuario ha sido eliminado del sistema.',
                    'error_delete_self' => 'No puedes eliminar tu propia cuenta.',
                    'error_db' => 'Ocurrió un error en la base de datos.'
                ];
                echo $msgs[$_GET['status']] ?? 'Operación realizada.';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Usuario</th>
                            <th>Email</th>
                            <th>Rol / Sucursal</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-3 bg-light rounded-circle text-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-fill fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['nombre']); ?></div>
                                        <small class="text-muted">ID: #<?php echo $user['id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-info text-dark mb-1"><?php echo htmlspecialchars($user['nombre_rol']); ?></span><br>
                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo $user['sucursal_nombre'] ?? 'Acceso Global'; ?></small>
                            </td>
                            <td>
                                <?php if ($user['activo']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success px-3">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger px-3">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm">
                                    <a href="usuario_editar.php?id=<?php echo $user['id']; ?>" class="btn btn-white btn-sm border" title="Editar">
                                        <i class="bi bi-pencil-square text-warning"></i>
                                    </a>
                                    <button type="button" 
                                            onclick="abrirModalUsuario(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['nombre']); ?>')" 
                                            class="btn btn-white btn-sm border" 
                                            title="Eliminar">
                                        <i class="bi bi-trash3-fill text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.5rem;">
            <div class="modal-body text-center p-5">
                <div class="mb-4">
                    <i class="bi bi-person-x-fill" style="font-size: 5rem; color: #F4AC05;"></i>
                </div>
                <h2 class="fw-bold mb-2" style="color: #0F1A2D;">¿Eliminar Usuario?</h2>
                <p class="text-muted mb-0 lead">Estás a punto de eliminar a:</p>
                <h5 id="nombreUsuarioEliminar" class="fw-bold text-primary mb-4"></h5>
                <p class="text-muted mb-4 small">Esta acción revocará todos los accesos de forma permanente.</p>
                
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-light btn-lg px-4 fw-bold text-secondary" data-bs-dismiss="modal">CANCELAR</button>
                    <a id="btnConfirmarEliminarUser" href="#" class="btn btn-danger btn-lg px-4 fw-bold">ELIMINAR AHORA</a>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center bg-light rounded-bottom-4 py-3">
                <span class="small text-muted text-uppercase fw-bold">Seguridad del Sistema TI</span>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Abre el modal de confirmación personalizado para usuarios
 * @param {number} id - ID del usuario
 * @param {string} nombre - Nombre para mostrar en el modal
 */
function abrirModalUsuario(id, nombre) {
    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    const btnConfirmar = document.getElementById('btnConfirmarEliminarUser');
    const labelNombre = document.getElementById('nombreUsuarioEliminar');
    
    // Insertar el nombre en el modal
    labelNombre.textContent = nombre;
    
    // Configurar la ruta hacia el archivo que procesa la eliminación de usuarios
    btnConfirmar.href = `../includes/procesar_eliminacion_usuario.php?id=${id}`;
    
    // Mostrar modal
    modal.show();
}
</script>

<style>
/* Estilos para mantener la estética azul/dorado */
.table thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; }
.btn-white { background-color: #fff; }
.btn-white:hover { background-color: #f8f9fa; }
.bg-success-subtle { background-color: #e1f6eb !important; }
.bg-danger-subtle { background-color: #fce8e8 !important; }
#deleteUserModal .btn-danger { background-color: #FF2B00; border: none; }
#deleteUserModal .btn-danger:hover { background-color: #d12400; }
</style>

<?php 
require_once '../templates/footer.php';
ob_end_flush();
?>