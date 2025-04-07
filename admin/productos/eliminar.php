<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Verificar que se ha enviado un ID de producto
if (!isset($_POST['id']) || empty($_POST['id'])) {
    header('Location: index.php');
    exit;
}

$producto_id = intval($_POST['id']);

try {
    // Primero obtener la información del producto para eliminar la imagen
    $query = "SELECT imagen_principal FROM productos WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $producto_id);
    $stmt->execute();
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Eliminar la imagen principal si existe
    if (!empty($producto['imagen_principal'])) {
        $imagen_path = "../../assets/uploads/productos/" . $producto['imagen_principal'];
        if (file_exists($imagen_path)) {
            unlink($imagen_path);
        }
    }
    
    // Obtener y eliminar imágenes adicionales
    $query = "SELECT url_imagen FROM imagenes_producto WHERE producto_id = :producto_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':producto_id', $producto_id);
    $stmt->execute();
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($imagenes as $imagen) {
        $imagen_path = "../../assets/uploads/productos/" . $imagen['url_imagen'];
        if (file_exists($imagen_path)) {
            unlink($imagen_path);
        }
    }
    
    // Eliminar registros de la base de datos
    // 1. Eliminar especificaciones
    $query = "DELETE FROM especificaciones WHERE producto_id = :producto_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':producto_id', $producto_id);
    $stmt->execute();
    
    // 2. Eliminar imágenes
    $query = "DELETE FROM imagenes_producto WHERE producto_id = :producto_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':producto_id', $producto_id);
    $stmt->execute();
    
    // 3. Eliminar el producto
    $query = "DELETE FROM productos WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $producto_id);
    $stmt->execute();
    
    // Redireccionar con mensaje de éxito
    header('Location: index.php?mensaje=Producto eliminado correctamente&tipo=success');
    exit;
} catch (PDOException $e) {
    // Redireccionar con mensaje de error
    header('Location: index.php?mensaje=Error al eliminar el producto: ' . $e->getMessage() . '&tipo=danger');
    exit;
}
?>