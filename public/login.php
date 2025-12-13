<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Sistema de Inventario TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* ---------------------------------------------------- */
        /* PALETA DE COLORES */
        /* Primario: #0F1A2D (Azul Oscuro) */
        /* Acento: #F4AC05 (Dorado/Mostaza) */
        /* Error: #FF2B00 (Rojo Brillante) */
        /* ---------------------------------------------------- */

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            /* Fondo con degradado sutil */
            background: linear-gradient(135deg, #0F1A2D 0%, #1a2a43 100%); 
            font-family: 'Arial', sans-serif;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
        }

        /* Título del Sistema */
        .system-title {
            color: #ffffff;
            font-weight: 700;
            letter-spacing: 2px;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.7);
            margin-bottom: 20px !important; /* Espacio ajustado */
        }
        
        /* [NUEVO CSS] Estilo del Reloj */
        .digital-clock {
            color: #F4AC05; /* Color dorado/acento */
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 25px; /* Espacio debajo del reloj */
            text-shadow: 0 0 5px #000;
        }

        /* Estilo de la Tarjeta */
        .card {
            border: 1px solid #0F1A2D;
            border-radius: 1.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            background-color: white;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px); 
        }

        /* Título de la tarjeta "Iniciar Sesión" */
        .card-title {
            color: #0F1A2D; 
            font-weight: 600;
            border-bottom: 3px solid #F4AC05; 
            padding-bottom: 10px;
            margin-bottom: 30px !important;
        }

        /* Logo de la Carpeta */
        .logo-img {
            max-width: 100px;
            height: auto;
            margin-bottom: 20px;
            border-radius: 50%;
            border: 5px solid #F4AC05;
            padding: 2px;
            background-color: white;
            transition: transform 0.3s ease;
            box-shadow: 0 0 15px #F4AC0555;
        }
        .logo-img:hover {
            transform: scale(1.1);
        }

        /* Inputs con Icono (El resto se mantiene igual) */
        .input-group-text {
            background-color: #0F1A2D;
            border: 1px solid #0F1A2D;
            color: white; 
            border-right: none;
            border-radius: 0.5rem 0 0 0.5rem !important;
        }
        
        .form-control {
            border-radius: 0 0.5rem 0.5rem 0 !important;
            border-left: none;
            padding: 1rem 0.75rem; 
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem #F4AC0540;
            border-color: #F4AC05;
        }

        /* Botón de Ingresar - DORADO */
        .btn-primary {
            background-color: #F4AC05; 
            border-color: #F4AC05;
            color: #0F1A2D;
            transition: background-color 0.3s ease, transform 0.1s ease;
            font-weight: bold;
            font-size: 1.25rem;
        }
        
        .btn-primary:hover {
            background-color: #0F1A2D; 
            border-color: #F4AC05;
            color: #F4AC05;
            transform: translateY(-3px); 
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.3);
        }

        /* Alerta de Error */
        .alert-danger {
            background-color: #FF2B0015;
            border-color: #FF2B00;
            color: #FF2B00;
            font-weight: 500;
        }

        .text-muted {
            color: #0F1A2D !important;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="text-center mb-5">
            <h1 class="h2 system-title">SISTEMA WEB DE INVENTARIO</h1>
            
            <img src="img/Logo.png" alt="Icono de Carpeta para Inventario" class="logo-img">
            
            <div id="digital-clock" class="digital-clock"></div>
            
        </div>
        
        <div class="card">
            <div class="card-body p-5">
                <h3 class="card-title text-center">
                    <i class="bi bi-person-circle me-2" style="color: #F4AC05;"></i> INICIAR SESIÓN
                </h3>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger text-center small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Credenciales incorrectas.
                    </div>
                <?php endif; ?>

                <form action="../includes/procesar_login.php" method="POST">
                    
                    <div class="mb-4">
                        <label for="email" class="form-label visually-hidden">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Correo Electrónico" required>
                        </div>
                    </div>
                    
                    <div class="mb-5">
                        <label for="password" class="form-label visually-hidden">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i> INGRESAR
                        </button>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="#" class="text-muted small text-decoration-underline">¿Olvidaste tu contraseña?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function updateClock() {
            const now = new Date();
            
            // Opciones de formato de hora (e.g., 14:30:15)
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const timeString = now.toLocaleTimeString('es-ES', timeOptions);
            
            // Opciones de formato de fecha (e.g., lunes, 15 de octubre de 2025)
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateString = now.toLocaleDateString('es-ES', dateOptions);
            
            // Formato final
            document.getElementById('digital-clock').innerHTML = 
                dateString.charAt(0).toUpperCase() + dateString.slice(1) + 
                ' | ' + timeString;
        }

        // 1. Ejecutar la función inmediatamente
        updateClock(); 
        // 2. Ejecutar la función cada segundo
        setInterval(updateClock, 1000);
    </script>
</body>
</html>