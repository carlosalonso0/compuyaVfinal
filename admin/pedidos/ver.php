<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Detalle de Pedido';

// Verificar que se ha enviado un ID de pedido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$pedido_id = intval($_GET['id']);

// Obtener información del pedido
$query = "SELECT p.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido, u.email as usuario_email 
          FROM pedidos p
          LEFT JOIN usuarios u ON p.usuario_id = u.id
          WHERE p.id = :id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $pedido_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: index.php');
    exit;
}

$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener detalles del pedido
$query = "SELECT d.*, p.imagen_principal, p.nombre as producto_nombre_actual 
          FROM detalles_pedido d
          LEFT JOIN productos p ON d.producto_id = p.id
          WHERE d.pedido_id = :pedido_id
          ORDER BY d.id ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':pedido_id', $pedido_id);
$stmt->execute();
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar actualización de estado
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_estado'])) {
    $nuevo_estado = $_POST['estado'];
    $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
    
    try {
        // Actualizar estado del pedido
        $query = "UPDATE pedidos SET estado = :estado WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':estado', $nuevo_estado);
        $stmt->bindParam(':id', $pedido_id);
        $stmt->execute();
        
        // Guardar comentario si existe
        if (!empty($comentario)) {
            // Aquí podrías implementar un sistema de comentarios o notas para los pedidos
        }
        
        $mensaje = "Estado del pedido actualizado correctamente.";
        $tipo_mensaje = "success";
        
        // Actualizar la información del pedido
        $query = "SELECT p.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido, u.email as usuario_email 
                  FROM pedidos p
                  LEFT JOIN usuarios u ON p.usuario_id = u.id
                  WHERE p.id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $pedido_id);
        $stmt->execute();
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar el estado del pedido: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Incluir encabezado
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Pedido #<?php echo $pedido['numero_pedido']; ?></h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Pedidos
        </a>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>" role="alert">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Información del pedido -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Información del Pedido</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Estado:</strong>
                        <?php
                        switch($pedido['estado']) {
                            case 'pendiente':
                                echo '<span class="badge bg-warning">Pendiente</span>';
                                break;
                            case 'pagado':
                                echo '<span class="badge bg-info">Pagado</span>';
                                break;
                            case 'enviado':
                                echo '<span class="badge bg-primary">Enviado</span>';
                                break;
                            case 'entregado':
                                echo '<span class="badge bg-success">Entregado</span>';
                                break;
                            case 'cancelado':
                                echo '<span class="badge bg-danger">Cancelado</span>';
                                break;
                        }
                        ?>
                    </div>
                    <div class="mb-3">
                        <strong>Fecha del Pedido:</strong><br>
                        <?php echo date("d/m/Y H:i", strtotime($pedido['fecha_pedido'])); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Método de Pago:</strong><br>
                        <?php echo $pedido['metodo_pago']; ?>
                    </div>
                    <?php if(!empty($pedido['id_transaccion'])): ?>
                        <div class="mb-3">
                            <strong>ID de Transacción:</strong><br>
                            <?php echo $pedido['id_transaccion']; ?>
                        </div>
                    <?php endif; ?>
                    <?php if(!empty($pedido['notas'])): ?>
                        <div class="mb-3">
                            <strong>Notas:</strong><br>
                            <?php echo nl2br($pedido['notas']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actualizar Estado -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Actualizar Estado</h6>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado:</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="pendiente" <?php echo $pedido['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="pagado" <?php echo $pedido['estado'] == 'pagado' ? 'selected' : ''; ?>>Pagado</option>
                                <option value="enviado" <?php echo $pedido['estado'] == 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                                <option value="entregado" <?php echo $pedido['estado'] == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                <option value="cancelado" <?php echo $pedido['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="comentario" class="form-label">Comentario (opcional):</label>
                            <textarea class="form-control" id="comentario" name="comentario" rows="3"></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="actualizar_estado" class="btn btn-primary">Actualizar Estado</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Detalles del pedido -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Productos</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $detalle): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($detalle['imagen_principal'])): ?>
                                                    <img src="../../assets/uploads/productos/<?php echo $detalle['imagen_principal']; ?>" alt="<?php echo $detalle['nombre_producto']; ?>" class="me-2" style="width: 50px; height: auto;">
                                                <?php endif; ?>
                                                <div>
                                                    <?php echo $detalle['nombre_producto']; ?>
                                                    <?php if (!empty($detalle['producto_id']) && !empty($detalle['producto_nombre_actual']) && $detalle['nombre_producto'] != $detalle['producto_nombre_actual']): ?>
                                                        <br><small class="text-muted">Nombre actual: <?php echo $detalle['producto_nombre_actual']; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>S/ <?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                        <td><?php echo $detalle['cantidad']; ?></td>
                                        <td>S/ <?php echo number_format($detalle['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th>S/ <?php echo number_format($pedido['total'], 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Información del cliente -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold">Información del Cliente</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Nombre:</strong> 
                                <?php 
                                if(!empty($pedido['usuario_nombre']) && !empty($pedido['usuario_apellido'])) {
                                    echo $pedido['usuario_nombre'] . ' ' . $pedido['usuario_apellido'];
                                } else {
                                    echo $pedido['nombre_envio'] . ' ' . $pedido['apellido_envio'];
                                }
                                ?>
                            </p>
                            <p><strong>Email:</strong> 
                                <?php 
                                if(!empty($pedido['usuario_email'])) {
                                    echo $pedido['usuario_email'];
                                } else {
                                    echo $pedido['email_envio'];
                                }
                                ?>
                            </p>
                            <p><strong>Teléfono:</strong> <?php echo $pedido['telefono_envio']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold">Dirección de Envío</h6>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br($pedido['direccion_envio']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir pie de página
include '../includes/footer.php';
?>