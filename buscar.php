<?php
// Incluir archivo de configuración
require_once 'config/config.php';
require_once 'config/database.php';

// Crear instancia de la base de datos
$database = new Database();
$db = $database->connect();

// Verificar si se ha proporcionado un término de búsqueda
if (!isset($_GET['q']) || empty($_GET['q'])) {
    header('Location: index.php');
    exit;
}

$busqueda = trim($_GET['q']);

// Incluir modelos necesarios
require_once 'models/Producto.php';

// Crear instancia del modelo
$producto = new Producto($db);

// Paginación
$productos_por_pagina = 12;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// Buscar productos
$query = "SELECT p.*, c.nombre as categoria_nombre 
          FROM productos p
          LEFT JOIN categorias c ON p.categoria_id = c.id
          WHERE (p.nombre LIKE :busqueda 
             OR p.descripcion LIKE :busqueda 
             OR p.marca LIKE :busqueda 
             OR p.modelo LIKE :busqueda)
             AND p.activo = true
          ORDER BY p.destacado DESC, p.nombre ASC 
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
$param_busqueda = '%' . $busqueda . '%';
$stmt->bindParam(':busqueda', $param_busqueda);
$stmt->bindParam(':limit', $productos_por_pagina, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el total de productos para la paginación
$query_total = "SELECT COUNT(*) as total FROM productos 
                WHERE (nombre LIKE :busqueda 
                   OR descripcion LIKE :busqueda 
                   OR marca LIKE :busqueda 
                   OR modelo LIKE :busqueda)
                   AND activo = true";

$stmt_total = $db->prepare($query_total);
$stmt_total->bindParam(':busqueda', $param_busqueda);
$stmt_total->execute();
$row = $stmt_total->fetch(PDO::FETCH_ASSOC);
$total_productos = $row['total'];
$total_paginas = ceil($total_productos / $productos_por_pagina);

// Título de la página
$page_title = 'Resultados de búsqueda: ' . $busqueda;

// Incluir encabezado
include 'includes/header.php';

// Incluir navegación
include 'includes/navbar.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Resultados de búsqueda</h1>
        <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
    
    <div class="mb-4">
        <form action="buscar.php" method="GET" class="d-flex">
            <input type="text" name="q" class="form-control me-2" placeholder="Buscar productos..." value="<?php echo htmlspecialchars($busqueda); ?>" required>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </form>
    </div>
    
    <div class="mb-3">
        <p>Se encontraron <?php echo $total_productos; ?> productos para "<strong><?php echo htmlspecialchars($busqueda); ?></strong>"</p>
    </div>
    
    <?php if (count($productos) > 0): ?>
        <div class="row">
            <?php foreach ($productos as $prod): ?>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card product-card">
                        <?php if ($prod['nuevo']): ?>
                            <span class="badge bg-info product-card__badge">Nuevo</span>
                        <?php endif; ?>
                        
                        <a href="<?php echo BASE_URL; ?>producto/<?php echo $prod['slug']; ?>">
                            <img src="<?php echo !empty($prod['imagen_principal']) ? BASE_URL . 'assets/uploads/productos/' . $prod['imagen_principal'] : BASE_URL . 'assets/img/no-image.png'; ?>" class="card-img-top product-card__image" alt="<?php echo $prod['nombre']; ?>">
                        </a>
                        
                        <div class="card-body product-card__body">
                            <h5 class="card-title product-card__title">
                                <a href="<?php echo BASE_URL; ?>producto/<?php echo $prod['slug']; ?>"><?php echo $prod['nombre']; ?></a>
                            </h5>
                            
                            <?php if (!empty($prod['categoria_nombre'])): ?>
                                <div class="mb-2 small text-muted">
                                    <a href="<?php echo BASE_URL; ?>categoria/<?php echo $prod['categoria_id']; ?>" class="text-decoration-none text-muted">
                                        <?php echo $prod['categoria_nombre']; ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <p class="card-text product-card__price">
                                <?php if (!empty($prod['precio_oferta'])): ?>
                                    <span class="product-card__discount-price">S/ <?php echo number_format($prod['precio_oferta'], 2); ?></span>
                                    <span class="product-card__original-price">S/ <?php echo number_format($prod['precio'], 2); ?></span>
                                <?php else: ?>
                                    S/ <?php echo number_format($prod['precio'], 2); ?>
                                <?php endif; ?>
                            </p>
                            
                            <a href="#" class="btn btn-compuya product-card__button add-to-cart" 
                               data-id="<?php echo $prod['id']; ?>" 
                               data-name="<?php echo $prod['nombre']; ?>" 
                               data-price="<?php echo !empty($prod['precio_oferta']) ? $prod['precio_oferta'] : $prod['precio']; ?>" 
                               data-image="<?php echo !empty($prod['imagen_principal']) ? $prod['imagen_principal'] : ''; ?>">
                                <i class="fas fa-shopping-cart me-2"></i>Añadir al carrito
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <nav aria-label="Navegación de páginas" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($pagina_actual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo BASE_URL; ?>buscar.php?q=<?php echo urlencode($busqueda); ?>&pagina=<?php echo $pagina_actual - 1; ?>" aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo BASE_URL; ?>buscar.php?q=<?php echo urlencode($busqueda); ?>&pagina=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo BASE_URL; ?>buscar.php?q=<?php echo urlencode($busqueda); ?>&pagina=<?php echo $pagina_actual + 1; ?>" aria-label="Siguiente">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No se encontraron productos que coincidan con tu búsqueda.
        </div>
        
        <div class="mt-4">
            <h4>Sugerencias:</h4>
            <ul>
                <li>Revisa la ortografía del término de búsqueda.</li>
                <li>Utiliza palabras más generales o menos palabras.</li>
                <li>Prueba con sinónimos del término que estás buscando.</li>
                <li>Explora nuestras categorías de productos para encontrar lo que buscas.</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Añadir al carrito
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            const productPrice = parseFloat(this.getAttribute('data-price'));
            const productImage = this.getAttribute('data-image');
            
            // Función para añadir al carrito (definida en main.js)
            addToCart(productId, productName, productPrice, productImage, 1);
            
            // Mostrar notificación
            showNotification(`"${productName}" añadido al carrito`);
        });
    });
});
</script>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>