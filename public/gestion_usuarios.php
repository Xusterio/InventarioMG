<?php
require_once '../templates/header.php';

// Solo los administradores pueden acceder
if ($_SESSION['user_rol'] !== 'Administrador') {
    echo "<div class='container mt-5'><div class='alert alert-danger d-flex align-items-center' role='alert'>
            <i class='bi bi-exclamation-octagon-fill me-2'></i>
            <div>Acceso denegado. No tienes permisos para ver esta sección.</div>
          </div></div>";
    require_once '../templates/footer.php';
    exit();
}

$usuarios = $conexion->query("SELECT u.id, u.nombre, u.email, r.nombre_rol, s.nombre as sucursal_nombre FROM usuarios u LEFT JOIN usuario_roles ur ON u.id = ur.id_usuario LEFT JOIN roles r ON ur.id_rol = r.id LEFT JOIN sucursales s ON u.id_sucursal = s.id ORDER BY u.nombre");
?>

<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col">
            <h1 class="h3 text-dark fw-bold mb-0">
                <i class="bi bi-people-fill text-primary me-2"></i> Gestión de Usuarios
            </h1>
            <p class="text-muted mb-0">Administra las cuentas de acceso, roles y asignación de sucursales.</p>
        </div>
        <div class="col-auto">
            <a href="usuario_agregar.php" class="btn btn-primary shadow-sm">
                <i class="bi bi-person-plus-fill me-2"></i>Nuevo Usuario
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title fw-semibold mb-0">Lista de Usuarios</h5>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="busquedaUsuario" class="form-control bg-light border-start-0" placeholder="Buscar usuario...">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 border-0 text-uppercase small fw-bold text-muted">Nombre del Usuario</th>
                            <th class="border-0 text-uppercase small fw-bold text-muted">Correo Electrónico</th>
                            <th class="border-0 text-uppercase small fw-bold text-muted">Rol asignado</th>
                            <th class="border-0 text-uppercase small fw-bold text-muted">Sucursal</th>
                            <th class="border-0 text-uppercase small fw-bold text-muted text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($usuario = $usuarios->fetch_assoc()): 
                            // Lógica de colores para roles
                            $rol = $usuario['nombre_rol'] ?? 'Sin rol';
                            $badgeClass = ($rol === 'Administrador') ? 'bg-primary-subtle text-primary border-primary' : 'bg-info-subtle text-dark border-info';
                            
                            // Lógica para sucursal
                            $sucursal = $usuario['sucursal_nombre'] ?? 'Acceso Global';
                            $sucursalClass = ($usuario['sucursal_nombre']) ? 'text-dark' : 'text-primary fw-bold';
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3 bg-light rounded-circle d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 40px; height: 40px;">
                                            <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                                        </div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                                    </div>
                                </td>
                                <td class="text-muted small">
                                    <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($usuario['email']); ?>
                                </td>
                                <td>
                                    <span class="badge border <?php echo $badgeClass; ?> px-2 py-1">
                                        <?php echo htmlspecialchars($rol); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small <?php echo $sucursalClass; ?>">
                                        <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($sucursal); ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="usuario_editar.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar Usuario">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="confirmarEliminar(<?php echo $usuario['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 border-top">
            <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i> Los usuarios con "Acceso Global" pueden ver datos de todas las sucursales.</p>
        </div>
    </div>
</div>

<script>
document.getElementById('busquedaUsuario').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    let filas = document.querySelectorAll('#tablaUsuarios tbody tr');
    
    filas.forEach(fila => {
        let texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(filtro) ? '' : 'none';
    });
});

function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
        window.location.href = `../includes/procesar_eliminacion_usuario.php?id=${id}`;
    }
}
</script>

<style>
/* Estilos adicionales para un look premium */
.table thead th {
    font-size: 0.75rem;
    letter-spacing: 0.05rem;
}
.table tbody tr:hover {
    background-color: rgba(0,0,0,0.01) !important;
}
.badge {
    font-weight: 500;
}
.avatar-sm {
    font-size: 1.1rem;
    border: 1px solid #dee2e6;
}
.btn-group .btn {
    padding: 0.25rem 0.5rem;
}
</style>

<?php require_once '../templates/footer.php'; ?>