<?php
define('IN_COMPUYA', true);
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Variable para el título de la página
$page_title = 'Inicio';

// Obtener productos destacados
$db = Database::getInstance();
$conn = $db->getConnection();

// Productos destacados
$sql_destacados = "SELECT p.*, c.nombre as categoria_nombre FROM productos p 
                JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.destacado = 1 AND p.activo = 1 
                ORDER BY p.id DESC LIMIT 8";
$result_destacados = $conn->query($sql_destacados);
$productosDestacados = $result_destacados->fetch_all(MYSQLI_ASSOC);

// Productos nuevos
$sql_nuevos = "SELECT p.*, c.nombre as categoria_nombre FROM productos p 
             JOIN categorias c ON p.categoria_id = c.id 
             WHERE p.nuevo = 1 AND p.activo = 1 
             ORDER BY p.id DESC LIMIT 8";
$result_nuevos = $conn->query($sql_nuevos);
$productosNuevos = $result_nuevos->fetch_all(MYSQLI_ASSOC);

// Productos en oferta
$sql_ofertas = "SELECT p.*, c.nombre as categoria_nombre FROM productos p 
              JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.activo = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0
              ORDER BY p.id DESC LIMIT 8";
$result_ofertas = $conn->query($sql_ofertas);
$productosOfertas = $result_ofertas->fetch_all(MYSQLI_ASSOC);

// Categorías destacadas
$sql_categorias = "SELECT c.* FROM categorias c
                 JOIN categorias_destacadas cd ON c.id = cd.categoria_id
                 WHERE c.activo = 1
                 ORDER BY cd.orden";
$result_categorias = $conn->query($sql_categorias);
$categoriasDestacadas = $result_categorias->fetch_all(MYSQLI_ASSOC);

// Incluir cabecera
include 'includes/header.php';
?>

<!-- Banner Principal / Slider -->
<section class="hero-slider">
    <div class="container">
        <div class="slider">
            <div class="slide">
                <h2>Ofertas Especiales</h2>
                <p>Descuentos en los mejores productos de tecnología</p>
                <a href="category.php?id=1" class="btn">Ver Ofertas</a>
            </div>
        </div>
    </div>
</section>

<!-- Categorías Principales -->
<section class="categorias-principales">
    <div class="container">
        <h2>Categorías Principales</h2>
        <?php if (!empty($categoriasDestacadas)): ?>
            <div class="categorias-grid">
                <?php foreach ($categoriasDestacadas as $categoria): ?>
                    <div class="categoria-card">
                        <a href="category.php?id=<?php echo $categoria['id']; ?>">
                            <h3><?php echo $categoria['nombre']; ?></h3>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No hay categorías destacadas disponibles.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Productos Destacados -->
<section class="productos-destacados">
    <div class="container">
        <h2>Productos Destacados</h2>
        <?php if (!empty($productosDestacados)): ?>
            <div class="productos-grid">
                <?php foreach ($productosDestacados as $producto): ?>
                    <div class="producto-card">
                        <div class="producto-imagen">
                            <!-- Por ahora sin imagen real -->
                            <img src="assets/img/productos/placeholder.png" alt="<?php echo $producto['nombre']; ?>">
                        </div>
                        <div class="producto-info">
                            <div class="producto-marca"><?php echo $producto['marca']; ?></div>
                            <h3 class="producto-nombre"><?php echo $producto['nombre']; ?></h3>
                            <div class="producto-precio">
                                <?php if (!empty($producto['precio_oferta'])): ?>
                                    <span class="precio-antiguo">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                                    <span class="precio-actual">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></span>
                               <?php else: ?>
                                   <span class="precio-actual">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                               <?php endif; ?>
                           </div>
                           <a href="product.php?id=<?php echo $producto['id']; ?>" class="btn-ver">Ver Producto</a>
                       </div>
                   </div>
               <?php endforeach; ?>
           </div>
       <?php else: ?>
           <p>No hay productos destacados disponibles.</p>
       <?php endif; ?>
   </div>
</section>

