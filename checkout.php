<?php
// Incluir archivo de configuración
require_once 'config/config.php';
require_once 'config/database.php';

// Título de la página
$page_title = 'Checkout';

// Incluir encabezado
include 'includes/header.php';

// Incluir navegación
include 'includes/navbar.php';

// Verificar si el usuario está logueado (opcional, por ahora no implementaremos login)
$user_logged_in = false;

// Procesar formulario de checkout
$mensaje = '';
$tipo_mensaje = '';
$pedido_id = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $metodo_pago = trim($_POST['metodo_pago'] ?? 'mercado-pago');
    $notas = trim($_POST['notas'] ?? '');
    
    // Validación básica
    $errors = [];
    
    if (empty($nombre)) $errors[] = 'El nombre es obligatorio';
    if (empty($apellido)) $errors[] = 'El apellido es obligatorio';
    if (empty($email)) $errors[] = 'El email es obligatorio';
    if (empty($telefono)) $errors[] = 'El teléfono es obligatorio';
    if (empty($direccion)) $errors[] = 'La dirección es obligatoria';
    if (empty($ciudad)) $errors[] = 'La ciudad es obligatoria';
    
    // Si no hay errores, procesar el pedido
    if (empty($errors)) {
        try {
            // Generamos un número de pedido único
            $numero_pedido = 'COMPUYA-' . date('YmdHi') . '-' . rand(1000, 9999);
            
            // Dirección completa
            $direccion_completa = $direccion . "\n" . $ciudad;
            
            // Datos para el pedido (esto vendría del carrito en localStorage, lo procesaríamos con JS)
            $total = floatval($_POST['total'] ?? 0);
            
            // Iniciar transacción
            $db->beginTransaction();
            
            // Insertar pedido
            $query = "INSERT INTO pedidos (
                        usuario_id, numero_pedido, estado, total, metodo_pago, 
                        nombre_envio, apellido_envio, email_envio, telefono_envio, 
                        direccion_envio, notas
                      ) VALUES (
                        :usuario_id, :numero_pedido, :estado, :total, :metodo_pago, 
                        :nombre_envio, :apellido_envio, :email_envio, :telefono_envio, 
                        :direccion_envio, :notas
                      )";
            
            $stmt = $db->prepare($query);
            
            $usuario_id = null; // Por ahora no hay usuarios logueados
            $estado = 'pendiente';
            
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':numero_pedido', $numero_pedido);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':total', $total);
            $stmt->bindParam(':metodo_pago', $metodo_pago);
            $stmt->bindParam(':nombre_envio', $nombre);
            $stmt->bindParam(':apellido_envio', $apellido);
            $stmt->bindParam(':email_envio', $email);
            $stmt->bindParam(':telefono_envio', $telefono);
            $stmt->bindParam(':direccion_envio', $direccion_completa);
            $stmt->bindParam(':notas', $notas);
            
            $stmt->execute();
            
            $pedido_id = $db->lastInsertId();
            
            // Los detalles del pedido se procesarán con JavaScript para obtener los productos del carrito
            
            // Confirmar transacción
            $db->commit();
            
            // Mensaje de éxito
            $mensaje = "Pedido creado correctamente. Número de pedido: $numero_pedido";
            $tipo_mensaje = "success";
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $db->rollBack();
            
            $mensaje = "Error al procesar el pedido: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = "Por favor, corrige los siguientes errores: " . implode(', ', $errors);
        $tipo_mensaje = "danger";
    }
}
?>

