<?php
// Iniciar sesión
session_start();

// Definir ruta raíz para incluir archivos
$root_path = $_SERVER['DOCUMENT_ROOT'] . '/compuya/';

// Incluir archivo de configuración
require_once $root_path . 'config/config.php';
require_once $root_path . 'config/database.php';

// Crear instancia de la base de datos
$database = new Database();
$db = $database->connect();
?>