<?php
// Desactivar cualquier almacenamiento en búfer
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Mostrar todos los errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Información básica
echo "Este archivo está siendo ejecutado directamente.<br>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "QUERY_STRING: " . $_SERVER['QUERY_STRING'] . "<br>";
echo "GET params: ";
echo "<pre>";
print_r($_GET);
echo "</pre>";

// Detener ejecución aquí
exit("Fin del diagnóstico");
?>