<!-- Productos en Oferta -->
<section class="productos-ofertas">
   <div class="container">
       <h2>Ofertas Especiales</h2>
       <?php if (!empty($productosOfertas)): ?>
           <div class="productos-grid">
               <?php foreach ($productosOfertas as $producto): ?>
                   <div class="producto-card oferta">
                       <div class="etiqueta-oferta">OFERTA</div>
                       <div class="producto-imagen">
                           <img src="assets/img/productos/placeholder.png" alt="<?php echo $producto['nombre']; ?>">
                       </div>
                       <div class="producto-info">
                           <div class="producto-marca"><?php echo $producto['marca']; ?></div>
                           <h3 class="producto-nombre"><?php echo $producto['nombre']; ?></h3>
                           <div class="producto-precio">
                               <span class="precio-antiguo">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                               <span class="precio-actual">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></span>
                               <span class="descuento">
                                   <?php echo round(100 - (($producto['precio_oferta'] / $producto['precio']) * 100)); ?>% DSCTO
                               </span>
                           </div>
                           <a href="product.php?id=<?php echo $producto['id']; ?>" class="btn-ver">Ver Producto</a>
                       </div>
                   </div>
               <?php endforeach; ?>
           </div>
       <?php else: ?>
           <p>No hay ofertas disponibles.</p>
       <?php endif; ?>
   </div>
</section>

<!-- Banner Promocional -->
<section class="banner-promocional">
   <div class="container">
       <div class="banner">
           <h2>SUPER OFERTAS EN LAPTOPS</h2>
           <p>Hasta 30% de descuento en marcas seleccionadas</p>
           <a href="category.php?id=3" class="btn">Ver Ofertas</a>
       </div>
   </div>
</section>

<!-- Productos Nuevos -->
<section class="productos-nuevos">
   <div class="container">
       <h2>Productos Nuevos</h2>
       <?php if (!empty($productosNuevos)): ?>
           <div class="productos-grid">
               <?php foreach ($productosNuevos as $producto): ?>
                   <div class="producto-card nuevo">
                       <div class="etiqueta-nuevo">NUEVO</div>
                       <div class="producto-imagen">
                           <img src="assets/img/productos/placeholder.png" alt="<?php echo $producto['nombre']; ?>">
                       </div>
                       <div class="producto-info">
                           <div class="producto-marca"><?php echo $producto['marca']; ?></div>
                           <h3 class="producto-nombre"><?php echo $producto['nombre']; ?></h3>
                           <div class="producto-precio">
                               <?php if (!empty($producto['precio_oferta'])): ?>
                                   <span class="precio-antiguo">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                                   <span class="precio-actual">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></span>
                               <?php else: ?>
                                   <span class="precio-actual">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                               <?php endif; ?>
                           </div>
                           <a href="product.php?id=<?php echo $producto['id']; ?>" class="btn-ver">Ver Producto</a>
                       </div>
                   </div>
               <?php endforeach; ?>
           </div>
       <?php else: ?>
           <p>No hay productos nuevos disponibles.</p>
       <?php endif; ?>
   </div>
</section>

<!-- Ventajas -->
<section class="ventajas">
   <div class="container">
       <div class="ventajas-grid">
           <div class="ventaja">
               <h3>Envío Gratis</h3>
               <p>En compras mayores a S/ 200</p>
           </div>
           <div class="ventaja">
               <h3>Garantía</h3>
               <p>Todos nuestros productos tienen garantía</p>
           </div>
           <div class="ventaja">
               <h3>Soporte Técnico</h3>
               <p>Atención personalizada</p>
           </div>
           <div class="ventaja">
               <h3>Pago Seguro</h3>
               <p>Tus compras están protegidas</p>
           </div>
       </div>
   </div>
</section>

<!-- Marcas -->
<section class="marcas">
   <div class="container">
       <h2>Nuestras Marcas</h2>
       <div class="marcas-grid">
           <div class="marca">HP</div>
           <div class="marca">EPSON</div>
           <div class="marca">NVIDIA</div>
           <div class="marca">INTEL</div>
           <div class="marca">AMD</div>
           <div class="marca">ASUS</div>
       </div>
   </div>
</section>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>