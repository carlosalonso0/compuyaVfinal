<?php
// Incluir archivo de configuración
require_once 'config/config.php';
require_once 'config/database.php';

// Crear instancia de la base de datos
$database = new Database();
$db = $database->connect();

// Verificar si se ha proporcionado un slug
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: index.php');
    exit;
}

$slug = $_GET['slug'];

// Incluir modelos necesarios
require_once 'models/Producto.php';
require_once 'models/Categoria.php';

// Crear instancias
$producto = new Producto($db);
$categoria = new Categoria($db);

// Buscar el producto por su slug
$existe = $producto->getBySlug($slug);

// Si no existe el producto, redirigir a la página principal
if (!$existe) {
    header('Location: index.php');
    exit;
}

// Obtener la categoría del producto
$categoria->id = $producto->categoria_id;
$categoria->getSingle();

// Obtener imágenes adicionales del producto
$query = "SELECT * FROM imagenes_producto WHERE producto_id = :producto_id ORDER BY orden ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':producto_id', $producto->id);
$stmt->execute();
$imagenes_adicionales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener especificaciones técnicas
$query = "SELECT * FROM especificaciones WHERE producto_id = :producto_id ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':producto_id', $producto->id);
$stmt->execute();
$especificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos relacionados (de la misma categoría)
$productos_relacionados = [];
if ($producto->categoria_id) {
    $query = "SELECT p.* FROM productos p 
              WHERE p.categoria_id = :categoria_id 
              AND p.id != :producto_id 
              AND p.activo = true 
              ORDER BY p.destacado DESC, RAND() 
              LIMIT 4";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':categoria_id', $producto->categoria_id);
    $stmt->bindParam(':producto_id', $producto->id);
    $stmt->execute();
    $productos_relacionados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Título de la página
$page_title = $producto->nombre;

// Incluir encabezado
include 'includes/header.php';

// Incluir navegación
include 'includes/navbar.php';
?>

<div class="container mt-4">
    <!-- Navegación de migas de pan -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Inicio</a></li>
            <?php if ($categoria->id): ?>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>categoria/<?php echo $categoria->slug; ?>"><?php echo $categoria->nombre; ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $producto->nombre; ?></li>
        </ol>
    </nav>

    <div class="row mb-5">
        <!-- Galería de imágenes -->
        <div class="col-md-5 mb-4">
            <div class="product-image-container mb-3">
                <img id="main-product-image" src="<?php echo !empty($producto->imagen_principal) ? BASE_URL . 'assets/uploads/productos/' . $producto->imagen_principal : BASE_URL . 'assets/img/no-image.png'; ?>" class="product-detail__image-main img-fluid" alt="<?php echo $producto->nombre; ?>">
            </div>
            
            <?php if (!empty($producto->imagen_principal) || count($imagenes_adicionales) > 0): ?>
                <div class="product-thumbnails d-flex flex-wrap">
                    <?php if (!empty($producto->imagen_principal)): ?>
                        <div class="thumbnail-item me-2 mb-2">
                            <img src="<?php echo BASE_URL; ?>assets/uploads/productos/<?php echo $producto->imagen_principal; ?>" class="product-detail__image-thumbnail active" alt="<?php echo $producto->nombre; ?>" data-image="<?php echo BASE_URL; ?>assets/uploads/productos/<?php echo $producto->imagen_principal; ?>">
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($imagenes_adicionales as $imagen): ?>
                        <div class="thumbnail-item me-2 mb-2">
                            <img src="<?php echo BASE_URL; ?>assets/uploads/productos/<?php echo $imagen['url_imagen']; ?>" class="product-detail__image-thumbnail" alt="<?php echo $producto->nombre; ?>" data-image="<?php echo BASE_URL; ?>assets/uploads/productos/<?php echo $imagen['url_imagen']; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Información del producto -->
        <div class="col-md-7">
            <h1 class="product-detail__title"><?php echo $producto->nombre; ?></h1>
            
            <?php if ($producto->nuevo): ?>
                <span class="badge bg-info me-2">Nuevo</span>
            <?php endif; ?>
            
            <div class="product-detail__price my-3">
                <?php if (!empty($producto->precio_oferta)): ?>
                    <span class="product-card__original-price text-decoration-line-through me-2">S/ <?php echo number_format($producto->precio, 2); ?></span>
                    <span class="product-card__discount-price">S/ <?php echo number_format($producto->precio_oferta, 2); ?></span>
                <?php else: ?>
                    <span>S/ <?php echo number_format($producto->precio, 2); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- Disponibilidad -->
            <div class="mb-3">
                <strong>Disponibilidad:</strong>
                <?php if ($producto->stock > 0): ?>
                    <span class="text-success">En stock (<?php echo $producto->stock; ?> unidades)</span>
                <?php else: ?>
                    <span class="text-danger">Agotado</span>
                <?php endif; ?>
            </div>
            
            <!-- Marca y modelo -->
            <div class="mb-3">
                <?php if (!empty($producto->marca)): ?>
                    <div><strong>Marca:</strong> <?php echo $producto->marca; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($producto->modelo)): ?>
                    <div><strong>Modelo:</strong> <?php echo $producto->modelo; ?></div>
                <?php endif; ?>
            </div>
            
            <!-- Descripción corta -->
            <?php if (!empty($producto->descripcion_corta)): ?>
                <div class="product-detail__short-description mb-3">
                    <?php echo $producto->descripcion_corta; ?>
                </div>
            <?php endif; ?>
            
            <!-- Añadir al carrito -->
            <div class="product-detail__cart-actions mb-4">
                <div class="row">
                    <div class="col-md-4 col-sm-4 col-6">
                        <div class="input-group mb-3">
                            <button class="btn btn-outline-secondary decrease-quantity" type="button">-</button>
                            <input type="number" class="form-control text-center quantity-input" value="1" min="1" max="<?php echo $producto->stock; ?>">
                            <button class="btn btn-outline-secondary increase-quantity" type="button">+</button>
                        </div>
                    </div>
                    <div class="col-md-8 col-sm-8 col-6">
                        <button class="btn btn-compuya btn-lg w-100 add-to-cart" <?php echo ($producto->stock <= 0) ? 'disabled' : ''; ?> 
                                data-id="<?php echo $producto->id; ?>" 
                                data-name="<?php echo $producto->nombre; ?>" 
                                data-price="<?php echo !empty($producto->precio_oferta) ? $producto->precio_oferta : $producto->precio; ?>" 
                                data-image="<?php echo !empty($producto->imagen_principal) ? $producto->imagen_principal : ''; ?>">
                            <i class="fas fa-shopping-cart me-2"></i> Añadir al carrito
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Características principales -->
            <?php if (!empty($producto->caracteristicas)): ?>
                <div class="product-detail__features">
                    <h5>Características principales</h5>
                    <ul>
                        <?php 
                        $caracteristicas = explode("\n", $producto->caracteristicas);
                        foreach ($caracteristicas as $caracteristica): 
                            if (trim($caracteristica) !== ''):
                        ?>
                            <li><?php echo trim($caracteristica); ?></li>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pestañas de contenido -->
    <div class="product-detail__tabs mb-5">
        <ul class="nav nav-tabs" id="productTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">Descripción</button>
            </li>
            <?php if (count($especificaciones) > 0): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs" type="button" role="tab" aria-controls="specs" aria-selected="false">Especificaciones</button>
                </li>
            <?php endif; ?>
        </ul>
        <div class="tab-content p-4 border border-top-0" id="productTabsContent">
            <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                <?php echo nl2br($producto->descripcion); ?>
            </div>
            <?php if (count($especificaciones) > 0): ?>
                <div class="tab-pane fade" id="specs" role="tabpanel" aria-labelledby="specs-tab">
                    <h4 class="mb-4">Especificaciones técnicas</h4>
                    <table class="table table-striped">
                        <tbody>
                            <?php foreach ($especificaciones as $spec): ?>
                                <tr>
                                    <th width="30%"><?php echo $spec['nombre']; ?></th>
                                    <td><?php echo $spec['valor']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Productos relacionados -->
    <?php if (count($productos_relacionados) > 0): ?>
        <div class="product-detail__related mb-5">
            <h3 class="mb-4">Productos relacionados</h3>
            <div class="row">
                <?php foreach ($productos_relacionados as $prod_rel): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="card product-card">
                            <?php if ($prod_rel['nuevo']): ?>
                                <span class="badge bg-info product-card__badge">Nuevo</span>
                            <?php endif; ?>
                            
                            <a href="<?php echo BASE_URL; ?>producto/<?php echo $prod_rel['slug']; ?>">
                                <img src="<?php echo !empty($prod_rel['imagen_principal']) ? BASE_URL . 'assets/uploads/productos/' . $prod_rel['imagen_principal'] : BASE_URL . 'assets/img/no-image.png'; ?>" class="card-img-top product-card__image" alt="<?php echo $prod_rel['nombre']; ?>">
                            </a>
                            
                            <div class="card-body product-card__body">
                                <h5 class="card-title product-card__title">
                                    <a href="<?php echo BASE_URL; ?>producto/<?php echo $prod_rel['slug']; ?>"><?php echo $prod_rel['nombre']; ?></a>
                                </h5>
                                
                                <p class="card-text product-card__price">
                                    <?php if (!empty($prod_rel['precio_oferta'])): ?>
                                        <span class="product-card__discount-price">S/ <?php echo number_format($prod_rel['precio_oferta'], 2); ?></span>
                                        <span class="product-card__original-price">S/ <?php echo number_format($prod_rel['precio'], 2); ?></span>
                                    <?php else: ?>
                                        S/ <?php echo number_format($prod_rel['precio'], 2); ?>
                                    <?php endif; ?>
                                </p>
                                
                                <a href="#" class="btn btn-compuya product-card__button add-to-cart" 
                                   data-id="<?php echo $prod_rel['id']; ?>" 
                                   data-name="<?php echo $prod_rel['nombre']; ?>" 
                                   data-price="<?php echo !empty($prod_rel['precio_oferta']) ? $prod_rel['precio_oferta'] : $prod_rel['precio']; ?>" 
                                   data-image="<?php echo !empty($prod_rel['imagen_principal']) ? $prod_rel['imagen_principal'] : ''; ?>">
                                    <i class="fas fa-shopping-cart me-2"></i>Añadir al carrito
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cambiar imagen principal al hacer clic en las miniaturas
    const thumbnails = document.querySelectorAll('.product-detail__image-thumbnail');
    const mainImage = document.getElementById('main-product-image');
    
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            // Cambiar la imagen principal
            mainImage.src = this.getAttribute('data-image');
            
            // Actualizar clase activa
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Control de cantidad
    const quantityInput = document.querySelector('.quantity-input');
    const decreaseBtn = document.querySelector('.decrease-quantity');
    const increaseBtn = document.querySelector('.increase-quantity');
    
    decreaseBtn.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        if (value > 1) {
            quantityInput.value = value - 1;
        }
    });
    
    increaseBtn.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        let max = parseInt(quantityInput.getAttribute('max'));
        if (value < max) {
            quantityInput.value = value + 1;
        }
    });
    
    // Añadir al carrito
    const addToCartBtn = document.querySelector('.product-detail__cart-actions .add-to-cart');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            const productPrice = parseFloat(this.getAttribute('data-price'));
            const productImage = this.getAttribute('data-image');
            const quantity = parseInt(quantityInput.value);
            
            // Añadir al carrito con la cantidad seleccionada
            addToCart(productId, productName, productPrice, productImage, quantity);
            
            // Mostrar notificación
            showNotification(`"${productName}" añadido al carrito`);
        });
    }
});
</script>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>