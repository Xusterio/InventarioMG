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
    
    $stmt = $conexion->prepare("UPDATE {$table_name} SET nombre = ?, estado = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nombre, $estado, $id);
    if ($stmt->execute()) {
        header("Location: gestion_catalogos.php?status=success_edit");
        exit();
    } else {
        $error_message = "Error al actualizar.";
    }
}

// Cargar datos del item a editar
$stmt = $conexion->prepare("SELECT * FROM {$table_name} WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
?>

<h1 class="h2 mb-4">Editar Elemento de Catálogo</h1>

<div class="card">
    <div class="card-header">Editando: <?php echo htmlspecialchars($item['nombre']); ?></div>
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

            <hr class="my-4">
            <a href="gestion_catalogos.php" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>