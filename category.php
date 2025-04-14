<?php
define('IN_COMPUYA', true);
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Obtener ID de categoría
$categoria_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

$db = Database::getInstance();
$conn = $db->getConnection();

// Determinar qué consulta usar
if ($categoria_id > 0) {
    // Buscar por ID
    $sql_categoria = "SELECT * FROM categorias WHERE id = $categoria_id AND activo = 1";
} elseif (!empty($slug)) {
    // Buscar por slug
    $slug = $conn->real_escape_string($slug);
    $sql_categoria = "SELECT * FROM categorias WHERE slug = '$slug' AND activo = 1";
} else {
    // Ni ID ni slug proporcionados
    header('Location: index.php');
    exit;
}

// Obtener datos de la categoría
$result_categoria = $conn->query($sql_categoria);

if (!$result_categoria || $result_categoria->num_rows == 0) {
    // Categoría no encontrada
    header('Location: index.php');
    exit;
}

$categoria = $result_categoria->fetch_assoc();
$page_title = $categoria['nombre'];

// Obtener subcategorías (si las hay)
$sql_subcategorias = "SELECT * FROM categorias WHERE padre_id = $categoria_id AND activo = 1 ORDER BY nombre";
$result_subcategorias = $conn->query($sql_subcategorias);
$subcategorias = [];
if ($result_subcategorias && $result_subcategorias->num_rows > 0) {
    while ($sub = $result_subcategorias->fetch_assoc()) {
        $subcategorias[] = $sub;
    }
}

// Obtener todas las marcas para filtro
$sql_marcas = "SELECT DISTINCT marca FROM productos WHERE categoria_id = $categoria_id OR categoria_id IN (SELECT id FROM categorias WHERE padre_id = $categoria_id) AND activo = 1 ORDER BY marca";
$result_marcas = $conn->query($sql_marcas);
$marcas = [];
if ($result_marcas && $result_marcas->num_rows > 0) {
    while ($marca = $result_marcas->fetch_assoc()) {
        if (!empty($marca['marca'])) {
            $marcas[] = $marca['marca'];
        }
    }
}

// Aplicar filtros
$condiciones = [];
$condiciones[] = "(p.categoria_id = $categoria_id OR p.categoria_id IN (SELECT id FROM categorias WHERE padre_id = $categoria_id))";
$condiciones[] = "p.activo = 1";

// Filtro de subcategoría
$subcategoria_id = isset($_GET['subcategoria']) ? (int)$_GET['subcategoria'] : 0;
if ($subcategoria_id > 0) {
    $condiciones[] = "p.categoria_id = $subcategoria_id";
}

// Filtro de marca
$marca_filtro = isset($_GET['marca']) ? $_GET['marca'] : '';
if (!empty($marca_filtro)) {
    $marca_segura = $conn->real_escape_string($marca_filtro);
    $condiciones[] = "p.marca = '$marca_segura'";
}

// Filtros de precio
$precio_min = isset($_GET['precio_min']) ? (float)$_GET['precio_min'] : 0;
$precio_max = isset($_GET['precio_max']) ? (float)$_GET['precio_max'] : 0;

if ($precio_min > 0) {
    $condiciones[] = "p.precio >= $precio_min";
}
if ($precio_max > 0) {
    $condiciones[] = "p.precio <= $precio_max";
}

// Filtro de disponibilidad
$en_stock = isset($_GET['en_stock']) ? (int)$_GET['en_stock'] : 0;
if ($en_stock == 1) {
    $condiciones[] = "p.stock > 0";
}

// Filtro de ofertas
$solo_ofertas = isset($_GET['ofertas']) ? (int)$_GET['ofertas'] : 0;
if ($solo_ofertas == 1) {
    $condiciones[] = "p.precio_oferta IS NOT NULL AND p.precio_oferta > 0";
}

// Ordenamiento
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'destacados';
$orden_sql = "p.destacado DESC, p.id DESC"; // Predeterminado: destacados primero

