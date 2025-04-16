<?php
define('IN_COMPUYA', true);
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Variable para el título de la página
$page_title = 'Resultados de búsqueda';

// Obtener término de búsqueda
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

$db = Database::getInstance();
$conn = $db->getConnection();
$productos = [];

// Si hay un término de búsqueda, realizar la consulta
if (!empty($busqueda)) {
    // Escapar el término para prevenir SQL injection
    $busqueda_safe = $conn->real_escape_string($busqueda);
    
    // Consulta para búsqueda en nombre, marca y modelo
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM productos p 
            JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.activo = 1 
            AND (
                LOWER(p.nombre) LIKE LOWER('%$busqueda_safe%') OR 
                LOWER(p.marca) LIKE LOWER('%$busqueda_safe%') OR 
                LOWER(p.modelo) LIKE LOWER('%$busqueda_safe%') OR
                LOWER(p.descripcion_corta) LIKE LOWER('%$busqueda_safe%')
            )
            ORDER BY p.destacado DESC, p.id DESC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $productos = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Definir variable CSS adicional
$extra_css = ['unified-category-search.css'];

// Incluir cabecera
include 'includes/header.php';
?>

<section class="category-header">
    <div class="container">
        <h1>Resultados de búsqueda</h1>
        <div class="breadcrumbs">
            <a href="<?php echo BASE_URL; ?>/index.php">Inicio</a> &raquo; Resultados de búsqueda
        </div>
    </div>
</section>

<div class="container">
    <div class="products-header" style="margin-top: 20px;">
        <div class="products-count">
            Se encontraron <?php echo count($productos); ?> producto(s)
        </div>
    </div>
</div>

<section class="search-content">
    <div class="container">
        <?php if (empty($busqueda)): ?>
            <!-- Mensaje si no hay término de búsqueda -->
            <div class="search-no-results">
                <h2>Ingresa un término de búsqueda</h2>
                <p>Utiliza la barra de búsqueda para encontrar los productos que necesitas.</p>
                <div class="search-bar-large">
                    <form action="<?php echo BASE_URL; ?>/search.php" method="get">
                        <input type="text" name="q" placeholder="¿Qué estás buscando?" required>
                        <button type="submit"><i class="fas fa-search"></i> Buscar</button>
                    </form>
                </div>
            </div>
        <?php elseif (empty($productos)): ?>
            <!-- Mensaje si no hay resultados -->
            <div class="search-no-results">
                <h2>No se encontraron resultados</h2>
                <p>No se encontraron productos que coincidan con tu búsqueda.</p>
                <div class="search-suggestions">
                    <p>Sugerencias:</p>
                    <ul>
                        <li>Verifica la ortografía del término de búsqueda.</li>
                        <li>Utiliza palabras más generales o menos palabras.</li>
                        <li>Prueba con un término de búsqueda diferente.</li>
                    </ul>
                </div>
                <a href="<?php echo BASE_URL; ?>" class="btn-primary">Volver al inicio</a>
            </div>
        <?php else: ?>
            <!-- Layout búsqueda con productos -->
            <div class="search-layout">
                <!-- Sidebar filtros (opcional en búsqueda) -->
                <div class="filters-sidebar">
                    <h3>Filtros</h3>
                    <form action="" method="get" id="filtros-form">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($busqueda); ?>">
                        
                        <div class="filter-group">
                            <h4>Disponibilidad</h4>
                            <div class="availability-options">
                                <input type="checkbox" name="en_stock" id="en_stock" value="1" <?php echo isset($_GET['en_stock']) ? 'checked' : ''; ?>>
                                <label for="en_stock">Productos en stock</label>
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <h4>Ofertas</h4>
                            <div class="offers-options">
                                <input type="checkbox" name="ofertas" id="ofertas" value="1" <?php echo isset($_GET['ofertas']) ? 'checked' : ''; ?>>
                                <label for="ofertas">Solo productos en oferta</label>
                            </div>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">Aplicar Filtros</button>
                            <a href="search.php?q=<?php echo htmlspecialchars($busqueda); ?>" class="btn-clear">Limpiar Filtros</a>
                        </div>
                    </form>
                </div>
                
                <!-- Productos encontrados -->
                <div class="products-container">
                    <div class="products-header">
                        <div class="products-count">
                            <?php echo count($productos); ?> producto(s) encontrado(s)
                        </div>
                        
                        <div class="products-sort">
                            <label for="orden">Ordenar por:</label>
                            <select name="orden" id="orden" onchange="cambiarOrden(this.value)">
                                <option value="destacados" <?php echo (!isset($_GET['orden']) || $_GET['orden'] == 'destacados') ? 'selected' : ''; ?>>Destacados</option>
                                <option value="precio_asc" <?php echo isset($_GET['orden']) && $_GET['orden'] == 'precio_asc' ? 'selected' : ''; ?>>Precio: Menor a Mayor</option>
                                <option value="precio_desc" <?php echo isset($_GET['orden']) && $_GET['orden'] == 'precio_desc' ? 'selected' : ''; ?>>Precio: Mayor a Menor</option>
                                <option value="nombre_asc" <?php echo isset($_GET['orden']) && $_GET['orden'] == 'nombre_asc' ? 'selected' : ''; ?>>Nombre (A-Z)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Grid de productos -->
                    <div class="productos-grid">
                        <?php foreach ($productos as $producto): ?>
                            <div class="producto-card">
    <?php if (!empty($producto['precio_oferta'])): ?>
    <div class="etiqueta-oferta">OFERTA</div>
    <?php endif; ?>
    
    <div class="producto-imagen">
        <img src="<?php echo BASE_URL . '/' . obtenerImagenProducto($producto['id']); ?>" alt="<?php echo $producto['nombre']; ?>">
    </div>
    
    <div class="producto-info">
        <div class="producto-marca"><?php echo $producto['marca']; ?></div>
        <h3 class="producto-nombre">
            <?php echo $producto['nombre']; ?>
        </h3>
        
        <div class="producto-precio">
            <?php if (!empty($producto['precio_oferta'])): ?>
            <div>
                <span class="precio-antiguo">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                <span class="precio-actual">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></span>
                <div>
                    <span class="descuento">
                        <?php echo round(100 - (($producto['precio_oferta'] / $producto['precio']) * 100)); ?>% DSCTO
                    </span>
                </div>
            </div>
            <?php else: ?>
            <span class="precio-actual">S/ <?php echo number_format($producto['precio'], 2); ?></span>
            <?php endif; ?>
        </div>
        
        <?php if ($producto['stock'] > 0): ?>
        <div class="producto-stock">
            <span class="en-stock">En stock</span>
        </div>
        <?php else: ?>
        <div class="producto-stock">
            <span class="sin-stock">Agotado</span>
        </div>
        <?php endif; ?>
        
        <a href="producto/<?php echo $producto['slug']; ?>" class="btn-ver">Ver Producto</a>
    </div>
</div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
    function cambiarOrden(orden) {
        // Obtener todos los parámetros actuales
        const urlParams = new URLSearchParams(window.location.search);
        // Modificar el parámetro de orden
        urlParams.set('orden', orden);
        // Redireccionar a la URL con el nuevo orden
        window.location.href = 'search.php?' + urlParams.toString();
    }

    // Cambiar automáticamente el formulario cuando se selecciona una opción
    document.addEventListener('DOMContentLoaded', function() {
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