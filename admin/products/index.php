<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Verificar si está iniciada la sesión
// Aquí iría el control de acceso cuando implementemos el login

$db = Database::getInstance();
$conn = $db->getConnection();

$mensajes = [];
$errores = [];

// Obtener parámetros de filtro y paginación
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$destacados = isset($_GET['destacados']) ? (int)$_GET['destacados'] : -1;
$nuevos = isset($_GET['nuevos']) ? (int)$_GET['nuevos'] : -1;
$ofertas = isset($_GET['ofertas']) ? (int)$_GET['ofertas'] : -1;
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'recientes';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;

// Procesar acción para cambiar estado (activo/inactivo)
if (isset($_POST['toggle_activo']) && isset($_POST['producto_id'])) {
    $producto_id = (int)$_POST['producto_id'];
    
    // Verificar estado actual
    $stmt = $conn->prepare("SELECT activo FROM productos WHERE id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $nuevo_estado = $row['activo'] ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE productos SET activo = ? WHERE id = ?");
        $stmt->bind_param("ii", $nuevo_estado, $producto_id);
        
        if ($stmt->execute()) {
            $estado_texto = $nuevo_estado ? "activado" : "desactivado";
            $mensajes[] = "Producto $estado_texto correctamente.";
        } else {
            $errores[] = "Error al cambiar el estado del producto.";
        }
    }
}

// Construir consulta SQL con filtros
$condiciones = ["1=1"]; // Siempre verdadero para facilitar la concatenación de AND
$params = [];
$param_types = "";

if (!empty($busqueda)) {
    $condiciones[] = "(p.nombre LIKE ? OR p.marca LIKE ? OR p.modelo LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $param_types .= "sss";
}

if ($categoria > 0) {
    $condiciones[] = "p.categoria_id = ?";
    $params[] = $categoria;
    $param_types .= "i";
}

if ($destacados >= 0) {
    $condiciones[] = "p.destacado = ?";
    $params[] = $destacados;
    $param_types .= "i";
}

if ($nuevos >= 0) {
    $condiciones[] = "p.nuevo = ?";
    $params[] = $nuevos;
    $param_types .= "i";
}

if ($ofertas == 1) {
    $condiciones[] = "p.precio_oferta IS NOT NULL AND p.precio_oferta > 0";
} elseif ($ofertas == 0) {
    $condiciones[] = "(p.precio_oferta IS NULL OR p.precio_oferta = 0)";
}

// Ordenamiento
$order_by = "p.id DESC"; // Por defecto, más recientes primero
switch ($orden) {
    case 'nombre_asc':
        $order_by = "p.nombre ASC";
        break;
    case 'nombre_desc':
        $order_by = "p.nombre DESC";
        break;
    case 'precio_asc':
        $order_by = "p.precio ASC";
        break;
    case 'precio_desc':
        $order_by = "p.precio DESC";
        break;
    case 'stock_asc':
        $order_by = "p.stock ASC";
        break;
    case 'stock_desc':
        $order_by = "p.stock DESC";
        break;
}

// Construir la consulta
$where = implode(' AND ', $condiciones);
$sql_count = "SELECT COUNT(*) as total FROM productos p WHERE $where";
$sql = "SELECT p.*, c.nombre as categoria_nombre 
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE $where 
        ORDER BY $order_by 
        LIMIT ?, ?";

// Ejecutar consulta para contar total
$stmt_count = $conn->prepare($sql_count);
if (!empty($param_types)) {
    $stmt_count->bind_param($param_types, ...$params);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_productos = $row_count['total'];
$total_paginas = ceil($total_productos / $por_pagina);

if ($pagina < 1) $pagina = 1;
if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

$offset = ($pagina - 1) * $por_pagina;

// Ejecutar consulta principal
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $params[] = $offset;
    $params[] = $por_pagina;
    $param_types .= "ii";
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param("ii", $offset, $por_pagina);
}
$stmt->execute();
$result = $stmt->get_result();
$productos = [];

while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

