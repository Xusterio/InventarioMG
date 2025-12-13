<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../config/database.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <style>
        /* ---------------------------------------------------- */
        /* PALETA DE COLORES Y VARIABLES */
        /* ---------------------------------------------------- */
        :root {
            --color-primary: #0F1A2D; /* Azul Oscuro - Fondo de Sidebar y Botones */
            --color-accent: #F4AC05; /* Dorado/Mostaza - Hover, Activo, Resaltado */
            --color-error: #FF2B00;  /* Rojo Brillante - Alertas */
            --color-light: #f8f9fa;  /* Gris claro para el fondo */

            --sidebar-width: 280px;
            --sidebar-bg-color: var(--color-primary);
            --border-radius-base: 0.75rem;
        }

        /* ------------------------------------------------------------------ */
        /* 1. ESTILOS BASE Y CUERPO PRINCIPAL */
        /* ------------------------------------------------------------------ */

        body {
            background-color: var(--color-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            color: #333;
        }

        .main-content {
            padding: 2.5rem;
            transition: margin-left .3s;
            min-height: 100vh;
        }


        /* ------------------------------------------------------------------ */
        /* 2. SIDEBAR (MENU LATERAL) - USO DE COLOR PRIMARY Y ACCENT */
        /* ------------------------------------------------------------------ */

        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1020;
            background-color: var(--sidebar-bg-color) !important;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
            padding: 0 !important; /* Elimina el p-3 de Bootstrap */
        }

        /* Estilo del logo y encabezado del sidebar (Contenedor) */
        .sidebar-header {
            padding: 1rem 1rem 0.5rem;
            color: var(--color-accent);
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            letter-spacing: 1px;
            margin-bottom: 1rem;
        }

        /* Estilo de la imagen del Logo */
        .school-logo {
            max-width: 80px;
            height: auto;
            border-radius: 50%;
            border: 3px solid var(--color-accent);
            padding: 2px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease;
        }

        /* Estilo de los enlaces de navegación */
        .sidebar .nav-pills {
            padding: 0 1rem;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 0;
            transition: all 0.2s ease;
            border-radius: var(--border-radius-base);
        }

        /* Estado Hover y Activo */
        .sidebar .nav-link:hover {
            background-color: var(--color-accent);
            color: var(--color-primary) !important;
            font-weight: 600;
        }

        .sidebar .nav-link.active {
            background-color: var(--color-accent) !important;
            color: var(--color-primary) !important;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(244, 172, 5, 0.4);
        }
        
        /* Estilo del Dropdown de Usuario */
        .sidebar .dropdown {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .dropdown-toggle strong {
            font-weight: 600;
        }
        
        .sidebar .dropdown-menu-dark {
            border: 1px solid var(--color-accent);
        }


        /* ------------------------------------------------------------------ */
        /* 3. ESTILOS DE COMPONENTES GENERALES (CARDS, BOTONES) */
        /* ------------------------------------------------------------------ */

        .card { 
            border: 1px solid #e9ecef;
            border-radius: var(--border-radius-base); 
            box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.08);
        }

        .card-header { 
            background-color: #fff; 
            border-bottom: 1px solid #dee2e6; 
            font-weight: 600; 
            color: var(--color-primary); 
            padding: 1.25rem 1.5rem;
            font-size: 1.1rem;
            border-radius: calc(var(--border-radius-base) - 0.25rem) calc(var(--border-radius-base) - 0.25rem) 0 0;
        }

        .btn { 
            border-radius: 0.5rem; 
            font-weight: 600;
            padding: 0.6rem 1.25rem;
        }

        /* Botones principales (btn-primary) */
        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            transition: background-color 0.2s ease;
        }
        .btn-primary:hover {
            background-color: #1a2a43;
            border-color: #1a2a43;
        }
        
        /* Botones de DataTables */
        .dt-buttons .btn { margin-right: 5px; }


        /* ------------------------------------------------------------------ */
        /* 4. ESTILOS RESPONSIVE */
        /* ------------------------------------------------------------------ */

        /* Pantallas Grandes (Desktop) */
        @media (min-width: 992px) {
            .main-content {
                margin-left: var(--sidebar-width);
            }
        }

        /* Pantallas Pequeñas (Tablet y Móvil) */
        @media (max-width: 991.98px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
                transition: margin-left .3s;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
                padding-top: 5rem;
            }
            .mobile-header {
                display: flex !important;
                align-items: center;
                padding: .75rem 1rem;
                background-color: var(--sidebar-bg-color);
                color: white;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1019;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            }
            /* Botón de alternar menú móvil */
            .mobile-header .btn-toggler {
                color: var(--color-accent) !important; 
                border: 1px solid var(--color-accent);
                background-color: transparent;
                font-size: 1.2rem;
                padding: 0.25rem 0.5rem;
                border-radius: 0.25rem;
                line-height: 1;
            }
            /* El título del inventario en móvil */
            .mobile-header a span {
                color: white !important;
                font-weight: 600;
            }
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
</head>
<body>

