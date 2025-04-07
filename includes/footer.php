<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3">
                <h5 class="footer__title">CompuYA</h5>
                <p>Tu tienda de confianza para productos tecnológicos</p>
                <p>
                    <i class="fas fa-map-marker-alt me-2"></i> Lima, Perú<br>
                    <i class="fas fa-phone me-2"></i> +51 XXX XXX XXX<br>
                    <i class="fas fa-envelope me-2"></i> contacto@compuya.pe
                </p>
            </div>
            <div class="col-md-4 mb-3">
                <h5 class="footer__title">Enlaces rápidos</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE_URL; ?>" class="footer__link">Inicio</a></li>
                    <li><a href="<?php echo BASE_URL; ?>productos" class="footer__link">Productos</a></li>
                    <li><a href="<?php echo BASE_URL; ?>nosotros" class="footer__link">Nosotros</a></li>
                    <li><a href="<?php echo BASE_URL; ?>contacto" class="footer__link">Contacto</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-3">
                <h5 class="footer__title">Síguenos</h5>
                <div class="footer__social">
                    <a href="#" class="footer__link"><i class="fab fa-facebook-f fa-lg"></i></a>
                    <a href="#" class="footer__link"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="footer__link"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="footer__link"><i class="fab fa-youtube fa-lg"></i></a>
                </div>
                <h5 class="footer__title mt-3">Métodos de pago</h5>
                <div class="footer__payment">
                    <i class="fab fa-cc-visa fa-2x me-2"></i>
                    <i class="fab fa-cc-mastercard fa-2x me-2"></i>
                    <i class="fab fa-cc-paypal fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="footer__copyright text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> CompuYA. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>

</div><!-- Fin del contenedor principal -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- JavaScript personalizado -->
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>

</body>
</html>