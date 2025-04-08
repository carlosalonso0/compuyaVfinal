<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Verificar que se ha enviado un array de orden
if (!isset($_POST['order']) || !is_array($_POST['order'])) {
    echo json_encode(['success' => false, 'message' => 'Datos de orden no válidos']);
    exit;
}

$order = $_POST['order'];
$tipo = $_POST['tipo'] ?? 'slider';

try {
    // Actualizar el orden de los elementos
    if ($tipo == 'slider') {
        $tabla = 'home_sliders';
    } elseif ($tipo == 'banner') {
        $tabla = 'home_banners';
    } else {
        echo json_encode(['success' => false, 'message' => 'Tipo no válido']);
        exit;
    }
    
    foreach ($order as $id => $position) {
        $query = "UPDATE $tabla SET orden = :orden WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':orden', $position, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el orden: ' . $e->getMessage()]);
}
?>