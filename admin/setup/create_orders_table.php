<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Crear tabla de pedidos
$sql_pedidos = "CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_nombre VARCHAR(100) NOT NULL,
    cliente_email VARCHAR(100) NOT NULL,
    cliente_telefono VARCHAR(20),
    cliente_direccion TEXT NOT NULL,
    cliente_distrito VARCHAR(100),
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'pagado', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    metodo_pago VARCHAR(50),
    notas TEXT,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Crear tabla de detalles de pedido
$sql_detalles = "CREATE TABLE IF NOT EXISTS detalles_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    producto_nombre VARCHAR(255) NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
)";

if ($conn->query($sql_pedidos) === TRUE) {
    echo "Tabla de pedidos creada correctamente.<br>";
} else {
    echo "Error al crear tabla de pedidos: " . $conn->error . "<br>";
}

if ($conn->query($sql_detalles) === TRUE) {
    echo "Tabla de detalles de pedido creada correctamente.<br>";
} else {
    echo "Error al crear tabla de detalles de pedido: " . $conn->error . "<br>";
}

// Insertar pedidos de ejemplo para pruebas
$sql_ejemplo = "INSERT INTO pedidos (cliente_nombre, cliente_email, cliente_telefono, cliente_direccion, cliente_distrito, total, estado, metodo_pago, notas) VALUES 
('Juan Pérez', 'juan@example.com', '987654321', 'Av. Principal 123', 'San Isidro', 1499.00, 'pendiente', 'Mercado Pago', 'Entregar por la mañana'),
('María López', 'maria@example.com', '999888777', 'Jr. Los Olivos 456', 'Miraflores', 2345.50, 'pagado', 'Mercado Pago', 'Llamar antes de entregar'),
('Carlos Gómez', 'carlos@example.com', '955123456', 'Calle Las Flores 789', 'San Borja', 699.00, 'enviado', 'Transferencia', 'Dejar con el portero')";

if ($conn->query($sql_ejemplo) === TRUE) {
    echo "Pedidos de ejemplo insertados correctamente.<br>";
    
    // Obtener los IDs de los pedidos insertados
    $result = $conn->query("SELECT id FROM pedidos ORDER BY id DESC LIMIT 3");
    $pedidos_ids = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos_ids[] = $row['id'];
    }
    
    // Obtener algunos productos para los detalles de pedido
    $result = $conn->query("SELECT id, nombre, precio FROM productos LIMIT 5");
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    // Si hay productos, insertar detalles de pedido
    if (!empty($productos) && !empty($pedidos_ids)) {
        $detalles_sql = "INSERT INTO detalles_pedido (pedido_id, producto_id, producto_nombre, cantidad, precio_unitario, subtotal) VALUES ";
        $valores = [];
        
        foreach ($pedidos_ids as $pedido_id) {
            // Elegir 1-3 productos aleatorios para cada pedido
            $num_productos = rand(1, 3);
            $productos_seleccionados = array_slice($productos, 0, $num_productos);
            
            foreach ($productos_seleccionados as $producto) {
                $cantidad = rand(1, 3);
                $subtotal = $producto['precio'] * $cantidad;
                
                $valores[] = "($pedido_id, {$producto['id']}, '{$producto['nombre']}', $cantidad, {$producto['precio']}, $subtotal)";
            }
        }
        
        if (!empty($valores)) {
            $detalles_sql .= implode(", ", $valores);
            
            if ($conn->query($detalles_sql) === TRUE) {
                echo "Detalles de pedido de ejemplo insertados correctamente.<br>";
                
                // Actualizar los totales de los pedidos basados en los detalles
                foreach ($pedidos_ids as $pedido_id) {
                    $result = $conn->query("SELECT SUM(subtotal) as total FROM detalles_pedido WHERE pedido_id = $pedido_id");
                    if ($row = $result->fetch_assoc()) {
                        $total = $row['total'];
                        $conn->query("UPDATE pedidos SET total = $total WHERE id = $pedido_id");
                    }
                }
                
                echo "Totales de pedidos actualizados correctamente.<br>";
            } else {
                echo "Error al insertar detalles de pedido de ejemplo: " . $conn->error . "<br>";
            }
        }
    } else {
        echo "No hay productos o pedidos para crear detalles de ejemplo.<br>";
    }
} else {
    echo "Error al insertar pedidos de ejemplo: " . $conn->error . "<br>";
}

echo "<br>Proceso completado. <a href='../orders/index.php'>Ir a gestión de pedidos</a>";
?>