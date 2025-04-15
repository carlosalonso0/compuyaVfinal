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

// Organizar especificaciones por tipo
$specs_por_tipo = [];
if (!empty($especificaciones)) {
    $specs_por_tipo['especificaciones_tecnicas'] = $especificaciones;
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

// Incluir cabecera
include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/product.css">

<section class="product-header">
    <div class="container">
        <div class="breadcrumbs">
            <a href="index.php">Inicio</a> &raquo; 
            <a href="category.php?id=<?php echo $producto['categoria_id']; ?>"><?php echo $producto['categoria_nombre']; ?></a> &raquo; 
            <?php echo $producto['nombre']; ?>
        </div>
    </div>
</section>

<?php if (!empty($mensaje)): ?>
<div class="container">
    <div class="mensaje-container">
        <div class="mensaje exito"><?php echo $mensaje; ?></div>
    </div>
</div>
<?php endif; ?>

<section class="product-main">
    <div class="container">
        <div class="product-layout">
            <!-- Galería de imágenes -->
            <div class="product-gallery">
            <div class="gallery-main">
                <img id="main-image" src="<?php echo BASE_URL . '/' . obtenerImagenProducto($producto['id']); ?>" alt="<?php echo $producto['nombre']; ?>">
                <div class="zoom-hint">🔍 Pase el mouse para hacer zoom</div>
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
            
            <!-- Información del producto -->
            <div class="product-info">
                <div class="product-brand"><?php echo $producto['marca']; ?></div>
                <h1 class="product-name"><?php echo $producto['nombre']; ?></h1>
                <div class="product-model">Modelo: <?php echo $producto['modelo']; ?></div>
                
                <div class="product-price">
                    <?php if (!empty($producto['precio_oferta'])): ?>
                        <div class="price-original">S/ <?php echo number_format($producto['precio'], 2); ?></div>
                        <div class="price-current">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></div>
                        <div class="price-discount">
                            <?php 
                            $discount = round(100 - (($producto['precio_oferta'] / $producto['precio']) * 100)); 
                            echo $discount; 
                            ?>% DSCTO
                        </div>
                    <?php else: ?>
                        <div class="price-current">S/ <?php echo number_format($producto['precio'], 2); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="product-stock <?php echo $producto['stock'] <= 5 ? 'low-stock' : ''; ?>">
                    <?php if ($producto['stock'] > 0): ?>
                        <span class="stock-status in-stock">En stock</span>
                        <?php if ($producto['stock'] <= 5): ?>
                            <span class="stock-qty">¡Solo quedan <?php echo $producto['stock']; ?> unidades!</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="stock-status out-of-stock">Agotado</span>
                    <?php endif; ?>
                </div>
                
                <div class="product-short-desc">
                    <?php echo $producto['descripcion_corta']; ?>
                </div>
                
                <?php if ($producto['stock'] > 0): ?>
                <div class="product-actions">
                    <form id="add-to-cart-form" class="cart-form">
                        <div class="quantity-control">
                            <button type="button" class="qty-btn qty-decrease">-</button>
                            <input type="number" name="cantidad" id="cantidad" value="1" min="1" max="<?php echo $producto['stock']; ?>">
                            <button type="button" class="qty-btn qty-increase">+</button>
                        </div>
                        
                        <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
                        <button type="submit" class="btn-add-cart">
                            <span class="cart-icon">🛒</span>
                            Añadir al carrito
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="product-meta">
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
                </div>
                
                <div class="product-benefits">
                    <div class="benefit">
                        <div class="benefit-icon">🚚</div>
                        <div class="benefit-text">Envío a todo Perú</div>
                    </div>
                    
                    <div class="benefit">
                        <div class="benefit-icon">🛡️</div>
                        <div class="benefit-text">Garantía oficial</div>
                    </div>
                    
                    <div class="benefit">
                        <div class="benefit-icon">💳</div>
                        <div class="benefit-text">Pago seguro</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="product-details">
    <div class="container">
        <div class="product-tabs">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="description">Descripción</button>
                <?php if (!empty($caracteristicas_lista)): ?>
                <button class="tab-btn" data-tab="features">Características</button>
                <?php endif; ?>
                <?php if (!empty($especificaciones)): ?>
                <button class="tab-btn" data-tab="specs">Especificaciones</button>
                <?php endif; ?>
                <button class="tab-btn" data-tab="shipping">Envío y Garantía</button>
            </div>
            
            <div class="tabs-content">
                <div class="tab-panel active" id="description">
                    <div class="product-description">
                        <?php echo nl2br($producto['descripcion']); ?>
                    </div>
                </div>
                
                <?php if (!empty($caracteristicas_lista)): ?>
                <div class="tab-panel" id="features">
                    <div class="product-features">
                        <ul>
                            <?php foreach ($caracteristicas_lista as $caracteristica): ?>
                            <li><?php echo $caracteristica; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($especificaciones)): ?>
                    <div class="tab-panel" id="specs">
                        <div class="product-specs">
                            <div class="specs-group">
                                <h3>Especificaciones Técnicas</h3>
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
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                
                <div class="tab-panel" id="shipping">
                    <div class="shipping-info">
                        <h3>Envío</h3>
                        <p>En COMPU YA ofrecemos varias opciones de envío para tu comodidad:</p>
                        <ul>
                            <li><strong>Envío estándar:</strong> De 3 a 5 días hábiles en Lima Metropolitana.</li>
                            <li><strong>Envío a provincia:</strong> De 5 a 7 días hábiles según la localidad.</li>
                            <li><strong>Recojo en tienda:</strong> Disponible el mismo día si el producto está en stock.</li>
                        </ul>
                        
                        <p>El costo de envío se calcula en función de la ubicación y el tamaño/peso del producto. Los envíos para compras superiores a S/ 300 en Lima Metropolitana son GRATIS.</p>
                        
                        <h3>Garantía</h3>
                        <p>Todos nuestros productos cuentan con garantía oficial del fabricante:</p>
                        <ul>
                            <li>Garantía de 12 meses en la mayoría de productos.</li>
                            <li>Soporte técnico especializado para resolver cualquier problema.</li>
                            <li>Posibilidad de extensión de garantía en productos seleccionados.</li>
                        </ul>
                        
                        <p>Para hacer efectiva la garantía, es necesario presentar la factura de compra y el producto debe estar en buenas condiciones físicas, sin daños causados por mal uso.</p>
                        
                        <h3>Política de devoluciones</h3>
                        <p>Si no estás satisfecho con tu compra, puedes solicitar una devolución dentro de los primeros 7 días después de recibir el producto. El producto debe estar en su empaque original y sin señales de uso.</p>
                        
                        <p>Para más información sobre nuestras políticas de envío, garantía y devoluciones, puedes contactar a nuestro servicio al cliente.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($productos_relacionados)): ?>
