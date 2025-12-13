<?php
require_once '../templates/header.php';

// Cargar equipos disponibles
// Nota: Se cargan TODOS los equipos (activos e inactivos) si el QR es para identificación
$sql_equipos = "SELECT 
                    e.id, 
                    e.codigo_inventario, 
                    e.estado,
                    te.nombre AS tipo_nombre,
                    m.nombre AS marca_nombre,
                    s.nombre AS sucursal_nombre
                FROM equipos e
                JOIN tipos_equipo te ON e.id_tipo_equipo = te.id
                JOIN marcas m ON e.id_marca = m.id
                JOIN sucursales s ON e.id_sucursal = s.id
                ORDER BY e.codigo_inventario";

$equipos_q = $conexion->query($sql_equipos);
$equipos = [];
if($equipos_q) $equipos = $equipos_q->fetch_all(MYSQLI_ASSOC);

?>

<h1 class="h2 mb-4">Generador de Códigos QR por Lote</h1>
<p class="text-muted">Seleccione los equipos para generar un archivo PDF con todos los códigos QR correspondientes.</p>

<form action="generar_reporte_qr.php" method="POST" target="_blank" onsubmit="return validarSeleccion();">

    <div class="mb-3">
        <label for="selectEquipos" class="form-label fw-bold">Equipos Disponibles para QR:</label>
        
        <!-- Buscador de equipos -->
        <div class="input-group mb-2">
            <span class="input-group-text">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" class="form-control" id="buscadorEquipo" placeholder="Buscar por código, tipo, marca o sucursal...">
            <button type="button" class="btn btn-outline-secondary" id="limpiarBusqueda">
                <i class="bi bi-x-circle"></i>
            </button>
        </div>

        <select class="form-select form-resaltante" id="selectEquipos" name="id_equipos[]" multiple size="15" required>
            <?php if (empty($equipos)): ?>
                <option value="" disabled>No se encontraron equipos para generar QR.</option>
            <?php else: ?>
                <?php foreach ($equipos as $equipo): ?>
                    <option value="<?php echo $equipo['id']; ?>" 
                            data-filter="<?php echo htmlspecialchars(strtolower($equipo['codigo_inventario'] . ' ' . $equipo['tipo_nombre'] . ' ' . $equipo['marca_nombre'] . ' ' . $equipo['sucursal_nombre'])); ?>">
                        <?php echo htmlspecialchars($equipo['codigo_inventario'] . ' | ' . $equipo['tipo_nombre'] . ' - ' . $equipo['marca_nombre']); ?> 
                        (Ubicación: <?php echo htmlspecialchars($equipo['sucursal_nombre']); ?> | Estado: <?php echo htmlspecialchars($equipo['estado']); ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <div class="form-text" id="contadorSeleccion">
            Seleccionados: 0 de <?php echo count($equipos); ?> equipos. (Ctrl+Clic o Shift+Clic para seleccionar múltiples)
        </div>
    </div>
    
    <hr class="my-4">
    <button type="submit" class="btn btn-success btn-lg">
        <i class="bi bi-file-earmark-pdf-fill me-2"></i> Generar PDF de Códigos QR
    </button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectEquipos = document.getElementById('selectEquipos');
    const buscadorEquipo = document.getElementById('buscadorEquipo');
    const limpiarBusqueda = document.getElementById('limpiarBusqueda');
    const contadorSeleccion = document.getElementById('contadorSeleccion');
    const totalEquipos = <?php echo count($equipos); ?>;

    // Función para actualizar el contador de seleccionados
    function actualizarContador() {
        const seleccionados = Array.from(selectEquipos.options).filter(option => option.selected).length;
        contadorSeleccion.textContent = `Seleccionados: ${seleccionados} de ${totalEquipos} equipos. (Ctrl+Clic o Shift+Clic para seleccionar múltiples)`;
    }

    // Escuchador de cambios en el select (para actualizar el contador)
    selectEquipos.addEventListener('change', actualizarContador);

    // Lógica de búsqueda
    buscadorEquipo.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let visibleCount = 0;
        
        Array.from(selectEquipos.options).forEach(option => {
            const filterData = option.getAttribute('data-filter');
            
            if (!searchTerm || (filterData && filterData.includes(searchTerm))) {
                option.style.display = '';
                visibleCount++;
            } else {
                option.style.display = 'none';
            }
        });

        // Opcional: Actualizar el contador para mostrar solo los visibles
        // contadorSeleccion.textContent = `Mostrando ${visibleCount} de ${totalEquipos} equipos.`; 
    });

    // Limpiar búsqueda
    limpiarBusqueda.addEventListener('click', function() {
        buscadorEquipo.value = '';
        buscadorEquipo.dispatchEvent(new Event('input')); // Disparar evento para resetear la lista
    });
    
    // Función de validación del formulario
    window.validarSeleccion = function() {
        if (selectEquipos.selectedIndex === -1 || Array.from(selectEquipos.options).filter(option => option.selected).length === 0) {
            // Utilizamos el mismo método de notificación que implementaste en asignar_equipo.php
            // Nota: Como no tenemos la función showNotification aquí, usaremos la validación nativa o alert() como último recurso.
            // Para mantener la consistencia con su código, si usa alerts:
             alert('Debe seleccionar al menos un equipo para generar el PDF.');
             return false;
        }
        return true;
    }

    // Aplicar estilo de resaltado
    const estilos = `
    <style>
        .form-resaltante {
            border: 1px solid #ced4da;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: all 0.2s ease-in-out;
        }
        .form-resaltante:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.25rem rgba(74, 144, 226, 0.25);
            outline: 0;
        }
    </style>`;
    document.head.insertAdjacentHTML('beforeend', estilos); 

});
</script>

<?php require_once '../templates/footer.php'; ?>