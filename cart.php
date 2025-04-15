<?php
define('IN_COMPUYA', true);
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$db = Database::getInstance();
$conn = $db->getConnection();

$page_title = 'Carrito de Compras';
$mensajes = [];
$errores = [];

// Procesar acciones del carrito
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Actualizar cantidad
    if ($action == 'update' && isset($_POST['producto_id']) && isset($_POST['cantidad'])) {
        $producto_id = (int)$_POST['producto_id'];
        $cantidad = (int)$_POST['cantidad'];
        
        if ($cantidad <= 0) {
            // Si la cantidad es 0 o negativa, eliminar del carrito
            if (isset($_SESSION['carrito'][$producto_id])) {
                unset($_SESSION['carrito'][$producto_id]);
                $mensajes[] = "Producto eliminado del carrito.";
            }
        } else {
            // Verificar stock disponible
            $result = $conn->query("SELECT stock FROM productos WHERE id = $producto_id AND activo = 1");
            if ($result && $row = $result->fetch_assoc()) {
                $stock_disponible = $row['stock'];
                
                if ($cantidad > $stock_disponible) {
                    $errores[] = "No hay suficiente stock. Solo hay $stock_disponible unidades disponibles.";
                    $cantidad = $stock_disponible; // Ajustar a stock disponible
                }
                
                // Actualizar cantidad
                $_SESSION['carrito'][$producto_id] = $cantidad;
                $mensajes[] = "Cantidad actualizada.";
            } else {
                $errores[] = "Producto no encontrado o no disponible.";
            }
        }
    }
    
    // Eliminar producto
    if ($action == 'remove' && isset($_POST['producto_id'])) {
        $producto_id = (int)$_POST['producto_id'];
        
        if (isset($_SESSION['carrito'][$producto_id])) {
            unset($_SESSION['carrito'][$producto_id]);
            $mensajes[] = "Producto eliminado del carrito.";
        }
    }
    
    // Vaciar carrito
    if ($action == 'clear') {
        $_SESSION['carrito'] = [];
        $mensajes[] = "Carrito vaciado correctamente.";
    }
    
    // Si es una solicitud AJAX, devolver respuesta JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $response = [
            'success' => empty($errores),
            'mensajes' => $mensajes,
            'errores' => $errores
        ];
        
        // Obtener totales actualizados
        $subtotal = 0;
        $total = 0;
        $productos_carrito = [];
        
        if (!empty($_SESSION['carrito'])) {
            $ids = array_keys($_SESSION['carrito']);
            $ids_str = implode(',', $ids);
            
            $result = $conn->query("SELECT id, nombre, precio, precio_oferta FROM productos WHERE id IN ($ids_str)");
            
            while ($row = $result->fetch_assoc()) {
                $precio = !empty($row['precio_oferta']) ? $row['precio_oferta'] : $row['precio'];
                $cantidad = $_SESSION['carrito'][$row['id']];
                $subtotal += $precio * $cantidad;
            }
            
            $total = $subtotal;
        }
        
        $response['subtotal'] = number_format($subtotal, 2);
        $response['total'] = number_format($total, 2);
        
        echo json_encode($response);
        exit;
    }
}

// Obtener productos del carrito
$productos_carrito = [];
$subtotal = 0;

if (!empty($_SESSION['carrito'])) {
    $ids = array_keys($_SESSION['carrito']);
    $ids_str = implode(',', $ids);
    
    $result = $conn->query("
        SELECT p.*, c.nombre as categoria_nombre 
        FROM productos p
        JOIN categorias c ON p.categoria_id = c.id
        WHERE p.id IN ($ids_str)
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $precio = !empty($row['precio_oferta']) ? $row['precio_oferta'] : $row['precio'];
            $cantidad = $_SESSION['carrito'][$row['id']];
            
            $producto = $row;
            $producto['cantidad'] = $cantidad;
            $producto['precio_final'] = $precio;
            $producto['subtotal'] = $precio * $cantidad;
            
            $productos_carrito[] = $producto;
            $subtotal += $producto['subtotal'];
        }
    }
}

// Total e impuestos
$impuestos = 0; // Si necesitas calcular impuestos
$total = $subtotal + $impuestos;

// Incluir cabecera
include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/cart.css">

<section class="cart-header">
    <div class="container">
        <h1>Carrito de Compras</h1>
        <div class="breadcrumbs">
            <a href="index.php">Inicio</a> &raquo; Carrito
        </div>
    </div>
</section>

