<?php
// Incluir archivo de inicialización
require_once 'includes/init.php';

// Título de la página
$page_title = 'Inicio';

// Incluir encabezado
include 'includes/header.php';

// Incluir barra de navegación
include 'includes/navbar.php';
?>

<!-- Carrusel principal -->
<div id="mainCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="assets/img/slide1.jpg" class="d-block w-100" alt="Ofertas especiales">
            <div class="carousel-caption d-none d-md-block">
                <h2>Ofertas Especiales</h2>
                <p>Descubre nuestras mejores ofertas en tecnología</p>
                <a href="#" class="btn btn-compuya">Ver ofertas</a>
            </div>
        </div>
        <div class="carousel-item">
            <img src="assets/img/slide2.jpg" class="d-block w-100" alt="Nuevos productos">
            <div class="carousel-caption d-none d-md-block">
                <h2>Nuevos Productos</h2>
                <p>Descubre las últimas novedades en nuestra tienda</p>
                <a href="#" class="btn btn-compuya">Ver novedades</a>
            </div>
        </div>
        <div class="carousel-item">
            <img src="assets/img/slide3.jpg" class="d-block w-100" alt="Promociones">
            <div class="carousel-caption d-none d-md-block">
                <h2>Armamos tu PC</h2>
                <p>Configura tu equipo a medida con los mejores componentes</p>
                <a href="#" class="btn btn-compuya">Cotizar ahora</a>
            </div>
        </div>
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

