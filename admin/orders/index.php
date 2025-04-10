<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Parámetros de filtrado y ordenación
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'recientes';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;

// Mensajes y errores
$mensajes = [];
$errores = [];

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $pedido_id = (int)$_POST['pedido_id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    // Validar el estado
    $estados_validos = ['pendiente', 'pagado', 'enviado', 'entregado', 'cancelado'];
    if (in_array($nuevo_estado, $estados_validos)) {
        $stmt = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $pedido_id);
        
        if ($stmt->execute()) {
            $mensajes[] = "Estado del pedido #$pedido_id actualizado a " . ucfirst($nuevo_estado);
        } else {
            $errores[] = "Error al actualizar el estado del pedido: " . $conn->error;
        }
    } else {
        $errores[] = "Estado no válido.";
    }
}

// Construir consulta SQL con filtros
$condiciones = ["1=1"]; // Siempre verdadero para facilitar la concatenación de AND
$params = [];
$param_types = "";

if (!empty($estado)) {
    $condiciones[] = "estado = ?";
    $params[] = $estado;
    $param_types .= "s";
}

if (!empty($fecha_desde)) {
    $condiciones[] = "DATE(fecha_pedido) >= ?";
    $params[] = $fecha_desde;
    $param_types .= "s";
}

if (!empty($fecha_hasta)) {
    $condiciones[] = "DATE(fecha_pedido) <= ?";
    $params[] = $fecha_hasta;
    $param_types .= "s";
}

if (!empty($busqueda)) {
    $condiciones[] = "(cliente_nombre LIKE ? OR cliente_email LIKE ? OR id LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $param_types .= "sss";
}

// Ordenamiento
$order_by = "fecha_pedido DESC"; // Por defecto, más recientes primero
switch ($orden) {
    case 'total_asc':
        $order_by = "total ASC";
        break;
    case 'total_desc':
        $order_by = "total DESC";
        break;
    case 'fecha_asc':
        $order_by = "fecha_pedido ASC";
        break;
    case 'cliente':
        $order_by = "cliente_nombre ASC";
        break;
}

// Construir la consulta
$where = implode(' AND ', $condiciones);
$sql_count = "SELECT COUNT(*) as total FROM pedidos WHERE $where";
$sql = "SELECT * FROM pedidos WHERE $where ORDER BY $order_by LIMIT ?, ?";

// Ejecutar consulta para contar total
$stmt_count = $conn->prepare($sql_count);
if (!empty($param_types)) {
    $stmt_count->bind_param($param_types, ...$params);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_pedidos = $row_count['total'];
$total_paginas = ceil($total_pedidos / $por_pagina);

if ($pagina < 1) $pagina = 1;
if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

$offset = ($pagina - 1) * $por_pagina;

// Ejecutar consulta principal
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $params_with_limit = $params;
    $params_with_limit[] = $offset;
    $params_with_limit[] = $por_pagina;
    $param_types .= "ii";
    $stmt->bind_param($param_types, ...$params_with_limit);
} else {
    $stmt->bind_param("ii", $offset, $por_pagina);
}
$stmt->execute();
$result = $stmt->get_result();
$pedidos = [];