// Obtener todas las categorías para el filtro
$result_categorias = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
$categorias = [];
while ($row = $result_categorias->fetch_assoc()) {
    $categorias[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Gestión de Productos</h1>
                <div class="admin-actions">
                    <a href="add.php" class="btn btn-primary">Añadir Producto</a>
                    <a href="import.php" class="btn btn-secondary">Importar CSV</a>
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
                                <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Nombre, marca o modelo">
                            </div>
                            
                            <div class="filter-group">
                                <label for="categoria">Categoría:</label>
                                <select id="categoria" name="categoria">
                                    <option value="0">Todas las categorías</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoria == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="destacados">Destacados:</label>
                                <select id="destacados" name="destacados">
                                    <option value="-1" <?php echo $destacados == -1 ? 'selected' : ''; ?>>Todos</option>
                                    <option value="1" <?php echo $destacados == 1 ? 'selected' : ''; ?>>Sí</option>
                                    <option value="0" <?php echo $destacados == 0 ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="nuevos">Nuevos:</label>
                                <select id="nuevos" name="nuevos">
                                    <option value="-1" <?php echo $nuevos == -1 ? 'selected' : ''; ?>>Todos</option>
                                    <option value="1" <?php echo $nuevos == 1 ? 'selected' : ''; ?>>Sí</option>
                                    <option value="0" <?php echo $nuevos == 0 ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="ofertas">Ofertas:</label>
                                <select id="ofertas" name="ofertas">
                                    <option value="-1" <?php echo $ofertas == -1 ? 'selected' : ''; ?>>Todos</option>
                                    <option value="1" <?php echo $ofertas == 1 ? 'selected' : ''; ?>>Con oferta</option>
                                    <option value="0" <?php echo $ofertas == 0 ? 'selected' : ''; ?>>Sin oferta</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="orden">Ordenar por:</label>
                                <select id="orden" name="orden">
                                    <option value="recientes" <?php echo $orden == 'recientes' ? 'selected' : ''; ?>>Más recientes</option>
                                    <option value="nombre_asc" <?php echo $orden == 'nombre_asc' ? 'selected' : ''; ?>>Nombre (A-Z)</option>
                                    <option value="nombre_desc" <?php echo $orden == 'nombre_desc' ? 'selected' : ''; ?>>Nombre (Z-A)</option>
                                    <option value="precio_asc" <?php echo $orden == 'precio_asc' ? 'selected' : ''; ?>>Precio (Menor a Mayor)</option>
                                    <option value="precio_desc" <?php echo $orden == 'precio_desc' ? 'selected' : ''; ?>>Precio (Mayor a Menor)</option>
                                    <option value="stock_asc" <?php echo $orden == 'stock_asc' ? 'selected' : ''; ?>>Stock (Menor a Mayor)</option>
                                    <option value="stock_desc" <?php echo $orden == 'stock_desc' ? 'selected' : ''; ?>>Stock (Mayor a Menor)</option>
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
                    <span>Mostrando <?php echo count($productos); ?> de <?php echo $total_productos; ?> productos</span>
                </div>
                
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Destacado</th>
                                <th>Nuevo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productos)): ?>
                                <tr>
                                    <td colspan="10" class="no-results">No se encontraron productos</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($productos as $producto): ?>
                                    <tr>
                                        <td><?php echo $producto['id']; ?></td>
                                        <td class="product-image">
                                            <img src="../../assets/img/productos/placeholder.png" alt="<?php echo $producto['nombre']; ?>">
                                        </td>
                                        <td class="product-name">
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
                                        <td class="product-stock <?php echo $producto['stock'] <= 5 ? 'low-stock' : ''; ?>">
                                            <?php echo $producto['stock']; ?>
                                        </td>
                                        <td class="product-featured">
                                            <?php if ($producto['destacado']): ?>
                                                <span class="status-badge featured">Sí</span>
                                            <?php else: ?>
                                                <span class="status-badge not-featured">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="product-new">
                                            <?php if ($producto['nuevo']): ?>
                                                <span class="status-badge new">Sí</span>
                                            <?php else: ?>
                                                <span class="status-badge not-new">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="product-status">
                                            <?php if ($producto['activo']): ?>
                                                <span class="status-badge active">Activo</span>
                                            <?php else: ?>
                                                <span class="status-badge inactive">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions">
                                            <a href="edit.php?id=<?php echo $producto['id']; ?>" class="btn-action edit" title="Editar">
                                                ✏️
                                            </a>
                                            
                                            <form action="" method="post" style="display: inline;" onsubmit="return confirm('¿Estás seguro de cambiar el estado de este producto?');">
                                                <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                                <button type="submit" name="toggle_activo" class="btn-action <?php echo $producto['activo'] ? 'deactivate' : 'activate'; ?>" title="<?php echo $producto['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                    <?php echo $producto['activo'] ? '🔴' : '🟢'; ?>
                                                </button>
                                            </form>
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
                            <a href="?pagina=1<?php echo isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : ''; ?><?php echo isset($_GET['categoria']) ? '&categoria=' . $_GET['categoria'] : ''; ?><?php echo isset($_GET['destacados']) ? '&destacados=' . $_GET['destacados'] : ''; ?><?php echo isset($_GET['nuevos']) ? '&nuevos=' . $_GET['nuevos'] : ''; ?><?php echo isset($_GET['ofertas']) ? '&ofertas=' . $_GET['ofertas'] : ''; ?><?php echo isset($_GET['orden']) ? '&orden=' . $_GET['orden'] : ''; ?>" class="page-link">«</a>
                            <a href="?pagina=<?php echo $pagina - 1; ?><?php echo isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : ''; ?><?php echo isset($_GET['categoria']) ? '&categoria=' . $_GET['categoria'] : ''; ?><?php echo isset($_GET['destacados']) ? '&destacados=' . $_GET['destacados'] : ''; ?><?php echo isset($_GET['nuevos']) ? '&nuevos=' . $_GET['nuevos'] : ''; ?><?php echo isset($_GET['ofertas']) ? '&ofertas=' . $_GET['ofertas'] : ''; ?><?php echo isset($_GET['orden']) ? '&orden=' . $_GET['orden'] : ''; ?>" class="page-link">‹</a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $pagina - 2);
                        $end_page = min($total_paginas, $pagina + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?pagina=<?php echo $i; ?><?php echo isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : ''; ?><?php echo isset($_GET['categoria']) ? '&categoria=' . $_GET['categoria'] : ''; ?><?php echo isset($_GET['destacados']) ? '&destacados=' . $_GET['destacados'] : ''; ?><?php echo isset($_GET['nuevos']) ? '&nuevos=' . $_GET['nuevos'] : ''; ?><?php echo isset($_GET['ofertas']) ? '&ofertas=' . $_GET['ofertas'] : ''; ?><?php echo isset($_GET['orden']) ? '&orden=' . $_GET['orden'] : ''; ?>" class="page-link <?php echo $i == $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina + 1; ?><?php echo isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : ''; ?><?php echo isset($_GET['categoria']) ? '&categoria=' . $_GET['categoria'] : ''; ?><?php echo isset($_GET['destacados']) ? '&destacados=' . $_GET['destacados'] : ''; ?><?php echo isset($_GET['nuevos']) ? '&nuevos=' . $_GET['nuevos'] : ''; ?><?php echo isset($_GET['ofertas']) ? '&ofertas=' . $_GET['ofertas'] : ''; ?><?php echo isset($_GET['orden']) ? '&orden=' . $_GET['orden'] : ''; ?>" class="page-link">›</a>
                            <a href="?pagina=<?php echo $total_paginas; ?><?php echo isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : ''; ?><?php echo isset($_GET['categoria']) ? '&categoria=' . $_GET['categoria'] : ''; ?><?php echo isset($_GET['destacados']) ? '&destacados=' . $_GET['destacados'] : ''; ?><?php echo isset($_GET['nuevos']) ? '&nuevos=' . $_GET['nuevos'] : ''; ?><?php echo isset($_GET['ofertas']) ? '&ofertas=' . $_GET['ofertas'] : ''; ?><?php echo isset($_GET['orden']) ? '&orden=' . $_GET['orden'] : ''; ?>" class="page-link">»</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    
    <script>
        // Aplicar filtros automáticamente al cambiar un select
        document.querySelectorAll('.filter-form select').forEach(select => {
            select.addEventListener('change', function() {
                document.querySelector('.filter-form').submit();
            });
        });
    </script>
</body>
</html>