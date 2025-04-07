<?php
// Rutas de la aplicación
define('BASE_URL', 'http://localhost/compuya/');
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOADS_PATH', ROOT_PATH . 'assets/uploads/');
define('UPLOADS_URL', BASE_URL . 'assets/uploads/');

// Configuración de tiempo y zona horaria
date_default_timezone_set('America/Lima');

// Función para cargar clases automáticamente
spl_autoload_register(function($className) {
    // Directorios donde buscar clases
    $directories = [
        'models/',
        'controllers/',
        'config/'
    ];
    
    // Recorrer directorios
    foreach ($directories as $directory) {
        $file = ROOT_PATH . $directory . $className . '.php';
        
        // Si existe el archivo, lo incluimos
        if (file_exists($file)) {
            include $file;
            return;
        }
    }
});

// Función para redireccionar
function redirect($url) {
    header('Location: ' . BASE_URL . $url);
    exit;
}

// Función para mostrar mensajes de error o éxito
function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

// Función para obtener y borrar mensajes
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

// Función para limpiar datos de entrada
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para generar slug
function generateSlug($text) {
    // Reemplazar caracteres especiales
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterar
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Eliminar caracteres no deseados
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Eliminar duplicados
    $text = preg_replace('~-+~', '-', $text);
    // Convertir a minúsculas
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}
?>