while ($row = $result->fetch_assoc()) {
    $pedidos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Gestión de Pedidos</h1>
                <div class="admin-actions">
                    <a href="../../index.php" target="_blank" class="btn btn-secondary">Ver Tienda</a>
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
                <div class="filter-card">
                    <form action="" method="get" class="filter-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="q">Buscar:</label>
                                <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Nombre, email o ID">
                            </div>
                            
                            <div class="filter-group">
                                <label for="estado">Estado:</label>
                                <select id="estado" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="pendiente" <?php echo $estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="pagado" <?php echo $estado === 'pagado' ? 'selected' : ''; ?>>Pagado</option>
                                    <option value="enviado" <?php echo $estado === 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                                    <option value="entregado" <?php echo $estado === 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                    <option value="cancelado" <?php echo $estado === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="fecha_desde">Desde:</label>
                                <input type="date" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="fecha_hasta">Hasta:</label>
                                <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="orden">Ordenar por:</label>
                                <select id="orden" name="orden">
                                    <option value="recientes" <?php echo $orden === 'recientes' ? 'selected' : ''; ?>>Más recientes</option>
                                    <option value="fecha_asc" <?php echo $orden === 'fecha_asc' ? 'selected' : ''; ?>>Más antiguos</option>
                                    <option value="total_desc" <?php echo $orden === 'total_desc' ? 'selected' : ''; ?>>Mayor importe</option>
                                    <option value="total_asc" <?php echo $orden === 'total_asc' ? 'selected' : ''; ?>>Menor importe</option>
                                    <option value="cliente" <?php echo $orden === 'cliente' ? 'selected' : ''; ?>>Nombre de cliente</option>
                                </select>
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-filter">Aplicar Filtros</button>
                                <a href="index.php" class="btn btn-clear">Limpiar Filtros</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="data-summary">
                    <span>Mostrando <?php echo count($pedidos); ?> de <?php echo $total_pedidos; ?> pedidos</span>
                </div>
                
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Método de Pago</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pedidos)): ?>
                                <tr>
                                    <td colspan="7" class="no-results">No se encontraron pedidos</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pedidos as $pedido): ?>
                                    <tr>
                                        <td>#<?php echo $pedido['id']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                        <td>
                                            <div><?php echo $pedido['cliente_nombre']; ?></div>
                                            <div class="order-email"><?php echo $pedido['cliente_email']; ?></div>
                                        </td>
                                        <td class="order-total">S/ <?php echo number_format($pedido['total'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $pedido['estado']; ?>">
                                                <?php echo ucfirst($pedido['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $pedido['metodo_pago']; ?></td>
                                        <td class="actions">
                                            <a href="view.php?id=<?php echo $pedido['id']; ?>" class="btn-action view" title="Ver detalles">
                                                👁️
                                            </a>
                                            
                                            <button type="button" class="btn-action change-status" title="Cambiar estado" onclick="mostrarCambioEstado(<?php echo $pedido['id']; ?>, '<?php echo $pedido['estado']; ?>')">
                                                🔄
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=1<?php echo !empty($busqueda) ? '&q=' . urlencode($busqueda) : ''; ?><?php echo !empty($estado) ? '&estado=' . $estado : ''; ?><?php echo !empty($fecha_desde) ? '&fecha_desde=' . $fecha_desde : ''; ?><?php echo !empty($fecha_hasta) ? '&fecha_hasta=' . $fecha_hasta : ''; ?><?php echo !empty($orden) ? '&orden=' . $orden : ''; ?>" class="page-link">«</a>
                            <a href="?pagina=<?php echo $pagina - 1; ?><?php echo !empty($busqueda) ? '&q=' . urlencode($busqueda) : ''; ?><?php echo !empty($estado) ? '&estado=' . $estado : ''; ?><?php echo !empty($fecha_desde) ? '&fecha_desde=' . $fecha_desde : ''; ?><?php echo !empty($fecha_hasta) ? '&fecha_hasta=' . $fecha_hasta : ''; ?><?php echo !empty($orden) ? '&orden=' . $orden : ''; ?>" class="page-link">‹</a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $pagina - 2);
                        $end_page = min($total_paginas, $pagina + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?pagina=<?php echo $i; ?><?php echo !empty($busqueda) ? '&q=' . urlencode($busqueda) : ''; ?><?php echo !empty($estado) ? '&estado=' . $estado : ''; ?><?php echo !empty($fecha_desde) ? '&fecha_desde=' . $fecha_desde : ''; ?><?php echo !empty($fecha_hasta) ? '&fecha_hasta=' . $fecha_hasta : ''; ?><?php echo !empty($orden) ? '&orden=' . $orden : ''; ?>" class="page-link <?php echo $i == $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina + 1; ?><?php echo !empty($busqueda) ? '&q=' . urlencode($busqueda) : ''; ?><?php echo !empty($estado) ? '&estado=' . $estado : ''; ?><?php echo !empty($fecha_desde) ? '&fecha_desde=' . $fecha_desde : ''; ?><?php echo !empty($fecha_hasta) ? '&fecha_hasta=' . $fecha_hasta : ''; ?><?php echo !empty($orden) ? '&orden=' . $orden : ''; ?>" class="page-link">›</a>
                            <a href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($busqueda) ? '&q=' . urlencode($busqueda) : ''; ?><?php echo !empty($estado) ? '&estado=' . $estado : ''; ?><?php echo !empty($fecha_desde) ? '&fecha_desde=' . $fecha_desde : ''; ?><?php echo !empty($fecha_hasta) ? '&fecha_hasta=' . $fecha_hasta : ''; ?><?php echo !empty($orden) ? '&orden=' . $orden : ''; ?>" class="page-link">»</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    
    <!-- Modal para cambiar estado -->
    <div id="modal-estado" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" onclick="cerrarModal()">&times;</span>
            <h2>Cambiar Estado del Pedido</h2>
            <form action="" method="post" id="form-cambiar-estado">
                <input type="hidden" name="pedido_id" id="pedido_id" value="">
                
                <div class="form-group">
                    <label for="nuevo_estado">Nuevo Estado:</label>
                    <select id="nuevo_estado" name="nuevo_estado" required>
                        <option value="pendiente">Pendiente</option>
                        <option value="pagado">Pagado</option>
                        <option value="enviado">Enviado</option>
                        <option value="entregado">Entregado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="actualizar_estado" class="btn btn-primary">Actualizar Estado</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        .order-email {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
        
        .order-total {
            font-weight: bold;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-pagado {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-enviado {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-entregado {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-cancelado {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Estilos para el modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }
        
        .modal-close:hover {
            color: #333;
        }
    </style>
    
    <script>
        // Función para mostrar el modal de cambio de estado
        function mostrarCambioEstado(pedidoId, estadoActual) {
            document.getElementById('pedido_id').value = pedidoId;
            document.getElementById('nuevo_estado').value = estadoActual;
            document.getElementById('modal-estado').style.display = 'flex';
        }
        
        // Función para cerrar el modal
        function cerrarModal() {
            document.getElementById('modal-estado').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera del contenido
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('modal-estado');
            if (event.target === modal) {
                cerrarModal();
            }
        });
        
        // Aplicar filtros automáticamente al cambiar un select
        document.querySelectorAll('.filter-form select, .filter-form input[type="date"]').forEach(el => {
            el.addEventListener('change', function() {
                document.querySelector('.filter-form').submit();
            });
        });
    </script>
</body>
</html>