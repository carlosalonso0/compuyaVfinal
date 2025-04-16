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

// Incluir cabecera
include 'includes/header.php';
?>

<div class="container">
    <!-- Resultados de búsqueda -->
    <div class="search-results">
        <div class="search-results-header">
            <h1>Resultados de búsqueda</h1>
            <?php if (count($productos) > 0): ?>
                <div class="search-count">Se encontraron <?php echo count($productos); ?> producto(s)</div>
            <?php endif; ?>
        </div>
        
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
            <!-- Grid de productos encontrados -->
            <div class="search-results-grid">
                <?php foreach ($productos as $producto): ?>
                    <div class="search-product-card">
                        <div class="search-product-image">
                            <img src="<?php echo BASE_URL . '/' . obtenerImagenProducto($producto['id']); ?>" alt="<?php echo $producto['nombre']; ?>">
                        </div>
                        <div class="search-product-info">
                            <div class="search-product-brand"><?php echo $producto['marca']; ?></div>
                            <h3 class="search-product-name"><?php echo $producto['nombre']; ?></h3>
                            <span class="search-product-category"><?php echo $producto['categoria_nombre']; ?></span>
                            <div class="search-product-price">
                                <?php if (!empty($producto['precio_oferta'])): ?>
                                    <span class="search-price-original">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                                    <span class="search-price-current">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></span>
                                <?php else: ?>
                                    <span class="search-price-current">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo BASE_URL; ?>/producto/<?php echo $producto['slug']; ?>" class="search-btn-view">Ver Producto</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


<?php
// Incluir pie de página
include 'includes/footer.php';
?>