switch ($orden) {
    case 'precio_asc':
        $orden_sql = "(CASE WHEN p.precio_oferta > 0 THEN p.precio_oferta ELSE p.precio END) ASC";
        break;
    case 'precio_desc':
        $orden_sql = "(CASE WHEN p.precio_oferta > 0 THEN p.precio_oferta ELSE p.precio END) DESC";
        break;
    case 'nombre_asc':
        $orden_sql = "p.nombre ASC";
        break;
    case 'mas_recientes':
        $orden_sql = "p.id DESC";
        break;
}

// Construir la consulta completa
$where = implode(' AND ', $condiciones);
$sql_productos = "SELECT p.*, c.nombre as categoria_nombre 
                 FROM productos p
                 JOIN categorias c ON p.categoria_id = c.id
                 WHERE $where
                 ORDER BY $orden_sql";

$result_productos = $conn->query($sql_productos);
$productos = [];
if ($result_productos && $result_productos->num_rows > 0) {
    while ($producto = $result_productos->fetch_assoc()) {
        $productos[] = $producto;
    }
}

// Paginación
$productos_por_pagina = 12;
$total_productos = count($productos);
$total_paginas = ceil($total_productos / $productos_por_pagina);

$pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
} elseif ($pagina_actual > $total_paginas && $total_paginas > 0) {
    $pagina_actual = $total_paginas;
}

$indice_inicio = ($pagina_actual - 1) * $productos_por_pagina;
$productos_pagina = array_slice($productos, $indice_inicio, $productos_por_pagina);

// Incluir cabecera
include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/category.css">

<section class="category-header">
    <div class="container">
        <h1><?php echo $categoria['nombre']; ?></h1>
        <div class="breadcrumbs">
            <a href="<?php echo BASE_URL; ?>/index.php">Inicio</a> &raquo; <?php echo $categoria['nombre']; ?>
        </div>
    </div>
</section>

