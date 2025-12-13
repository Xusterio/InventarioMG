<?php require_once '../templates/header.php'; ?>

<h1 class="h2">Asignar Equipo a Empleado</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="../includes/procesar_asignacion.php" method="POST">
            <div class="mb-3">
                <label for="id_empleado" class="form-label">Seleccionar Empleado <span class="text-danger">*</span></label>
                <select class="form-select" name="id_empleado" id="id_empleado" required>
                    <option value="" selected disabled>-- Empleados Activos --</option>
                    <?php
                    // ... (lógica PHP sin cambios) ...
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="id_equipo" class="form-label">Seleccionar Equipo <span class="text-danger">*</span></label>
                <select class="form-select" name="id_equipo" id="id_equipo" required>
                    <option value="" selected disabled>-- Equipos Disponibles --</option>
                    <?php
                    // ... (lógica PHP sin cambios) ...
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="observaciones_entrega" class="form-label">Observaciones de la Entrega</label>
                <textarea class="form-control" name="observaciones_entrega" id="observaciones_entrega" rows="3" placeholder="Ej: Equipo nuevo en caja, con cargador y mouse."></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Asignar Equipo</button>
        </form>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>