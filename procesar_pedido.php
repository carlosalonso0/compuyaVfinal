<?php
// Este archivo se llamará desde JavaScript para añadir los detalles del pedido

// Incluir archivo de configuración
require_once 'config/config.php';
require_once 'config/database.php';

// Verificar que sea una petición POST y tenga los datos necesarios
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['pedido_id']) || empty($_POST['items'])) {
   http_response_code(400);
   echo json_encode(['error' => 'Datos de pedido incompletos']);
   exit;
}

// Obtener datos del pedido
$pedido_id = intval($_POST['pedido_id']);
$items = json_decode($_POST['items'], true);

// Verificar que los items sean válidos
if (!is_array($items) || empty($items)) {
   http_response_code(400);
   echo json_encode(['error' => 'No hay productos en el pedido']);
   exit;
}

try {
   // Conectar a la base de datos
   $database = new Database();
   $db = $database->connect();
   
   // Iniciar transacción
   $db->beginTransaction();
   
   // Preparar la consulta para insertar detalles
   $query = "INSERT INTO detalles_pedido 
             (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, subtotal) 
             VALUES 
             (:pedido_id, :producto_id, :nombre_producto, :precio_unitario, :cantidad, :subtotal)";
   
   $stmt = $db->prepare($query);
   
   // Insertar cada producto
   foreach ($items as $item) {
       $producto_id = $item['id'] ?? null;
       $nombre = $item['name'] ?? '';
       $precio = floatval($item['price'] ?? 0);
       $cantidad = intval($item['quantity'] ?? 0);
       $subtotal = $precio * $cantidad;
       
       $stmt->bindParam(':pedido_id', $pedido_id);
       $stmt->bindParam(':producto_id', $producto_id);
       $stmt->bindParam(':nombre_producto', $nombre);
       $stmt->bindParam(':precio_unitario', $precio);
       $stmt->bindParam(':cantidad', $cantidad);
       $stmt->bindParam(':subtotal', $subtotal);
       
       $stmt->execute();
       
       // Actualizar stock si es necesario
       if ($producto_id) {
           $update_stock = "UPDATE productos SET stock = stock - :cantidad WHERE id = :producto_id";
           $stock_stmt = $db->prepare($update_stock);
           $stock_stmt->bindParam(':cantidad', $cantidad);
           $stock_stmt->bindParam(':producto_id', $producto_id);
           $stock_stmt->execute();
       }
   }
   
   // Confirmar transacción
   $db->commit();
   
   // Respuesta exitosa
   http_response_code(200);
   echo json_encode(['success' => true, 'message' => 'Detalles del pedido guardados correctamente']);
   
} catch (PDOException $e) {
   // Revertir transacción en caso de error
   if ($db->inTransaction()) {
       $db->rollBack();
   }
   
   // Respuesta de error
   http_response_code(500);
   echo json_encode(['error' => 'Error al procesar los detalles del pedido: ' . $e->getMessage()]);
}