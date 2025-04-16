<?php
define('IN_COMPUYA', true);
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Obtener ID o slug del producto
$producto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

$db = Database::getInstance();
$conn = $db->getConnection();

// Determinar qué consulta usar
if ($producto_id > 0) {
    // Buscar por ID
    $stmt = $conn->prepare("SELECT p.*, c.nombre as categoria_nombre FROM productos p 
                          LEFT JOIN categorias c ON p.categoria_id = c.id 
                          WHERE p.id = ? AND p.activo = 1");
    $stmt->bind_param("i", $producto_id);
} elseif (!empty($slug)) {
    // Buscar por slug
    $stmt = $conn->prepare("SELECT p.*, c.nombre as categoria_nombre FROM productos p 
                          LEFT JOIN categorias c ON p.categoria_id = c.id 
                          WHERE p.slug = ? AND p.activo = 1");
    $stmt->bind_param("s", $slug);
} else {
    // Ni ID ni slug proporcionados
    header('Location: index.php');
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$producto = $result->fetch_assoc();
$producto_id = $producto['id']; // Asignar ID para usar en el resto del código
$page_title = $producto['nombre'];

// Obtener especificaciones del producto
$especificaciones = [];
$stmt = $conn->prepare("SELECT * FROM especificaciones_producto WHERE producto_id = ? ORDER BY nombre");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result_specs = $stmt->get_result();

while ($spec = $result_specs->fetch_assoc()) {
    $especificaciones[] = $spec;
}

// Obtener imágenes del producto
$imagenes = [];
$stmt = $conn->prepare("SELECT * FROM imagenes_producto WHERE producto_id = ? ORDER BY principal DESC, id ASC");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result_img = $stmt->get_result();

while ($img = $result_img->fetch_assoc()) {
    $imagenes[] = $img;
}

// Si no hay imágenes, usar una por defecto
if (empty($imagenes)) {
    $imagenes[] = ['ruta' => 'assets/img/productos/placeholder.png', 'principal' => 1];
}

// Obtener productos relacionados (misma categoría)
$productos_relacionados = [];
$stmt = $conn->prepare("SELECT p.* FROM productos p 
                       WHERE p.categoria_id = ? AND p.id != ? AND p.activo = 1 
                       ORDER BY p.destacado DESC, RAND() 
                       LIMIT 4");
$stmt->bind_param("ii", $producto['categoria_id'], $producto_id);
$stmt->execute();
$result_rel = $stmt->get_result();

while ($prod_rel = $result_rel->fetch_assoc()) {
    $productos_relacionados[] = $prod_rel;
}

// Procesar caracteristicas (formato de lista)
$caracteristicas_lista = [];
if (!empty($producto['caracteristicas'])) {
    $caracteristicas_lista = explode("\n", $producto['caracteristicas']);
    $caracteristicas_lista = array_map('trim', $caracteristicas_lista);
    $caracteristicas_lista = array_filter($caracteristicas_lista);
}

// Controlar mensajes
$mensaje = '';
if (isset($_GET['added']) && $_GET['added'] == 1) {
    $mensaje = 'Producto añadido al carrito correctamente.';
}

// Definir CSS adicional para esta página
$extra_css = ['product-redesign.css'];

// Incluir cabecera
include 'includes/header.php';
?>

<!-- Barra de navegación / migas de pan -->
<div class="breadcrumb-container">
    <div class="container">
        <div class="breadcrumbs">
            <a href="<?php echo BASE_URL; ?>/index.php">Inicio</a> &raquo; 
            <a href="<?php echo BASE_URL; ?>/category.php?id=<?php echo $producto['categoria_id']; ?>"><?php echo $producto['categoria_nombre']; ?></a> &raquo; 
            <?php echo $producto['nombre']; ?>
        </div>
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="container">
    <div class="mensaje-container">
        <div class="mensaje exito"><?php echo $mensaje; ?></div>
    </div>
</div>
<?php endif; ?>

<!-- Contenido Principal -->
<div class="product-main-container">
    <div class="container">
        <div class="white-card">
            <div class="product-layout">
                <!-- Galería de imágenes - Columna izquierda -->
                <div class="product-gallery">
                    <div class="gallery-main">
                        <img id="main-image" src="<?php echo BASE_URL . '/' . obtenerImagenProducto($producto['id']); ?>" alt="<?php echo $producto['nombre']; ?>">
                    </div>
                    
                    <?php if (count($imagenes) > 1): ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($imagenes as $index => $imagen): ?>
                        <div class="thumb <?php echo $index === 0 ? 'active' : ''; ?>" data-img="<?php echo BASE_URL . '/' . $imagen['ruta']; ?>">
                            <img src="<?php echo BASE_URL . '/' . $imagen['ruta']; ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Información del producto - Columna derecha -->
                <div class="product-info">
                    <div class="product-header">
                        <div class="product-brand"><?php echo $producto['marca']; ?></div>
                        <h1 class="product-name"><?php echo $producto['nombre']; ?></h1>
                        
                        <?php if ($producto['stock'] > 5): ?>
                        <div class="product-rating">
                            <div class="stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <span>(143 opiniones)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-price-block">
                        <?php if (!empty($producto['precio_oferta'])): ?>
                            <div class="price-original">S/ <?php echo number_format($producto['precio'], 2); ?></div>
                            <div class="price-current">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></div>
                        <?php else: ?>
                            <div class="price-current">S/ <?php echo number_format($producto['precio'], 2); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-stock <?php echo $producto['stock'] <= 5 ? 'low-stock' : ''; ?>">
                        <?php if ($producto['stock'] > 0): ?>
                            <span class="stock-status in-stock"><i class="fas fa-check-circle"></i> En stock (<?php echo $producto['stock']; ?> unidades)</span>
                        <?php else: ?>
                            <span class="stock-status out-of-stock"><i class="fas fa-times-circle"></i> Agotado</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-short-desc">
                        <?php echo $producto['descripcion_corta']; ?>
                    </div>
                    
                    <?php if ($producto['stock'] > 0): ?>
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <button type="button" class="qty-btn qty-decrease">-</button>
                            <input type="number" id="cantidad" value="1" min="1" max="<?php echo $producto['stock']; ?>">
                            <button type="button" class="qty-btn qty-increase">+</button>
                        </div>
                        
                        <button id="btn-add-cart" class="btn-add-cart">
                            <i class="fas fa-shopping-cart"></i>
                            Añadir al carrito
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <span class="meta-label">SKU:</span>
                            <span class="meta-value"><?php echo $producto['sku']; ?></span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Categoría:</span>
                            <a href="category.php?id=<?php echo $producto['categoria_id']; ?>" class="meta-value"><?php echo $producto['categoria_nombre']; ?></a>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Marca:</span>
                            <span class="meta-value"><?php echo $producto['marca']; ?></span>
                        </div>
                        
                        <?php if (!empty($producto['modelo'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Modelo:</span>
                            <span class="meta-value"><?php echo $producto['modelo']; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <span class="meta-label">Garantía:</span>
                            <span class="meta-value">1 año con el fabricante</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Especificaciones y descripción -->
        <div class="product-details-container">
            <div class="product-details-layout">
                <!-- Especificaciones -->
                <div class="white-card product-specs-card">
                    <h2 class="section-title">Especificaciones técnicas</h2>
                    <?php if (!empty($especificaciones)): ?>
                        <table class="specs-table">
                            <tbody>
                                <?php foreach ($especificaciones as $spec): ?>
                                <tr>
                                    <th><?php echo $spec['nombre']; ?></th>
                                    <td><?php echo $spec['valor']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No hay especificaciones disponibles para este producto.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Descripción -->
                <div class="white-card product-description-card">
                    <h2 class="section-title">Descripción</h2>
                    <div class="product-description">
                        <?php echo nl2br($producto['descripcion']); ?>
                    </div>
                    
                    <?php if (!empty($caracteristicas_lista)): ?>
                    <div class="product-features">
                        <h3>Características</h3>
                        <ul>
                            <?php foreach ($caracteristicas_lista as $caracteristica): ?>
                            <li><?php echo $caracteristica; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Productos relacionados -->
        <?php if (!empty($productos_relacionados)): ?>
        <div class="related-products-section">
            <h2 class="section-title">Productos Relacionados</h2>
            
            <div class="related-products-grid">
                <?php foreach ($productos_relacionados as $prod_rel): ?>
                <div class="producto-card">
                    <?php if (!empty($prod_rel['precio_oferta'])): ?>
                    <div class="etiqueta-oferta">OFERTA</div>
                    <?php endif; ?>
                    
                    <div class="producto-imagen">
                        <img src="<?php echo BASE_URL . '/' . obtenerImagenProducto($prod_rel['id']); ?>" alt="<?php echo $prod_rel['nombre']; ?>">
                    </div>
                    
                    <div class="producto-info">
                        <div class="producto-marca"><?php echo $prod_rel['marca']; ?></div>
                        <h3 class="producto-nombre">
                            <?php echo $prod_rel['nombre']; ?>
                        </h3>
                        
                        <div class="producto-precio">
                            <?php if (!empty($prod_rel['precio_oferta'])): ?>
                            <div>
                                <span class="precio-antiguo">S/ <?php echo number_format($prod_rel['precio'], 2); ?></span>
                                <span class="precio-actual">S/ <?php echo number_format($prod_rel['precio_oferta'], 2); ?></span>
                                <div>
                                    <span class="descuento">
                                        <?php echo round(100 - (($prod_rel['precio_oferta'] / $prod_rel['precio']) * 100)); ?>% DSCTO
                                    </span>
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="precio-actual">S/ <?php echo number_format($prod_rel['precio'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($prod_rel['stock'] > 0): ?>
                        <div class="producto-stock">
                            <span class="en-stock">En stock</span>
                        </div>
                        <?php else: ?>
                        <div class="producto-stock">
                            <span class="sin-stock">Agotado</span>
                        </div>
                        <?php endif; ?>
                        
                        <a href="producto/<?php echo $prod_rel['slug']; ?>" class="btn-ver">Ver Producto</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cambio de imagen principal al hacer clic en miniaturas
    const thumbs = document.querySelectorAll('.thumb');
    const mainImage = document.getElementById('main-image');
    
    thumbs.forEach(thumb => {
        thumb.addEventListener('click', () => {
            thumbs.forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
            mainImage.src = thumb.getAttribute('data-img');
        });
    });
    
    // Control de cantidad
    const qtyDecrease = document.querySelector('.qty-decrease');
    const qtyIncrease = document.querySelector('.qty-increase');
    const qtyInput = document.getElementById('cantidad');
    
    if (qtyDecrease && qtyIncrease && qtyInput) {
        qtyDecrease.addEventListener('click', () => {
            const currentValue = parseInt(qtyInput.value);
            if (currentValue > 1) {
                qtyInput.value = currentValue - 1;
            }
        });
        
        qtyIncrease.addEventListener('click', () => {
            const currentValue = parseInt(qtyInput.value);
            const maxValue = parseInt(qtyInput.getAttribute('max'));
            if (currentValue < maxValue) {
                qtyInput.value = currentValue + 1;
            }
        });
        
        qtyInput.addEventListener('change', () => {
            const currentValue = parseInt(qtyInput.value);
            const maxValue = parseInt(qtyInput.getAttribute('max'));
            
            if (isNaN(currentValue) || currentValue < 1) {
                qtyInput.value = 1;
            } else if (currentValue > maxValue) {
                qtyInput.value = maxValue;
            }
        });
    }
    
    // Añadir al carrito
    const btnAddCart = document.getElementById('btn-add-cart');
    if (btnAddCart) {
        btnAddCart.addEventListener('click', function() {
            const cantidad = document.getElementById('cantidad') ? document.getElementById('cantidad').value : 1;
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('producto_id', <?php echo $producto_id; ?>);
            formData.append('cantidad', cantidad);
            
            fetch('<?php echo BASE_URL; ?>/cart_add.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect_url || `<?php echo BASE_URL; ?>/producto/<?php echo $producto['slug']; ?>?added=1`;
                } else {
                    alert(data.error || 'Error al añadir al carrito');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud: ' + error.message);
            });
        });
    }
});
</script>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>