<div class="container mt-4 mb-5">
    <h1 class="mb-4">Checkout</h1>
    
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>" role="alert">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>
    
    <!-- Mensaje de carrito vacío (se mostrará con JS si no hay productos) -->
    <div id="empty-cart-message" class="text-center py-5" style="display: none;">
        <div class="mb-4">
            <i class="fas fa-shopping-cart fa-5x text-muted"></i>
        </div>
        <h2 class="mb-3">Tu carrito está vacío</h2>
        <p class="mb-4">No puedes realizar un checkout sin productos en tu carrito.</p>
        <a href="<?php echo BASE_URL; ?>" class="btn btn-compuya">
            <i class="fas fa-shopping-basket me-2"></i>Ir a la Tienda
        </a>
    </div>
    
    <!-- Formulario de checkout -->
    <div id="checkout-form-container" class="row">
        <!-- Si el pedido ya fue procesado, mostrar confirmación -->
        <?php if ($pedido_id): ?>
            <div class="col-12 text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-check-circle fa-5x text-success"></i>
                </div>
                <h2 class="mb-3">¡Gracias por tu pedido!</h2>
                <p class="mb-4">Tu pedido ha sido recibido y está siendo procesado.</p>
                <p class="mb-4">Número de pedido: <strong><?php echo $numero_pedido; ?></strong></p>
                <p>Te hemos enviado un email con los detalles de tu compra a <strong><?php echo $email; ?></strong>.</p>
                <div class="mt-4">
                    <a href="<?php echo BASE_URL; ?>" class="btn btn-compuya">
                        <i class="fas fa-home me-2"></i>Volver a la Tienda
                    </a>
                </div>
                
                <!-- Limpiar el carrito con JavaScript -->
                <script>
                    // Vaciar el carrito al completar la compra
                    localStorage.removeItem('cart');
                </script>
            </div>
        <?php else: ?>
            <!-- Formulario de checkout -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Información de Envío</h5>
                    </div>
                    <div class="card-body">
                        <form id="checkout-form" method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellido" class="form-label">Apellido *</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label">Teléfono *</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección *</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ciudad" class="form-label">Ciudad *</label>
                                <input type="text" class="form-control" id="ciudad" name="ciudad" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notas" class="form-label">Notas adicionales</label>
                                <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Método de Pago *</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="metodo_mercadopago" value="mercado-pago" checked>
                                    <label class="form-check-label" for="metodo_mercadopago">
                                        <i class="fab fa-mercado-pago me-2"></i>Mercado Pago
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="metodo_transferencia" value="transferencia">
                                    <label class="form-check-label" for="metodo_transferencia">
                                        <i class="fas fa-university me-2"></i>Transferencia Bancaria
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Campo oculto para el total (se actualizará con JavaScript) -->
                            <input type="hidden" id="total-input" name="total" value="0">
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-compuya" id="place-order-btn">
                                    <i class="fas fa-lock me-2"></i>Realizar Pedido
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Resumen del pedido -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Resumen del Pedido</h5>
                    </div>
                    <div class="card-body">
                        <div id="checkout-items">
                            <!-- Los items se cargarán dinámicamente con JavaScript -->
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="checkout-subtotal">S/ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Envío:</span>
                            <span id="checkout-shipping">S/ 0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong id="checkout-total">S/ 0.00</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Métodos de pago aceptados -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Métodos de Pago Aceptados</h5>
                    </div>
                    <div class="card-body text-center">
                        <i class="fab fa-cc-visa fa-2x me-2"></i>
                        <i class="fab fa-cc-mastercard fa-2x me-2"></i>
                        <i class="fab fa-cc-amex fa-2x me-2"></i>
                        <i class="fab fa-mercado-pago fa-2x"></i>
                        <p class="mt-2 mb-0 small text-muted">Pago seguro procesado por Mercado Pago</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si hay productos en el carrito
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Si el carrito está vacío y no estamos en la página de confirmación, mostrar mensaje
    if (cart.length === 0 && !document.querySelector('.alert-success')) {
        document.getElementById('checkout-form-container').style.display = 'none';
        document.getElementById('empty-cart-message').style.display = 'block';
    } else {
        // Cargar los productos en el resumen
        const checkoutItems = document.getElementById('checkout-items');
        if (checkoutItems) {
            // Variable para calcular el total
            let subtotal = 0;
            
            // Limpiar contenido anterior
            checkoutItems.innerHTML = '';
            
            // Añadir cada producto al resumen
            cart.forEach(item => {
                // Calcular subtotal del item
                const itemSubtotal = item.price * item.quantity;
                subtotal += itemSubtotal;
                
                // Crear elemento para el producto
                const itemElement = document.createElement('div');
                itemElement.className = 'checkout-item d-flex justify-content-between align-items-center mb-2';
                
                itemElement.innerHTML = `
                    <div>
                        <span class="fw-bold">${item.name}</span>
                        <div class="small text-muted">Cantidad: ${item.quantity}</div>
                    </div>
                    <span>S/ ${itemSubtotal.toFixed(2)}</span>
                `;
                
                // Añadir al resumen
                checkoutItems.appendChild(itemElement);
            });
            
            // Calcular totales
            const shipping = subtotal > 0 ? 10 : 0; // Costo de envío fijo de S/ 10
            const total = subtotal + shipping;
            
            // Actualizar los totales en el DOM
            const checkoutSubtotal = document.getElementById('checkout-subtotal');
            const checkoutShipping = document.getElementById('checkout-shipping');
            const checkoutTotal = document.getElementById('checkout-total');
            const totalInput = document.getElementById('total-input');
            
            if (checkoutSubtotal && checkoutShipping && checkoutTotal) {
                checkoutSubtotal.textContent = `S/ ${subtotal.toFixed(2)}`;
                checkoutShipping.textContent = `S/ ${shipping.toFixed(2)}`;
                checkoutTotal.textContent = `S/ ${total.toFixed(2)}`;
                
                // Actualizar campo oculto para enviar con el formulario
                if (totalInput) {
                    totalInput.value = total.toFixed(2);
                }
            }
        }
    }
    
    // Formulario de checkout
    const checkoutForm = document.getElementById('checkout-form');
    
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(event) {
            // Verificar de nuevo si hay productos (por si se han eliminado mientras se llenaba el formulario)
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            if (cart.length === 0) {
                event.preventDefault();
                alert('Tu carrito está vacío. No puedes realizar un pedido sin productos.');
                return;
            }
        });
    }
});

// Enviar detalles del pedido después de crear el pedido
const successAlert = document.querySelector('.alert-success');
if (successAlert && cart.length > 0) {
    // Extraer el ID del pedido del mensaje de éxito (este método puede variar)
    const pedidoIdMatch = successAlert.textContent.match(/Número de pedido: (\w+-\d+-\d+)/);
    
    if (pedidoIdMatch && pedidoIdMatch[1]) {
        const numeroPedido = pedidoIdMatch[1];
        
        // Obtener el ID del pedido recién creado
        fetch('<?php echo BASE_URL; ?>get_pedido_id.php?numero=' + encodeURIComponent(numeroPedido))
            .then(response => response.json())
            .then(data => {
                if (data.pedido_id) {
                    // Enviar los items del carrito
                    const formData = new FormData();
                    formData.append('pedido_id', data.pedido_id);
                    formData.append('items', JSON.stringify(cart));
                    
                    return fetch('<?php echo BASE_URL; ?>procesar_pedido.php', {
                        method: 'POST',
                        body: formData
                    });
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Detalles del pedido procesados:', data);
                // Vaciar el carrito
                localStorage.removeItem('cart');
                // Actualizar contador del carrito
                const cartCountElement = document.getElementById('cart-count');
                if (cartCountElement) {
                    cartCountElement.textContent = '0';
                }
            })
            .catch(error => {
                console.error('Error al procesar detalles del pedido:', error);
            });
    }
}


</script>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>