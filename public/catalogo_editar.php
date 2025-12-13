<?php
require_once '../templates/header.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);

$table_map = [
    'sucursal' => 'sucursales', 'tipo' => 'tipos_equipo', 'marca' => 'marcas',
    'modelo' => 'modelos', 'area' => 'areas', 'cargo' => 'cargos'
];

if (!$id || !array_key_exists($type, $table_map)) {
    header("Location: gestion_catalogos.php");
    exit();
}

$table_name = $table_map[$type];

// Lógica para ACTUALIZAR el registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $estado = $_POST['estado'];
    
    // Si es un modelo o cargo, la lógica es más compleja y se saltó en el archivo original,
    // pero para los catálogos simples (sucursal, tipo, marca, área) es suficiente.
    
    // Nota: Para los catálogos complejos (modelo y cargo), se debería incluir lógica 
    // para campos adicionales, como se hizo en gestion_catalogos.php para el INSERT.
    // Aquí se asume solo 'nombre' y 'estado' son necesarios para el UPDATE.
    
    $stmt = $conexion->prepare("UPDATE {$table_name} SET nombre = ?, estado = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nombre, $estado, $id);
    if ($stmt->execute()) {
        // Redirige a la página de catálogos y se asume que gestion_catalogos.php 
        // tendrá la lógica para mostrar el mensaje de éxito (status=success_edit)
        header("Location: gestion_catalogos.php?status=success_edit");
        exit();
    } else {
        // En un entorno de producción, esta variable de error debería ser manejada
        // y mostrada al usuario, pero por ahora se mantiene la estructura básica.
        $error_message = "Error al actualizar.";
    }
}

// Cargar datos del item a editar
$stmt = $conexion->prepare("SELECT * FROM {$table_name} WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
?>

<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-dark fw-bold">
            <i class="bi bi-tags me-2 text-primary"></i> Editar Elemento de Catálogo
        </h1>
        <a href="gestion_catalogos.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver a Catálogos
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-light py-3">
            <h5 class="card-title mb-0 text-primary">
                Editando: <?php echo htmlspecialchars($item['nombre']); ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($item['nombre']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                    <select class="form-select" name="estado" required>
                        <option value="Activo" <?php if($item['estado'] == 'Activo') echo 'selected'; ?>>Activo</option>
                        <option value="Inactivo" <?php if($item['estado'] == 'Inactivo') echo 'selected'; ?>>Inactivo</option>
                    </select>
                </div>

                <?php if ($type === 'sucursal'): ?>
                    <?php 
                        // Cargar la dirección de la sucursal (aunque ya está en $item, se podría hacer una consulta separada si el catálogo fuera más complejo)
                        $direccion_sucursal = $item['direccion'] ?? ''; 
                    ?>
                    <div class="mb-3">
                        <label for="direccion_sucursal" class="form-label">Lugar</label>
                        <textarea class="form-control" name="direccion_sucursal" rows="2"><?php echo htmlspecialchars($direccion_sucursal); ?></textarea>
                        </div>
                <?php endif; ?>

                <hr class="my-4">
                <a href="gestion_catalogos.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Guardar Cambios</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>