<?php
// Incluir autenticación
require_once 'includes/auth.php';

// Título de la página
$page_title = 'Dashboard';

// Incluir encabezado
include 'includes/header.php';

// Obtener estadísticas básicas
$stats = array();

// Total de productos
$query = "SELECT COUNT(*) as total FROM productos WHERE activo = true";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['productos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de categorías
$query = "SELECT COUNT(*) as total FROM categorias WHERE activo = true";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['categorias'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de pedidos
$query = "SELECT COUNT(*) as total FROM pedidos";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pedidos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de usuarios
$query = "SELECT COUNT(*) as total FROM usuarios WHERE activo = true";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pedidos recientes
$query = "SELECT p.*, u.nombre, u.apellido 
          FROM pedidos p 
          LEFT JOIN usuarios u ON p.usuario_id = u.id 
          ORDER BY p.fecha_pedido DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$pedidos_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Dashboard</h1>
    
    <!-- Tarjetas de estadísticas -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Productos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['productos']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Categorías</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['categorias']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Pedidos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pedidos']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Usuarios</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['usuarios']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pedidos recientes -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Pedidos Recientes</h6>
                    <a href="<?php echo ADMIN_URL; ?>pedidos/" class="btn btn-sm btn-primary">Ver todos</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nº Pedido</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($pedidos_recientes) > 0): ?>
                                    <?php foreach($pedidos_recientes as $pedido): ?>
                                        <tr>
                                            <td><?php echo $pedido['numero_pedido']; ?></td>
                                            <td>
                                                <?php 
                                                if(isset($pedido['nombre']) && isset($pedido['apellido'])) {
                                                    echo $pedido['nombre'] . ' ' . $pedido['apellido'];
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
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>pedidos/ver.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No hay pedidos recientes</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Acciones Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="<?php echo ADMIN_URL; ?>productos/crear.php" class="btn btn-primary btn-block py-3">
                                <i class="fas fa-plus-circle me-2"></i> Añadir Producto
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="<?php echo ADMIN_URL; ?>categorias/crear.php" class="btn btn-success btn-block py-3">
                                <i class="fas fa-folder-plus me-2"></i> Añadir Categoría
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="<?php echo ADMIN_URL; ?>productos/importar.php" class="btn btn-info btn-block py-3">
                                <i class="fas fa-file-import me-2"></i> Importar Productos
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="<?php echo ADMIN_URL; ?>config/" class="btn btn-secondary btn-block py-3">
                                <i class="fas fa-cog me-2"></i> Configuración
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>