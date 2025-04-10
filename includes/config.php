<?php
// Configuración general del sitio
define('SITE_NAME', 'COMPU YA');
define('BASE_URL', 'http://localhost/compuya'); // Cambia esto cuando subas al hosting
define('ADMIN_EMAIL', 'admin@compuya.com'); // Cambia a tu email

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Cambia en producción
define('DB_PASS', '');     // Cambia en producción
define('DB_NAME', 'compuya');

// Configuración de rutas
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');

// Configuración de uploads
define('MAX_FILE_SIZE', 5000000); // 5MB en bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Zona horaria
date_default_timezone_set('America/Lima');
?>