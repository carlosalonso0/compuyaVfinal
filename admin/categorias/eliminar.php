<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Verificar que se ha enviado un ID de categoría
if (!isset($_POST['id']) || empty($_POST['id'])) {
    header('Location: index.php');
    exit;
}

$categoria_id = intval($_POST['id']);

try {
    // Primero obtener la información de la categoría para eliminar la imagen
    $query = "SELECT imagen FROM categorias WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $categoria_id);
    $stmt->execute();
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Eliminar la imagen si existe
    if (!empty($categoria['imagen'])) {
        $imagen_path = "../../assets/uploads/categorias/" . $categoria['imagen'];
        if (file_exists($imagen_path)) {
            unlink($imagen_path);
        }
    }
    
    // Actualizar los productos que usan esta categoría (opcional, podrías preferir rechazar la eliminación)
    $query = "UPDATE productos SET categoria_id = NULL WHERE categoria_id = :categoria_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':categoria_id', $categoria_id);
    $stmt->execute();
    
    // Actualizar las subcategorías para hacerlas categorías principales
    $query = "UPDATE categorias SET categoria_padre_id = NULL WHERE categoria_padre_id = :categoria_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':categoria_id', $categoria_id);
    $stmt->execute();
    
    // Eliminar la categoría
    $query = "DELETE FROM categorias WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $categoria_id);
    $stmt->execute();
    
    // Redireccionar con mensaje de éxito
    header('Location: index.php?mensaje=Categoría eliminada correctamente&tipo=success');
    exit;
} catch (PDOException $e) {
    // Redireccionar con mensaje de error
    header('Location: index.php?mensaje=Error al eliminar la categoría: ' . $e->getMessage() . '&tipo=danger');
    exit;
}
?>