<!-- Contenido principal -->
<div class="container fade-with-slide">
    
    <!-- Categorías destacadas -->
    <section class="mb-5">
        <h2 class="text-center mb-4">Categorías Destacadas</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="category-card">
                    <img src="assets/img/cat-componentes.jpg" class="category-card__image" alt="Componentes">
                    <div class="category-card__overlay">
                        <h3 class="category-card__title">Componentes</h3>
                    </div>
                    <a href="categoria/componentes" class="stretched-link"></a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="category-card">
                    <img src="assets/img/cat-laptops.jpg" class="category-card__image" alt="Laptops">
                    <div class="category-card__overlay">
                        <h3 class="category-card__title">Laptops</h3>
                    </div>
                    <a href="categoria/laptops" class="stretched-link"></a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="category-card">
                    <img src="assets/img/cat-perifericos.jpg" class="category-card__image" alt="Periféricos">
                    <div class="category-card__overlay">
                        <h3 class="category-card__title">Periféricos</h3>
                    </div>
                    <a href="categoria/perifericos" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Productos destacados -->
    <section class="mb-5">
        <h2 class="text-center mb-4">Productos Destacados</h2>
        <div class="row">
            <!-- Producto 1 -->
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card product-card">
                    <span class="badge bg-danger product-card__badge">Nuevo</span>
                    <img src="assets/img/product1.jpg" class="card-img-top product-card__image" alt="NVIDIA GeForce RTX 4060">
                    <div class="card-body product-card__body">
                        <h5 class="card-title product-card__title">NVIDIA GeForce RTX 4060 OC 8GB</h5>
                        <div class="product-card__rating mb-2">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star-half-alt text-warning"></i>
                            <small class="text-muted">(24)</small>
                        </div>
                        <p class="card-text product-card__price">S/ 1,799.00</p>
                        <a href="#" class="btn btn-compuya product-card__button add-to-cart" 
                           data-id="1" 
                           data-name="NVIDIA GeForce RTX 4060 OC 8GB" 
                           data-price="1799" 
                           data-image="assets/img/product1.jpg">
                            <i class="fas fa-shopping-cart me-2"></i>Añadir al carrito
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Producto 2 -->
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card product-card">
                    <img src="assets/img/product2.jpg" class="card-img-top product-card__image" alt="AMD Ryzen 7 5700G">
                    <div class="card-body product-card__body">
                        <h5 class="card-title product-card__title">AMD Ryzen 7 5700G APU</h5>
                        <div class="product-card__rating mb-2">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <small class="text-muted">(18)</small>
                        </div>
                        <p class="card-text product-card__price">
                            <span class="product-card__discount-price">S/ 999.00</span>
                            <span class="product-card__original-price">S/ 1,199.00</span>
                        </p>
                        <a href="#" class="btn btn-compuya product-card__button add-to-cart" 
                           data-id="2" 
                           data-name="AMD Ryzen 7 5700G APU" 
                           data-price="999" 
                           data-image="assets/img/product2.jpg">
                            <i class="fas fa-shopping-cart me-2"></i>Añadir al carrito
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Producto 3 -->
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card product-card">
                    <img src="assets/img/product3.jpg" class="card-img-top product-card__image" alt="HP Smart Tank 530">
                    <div class="card-body product-card__body">
                        <h5 class="card-title product-card__title">HP Smart Tank 530 Multifuncional</h5>
                        <div class="product-card__rating mb-2">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star-half-alt text-warning"></i>
                            <i class="far fa-star text-warning"></i>
                            <i class="far fa-star text-warning"></i>
                            <small class="text-muted">(12)</small>
                        </div>
                        <p class="card-text product-card__price">S/ 899.00</p>
                        <a href="#" class="btn btn-compuya product-card__button add-to-cart" 
                           data-id="3" 
                           data-name="HP Smart Tank 530 Multifuncional" 
                           data-price="899" 
                           data-image="assets/img/product3.jpg">
                            <i class="fas fa-shopping-cart me-2"></i>Añadir al carrito
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Producto 4 -->
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card product-card">
                    <span class="badge bg-danger product-card__badge">Nuevo</span>
                    <img src="assets/img/product4.jpg" class="card-img-top product-card__image" alt="ASUS PRIME B550">
                    <div class="card-body product-card__body">
                        <h5 class="card-title product-card__title">ASUS PRIME B550 GAMING X AM4</h5>
                        <div class="product-card__rating mb-2">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="far fa-star text-warning"></i>
                            <small class="text-muted">(8)</small>
                        </div>
                        <p class="card-text product-card__price">S/ 699.00</p>
                        <a href="#" class="btn btn-compuya product-card__button add-to-cart" 
                           data-id="4" 
                           data-name="ASUS PRIME B550 GAMING X AM4" 
                           data-price="699" 
                           data-image="assets/img/product4.jpg">
                            <i class="fas fa-shopping-cart me-2"></i>Añadir al carrito
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <a href="productos" class="btn btn-outline-primary">Ver todos los productos</a>
        </div>
    </section>
    
    <!-- Banner promocional -->
    <section class="mb-5">
        <div class="card bg-dark text-white">
            <img src="assets/img/banner.jpg" class="card-img" alt="Promoción especial">
            <div class="card-img-overlay d-flex flex-column justify-content-center text-center">
               <h3 class="card-title">OFERTA ESPECIAL</h3>
               <p class="card-text">Hasta 30% de descuento en componentes seleccionados</p>
               <a href="#" class="btn btn-compuya-secondary mx-auto">Ver ofertas</a>
           </div>
       </div>
   </section>
   
   <!-- Servicios -->
   <section class="mb-5">
       <h2 class="text-center mb-4">Nuestros Servicios</h2>
       <div class="row">
           <div class="col-md-4 mb-4">
               <div class="service-card">
                   <i class="fas fa-truck service-card__icon"></i>
                   <h3 class="service-card__title">Envío Rápido</h3>
                   <p>Entregas en Lima en 24 horas y a provincia en 48 horas hábiles.</p>
               </div>
           </div>
           <div class="col-md-4 mb-4">
               <div class="service-card">
                   <i class="fas fa-headset service-card__icon"></i>
                   <h3 class="service-card__title">Soporte Técnico</h3>
                   <p>Asesoría especializada para resolver todas tus dudas.</p>
               </div>
           </div>
           <div class="col-md-4 mb-4">
               <div class="service-card">
                   <i class="fas fa-shield-alt service-card__icon"></i>
                   <h3 class="service-card__title">Garantía Segura</h3>
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
               <img src="assets/img/marca-intel.png" alt="Intel" class="img-fluid" style="max-height: 60px;">
           </div>
           <div class="col-4 col-md-2 mb-3">
               <img src="assets/img/marca-amd.png" alt="AMD" class="img-fluid" style="max-height: 60px;">
           </div>
           <div class="col-4 col-md-2 mb-3">
               <img src="assets/img/marca-nvidia.png" alt="NVIDIA" class="img-fluid" style="max-height: 60px;">
           </div>
           <div class="col-4 col-md-2 mb-3">
               <img src="assets/img/marca-asus.png" alt="ASUS" class="img-fluid" style="max-height: 60px;">
           </div>
           <div class="col-4 col-md-2 mb-3">
               <img src="assets/img/marca-hp.png" alt="HP" class="img-fluid" style="max-height: 60px;">
           </div>
           <div class="col-4 col-md-2 mb-3">
               <img src="assets/img/marca-msi.png" alt="MSI" class="img-fluid" style="max-height: 60px;">
           </div>
       </div>
   </section>
</div>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>