<?php
// Archivo para añadir productos al carrito mediante AJAX
header('Content-Type: application/json');
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Asegurarse de que es una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'error' => 'Acceso no permitido']);
    exit;
}

// Obtener datos del producto
$producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
$cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;

// Validar ID y cantidad
if ($producto_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de producto inválido']);
    exit;
}

if ($cantidad <= 0) {
    echo json_encode(['success' => false, 'error' => 'Cantidad inválida']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Verificar si el producto existe y está activo
$stmt = $conn->prepare("SELECT id, nombre, stock, slug FROM productos WHERE id = ? AND activo = 1");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Producto no encontrado o no disponible']);
    exit;
}

$producto = $result->fetch_assoc();

// Verificar stock
if ($producto['stock'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'Producto sin stock disponible']);
    exit;
}

// Limitar cantidad al stock disponible
if ($cantidad > $producto['stock']) {
    $cantidad = $producto['stock'];
}

// Verificar si el producto ya está en el carrito
if (isset($_SESSION['carrito'][$producto_id])) {
    // Sumar la cantidad nueva, pero sin exceder el stock
    $cantidad_nueva = $_SESSION['carrito'][$producto_id] + $cantidad;
    
    if ($cantidad_nueva > $producto['stock']) {
        $cantidad_nueva = $producto['stock'];
    }
    
    $_SESSION['carrito'][$producto_id] = $cantidad_nueva;
    $mensaje = "Se actualizó la cantidad en el carrito.";
} else {
    // Añadir nuevo producto al carrito
    $_SESSION['carrito'][$producto_id] = $cantidad;
    $mensaje = "Producto añadido al carrito.";
}

// Obtener el número total de productos en el carrito
$total_productos = 0;
foreach ($_SESSION['carrito'] as $cantidad_producto) {
    $total_productos += $cantidad_producto;
}

// Generar URL de redirección
$redirect_url = BASE_URL . '/producto/' . $producto['slug'] . '?added=1';

// Devolver respuesta exitosa
echo json_encode([
    'success' => true, 
    'message' => $mensaje, 
    'product_name' => $producto['nombre'],
    'cart_count' => $total_productos,
    'redirect_url' => $redirect_url
]);
exit;
?>