<section class="related-products">
    <div class="container">
        <h2>Productos Relacionados</h2>
        
        <div class="related-products-grid">
            <?php foreach ($productos_relacionados as $prod_rel): ?>
                <div class="producto-card">
                <a href="<?php echo BASE_URL; ?>/producto/<?php echo $prod_rel['slug']; ?>" class="product-link">
                <div class="producto-imagen">
                    <img src="<?php echo BASE_URL . '/' . obtenerImagenProducto($prod_rel['id']); ?>" alt="<?php echo $prod_rel['nombre']; ?>">
                </div>
                        <div class="producto-info">
                            <div class="producto-marca"><?php echo $prod_rel['marca']; ?></div>
                            <h3 class="producto-nombre"><?php echo $prod_rel['nombre']; ?></h3>
                            <div class="producto-precio">
                                <?php if (!empty($prod_rel['precio_oferta'])): ?>
                                    <span class="precio-antiguo">S/ <?php echo number_format($prod_rel['precio'], 2); ?></span>
                                    <span class="precio-actual">S/ <?php echo number_format($prod_rel['precio_oferta'], 2); ?></span>
                                <?php else: ?>
                                    <span class="precio-actual">S/ <?php echo number_format($prod_rel['precio'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cambio de pestañas
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Desactivar todas las pestañas
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanels.forEach(panel => panel.classList.remove('active'));
            
            // Activar la pestaña seleccionada
            button.classList.add('active');
            document.getElementById(button.getAttribute('data-tab')).classList.add('active');
        });
    });
    
    // Galería de imágenes
    const thumbs = document.querySelectorAll('.thumb');
    const mainImage = document.getElementById('main-image');
    
    thumbs.forEach(thumb => {
        thumb.addEventListener('click', () => {
            thumbs.forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
            mainImage.src = thumb.getAttribute('data-img');
        });
    });
    
    // Zoom en la imagen principal
    const galleryMain = document.querySelector('.gallery-main');
    if (galleryMain) {
        galleryMain.addEventListener('mousemove', function(e) {
            const img = this.querySelector('img');
            const bounds = this.getBoundingClientRect();
            
            // Calcular posición relativa del cursor
            const x = (e.clientX - bounds.left) / bounds.width;
            const y = (e.clientY - bounds.top) / bounds.height;
            
            // Aplicar transformación
            img.style.transformOrigin = `${x * 100}% ${y * 100}%`;
            img.style.transform = 'scale(1.5)';
        });
        
        galleryMain.addEventListener('mouseleave', function() {
            const img = this.querySelector('img');
            img.style.transform = 'scale(1)';
        });
    }
    
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
    const addToCartForm = document.getElementById('add-to-cart-form');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('Formulario enviado'); // Para debugging
            
            const formData = new FormData(this);
            formData.append('action', 'add');
            
            // Enviar mediante fetch
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
                console.log('Respuesta:', data); // Para debugging
                
                if (data.success) {
                    // Redireccionar a la misma página con mensaje de éxito
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
    } else {
        console.error('Formulario no encontrado');
    }
});
</script>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>