<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$result = $conn->query("SELECT id, nombre FROM categorias");
while ($categoria = $result->fetch_assoc()) {
    $slug = generarSlugUnico($categoria['nombre'], $conn);
    $stmt = $conn->prepare("UPDATE categorias SET slug = ? WHERE id = ?");
    $stmt->bind_param("si", $slug, $categoria['id']);
    $stmt->execute();
    
    echo "Generado slug para: " . $categoria['nombre'] . " → " . $slug . "<br>";
}

echo "¡Proceso completado!";
?>