<?php
require_once '../templates/header.php';
// Se asume que la sesión ya está iniciada en el header o mediante un middleware
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="display-6 text-primary mb-2">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h2 class="h4 fw-bold">Cambiar Contraseña</h2>
                        <p class="text-muted small">Asegúrate de usar una contraseña segura que no utilices en otros sitios.</p>
                    </div>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            La contraseña actual es incorrecta.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="../includes/procesar_cambio_password.php" method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label small fw-semibold">Contraseña Actual</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-key"></i>
                                </span>
                                <input type="password" class="form-control bg-light border-start-0" 
                                       id="current_password" name="current_password" 
                                       placeholder="Ingresa tu clave actual" required>
                            </div>
                        </div>

                        <hr class="text-muted opacity-25 my-4">

                        <div class="mb-3">
                            <label for="new_password" class="form-label small fw-semibold">Nueva Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control bg-light border-start-0" 
                                       id="new_password" name="new_password" 
                                       placeholder="Mínimo 8 caracteres" required minlength="8">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label small fw-semibold">Confirmar Nueva Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-lock-check"></i>
                                </span>
                                <input type="password" class="form-control bg-light border-start-0" 
                                       id="confirm_password" name="confirm_password" 
                                       placeholder="Repite tu nueva clave" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-bold">
                                <i class="bi bi-check-circle me-2"></i>Actualizar Contraseña
                            </button>
                            <a href="index.php" class="btn btn-link link-secondary text-decoration-none small text-center">
                                Cancelar y volver al inicio
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center mt-4">
                <p class="text-muted small">&copy; <?php echo date('Y'); ?> Inventario MG - Seguridad</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Validación básica en el cliente para asegurar que las contraseñas coinciden
    document.querySelector('form').addEventListener('submit', function(e) {
        const newPass = document.getElementById('new_password').value;
        const confirmPass = document.getElementById('confirm_password').value;

        if (newPass !== confirmPass) {
            e.preventDefault();
            alert('Las nuevas contraseñas no coinciden. Por favor, verifica.');
        }
    });
</script>

<?php require_once '../templates/footer.php'; ?>