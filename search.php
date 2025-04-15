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

<section class="search-results">
    <div class="container">
        <h1>Resultados de búsqueda</h1>
        <p>Término de búsqueda: <strong><?php echo htmlspecialchars($busqueda); ?></strong></p>
        
        <?php if (empty($busqueda)): ?>
            <div class="search-message">
                <p>Por favor, ingrese un término de búsqueda.</p>
            </div>
        <?php elseif (empty($productos)): ?>
            <div class="search-message">
                <p>No se encontraron productos para: <strong><?php echo htmlspecialchars($busqueda); ?></strong></p>
                <p>Sugerencias:</p>
                <ul>
                    <li>Revise la ortografía del término de búsqueda.</li>
                    <li>Utilice palabras más generales o menos palabras.</li>
                    <li>Pruebe con un término de búsqueda diferente.</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="search-count">
                <p>Se encontraron <?php echo count($productos); ?> producto(s)</p>
            </div>
            
            <div class="productos-grid">
                <?php foreach ($productos as $producto): ?>
                    <div class="producto-card">
                        <div class="producto-imagen">
                        <img src="<?php echo BASE_URL . '/' . obtenerImagenProducto($producto['id']); ?>" alt="<?php echo $producto['nombre']; ?>">
                        </div>
                        <div class="producto-info">
                            <div class="producto-marca"><?php echo $producto['marca']; ?></div>
                            <h3 class="producto-nombre"><?php echo $producto['nombre']; ?></h3>
                            <div class="producto-categoria"><?php echo $producto['categoria_nombre']; ?></div>
                            <div class="producto-precio">
                                <?php if (!empty($producto['precio_oferta'])): ?>
                                    <span class="precio-antiguo">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                                    <span class="precio-actual">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></span>
                                <?php else: ?>
                                    <span class="precio-actual">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="producto/<?php echo $producto['slug']; ?>" class="btn-ver">Ver Producto</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>