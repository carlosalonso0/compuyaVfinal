<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Obtener ID del pedido
$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pedido_id <= 0) {
    header('Location: index.php');
    exit;
}

// Mensajes y errores
$mensajes = [];
$errores = [];

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $nuevo_estado = $_POST['nuevo_estado'];
    
    // Validar el estado
    $estados_validos = ['pendiente', 'pagado', 'enviado', 'entregado', 'cancelado'];
    if (in_array($nuevo_estado, $estados_validos)) {
        $stmt = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $pedido_id);
        
        if ($stmt->execute()) {
            $mensajes[] = "Estado del pedido actualizado a " . ucfirst($nuevo_estado);
        } else {
            $errores[] = "Error al actualizar el estado del pedido: " . $conn->error;
        }
    } else {
        $errores[] = "Estado no válido.";
    }
}

// Obtener datos del pedido
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$pedido = $result->fetch_assoc();

// Obtener detalles del pedido
$detalles = [];
$stmt = $conn->prepare("SELECT * FROM detalles_pedido WHERE pedido_id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $detalles[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Pedido #<?php echo $pedido_id; ?> - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Detalles del Pedido #<?php echo $pedido_id; ?></h1>
                <div class="admin-actions">
                    <a href="index.php" class="btn btn-secondary">Volver a Pedidos</a>
                    <button type="button" class="btn btn-primary" onclick="imprimirPedido()">Imprimir</button>
                </div>
            </header>
            
            <?php if (!empty($mensajes)): ?>
                <div class="mensajes-container">
                    <?php foreach ($mensajes as $mensaje): ?>
                        <div class="mensaje exito"><?php echo $mensaje; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errores)): ?>
                <div class="mensajes-container">
                    <?php foreach ($errores as $error): ?>
                        <div class="mensaje error"><?php echo $error; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <section class="admin-section">
                <div class="order-details">
                    <div class="order-status-bar">
                        <div class="order-current-status">
                            <span class="status-label">Estado actual:</span>
                            <span class="status-badge status-<?php echo $pedido['estado']; ?>">
                                <?php echo ucfirst($pedido['estado']); ?>
                            </span>
                        </div>
                        
                        <div class="order-status-actions">
                            <form action="" method="post" class="status-form">
                                <div class="form-inline">
                                    <label for="nuevo_estado">Cambiar a:</label>
                                    <select id="nuevo_estado" name="nuevo_estado">
                                        <option value="pendiente" <?php echo $pedido['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="pagado" <?php echo $pedido['estado'] === 'pagado' ? 'selected' : ''; ?>>Pagado</option>
                                        <option value="enviado" <?php echo $pedido['estado'] === 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                                        <option value="entregado" <?php echo $pedido['estado'] === 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                        <option value="cancelado" <?php echo $pedido['estado'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                    <button type="submit" name="actualizar_estado" class="btn btn-small">Actualizar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="order-grid">
                        <div class="order-info-card">
                            <h2>Información del Pedido</h2>
                            <div class="info-group">
                                <div class="info-label">Número de Pedido:</div>
                                <div class="info-value">#<?php echo $pedido['id']; ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Fecha:</div>
                                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Estado:</div>
                                <div class="info-value">
                                    <span class="status-badge status-<?php echo $pedido['estado']; ?>">
                                        <?php echo ucfirst($pedido['estado']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Método de Pago:</div>
                                <div class="info-value"><?php echo $pedido['metodo_pago']; ?></div>
                            </div>
                            <?php if (!empty($pedido['notas'])): ?>
                                <div class="info-group">
                                    <div class="info-label">Notas:</div>
                                    <div class="info-value"><?php echo nl2br(htmlspecialchars($pedido['notas'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-info-card">
                            <h2>Información del Cliente</h2>
                            <div class="info-group">
                                <div class="info-label">Nombre:</div>
                                <div class="info-value"><?php echo $pedido['cliente_nombre']; ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Email:</div>
                                <div class="info-value"><?php echo $pedido['cliente_email']; ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Teléfono:</div>
                                <div class="info-value"><?php echo $pedido['cliente_telefono']; ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Dirección:</div>
                                <div class="info-value"><?php echo $pedido['cliente_direccion']; ?></div>
                            </div>
                            <?php if (!empty($pedido['cliente_distrito'])): ?>
                                <div class="info-group">
                                    <div class="info-label">Distrito:</div>
                                    <div class="info-value"><?php echo $pedido['cliente_distrito']; ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="order-products">
                        <h2>Productos</h2>
                        <div class="order-products-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Precio Unitario</th>
                                        <th>Cantidad</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($detalles)): ?>
                                        <tr>
                                            <td colspan="4" class="no-results">No hay productos en este pedido</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($detalles as $detalle): ?>
                                            <tr>
                                                <td class="product-name">
                                                    <a href="../products/edit.php?id=<?php echo $detalle['producto_id']; ?>">
                                                        <?php echo $detalle['producto_nombre']; ?>
                                                    </a>
                                                </td>
                                                <td class="product-price">S/ <?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                                <td class="product-quantity"><?php echo $detalle['cantidad']; ?></td>
                                                <td class="product-subtotal">S/ <?php echo number_format($detalle['subtotal'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="order-total-label">Total</td>
                                        <td class="order-total-value">S/ <?php echo number_format($pedido['total'], 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    <style>
        /* Estilos específicos para detalles de pedido */
        .order-status-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .status-label {
            margin-right: 10px;
            font-weight: 500;
        }
        
        .form-inline {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 14px;
        }
        
        .order-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .order-info-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        .order-info-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-size: 18px;
            color: #333;
        }
        
        .info-group {
            display: flex;
            margin-bottom: 12px;
        }
        
        .info-label {
            width: 120px;
            font-weight: 500;
            color: #666;
        }
        
        .info-value {
            flex-grow: 1;
        }
        
        .order-products {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .order-products h2 {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-size: 18px;
            color: #333;
        }
        
        .order-products-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .order-products-table th, 
        .order-products-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .order-products-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #444;
        }
        
        .product-name a {
            color: #001CBD;
            text-decoration: none;
        }
        
        .product-name a:hover {
            text-decoration: underline;
        }
        
        .product-price, 
        .product-quantity, 
        .product-subtotal {
            text-align: center;
        }
        
        .order-total-label {
            text-align: right;
            font-weight: 600;
        }
        
        .order-total-value {
            font-weight: 700;
            font-size: 18px;
            color: #DD0B0B;
            text-align: center;
        }
        
        tfoot tr {
            background-color: #f8f9fa;
        }
        
        @media print {
            .admin-sidebar, 
            .admin-actions, 
            .order-status-actions,
            .mensajes-container {
                display: none !important;
            }
            
            .admin-container {
                display: block;
            }
            
            .admin-content {
                margin-left: 0;
                padding: 0;
            }
            
            .order-info-card, 
            .order-products {
                box-shadow: none;
                padding: 0;
            }
            
            body {
                font-size: 12pt;
                color: #000;
            }
            
            a {
                text-decoration: none;
                color: #000;
            }
        }
        
        @media (max-width: 768px) {
            .order-grid {
                grid-template-columns: 1fr;
            }
            
            .order-status-bar {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .info-group {
                flex-direction: column;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
    
    <script>
        function imprimirPedido() {
            window.print();
        }
    </script>
</body>
</html>