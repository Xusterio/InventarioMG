<?php
// Incluye el encabezado si es necesario, pero para una prueba simple de GD, a menudo se omite.
// require_once '../templates/header.php'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test de Extensión GD de PHP</title>
    <link href="../public/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h1 class="mb-4">Prueba de Compatibilidad con GD Library</h1>
        
        <?php if (extension_loaded('gd')): ?>
            <div class="alert alert-success">
                <p><strong>Estado: ¡Éxito!</strong></p>
                <p>La extensión GD está instalada y habilitada en su servidor PHP.</p>
                <p>Esto asegura que la generación de imágenes y códigos QR (si requieren GD) funcione correctamente.</p>
                <p>Versión de GD: <?php echo GD_VERSION; ?></p>
            </div>
            
            <?php
            // Intento de crear una imagen de prueba simple
            $width = 200;
            $height = 50;
            $image = imagecreate($width, $height);
            $bg_color = imagecolorallocate($image, 255, 255, 255); // Blanco
            $text_color = imagecolorallocate($image, 0, 100, 0); // Verde oscuro
            
            imagestring($image, 5, 10, 15, 'GD OK - Funcionando', $text_color);
            
            // Iniciar buffer de salida para la imagen
            ob_start();
            imagepng($image);
            $image_data = ob_get_clean();
            imagedestroy($image);
            
            $base64_image = 'data:image/png;base64,' . base64_encode($image_data);
            ?>
            
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Imagen de Prueba Generada Dinámicamente</h5>
                </div>
                <div class="card-body text-center">
                    <img src="<?php echo $base64_image; ?>" alt="Imagen de Prueba GD" class="img-fluid border p-2">
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-danger">
                <p><strong>Estado: ¡Error!</strong></p>
                <p>La extensión GD no está instalada o habilitada en su servidor PHP.</p>
                <p><strong>Acción requerida:</strong> La generación de códigos QR, reportes PDF con gráficos o cualquier otra manipulación de imágenes fallará. Por favor, edite su archivo <code>php.ini</code> y asegúrese de que <code>extension=gd</code> esté descomentada y que reinicie su servidor web (XAMPP/Apache).</p>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>
        </div>
    </div>
</body>
</html>