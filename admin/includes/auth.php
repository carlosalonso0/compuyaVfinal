<?php
// Verificar sesión de administrador
session_start();

if(!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Configuración e inicialización de base de datos
require_once '../config/database.php';
$database = new Database();
$db = $database->connect();

// Definir URL base del panel admin
define('ADMIN_URL', 'http://localhost/compuya/admin/');
?>