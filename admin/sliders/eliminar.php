<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Verificar que se ha enviado un ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    header('Location: index.php');
    exit;
}

$slider_id = intval($_POST['id']);

try {
    // Obtener información del slider para eliminar la imagen
    $query = "SELECT imagen FROM home_sliders WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $slider_id);
    $stmt->execute();
    $slider = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Eliminar la imagen si existe
    if (!empty($slider['imagen'])) {
        $imagen_path = "../../assets/uploads/sliders/" . $slider['imagen'];
        if (file_exists($imagen_path)) {
            unlink($imagen_path);
        }
    }
    
    // Eliminar el slider
    $query = "DELETE FROM home_sliders WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $slider_id);
    $stmt->execute();
    
    // Redireccionar con mensaje de éxito
    header('Location: index.php?mensaje=Slider eliminado correctamente&tipo=success');
    exit;
} catch (PDOException $e) {
    // Redireccionar con mensaje de error
    header('Location: index.php?mensaje=Error al eliminar el slider: ' . $e->getMessage() . '&tipo=danger');
    exit;
}
?>