<section class="category-content">
    <div class="container">
        <div class="category-layout">
            <!-- Filtros (Sidebar) -->
            <div class="filters-sidebar">
                <h3>Filtros</h3>
                <form action="" method="get" id="filtros-form">
                    <input type="hidden" name="id" value="<?php echo $categoria_id; ?>">
                    
                    <?php if (!empty($subcategorias)): ?>
                    <div class="filter-group">
                        <h4>Subcategorías</h4>
                        <ul class="filter-options">
                            <li>
                                <input type="radio" name="subcategoria" id="subcategoria_0" value="0" <?php echo $subcategoria_id == 0 ? 'checked' : ''; ?>>
                                <label for="subcategoria_0">Todas</label>
                            </li>
                            <?php foreach ($subcategorias as $sub): ?>
                            <li>
                                <input type="radio" name="subcategoria" id="subcategoria_<?php echo $sub['id']; ?>" value="<?php echo $sub['id']; ?>" <?php echo $subcategoria_id == $sub['id'] ? 'checked' : ''; ?>>
                                <label for="subcategoria_<?php echo $sub['id']; ?>"><?php echo $sub['nombre']; ?></label>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($marcas)): ?>
                    <div class="filter-group">
                        <h4>Marcas</h4>
                        <ul class="filter-options">
                            <li>
                                <input type="radio" name="marca" id="marca_0" value="" <?php echo empty($marca_filtro) ? 'checked' : ''; ?>>
                                <label for="marca_0">Todas</label>
                            </li>
                            <?php foreach ($marcas as $marca): ?>
                            <li>
                                <input type="radio" name="marca" id="marca_<?php echo md5($marca); ?>" value="<?php echo htmlspecialchars($marca); ?>" <?php echo $marca_filtro == $marca ? 'checked' : ''; ?>>
                                <label for="marca_<?php echo md5($marca); ?>"><?php echo $marca; ?></label>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <div class="filter-group">
                        <h4>Precio</h4>
                        <div class="price-range">
                            <input type="number" name="precio_min" placeholder="Mínimo" value="<?php echo $precio_min > 0 ? $precio_min : ''; ?>" min="0" step="10">
                            <span>hasta</span>
                            <input type="number" name="precio_max" placeholder="Máximo" value="<?php echo $precio_max > 0 ? $precio_max : ''; ?>" min="0" step="10">
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <h4>Disponibilidad</h4>
                        <div class="availability-options">
                            <input type="checkbox" name="en_stock" id="en_stock" value="1" <?php echo $en_stock == 1 ? 'checked' : ''; ?>>
                            <label for="en_stock">Productos en stock</label>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <h4>Ofertas</h4>
                        <div class="offers-options">
                            <input type="checkbox" name="ofertas" id="ofertas" value="1" <?php echo $solo_ofertas == 1 ? 'checked' : ''; ?>>
                            <label for="ofertas">Solo productos en oferta</label>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">Aplicar Filtros</button>
                        <a href="category.php?id=<?php echo $categoria_id; ?>" class="btn-clear">Limpiar Filtros</a>
                    </div>
                </form>
            </div>
            
            <!-- Productos y Ordenamiento -->
            <div class="products-container">
                <div class="products-header">
                    <div class="products-count">
                        <?php echo $total_productos; ?> producto(s) encontrado(s)
                    </div>
                    
                    <div class="products-sort">
                        <label for="orden">Ordenar por:</label>
                        <select name="orden" id="orden" onchange="cambiarOrden(this.value)">
                            <option value="destacados" <?php echo $orden == 'destacados' ? 'selected' : ''; ?>>Destacados</option>
                            <option value="precio_asc" <?php echo $orden == 'precio_asc' ? 'selected' : ''; ?>>Precio: Menor a Mayor</option>
                            <option value="precio_desc" <?php echo $orden == 'precio_desc' ? 'selected' : ''; ?>>Precio: Mayor a Menor</option>
                            <option value="nombre_asc" <?php echo $orden == 'nombre_asc' ? 'selected' : ''; ?>>Nombre (A-Z)</option>
                            <option value="mas_recientes" <?php echo $orden == 'mas_recientes' ? 'selected' : ''; ?>>Más recientes</option>
                        </select>
                    </div>
                </div>
                
                <?php if (empty($productos_pagina)): ?>
                <div class="no-products-message">
                    <p>No se encontraron productos que coincidan con los filtros seleccionados.</p>
                    <p>Intente con diferentes criterios de búsqueda o <a href="category.php?id=<?php echo $categoria_id; ?>">ver todos los productos</a>.</p>
                </div>
                <?php else: ?>
                <div class="productos-grid">
                    <?php foreach ($productos_pagina as $producto): ?>
                    <div class="producto-card<?php echo !empty($producto['precio_oferta']) ? ' oferta' : ''; ?>">
                        <?php if (!empty($producto['precio_oferta'])): ?>
                        <div class="etiqueta-oferta">OFERTA</div>
                        <?php endif; ?>
                        
                        <div class="producto-imagen">
                            <a href="producto/<?php echo $producto['slug']; ?>">
                                <img src="assets/img/productos/placeholder.png" alt="<?php echo $producto['nombre']; ?>">
                            </a>
                        </div>
                        
                        <div class="producto-info">
                            <div class="producto-marca"><?php echo $producto['marca']; ?></div>
                            <h3 class="producto-nombre">
                                <a href="producto/<?php echo $producto['slug'];?>"><?php echo $producto['nombre']; ?></a>
                            </h3>
                            <div class="producto-precio">
                                <?php if (!empty($producto['precio_oferta'])): ?>
                                <span class="precio-antiguo">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                                <span class="precio-actual">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></span>
                                <span class="descuento">
                                    <?php echo round(100 - (($producto['precio_oferta'] / $producto['precio']) * 100)); ?>% DSCTO
                                </span>
                                <?php else: ?>
                                <span class="precio-actual">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="producto-stock">
                                <?php if ($producto['stock'] > 0): ?>
                                <span class="en-stock">En stock</span>
                                <?php else: ?>
                                <span class="sin-stock">Agotado</span>
                                <?php endif; ?>
                            </div>
                            
                            <a href="producto/<?php echo $producto['slug'];?>" class="btn-ver">Ver Producto</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <?php if ($pagina_actual > 1): ?>
                    <a href="?id=<?php echo $categoria_id; ?>&page=<?php echo $pagina_actual - 1; ?><?php 
                        echo isset($_GET['subcategoria']) ? '&subcategoria=' . $_GET['subcategoria'] : ''; 
                        echo isset($_GET['marca']) ? '&marca=' . urlencode($_GET['marca']) : '';
                        echo isset($_GET['precio_min']) ? '&precio_min=' . $_GET['precio_min'] : '';
                        echo isset($_GET['precio_max']) ? '&precio_max=' . $_GET['precio_max'] : '';
                        echo isset($_GET['en_stock']) ? '&en_stock=' . $_GET['en_stock'] : '';
                        echo isset($_GET['ofertas']) ? '&ofertas=' . $_GET['ofertas'] : '';
                        echo isset($_GET['orden']) ? '&orden=' . $_GET['orden'] : '';
                    ?>" class="page-prev">&laquo; Anterior</a>
                    <?php endif; ?>
                    
                    <div class="page-numbers">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?id=<?php echo $categoria_id; ?>&page=<?php echo $i; ?><?php 
                            echo isset($_GET['subcategoria']) ? '&subcategoria=' . $_GET['subcategoria'] : ''; 
                            echo isset($_GET['marca']) ? '&marca=' . urlencode($_GET['marca']) : '';
                            echo isset($_GET['precio_min']) ? '&precio_min=' . $_GET['precio_min'] : '';
                            echo isset($_GET['precio_max']) ? '&precio_max=' . $_GET['precio_max'] : '';
                            echo isset($_GET['en_stock']) ? '&en_stock=' . $_GET['en_stock'] : '';
                            echo isset($_GET['ofertas']) ? '&ofertas=' . $_GET['ofertas'] : '';
                            echo isset($_GET['orden']) ? '&orden=' . $_GET['orden'] : '';
                        ?>" class="page-number <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?id=<?php echo $categoria_id; ?>&page=<?php echo $pagina_actual + 1; ?><?php 
                        echo isset($_GET['subcategoria']) ? '&subcategoria=' . $_GET['subcategoria'] : ''; 
                        echo isset($_GET['marca']) ? '&marca=' . urlencode($_GET['marca']) : '';
                        echo isset($_GET['precio_min']) ? '&precio_min=' . $_GET['precio_min'] : '';
                        echo isset($_GET['precio_max']) ? '&precio_max=' . $_GET['precio_max'] : '';
                        echo isset($_GET['en_stock']) ? '&en_stock=' . $_GET['en_stock'] : '';
                        echo isset($_GET['ofertas']) ? '&ofertas=' . $_GET['ofertas'] : '';
                        echo isset($_GET['orden']) ? '&orden=' . $_GET['orden'] : '';
                    ?>" class="page-next">Siguiente &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function cambiarOrden(orden) {
    // Obtener todos los parámetros actuales
    const urlParams = new URLSearchParams(window.location.search);
    // Modificar el parámetro de orden
    urlParams.set('orden', orden);
    // Redireccionar a la URL con el nuevo orden
    window.location.href = 'category.php?' + urlParams.toString();
}

// Cambiar automáticamente el formulario cuando se selecciona una opción
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('.filter-options input[type="radio"]');
    radios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.getElementById('filtros-form').submit();
        });
    });
    
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            document.getElementById('filtros-form').submit();
        });
    });
});
</script>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>