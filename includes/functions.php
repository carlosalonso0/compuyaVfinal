<?php
require_once 'config.php';
require_once 'db.php';

// Función para crear un slug
function crearSlug($texto) {
    // Reemplaza espacios y caracteres especiales
    $texto = preg_replace('([^A-Za-z0-9])', '-', $texto);
    // Elimina guiones duplicados
    $texto = preg_replace('(-+)', '-', $texto);
    // Convierte a minúsculas
    $texto = strtolower(trim($texto, '-'));
    return $texto;
}

// Función para sanitizar input
function sanitizar($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Función para mostrar mensajes de error o éxito
function mostrarMensaje($tipo, $mensaje) {
    return "<div class='alert alert-{$tipo}'>{$mensaje}</div>";
}

// Función para validar si un archivo es una imagen válida
function esImagenValida($archivo) {
    $check = getimagesize($archivo["tmp_name"]);
    if($check === false) {
        return false;
    }
    
    $extension = strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION));
    if(!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    if($archivo["size"] > MAX_FILE_SIZE) {
        return false;
    }
    
    return true;
}

// Función para generar nombre único para archivos
function generarNombreUnico($nombre, $extension) {
    return crearSlug($nombre) . '-' . uniqid() . '.' . $extension;
}

// Función para validar campos de formulario
function validarCampoRequerido($campo) {
    return !empty(trim($campo));
}

// Función para obtener las categorías
function obtenerCategorias() {
    $db = Database::getInstance();
    $resultado = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre");
    $categorias = [];
    
    if($resultado) {
        while($row = $resultado->fetch_assoc()) {
            $categorias[] = $row;
        }
    }
    
    return $categorias;
}

// Función para obtener productos destacados
function obtenerProductosDestacados($limite = 8) {
    $db = Database::getInstance();
    $resultado = $db->query("SELECT p.*, c.nombre as categoria_nombre 
                            FROM productos p 
                            JOIN categorias c ON p.categoria_id = c.id 
                            WHERE p.destacado = 1 AND p.activo = 1 
                            ORDER BY p.fecha_creacion DESC 
                            LIMIT {$limite}");
    $productos = [];
    
    if($resultado) {
        while($row = $resultado->fetch_assoc()) {
            $productos[] = $row;
        }
    }
    
    return $productos;
}

// Función para obtener productos nuevos
function obtenerProductosNuevos($limite = 8) {
    $db = Database::getInstance();
    $resultado = $db->query("SELECT p.*, c.nombre as categoria_nombre 
                            FROM productos p 
                            JOIN categorias c ON p.categoria_id = c.id 
                            WHERE p.nuevo = 1 AND p.activo = 1 
                            ORDER BY p.fecha_creacion DESC 
                            LIMIT {$limite}");
    $productos = [];
    
    if($resultado) {
        while($row = $resultado->fetch_assoc()) {
            $productos[] = $row;
        }
    }
    
    return $productos;
}

// Función para obtener la imagen principal de un producto
function obtenerImagenProducto($producto_id) {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT ruta FROM imagenes_producto WHERE producto_id = ? AND principal = 1 LIMIT 1");
    
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if($row = $resultado->fetch_assoc()) {
        return $row['ruta'];
    }
    
    return 'assets/img/no-image.jpg'; // Imagen por defecto
}

// Función para generar slug único
function generarSlugUnico($texto, $conn, $id = 0) {
    // Convertir a minúsculas y eliminar caracteres especiales
    $slug = strtolower($texto);
    // Reemplazar espacios y guiones por guiones
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    // Eliminar caracteres que no sean letras, números o guiones
    $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
    // Eliminar guiones del principio y del final
    $slug = trim($slug, '-');
    
    // Verificar unicidad
    $slug_original = $slug;
    $contador = 1;
    
    while (true) {
        // Preparar consulta para verificar si el slug ya existe
        $sql = "SELECT id FROM productos WHERE slug = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $slug, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Si no existe, devolver el slug
        if ($result->num_rows == 0) {
            return $slug;
        }
        
        // Si existe, añadir contador y volver a verificar
        $slug = $slug_original . '-' . $contador;
        $contador++;
    }
}

// Función para generar SKU único basado en categoría
function generarSKU($nombre, $categoria_id, $conn) {
    // Obtener prefijo de categoría
    $prefijos = [
        1 => 'PERI', // Periféricos
        2 => 'MON',  // Monitores
        3 => 'LAP',  // Laptops
        4 => 'IMP',  // Impresoras
        5 => 'COMP', // Componentes
        6 => 'PC',   // Computadoras completas
        7 => 'GAB',  // Gabinetes
        8 => 'PROC', // Procesadores
        9 => 'GPU',  // Tarjetas gráficas
        10 => 'MB'   // Placas madre
    ];
    
    $prefijo = isset($prefijos[$categoria_id]) ? $prefijos[$categoria_id] : 'PROD';
    
    // Crear base del SKU
    $base_sku = $prefijo . '-' . strtoupper($nombre);
    
    // Verificar unicidad
    $sku_original = $base_sku;
    $contador = 1;
    
    while (true) {
        $sku = $contador < 10 ? $sku_original . '-00' . $contador : 
              ($contador < 100 ? $sku_original . '-0' . $contador : $sku_original . '-' . $contador);
        
        // Verificar si existe
        $sql = "SELECT id FROM productos WHERE sku = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $sku);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            return $sku;
        }
        
        $contador++;
    }
}
?>