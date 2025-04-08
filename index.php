<?php
// Incluir archivo de configuración
require_once 'config/config.php';
require_once 'config/database.php';

// Crear instancia de la base de datos
$database = new Database();
$db = $database->connect();

// Incluir modelos necesarios
require_once 'models/Producto.php';
require_once 'models/Categoria.php';

// Cargar configuración de la página principal
$config = [];
$query = "SELECT clave, valor FROM configuracion_home";
$stmt = $db->prepare($query);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $config[$row['clave']] = $row['valor'];
}

// Cargar slider
$sliders = [];
if (!empty($config['mostrar_slider']) && $config['mostrar_slider'] == '1') {
    $query = "SELECT * FROM home_sliders WHERE activo = true ORDER BY orden ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Cargar banners
$banners = [];
if (!empty($config['mostrar_banners']) && $config['mostrar_banners'] == '1') {
    $query = "SELECT * FROM home_banners WHERE activo = true ORDER BY orden ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Cargar productos destacados
$productos_destacados = [];
if (!empty($config['mostrar_destacados']) && $config['mostrar_destacados'] == '1') {
    $limite = !empty($config['productos_destacados_cantidad']) ? intval($config['productos_destacados_cantidad']) : 8;
    
    $query = "SELECT p.*, c.nombre as categoria_nombre 
              FROM productos p
              LEFT JOIN categorias c ON p.categoria_id = c.id
              WHERE p.destacado = true AND p.activo = true
              ORDER BY p.id DESC LIMIT :limite";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    $productos_destacados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Cargar categorías destacadas
$categorias_destacadas = [];
if (!empty($config['mostrar_categorias']) && $config['mostrar_categorias'] == '1' && !empty($config['categorias_destacadas'])) {
    $categoria_ids = explode(',', $config['categorias_destacadas']);
    $categoria_ids = array_map('intval', $categoria_ids);
    
    $placeholders = implode(',', array_fill(0, count($categoria_ids), '?'));
    $query = "SELECT * FROM categorias WHERE id IN ($placeholders) AND activo = true ORDER BY nombre ASC";
    $stmt = $db->prepare($query);
    
    // Bind params
    foreach ($categoria_ids as $key => $id) {
        $stmt->bindValue($key + 1, $id);
    }
    
    $stmt->execute();
    $categorias_destacadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Cargar productos nuevos
$productos_nuevos = [];
if (!empty($config['mostrar_nuevos']) && $config['mostrar_nuevos'] == '1') {
    $limite = !empty($config['productos_nuevos_cantidad']) ? intval($config['productos_nuevos_cantidad']) : 4;
    
    $query = "SELECT p.*, c.nombre as categoria_nombre 
              FROM productos p
              LEFT JOIN categorias c ON p.categoria_id = c.id
              WHERE p.nuevo = true AND p.activo = true
              ORDER BY p.id DESC LIMIT :limite";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    $productos_nuevos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Título de la página
$page_title = 'Inicio';

// Incluir encabezado
include 'includes/header.php';

// Incluir barra de navegación
include 'includes/navbar.php';
?>

<!-- Slider principal -->
<?php if (!empty($sliders)): ?>
    <div id="mainCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($sliders as $key => $slider): ?>
                <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="<?php echo $key; ?>" <?php echo $key === 0 ? 'class="active" aria-current="true"' : ''; ?> aria-label="Slide <?php echo $key + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($sliders as $key => $slider): ?>
                <div class="carousel-item <?php echo $key === 0 ? 'active' : ''; ?>">
                    <img src="<?php echo BASE_URL; ?>assets/uploads/sliders/<?php echo $slider['imagen']; ?>" class="d-block w-100" alt="<?php echo $slider['titulo'] ?? 'Slider ' . ($key + 1); ?>">
                    <?php if (!empty($slider['titulo']) || !empty($slider['subtitulo'])): ?>
                        <div class="carousel-caption d-none d-md-block">
                            <?php if (!empty($slider['titulo'])): ?>
                                <h2><?php echo $slider['titulo']; ?></h2>
                            <?php endif; ?>
                            <?php if (!empty($slider['subtitulo'])): ?>
                                <p><?php echo $slider['subtitulo']; ?></p>
                            <?php endif; ?>
                            <?php if (!empty($slider['link'])): ?>
                                <a href="<?php echo $slider['link']; ?>" class="btn btn-compuya">Ver más</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
<?php endif; ?>

<!-- Contenido principal -->
<div class="container fade-with-slide">
    
    <!-- Categorías destacadas -->
    <?php if (!empty($categorias_destacadas)): ?>
        <section class="mb-5">
            <h2 class="text-center mb-4">Categorías Destacadas</h2>
            <div class="row">
                <?php foreach ($categorias_destacadas as $categoria): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card category-card">
                            <img src="<?php echo !empty($categoria['imagen']) ? BASE_URL . 'assets/uploads/categorias/' . $categoria['imagen'] : BASE_URL . 'assets/img/cat-default.jpg'; ?>" class="category-card__image" alt="<?php echo $categoria['nombre']; ?>">
                            <div class="category-card__overlay">
                                <h3 class="category-card__title"><?php echo $categoria['nombre']; ?></h3>
                            </div>
                            <a href="<?php echo BASE_URL; ?>categoria/<?php echo $categoria['slug']; ?>" class="stretched-link"></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Banner superior (si existe) -->
    <?php 
    $banner_superior = array_filter($banners, function($banner) {
        return $banner['posicion'] == 'superior';
    });
    if (!empty($banner_superior)): 
        $banner = reset($banner_superior); // Obtener el primer banner
    ?>
        <section class="mb-5">
            <div class="card bg-dark text-white banner">
                <img src="<?php echo BASE_URL; ?>assets/uploads/banners/<?php echo $banner['imagen']; ?>" class="card-img" alt="<?php echo $banner['titulo'] ?? 'Promoción'; ?>">
                <div class="card-img-overlay d-flex flex-column justify-content-center text-center">
                    <?php if (!empty($banner['titulo'])): ?>
                        <h3 class="card-title"><?php echo $banner['titulo']; ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($banner['subtitulo'])): ?>
                        <p class="card-text"><?php echo $banner['subtitulo']; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($banner['link'])): ?>
                        <a href="<?php echo $banner['link']; ?>" class="btn btn-compuya-secondary mx-auto">Ver ofertas</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Productos destacados -->
    <?php if (!empty($productos_destacados)): ?>
        <section class="mb-5">
            <h2 class="text-center mb-4">Productos Destacados</h2>
            <div class="row">
                <?php foreach ($productos_destacados as $producto): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="card product-card">
                            <?php if ($producto['nuevo']): ?>
                                <span class="badge bg-info product-card__badge">Nuevo</span>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>producto/<?php echo $producto['slug']; ?>">
                                <img src="<?php echo !empty($producto['imagen_principal']) ? BASE_URL . 'assets/uploads/productos/' . $producto['imagen_principal'] : BASE_URL . 'assets/img/no-image.png'; ?>" class="card-img-top product-card__image" alt="<?php echo $producto['nombre']; ?>">
                            </a>
                            <div class="card-body product-card__body">
                                <h5 class="card-title product-card__title">
                                    <a href="<?php echo BASE_URL; ?>producto/<?php echo $producto['slug']; ?>"><?php echo $producto['nombre']; ?></a>
                                </h5>
                                <div class="mb-2">
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star-half-alt text-warning"></i>
                                </div>
                                <p class="card-text product-card__price">
                                    <?php if (!empty($producto['precio_oferta'])): ?>
                                        <span class="product-card__discount-price">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></span>
                                        <span class="product-card__original-price">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                                    <?php else: ?>
                                        S/ <?php echo number_format($producto['precio'], 2); ?>
                                    <?php endif; ?>
                                </p>
                                <a href="#" class="btn btn-compuya product-card__button add-to-cart" 
                                   data-id="<?php echo $producto['id']; ?>" 
                                   data-name="<?php echo $producto['nombre']; ?>" 
                                   data-price="<?php echo !empty($producto['precio_oferta']) ? $producto['precio_oferta'] : $producto['precio']; ?>" 
                                   data-image="<?php echo !empty($producto['imagen_principal']) ? $producto['imagen_principal'] : ''; ?>">
                                    <i class="fas fa-shopping-cart me-2"></i>Añadir al carrito
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-3">
                <a href="<?php echo BASE_URL; ?>productos" class="btn btn-outline-primary">Ver todos los productos</a>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Banner central (si existe) -->
    <?php 
    $banner_central = array_filter($banners, function($banner) {
        return $banner['posicion'] == 'centro';
    });
    if (!empty($banner_central)): 
        $banner = reset($banner_central); // Obtener el primer banner
    ?>
        <section class="mb-5">
            <div class="card bg-dark text-white banner">
                <img src="<?php echo BASE_URL; ?>assets/uploads/banners/<?php echo $banner['imagen']; ?>" class="card-img" alt="<?php echo $banner['titulo'] ?? 'Promoción'; ?>">
                <div class="card-img-overlay d-flex flex-column justify-content-center text-center">
                    <?php if (!empty($banner['titulo'])): ?>
                        <h3 class="card-title"><?php echo $banner['titulo']; ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($banner['subtitulo'])): ?>
                        <p class="card-text"><?php echo $banner['subtitulo']; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($banner['link'])): ?>
                        <a href="<?php echo $banner['link']; ?>" class="btn btn-compuya-secondary mx-auto">Ver más</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Nuevos productos -->
    <?php if (!empty($productos_nuevos)): ?>
        <section class="mb-5">
            <h2 class="text-center mb-4">Nuevos Productos</h2>
            <div class="row">
                <?php foreach ($productos_nuevos as $producto): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="card product-card">
                            <span class="badge bg-info product-card__badge">Nuevo</span>
                            <a href="<?php echo BASE_URL; ?>producto/<?php echo $producto['slug']; ?>">
                                <img src="<?php echo !empty($producto['imagen_principal']) ? BASE_URL . 'assets/uploads/productos/' . $producto['imagen_principal'] : BASE_URL . 'assets/img/no-image.png'; ?>" class="card-img-top product-card__image" alt="<?php echo $producto['nombre']; ?>">
                            </a>
                            <div class="card-body product-card__body">
                                <h5 class="card-title product-card__title">
                                    <a href="<?php echo BASE_URL; ?>producto/<?php echo $producto['slug']; ?>"><?php echo $producto['nombre']; ?></a>
                                </h5>
                                <div class="mb-2">
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star-half-alt text-warning"></i>
                                </div>
                                <p class="card-text product-card__price">
                                    <?php if (!empty($producto['precio_oferta'])): ?>
                                        <span class="product-card__discount-price">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></span>
                                        <span class="product-card__original-price">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                                    <?php else: ?>
                                        S/ <?php echo number_format($producto['precio'], 2); ?>
                                    <?php endif; ?>
                                </p>
                                <a href="#" class="btn btn-compuya product-card__button add-to-cart" 
                                   data-id="<?php echo $producto['id']; ?>" 
                                   data-name="<?php echo $producto['nombre']; ?>" 
                                   data-price="<?php echo !empty($producto['precio_oferta']) ? $producto['precio_oferta'] : $producto['precio']; ?>" 
                                   data-image="<?php echo !empty($producto['imagen_principal']) ? $producto['imagen_principal'] : ''; ?>">
                                    <i class="fas fa-shopping-cart me-2"></i>Añadir al carrito
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Banner inferior (si existe) -->
    <?php 
    $banner_inferior = array_filter($banners, function($banner) {
        return $banner['posicion'] == 'inferior';
    });
    if (!empty($banner_inferior)): 
        $banner = reset($banner_inferior); // Obtener el primer banner
    ?>
        <section class="mb-5">
            <div class="card bg-dark text-white banner">
                <img src="<?php echo BASE_URL; ?>assets/uploads/banners/<?php echo $banner['imagen']; ?>" class="card-img" alt="<?php echo $banner['titulo'] ?? 'Promoción'; ?>">
                <div class="card-img-overlay d-flex flex-column justify-content-center text-center">
                    <?php if (!empty($banner['titulo'])): ?>
                        <h3 class="card-title"><?php echo $banner['titulo']; ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($banner['subtitulo'])): ?>
                        <p class="card-text"><?php echo $banner['subtitulo']; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($banner['link'])): ?>
                        <a href="<?php echo $banner['link']; ?>" class="btn btn-compuya-secondary mx-auto">Ver más</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Servicios -->
    <section class="mb-5">
        <h2 class="text-center mb-4">Nuestros Servicios</h2>
        <div class="row">
            <div class="col-md-4 mb-4 text-center">
                <div class="p-4 bg-white rounded shadow-sm">
                    <i class="fas fa-truck fa-3x mb-3 text-primary"></i>
                    <h3>Envío Rápido</h3>
                    <p>Entregas en Lima en 24 horas y a provincia en 48 horas hábiles.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4 text-center">
                <div class="p-4 bg-white rounded shadow-sm">
                    <i class="fas fa-headset fa-3x mb-3 text-primary"></i>
                    <h3>Soporte Técnico</h3>
                    <p>Asesoría especializada para resolver todas tus dudas.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4 text-center">
                <div class="p-4 bg-white rounded shadow-sm">
                    <i class="fas fa-shield-alt fa-3x mb-3 text-primary"></i>
                    <h3>Garantía Segura</h3>
                    <p>Todos nuestros productos cuentan con garantía oficial.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Marcas -->
    <section class="mb-5">
        <h2 class="text-center mb-4">Marcas Destacadas</h2>
        <div class="row align-items-center text-center">
            <div class="col-4 col-md-2 mb-3">
                <img src="<?php echo BASE_URL; ?>assets/img/marca-intel.png" alt="Intel" class="img-fluid" style="max-height: 60px;">
            </div>
            <div class="col-4 col-md-2 mb-3">
                <img src="<?php echo BASE_URL; ?>assets/img/marca-amd.png" alt="AMD" class="img-fluid" style="max-height: 60px;">
            </div>
            <div class="col-4 col-md-2 mb-3">
                <img src="<?php echo BASE_URL; ?>assets/img/marca-nvidia.png" alt="NVIDIA" class="img-fluid" style="max-height: 60px;">
            </div>
            <div class="col-4 col-md-2 mb-3">
                <img src="<?php echo BASE_URL; ?>assets/img/marca-asus.png" alt="ASUS" class="img-fluid" style="max-height: 60px;">
            </div>
            <div class="col-4 col-md-2 mb-3">
                <img src="<?php echo BASE_URL; ?>assets/img/marca-hp.png" alt="HP" class="img-fluid" style="max-height: 60px;">
            </div>
            <div class="col-4 col-md-2 mb-3">
                <img src="<?php echo BASE_URL; ?>assets/img/marca-msi.png" alt="MSI" class="img-fluid" style="max-height: 60px;">
            </div>
        </div>
    </section>
</div>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>