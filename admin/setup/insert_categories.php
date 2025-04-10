<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Categorías a insertar
$categorias = [
    ['nombre' => 'Periféricos', 'padre_id' => NULL, 'slug' => 'perifericos'],
    ['nombre' => 'Monitores', 'padre_id' => NULL, 'slug' => 'monitores'],
    ['nombre' => 'Laptops', 'padre_id' => NULL, 'slug' => 'laptops'],
    ['nombre' => 'Impresoras', 'padre_id' => NULL, 'slug' => 'impresoras'],
    ['nombre' => 'Componentes', 'padre_id' => NULL, 'slug' => 'componentes'],
    ['nombre' => 'Computadoras Completas', 'padre_id' => NULL, 'slug' => 'computadoras-completas'],
    ['nombre' => 'Gabinetes', 'padre_id' => 5, 'slug' => 'gabinetes'],
    ['nombre' => 'Procesadores', 'padre_id' => 5, 'slug' => 'procesadores'],
    ['nombre' => 'Tarjetas Gráficas', 'padre_id' => 5, 'slug' => 'tarjetas-graficas'],
    ['nombre' => 'Placas Madre', 'padre_id' => 5, 'slug' => 'placas-madre']
];

$exito = 0;
$errores = 0;

try {
    // Insertar categorías
    $stmt = $conn->prepare("INSERT INTO categorias (nombre, padre_id, slug) VALUES (?, ?, ?)");
    
    foreach ($categorias as $categoria) {
        $stmt->bind_param("sis", $categoria['nombre'], $categoria['padre_id'], $categoria['slug']);
        
        if ($stmt->execute()) {
            $exito++;
        } else {
            $errores++;
        }
    }
    
    echo "Proceso completado: $exito categorías insertadas correctamente. $errores errores.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>