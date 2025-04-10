<?php
if (!defined('IN_COMPUYA')) {
    exit;
}
?>
    </main>
    
    <footer>
        <div class="footer-top">
            <div class="container">
                <div class="footer-columns">
                    <div class="footer-column">
                        <h3>Acerca de CompuYa</h3>
                        <ul>
                            <li><a href="about.php">Quiénes Somos</a></li>
                            <li><a href="terms.php">Términos y Condiciones</a></li>
                            <li><a href="privacy.php">Política de Privacidad</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h3>Servicio al Cliente</h3>
                        <ul>
                            <li><a href="faq.php">Preguntas Frecuentes</a></li>
                            <li><a href="returns.php">Devoluciones</a></li>
                            <li><a href="shipping.php">Envíos</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h3>Mi Cuenta</h3>
                        <ul>
                            <li><a href="login.php">Iniciar Sesión</a></li>
                            <li><a href="register.php">Registrarse</a></li>
                            <li><a href="orders.php">Mis Pedidos</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h3>Contacto</h3>
                        <p>Av. Principal 123, Lima</p>
                        <p>Teléfono: (01) 123-4567</p>
                        <p>Email: ventas@compuya.com</p>
                    </div>
                </div>
                
                <div class="payment-methods">
                    <h3>Métodos de Pago</h3>
                    <div class="payment-icons">
                        <span>Visa</span>
                        <span>MasterCard</span>
                        <span>PayPal</span>
                        <span>Transferencia</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>