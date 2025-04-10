<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar si está iniciada la sesión
// Aquí iría el control de acceso cuando implementemos el login

$db = Database::getInstance();
$conn = $db->getConnection();

// Estadísticas generales
$total_productos = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
if ($row = $result->fetch_assoc()) {
    $total_productos = $row['total'];
}

$total_categorias = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM categorias");
if ($row = $result->fetch_assoc()) {
    $total_categorias = $row['total'];
}

$total_pedidos = 0; // Esto se implementará cuando creemos la tabla de pedidos

$productos_destacados = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM productos WHERE destacado = 1 AND activo = 1");
if ($row = $result->fetch_assoc()) {
    $productos_destacados = $row['total'];
}

// Productos con poco stock
$productos_poco_stock = [];
$result = $conn->query("SELECT p.*, c.nombre as categoria_nombre 
                       FROM productos p
                       JOIN categorias c ON p.categoria_id = c.id
                       WHERE p.stock <= 5 AND p.activo = 1
                       ORDER BY p.stock ASC
                       LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $productos_poco_stock[] = $row;
}

// Productos más recientes
$productos_recientes = [];
$result = $conn->query("SELECT p.*, c.nombre as categoria_nombre 
                       FROM productos p
                       JOIN categorias c ON p.categoria_id = c.id
                       WHERE p.activo = 1
                       ORDER BY p.id DESC
                       LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $productos_recientes[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel de Administración</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-actions">
                    <a href="../index.php" target="_blank" class="btn btn-secondary">Ver Tienda</a>
                </div>
            </header>
            
            <section class="admin-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #e3f2fd;">📦</div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_productos; ?></div>
                            <div class="stat-label">Total Productos</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #e8f5e9;">🏷️</div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_categorias; ?></div>
                            <div class="stat-label">Categorías</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #fff3e0;">⭐</div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $productos_destacados; ?></div>
                            <div class="stat-label">Productos Destacados</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #f3e5f5;">🛒</div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_pedidos; ?></div>
                            <div class="stat-label">Total Pedidos</div>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="admin-section">
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h2>Productos con Poco Stock</h2>
                            <a href="products/index.php?stock_asc=1" class="btn-link">Ver Todos</a>
                        </div>
                        <div class="dashboard-card-content">
                            <?php if (empty($productos_poco_stock)): ?>
                                <div class="empty-data">No hay productos con poco stock.</div>
                            <?php else: ?>
                                <table class="dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Categoría</th>
                                            <th>Stock</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos_poco_stock as $producto): ?>
                                            <tr>
                                                <td>
                                                    <div class="product-name-text"><?php echo $producto['nombre']; ?></div>
                                                    <div class="product-brand"><?php echo $producto['marca']; ?> <?php echo $producto['modelo']; ?></div>
                                                </td>
                                                <td><?php echo $producto['categoria_nombre']; ?></td>
                                                <td class="product-stock <?php echo $producto['stock'] <= 0 ? 'out-of-stock' : 'low-stock'; ?>">
                                                    <?php echo $producto['stock']; ?>
                                                </td>
                                                <td>
                                                    <a href="products/edit.php?id=<?php echo $producto['id']; ?>" class="btn-action edit" title="Editar">
                                                        ✏️
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h2>Productos Añadidos Recientemente</h2>
                            <a href="products/index.php" class="btn-link">Ver Todos</a>
                        </div>
                        <div class="dashboard-card-content">
                            <?php if (empty($productos_recientes)): ?>
                                <div class="empty-data">No hay productos recientes.</div>
                            <?php else: ?>
                                <table class="dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Categoría</th>
                                            <th>Precio</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos_recientes as $producto): ?>
                                            <tr>
                                                <td>
                                                    <div class="product-name-text"><?php echo $producto['nombre']; ?></div>
                                                    <div class="product-brand"><?php echo $producto['marca']; ?> <?php echo $producto['modelo']; ?></div>
                                                </td>
                                                <td><?php echo $producto['categoria_nombre']; ?></td>
                                                <td class="product-price">
                                                    <?php if (!empty($producto['precio_oferta'])): ?>
                                                        <div class="price-original">S/ <?php echo number_format($producto['precio'], 2); ?></div>
                                                        <div class="price-offer">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></div>
                                                    <?php else: ?>
                                                        <div class="price-normal">S/ <?php echo number_format($producto['precio'], 2); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="products/edit.php?id=<?php echo $producto['id']; ?>" class="btn-action edit" title="Editar">
                                                        ✏️
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="admin-section">
                <div class="dashboard-footer">
                    <div class="quick-actions">
                        <h3>Acciones Rápidas</h3>
                        <div class="quick-actions-grid">
                            <a href="products/add.php" class="quick-action-card">
                                <div class="quick-action-icon">➕</div>
                                <div class="quick-action-text">Añadir Producto</div>
                            </a>
                            
                            <a href="products/import.php" class="quick-action-card">
                                <div class="quick-action-icon">📤</div>
                                <div class="quick-action-text">Importar CSV</div>
                            </a>
                            
                            <a href="homepage/index.php" class="quick-action-card">
                                <div class="quick-action-icon">🏠</div>
                                <div class="quick-action-text">Editar Inicio</div>
                            </a>
                            
                            <a href="orders/index.php" class="quick-action-card">
                                <div class="quick-action-icon">🛒</div>
                                <div class="quick-action-text">Ver Pedidos</div>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>