<header class="mobile-header d-lg-none">
    <button class="btn btn-toggler me-3" type="button" id="menu-toggle">
        <i class="bi bi-list fs-4"></i>
    </button>
    <a href="index.php" class="text-white text-decoration-none">
        <span class="fs-4">Inventario</span>
    </a>
</header>

<div class="sidebar d-flex flex-column flex-shrink-0 text-white bg-dark" id="sidebar">
    
    <div class="sidebar-header d-flex flex-column align-items-center justify-content-center">
        <img src="img/Logo.png" alt="Logo del Colegio" class="school-logo mb-2">
        <a href="index.php" class="text-white text-decoration-none">
            <span class="fs-5 fw-bold">INVENTARIO I.E.</span>
        </a>
    </div>
    
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item"><a href="index.php" class="nav-link <?php if($current_page == 'index.php') echo 'active'; ?>"><i class="bi bi-house-door me-2"></i> Dashboard</a></li>
        <li><a href="equipos.php" class="nav-link <?php if(in_array($current_page, ['equipos.php', 'equipo_agregar.php', 'equipo_editar.php'])) echo 'active'; ?>"><i class="bi bi-laptop me-2"></i> Equipos</a></li>
        <li><a href="empleados.php" class="nav-link <?php if(in_array($current_page, ['empleados.php', 'empleado_agregar.php', 'empleado_editar.php'])) echo 'active'; ?>"><i class="bi bi-people me-2"></i> Empleados</a></li>
        <li><a href="asignaciones.php" class="nav-link <?php if(str_starts_with($current_page, 'asignacion')) echo 'active'; ?>"><i class="bi bi-card-list me-2"></i> Asignaciones</a></li>
        <li><a href="gestion_catalogos.php" class="nav-link <?php if($current_page == 'gestion_catalogos.php') echo 'active'; ?>"><i class="bi bi-tags me-2"></i> Catálogos</a></li>
        <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'Administrador'): ?>
        <li><a href="gestion_usuarios.php" class="nav-link <?php if(str_starts_with($current_page, 'usuario')) echo 'active'; ?>"><i class="bi bi-person-badge me-2"></i> Usuarios y Roles</a></li>
        <?php endif; ?>
    </ul>
    
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2" style="font-size: 2rem;"></i>
            <strong><?php echo isset($_SESSION['user_nombre']) ? htmlspecialchars($_SESSION['user_nombre']) : 'Usuario'; ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
            <li><span class="dropdown-item-text"><?php echo isset($_SESSION['user_rol']) ? htmlspecialchars($_SESSION['user_rol']) : 'Rol'; ?></span></li>
            <li><a class="dropdown-item" href="cambiar_password.php"><i class="bi bi-key me-2"></i> Restablecer Contraseña</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php">Cerrar Sesión</a></li>
        </ul>
    </div>
</div>

<main class="main-content">