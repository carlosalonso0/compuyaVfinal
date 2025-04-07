<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Gestión de Pedidos';

// Incluir encabezado
include '../includes/header.php';

// Filtrar por estado si se proporciona
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Consulta base
$query = "SELECT p.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido 
          FROM pedidos p
          LEFT JOIN usuarios u ON p.usuario_id = u.id ";

// Añadir filtro si se seleccionó un estado
if (!empty($filtro_estado)) {
    $query .= "WHERE p.estado = :estado ";
}

// Ordenar por fecha de pedido (más reciente primero)
$query .= "ORDER BY p.fecha_pedido DESC";

$stmt = $db->prepare($query);

// Bind parámetro de estado si existe
if (!empty($filtro_estado)) {
    $stmt->bindParam(':estado', $filtro_estado);
}

$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Gestión de Pedidos</h1>
        <div>
            <!-- Filtros de estado -->
            <div class="btn-group">
                <a href="index.php" class="btn <?php echo empty($filtro_estado) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Todos
                </a>
                <a href="index.php?estado=pendiente" class="btn <?php echo $filtro_estado === 'pendiente' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    Pendientes
                </a>
                <a href="index.php?estado=pagado" class="btn <?php echo $filtro_estado === 'pagado' ? 'btn-info' : 'btn-outline-info'; ?>">
                    Pagados
                </a>
                <a href="index.php?estado=enviado" class="btn <?php echo $filtro_estado === 'enviado' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Enviados
                </a>
                <a href="index.php?estado=entregado" class="btn <?php echo $filtro_estado === 'entregado' ? 'btn-success' : 'btn-outline-success'; ?>">
                    Entregados
                </a>
                <a href="index.php?estado=cancelado" class="btn <?php echo $filtro_estado === 'cancelado' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                    Cancelados
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-<?php echo isset($_GET['tipo']) ? $_GET['tipo'] : 'info'; ?>" role="alert">
            <?php echo $_GET['mensaje']; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Nº Pedido</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Método de Pago</th>
                            <th>Acciones</th>
                       </tr>
                   </thead>
                   <tbody>
                       <?php if(count($pedidos) > 0): ?>
                           <?php foreach($pedidos as $pedido): ?>
                               <tr>
                                   <td><?php echo $pedido['numero_pedido']; ?></td>
                                   <td>
                                       <?php 
                                       if(isset($pedido['usuario_nombre']) && isset($pedido['usuario_apellido'])) {
                                           echo $pedido['usuario_nombre'] . ' ' . $pedido['usuario_apellido'];
                                       } else {
                                           echo $pedido['nombre_envio'] . ' ' . $pedido['apellido_envio'];
                                       }
                                       ?>
                                   </td>
                                   <td><?php echo date("d/m/Y H:i", strtotime($pedido['fecha_pedido'])); ?></td>
                                   <td>S/ <?php echo number_format($pedido['total'], 2); ?></td>
                                   <td>
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
                                   </td>
                                   <td><?php echo $pedido['metodo_pago']; ?></td>
                                   <td>
                                       <a href="ver.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Ver
                                       </a>
                                   </td>
                               </tr>
                           <?php endforeach; ?>
                       <?php else: ?>
                           <tr>
                               <td colspan="7" class="text-center">No hay pedidos disponibles</td>
                           </tr>
                       <?php endif; ?>
                   </tbody>
               </table>
           </div>
       </div>
   </div>
</div>

<?php
// Incluir pie de página
include '../includes/footer.php';
?>