<section class="cart-content">
    <div class="container">
        <?php if (!empty($mensajes)): ?>
            <div class="mensajes-container">
                <?php foreach ($mensajes as $mensaje): ?>
                    <div class="mensaje exito"><?php echo $mensaje; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errores)): ?>
            <div class="mensajes-container">
                <?php foreach ($errores as $error): ?>
                    <div class="mensaje error"><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($productos_carrito)): ?>
            <div class="cart-empty">
                <div class="cart-empty-icon">🛒</div>
                <h2>Tu carrito está vacío</h2>
                <p>Parece que aún no has añadido productos a tu carrito.</p>
                <a href="index.php" class="btn-continue">Continuar comprando</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-products">
                    <div class="cart-table">
                        <div class="cart-header">
                            <div class="cart-cell product-info">Producto</div>
                            <div class="cart-cell product-price">Precio</div>
                            <div class="cart-cell product-quantity">Cantidad</div>
                            <div class="cart-cell product-subtotal">Subtotal</div>
                            <div class="cart-cell product-actions">Acciones</div>
                        </div>
                        
                        <?php foreach ($productos_carrito as $producto): ?>
                            <div class="cart-row" data-id="<?php echo $producto['id']; ?>">
                                <div class="cart-cell product-info">
                                    <div class="product-image">
                                    <img src="<?php echo BASE_URL . '/' . obtenerImagenProducto($producto['id']); ?>" alt="<?php echo $producto['nombre']; ?>">
                                    </div>
                                    <div class="product-details">
                                        <div class="product-brand"><?php echo $producto['marca']; ?></div>
                                        <h3 class="product-name"><?php echo $producto['nombre']; ?></h3>
                                        <div class="product-category"><?php echo $producto['categoria_nombre']; ?></div>
                                        <?php if ($producto['stock'] < 5): ?>
                                            <div class="product-stock low-stock">Solo quedan <?php echo $producto['stock']; ?> unidades</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="cart-cell product-price">
                                    <?php if (!empty($producto['precio_oferta'])): ?>
                                        <div class="price-original">S/ <?php echo number_format($producto['precio'], 2); ?></div>
                                        <div class="price-final">S/ <?php echo number_format($producto['precio_final'], 2); ?></div>
                                    <?php else: ?>
                                        <div class="price-final">S/ <?php echo number_format($producto['precio_final'], 2); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="cart-cell product-quantity">
                                    <div class="quantity-control">
                                        <button type="button" class="qty-btn qty-decrease" data-id="<?php echo $producto['id']; ?>">-</button>
                                        <input type="number" name="quantity" class="qty-input" value="<?php echo $producto['cantidad']; ?>" min="1" max="<?php echo $producto['stock']; ?>" data-id="<?php echo $producto['id']; ?>">
                                        <button type="button" class="qty-btn qty-increase" data-id="<?php echo $producto['id']; ?>">+</button>
                                    </div>
                                </div>
                                
                                <div class="cart-cell product-subtotal">
                                    <div class="subtotal-amount">S/ <?php echo number_format($producto['subtotal'], 2); ?></div>
                                </div>
                                
                                <div class="cart-cell product-actions">
                                    <button type="button" class="btn-remove" data-id="<?php echo $producto['id']; ?>">Eliminar</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-actions">
                        <button type="button" class="btn-clear-cart">Vaciar carrito</button>
                        <a href="index.php" class="btn-continue">Continuar comprando</a>
                    </div>
                </div>
                
                <div class="cart-summary">
                    <h2>Resumen del pedido</h2>
                    
                    <div class="summary-row">
                        <div class="summary-label">Subtotal</div>
                        <div class="summary-value">S/ <span id="cart-subtotal"><?php echo number_format($subtotal, 2); ?></span></div>
                    </div>
                    
                    <?php if ($impuestos > 0): ?>
                    <div class="summary-row">
                        <div class="summary-label">Impuestos</div>
                        <div class="summary-value">S/ <span id="cart-tax"><?php echo number_format($impuestos, 2); ?></span></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total-row">
                        <div class="summary-label">Total</div>
                        <div class="summary-value">S/ <span id="cart-total"><?php echo number_format($total, 2); ?></span></div>
                    </div>
                    
                    <a href="checkout.php" class="btn-checkout">Proceder al pago</a>
                    
                    <div class="payment-methods">
                        <div class="payment-title">Métodos de pago aceptados</div>
                        <div class="payment-icons">
                            <div class="payment-icon">Mercado Pago</div>
                            <div class="payment-icon">Visa</div>
                            <div class="payment-icon">MasterCard</div>
                        </div>
                    </div>
                    
                    <div class="cart-security">
                        <div class="security-icon">🔒</div>
                        <div class="security-text">Pago 100% seguro</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para actualizar cantidad
    function updateQuantity(productId, quantity) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('producto_id', productId);
        formData.append('cantidad', quantity);
        
        fetch('cart.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar subtotales y totales
                document.getElementById('cart-subtotal').textContent = data.subtotal;
                document.getElementById('cart-total').textContent = data.total;
                
                // Actualizar subtotal del producto
                const price = parseFloat(document.querySelector(`.cart-row[data-id="${productId}"] .price-final`).textContent.replace('S/ ', ''));
                const newSubtotal = price * quantity;
                document.querySelector(`.cart-row[data-id="${productId}"] .subtotal-amount`).textContent = `S/ ${newSubtotal.toFixed(2)}`;
                
                // Si la cantidad es 0, eliminar la fila
                if (quantity <= 0) {
                    const row = document.querySelector(`.cart-row[data-id="${productId}"]`);
                    if (row) {
                        row.remove();
                        
                        // Si no quedan productos, mostrar carrito vacío
                        if (document.querySelectorAll('.cart-row').length === 0) {
                            location.reload(); // Recargar para mostrar mensaje de carrito vacío
                        }
                    }
                }
                
                // Mostrar mensaje de éxito
                showMessage(data.mensajes[0], 'exito');
            } else {
                // Mostrar error
                showMessage(data.errores[0], 'error');
                
                // Restaurar cantidad anterior
                const input = document.querySelector(`.qty-input[data-id="${productId}"]`);
                if (input) {
                    // Obtener el valor previo del atributo data-prev si existe
                    const prevValue = input.getAttribute('data-prev');
                    if (prevValue) {
                        input.value = prevValue;
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Función para eliminar producto
    function removeProduct(productId) {
        if (!confirm('¿Estás seguro de que deseas eliminar este producto del carrito?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('producto_id', productId);
        
        fetch('cart.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminar fila
                const row = document.querySelector(`.cart-row[data-id="${productId}"]`);
                if (row) {
                    row.remove();
                }
                
                // Actualizar totales
                document.getElementById('cart-subtotal').textContent = data.subtotal;
                document.getElementById('cart-total').textContent = data.total;
                
                // Si no quedan productos, mostrar carrito vacío
                if (document.querySelectorAll('.cart-row').length === 0) {
                    location.reload(); // Recargar para mostrar mensaje de carrito vacío
                }
                
                // Mostrar mensaje de éxito
                showMessage(data.mensajes[0], 'exito');
            } else {
                // Mostrar error
                showMessage(data.errores[0], 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Función para vaciar carrito
    function clearCart() {
        if (!confirm('¿Estás seguro de que deseas vaciar tu carrito?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'clear');
        
        fetch('cart.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar página para mostrar carrito vacío
                location.reload();
            } else {
                // Mostrar error
                showMessage(data.errores[0], 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Función para mostrar mensajes
    function showMessage(message, type) {
        // Verificar si ya existe el contenedor de mensajes
        let container = document.querySelector('.mensajes-container');
        
        if (!container) {
            container = document.createElement('div');
            container.className = 'mensajes-container';
            const cartContent = document.querySelector('.cart-content .container');
            cartContent.insertBefore(container, cartContent.firstChild);
        }
        
        // Crear el mensaje
        const messageDiv = document.createElement('div');
        messageDiv.className = `mensaje ${type}`;
        messageDiv.textContent = message;
        
        // Añadir a la página
        container.appendChild(messageDiv);
        
        // Eliminar después de 3 segundos
        setTimeout(() => {
            messageDiv.remove();
            // Si no quedan mensajes, eliminar el contenedor
            if (container.children.length === 0) {
                container.remove();
            }
        }, 3000);
    }
    
    // Event listeners para botones de cantidad
    document.querySelectorAll('.qty-decrease').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const input = document.querySelector(`.qty-input[data-id="${productId}"]`);
            
            // Guardar valor previo
            input.setAttribute('data-prev', input.value);
            
            let quantity = parseInt(input.value) - 1;
            if (quantity < 1) quantity = 1;
            
            input.value = quantity;
            updateQuantity(productId, quantity);
        });
    });
    
    document.querySelectorAll('.qty-increase').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const input = document.querySelector(`.qty-input[data-id="${productId}"]`);
            
            // Guardar valor previo
            input.setAttribute('data-prev', input.value);
            
            let quantity = parseInt(input.value) + 1;
            const max = parseInt(input.getAttribute('max'));
            
            if (quantity > max) {
                quantity = max;
                showMessage(`Solo hay ${max} unidades disponibles.`, 'error');
            }
            
            input.value = quantity;
            updateQuantity(productId, quantity);
        });
    });
    
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-id');
            let quantity = parseInt(this.value);
            const max = parseInt(this.getAttribute('max'));
            
            // Guardar valor previo
            const prevValue = this.getAttribute('data-prev') || this.defaultValue;
            
            // Validar cantidad
            if (isNaN(quantity) || quantity < 1) {
                quantity = 1;
                this.value = 1;
            } else if (quantity > max) {
                quantity = max;
                this.value = max;
                showMessage(`Solo hay ${max} unidades disponibles.`, 'error');
            }
            
            // Actualizar solo si cambió
            if (quantity != prevValue) {
                updateQuantity(productId, quantity);
            }
            
            // Actualizar valor previo
            this.setAttribute('data-prev', quantity);
        });
        
        // Guardar valor inicial
        input.setAttribute('data-prev', input.value);
    });
    
    // Event listener para botones de eliminar
    document.querySelectorAll('.btn-remove').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            removeProduct(productId);
        });
    });
    
    // Event listener para vaciar carrito
    document.querySelector('.btn-clear-cart')?.addEventListener('click', clearCart);
});
</script>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>