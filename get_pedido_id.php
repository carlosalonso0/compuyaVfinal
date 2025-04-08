<?php
// Incluir archivo de configuración
require_once 'config/config.php';
require_once 'config/database.php';

// Verificar que se proporcionó un número de pedido
if (empty($_GET['numero'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Número de pedido no proporcionado']);
    exit;
}

$numero_pedido = $_GET['numero'];

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->connect();
    
    // Consultar el ID del pedido
    $query = "SELECT id FROM pedidos WHERE numero_pedido = :numero_pedido LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':numero_pedido', $numero_pedido);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['pedido_id' => $pedido['id']]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido no encontrado']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al consultar el pedido: ' . $e->getMessage()]);
}