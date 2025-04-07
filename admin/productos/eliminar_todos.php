<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    // Primero obtener todas las imágenes para eliminarlas del sistema de archivos
    $query = "SELECT imagen_principal FROM productos WHERE imagen_principal IS NOT NULL AND imagen_principal != ''";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $imagenes_principales = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener imágenes adicionales
    $query = "SELECT ip.url_imagen FROM imagenes_producto ip";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $imagenes_adicionales = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Combinar todas las imágenes
    $todas_las_imagenes = array_merge($imagenes_principales, $imagenes_adicionales);
    
    // Eliminar archivos físicos
    $uploads_dir = "../../assets/uploads/productos/";
    foreach ($todas_las_imagenes as $imagen) {
        if (!empty($imagen)) {
            $ruta_imagen = $uploads_dir . $imagen;
            if (file_exists($ruta_imagen)) {
                unlink($ruta_imagen);
            }
        }
    }
    
    // Eliminar registros de la base de datos en orden para evitar problemas de integridad
    // 1. Eliminar especificaciones
    $query = "DELETE FROM especificaciones";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // 2. Eliminar imágenes adicionales
    $query = "DELETE FROM imagenes_producto";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // 3. Eliminar valoraciones
    $query = "DELETE FROM valoraciones";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // 4. Eliminar productos
    $query = "DELETE FROM productos";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // Obtener cantidad de filas eliminadas
    $count = $stmt->rowCount();
    
    // Redireccionar con mensaje de éxito
    header('Location: index.php?mensaje=Se han eliminado todos los productos (' . $count . ') correctamente&tipo=success');
    exit;
} catch (PDOException $e) {
    // Redireccionar con mensaje de error
    header('Location: index.php?mensaje=Error al eliminar los productos: ' . $e->getMessage() . '&tipo=danger');
    exit;
}
?>