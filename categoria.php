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
require_once 'models/Categoria.php';
require_once 'models/Producto.php';

// Crear instancias
$categoria = new Categoria($db);
$producto = new Producto($db);

// Buscar la categoría por su slug
$existe = $categoria->getBySlug($slug);

// Si no existe la categoría, redirigir a la página principal
if (!$existe) {
    header('Location: index.php');
    exit;
}

// Obtener la categoría padre si es una subcategoría
$categoria_padre = null;
if ($categoria->categoria_padre_id) {
    $categoria_padre = new Categoria($db);
    $categoria_padre->id = $categoria->categoria_padre_id;
    $categoria_padre->getSingle();
}

// Obtener subcategorías si es una categoría principal
$subcategorias = [];
if (!$categoria->categoria_padre_id) {
    $query = "SELECT * FROM categorias WHERE categoria_padre_id = :categoria_id AND activo = true ORDER BY nombre ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':categoria_id', $categoria->id);
    $stmt->execute();
    $subcategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Paginación
$productos_por_pagina = 12;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// Obtener productos de la categoría (incluyendo subcategorías)
$query = "SELECT p.* FROM productos p 
          WHERE (p.categoria_id = :categoria_id 
          OR p.categoria_id IN (SELECT id FROM categorias WHERE categoria_padre_id = :categoria_padre_id))
          AND p.activo = true 
          ORDER BY p.destacado DESC, p.fecha_creacion DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
$stmt->bindParam(':categoria_id', $categoria->id);
$stmt->bindParam(':categoria_padre_id', $categoria->id);
$stmt->bindParam(':limit', $productos_por_pagina, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el total de productos para la paginación
$query = "SELECT COUNT(*) as total FROM productos 
          WHERE (categoria_id = :categoria_id 
          OR categoria_id IN (SELECT id FROM categorias WHERE categoria_padre_id = :categoria_padre_id))
          AND activo = true";

$stmt = $db->prepare($query);
$stmt->bindParam(':categoria_id', $categoria->id);
$stmt->bindParam(':categoria_padre_id', $categoria->id);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_productos = $row['total'];
$total_paginas = ceil($total_productos / $productos_por_pagina);

// Título de la página
$page_title = $categoria->nombre;

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
            <?php if ($categoria_padre): ?>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>categoria/<?php echo $categoria_padre->slug; ?>"><?php echo $categoria_padre->nombre; ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $categoria->nombre; ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Barra lateral con filtros y subcategorías -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Categorías</h5>
                </div>
                <div class="card-body">
                    <?php if ($categoria_padre): ?>
                        <!-- Si es una subcategoría, mostrar enlace a la categoría padre y hermanas -->
                        <h6><?php echo $categoria_padre->nombre; ?></h6>
                        <ul class="list-unstyled mb-4">
                            <?php 
                            $query = "SELECT * FROM categorias WHERE categoria_padre_id = :categoria_padre_id AND activo = true ORDER BY nombre ASC";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':categoria_padre_id', $categoria_padre->id);
                            $stmt->execute();
                            $categorias_hermanas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($categorias_hermanas as $cat_hermana):
                            ?>
                                <li class="mb-2">
                                    <a href="<?php echo BASE_URL; ?>categoria/<?php echo $cat_hermana['slug']; ?>" class="<?php echo ($cat_hermana['id'] == $categoria->id) ? 'fw-bold text-primary' : ''; ?>">
                                        <?php echo $cat_hermana['nombre']; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php elseif (count($subcategorias) > 0): ?>
                        <!-- Si es una categoría principal con subcategorías, mostrarlas -->
                        <ul class="list-unstyled">
                            <?php foreach ($subcategorias as $subcat): ?>
                                <li class="mb-2">
                                    <a href="<?php echo BASE_URL; ?>categoria/<?php echo $subcat['slug']; ?>">
                                        <?php echo $subcat['nombre']; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <!-- Si no tiene subcategorías ni es una subcategoría, mostrar otras categorías principales -->
                        <ul class="list-unstyled">
                            <?php 
                            $query = "SELECT * FROM categorias WHERE categoria_padre_id IS NULL AND activo = true ORDER BY nombre ASC";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $categorias_principales = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($categorias_principales as $cat_principal):
                            ?>
                                <li class="mb-2">
                                    <a href="<?php echo BASE_URL; ?>categoria/<?php echo $cat_principal['slug']; ?>" class="<?php echo ($cat_principal['id'] == $categoria->id) ? 'fw-bold text-primary' : ''; ?>">
                                        <?php echo $cat_principal['nombre']; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Contenido principal - Listado de productos -->
        <div class="col-lg-9">
            <h1 class="mb-4"><?php echo $categoria->nombre; ?></h1>
            
            <?php if (!empty($categoria->descripcion)): ?>
                <div class="mb-4">
                    <?php echo $categoria->descripcion; ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($productos) > 0): ?>
                <!-- Resultados y ordenación -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <span>Mostrando <?php echo count($productos); ?> de <?php echo $total_productos; ?> productos</span>
                    </div>
                    <div>
                        <select class="form-select" id="sort-products">
                            <option value="destacado">Destacados</option>
                            <option value="reciente">Más recientes</option>
                            <option value="precio_asc">Precio: menor a mayor</option>
                            <option value="precio_desc">Precio: mayor a menor</option>
                            <option value="nombre_asc">Nombre: A-Z</option>
                            <option value="nombre_desc">Nombre: Z-A</option>
                        </select>
                    </div>
                </div>
                
                <!-- Listado de productos -->
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
                                   <a class="page-link" href="<?php echo BASE_URL; ?>categoria/<?php echo $categoria->slug; ?>?pagina=<?php echo $pagina_actual - 1; ?>" aria-label="Anterior">
                                       <span aria-hidden="true">&laquo;</span>
                                   </a>
                               </li>
                           <?php endif; ?>
                           
                           <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                               <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                                   <a class="page-link" href="<?php echo BASE_URL; ?>categoria/<?php echo $categoria->slug; ?>?pagina=<?php echo $i; ?>">
                                       <?php echo $i; ?>
                                   </a>
                               </li>
                           <?php endfor; ?>
                           
                           <?php if ($pagina_actual < $total_paginas): ?>
                               <li class="page-item">
                                   <a class="page-link" href="<?php echo BASE_URL; ?>categoria/<?php echo $categoria->slug; ?>?pagina=<?php echo $pagina_actual + 1; ?>" aria-label="Siguiente">
                                       <span aria-hidden="true">&raquo;</span>
                                   </a>
                               </li>
                           <?php endif; ?>
                       </ul>
                   </nav>
               <?php endif; ?>
               
           <?php else: ?>
               <div class="alert alert-info">
                   No hay productos disponibles en esta categoría.
               </div>
           <?php endif; ?>
       </div>
   </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
   // Ordenar productos
   const sortSelect = document.getElementById('sort-products');
   if (sortSelect) {
       sortSelect.addEventListener('change', function() {
           const value = this.value;
           let url = new URL(window.location.href);
           url.searchParams.set('ordenar', value);
           url.searchParams.delete('pagina'); // Reiniciar paginación
           window.location.href = url.toString();
       });
       
       // Establecer el valor seleccionado según la URL
       const urlParams = new URLSearchParams(window.location.search);
       const ordenar = urlParams.get('ordenar');
       if (ordenar) {
           sortSelect.value = ordenar;
       }
   }
   
   // Añadir al carrito
   const addToCartButtons = document.querySelectorAll('.add-to-cart');
   addToCartButtons.forEach(button => {
       button.addEventListener('click', function(e) {
           e.preventDefault();
           
           const productId = this.getAttribute('data-id');
           const productName = this.getAttribute('data-name');
           const productPrice = parseFloat(this.getAttribute('data-price'));
           const productImage = this.getAttribute('data-image');
           
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