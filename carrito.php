<?php
// Incluir archivo de configuración
require_once 'config/config.php';
require_once 'config/database.php';

// Título de la página
$page_title = 'Carrito de Compras';

// Incluir encabezado
include 'includes/header.php';

// Incluir navegación
include 'includes/navbar.php';
?>

<div class="container mt-4 mb-5">
    <h1 class="mb-4">Carrito de Compras</h1>
    
    <div class="row">
        <div class="col-lg-8" id="cart-items-container">
            <!-- Los items del carrito se cargarán dinámicamente con JavaScript -->
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0" id="cart-table">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col" width="100">Producto</th>
                                <th scope="col">Descripción</th>
                                <th scope="col" class="text-center" width="120">Cantidad</th>
                                <th scope="col" class="text-end" width="120">Precio</th>
                                <th scope="col" class="text-end" width="120">Subtotal</th>
                                <th scope="col" width="50"></th>
                            </tr>
                        </thead>
                        <tbody id="cart-items">
                            <!-- Aquí se cargarán los productos -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <div class="d-flex justify-content-between mt-3">
                <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Continuar Comprando
                </a>
                <button id="clear-cart" class="btn btn-outline-danger">
                    <i class="fas fa-trash me-2"></i>Vaciar Carrito
                </button>
            </div>
        </div>
        
        <div class="col-lg-4 mt-4 mt-lg-0">
            <!-- Resumen del carrito -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Resumen del Pedido</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span id="cart-subtotal">S/ 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Envío:</span>
                        <span id="cart-shipping">S/ 0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <strong>Total:</strong>
                        <strong id="cart-total">S/ 0.00</strong>
                    </div>
                    <div class="d-grid gap-2">
                        <button id="checkout-button" class="btn btn-compuya btn-lg">
                            Proceder al Pago
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Métodos de pago aceptados -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Métodos de Pago Aceptados</h5>
                </div>
                <div class="card-body text-center">
                    <i class="fab fa-cc-visa fa-2x me-2"></i>
                    <i class="fab fa-cc-mastercard fa-2x me-2"></i>
                    <i class="fab fa-cc-amex fa-2x me-2"></i>
                    <i class="fab fa-mercado-pago fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mensaje de carrito vacío (inicialmente oculto) -->
    <div id="empty-cart-message" class="text-center py-5" style="display: none;">
        <div class="mb-4">
            <i class="fas fa-shopping-cart fa-5x text-muted"></i>
        </div>
        <h2 class="mb-3">Tu carrito está vacío</h2>
        <p class="mb-4">Parece que aún no has añadido productos a tu carrito.</p>
        <a href="<?php echo BASE_URL; ?>" class="btn btn-compuya">
            <i class="fas fa-shopping-basket me-2"></i>Ir a la Tienda
        </a>
    </div>
</div>

<!-- Plantilla para items del carrito (no se muestra, solo para clonar con JS) -->
<template id="cart-item-template">
    <tr class="cart-item">
        <td>
            <img src="" alt="" width="80" height="80" class="img-thumbnail cart-item-image">
        </td>
        <td>
            <h6 class="cart-item-name mb-1"></h6>
            <small class="text-muted cart-item-code"></small>
        </td>
        <td class="text-center">
            <div class="input-group input-group-sm quantity-controls">
                <button class="btn btn-outline-secondary decrease-quantity" type="button">-</button>
                <input type="number" class="form-control text-center quantity-input" value="1" min="1" readonly>
                <button class="btn btn-outline-secondary increase-quantity" type="button">+</button>
            </div>
        </td>
        <td class="text-end cart-item-price"></td>
        <td class="text-end cart-item-subtotal"></td>
        <td class="text-center">
            <button class="btn btn-sm btn-outline-danger remove-item">
                <i class="fas fa-times"></i>
            </button>
        </td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const cartItemsContainer = document.getElementById('cart-items-container');
    const emptyCartMessage = document.getElementById('empty-cart-message');
    const cartItemsTable = document.getElementById('cart-table');
    const cartItems = document.getElementById('cart-items');
    const cartItemTemplate = document.getElementById('cart-item-template');
    const cartSubtotal = document.getElementById('cart-subtotal');
    const cartShipping = document.getElementById('cart-shipping');
    const cartTotal = document.getElementById('cart-total');
    const clearCartButton = document.getElementById('clear-cart');
    const checkoutButton = document.getElementById('checkout-button');
    
    // Cargar y mostrar los items del carrito
    function loadCartItems() {
        // Obtener carrito desde localStorage
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        // Mostrar mensaje de carrito vacío si no hay productos
        if (cart.length === 0) {
            cartItemsContainer.style.display = 'none';
            emptyCartMessage.style.display = 'block';
            return;
        }
        
        // Mostrar la tabla de productos si hay productos
        cartItemsContainer.style.display = 'block';
        emptyCartMessage.style.display = 'none';
        
        // Limpiar contenido anterior
        cartItems.innerHTML = '';
        
        // Variable para calcular el total
        let subtotal = 0;
        
        // Añadir cada producto al carrito
        cart.forEach(item => {
            // Clonar la plantilla
            const itemNode = document.importNode(cartItemTemplate.content, true);
            
            // Calcular subtotal del item
            const itemSubtotal = item.price * item.quantity;
            subtotal += itemSubtotal;
            
            // Establecer datos del producto
            const itemImage = itemNode.querySelector('.cart-item-image');
            itemImage.src = item.image ? `<?php echo BASE_URL; ?>assets/uploads/productos/${item.image}` : `<?php echo BASE_URL; ?>assets/img/no-image.png`;
            itemImage.alt = item.name;
            
            itemNode.querySelector('.cart-item-name').textContent = item.name;
            itemNode.querySelector('.cart-item-code').textContent = `ID: ${item.id}`;
            itemNode.querySelector('.quantity-input').value = item.quantity;
            itemNode.querySelector('.cart-item-price').textContent = `S/ ${item.price.toFixed(2)}`;
            itemNode.querySelector('.cart-item-subtotal').textContent = `S/ ${itemSubtotal.toFixed(2)}`;
            
            // Guardar ID del producto para referencias futuras
            const itemRow = itemNode.querySelector('.cart-item');
            itemRow.dataset.id = item.id;
            
            // Agregar eventos a los botones de cantidad
            const decreaseBtn = itemNode.querySelector('.decrease-quantity');
            const increaseBtn = itemNode.querySelector('.increase-quantity');
            const quantityInput = itemNode.querySelector('.quantity-input');
            const removeBtn = itemNode.querySelector('.remove-item');
            
            decreaseBtn.addEventListener('click', function() {
                updateItemQuantity(item.id, Math.max(1, item.quantity - 1));
            });
            
            increaseBtn.addEventListener('click', function() {
                updateItemQuantity(item.id, item.quantity + 1);
            });
            
            removeBtn.addEventListener('click', function() {
                removeItemFromCart(item.id);
            });
            
            // Añadir el elemento al DOM
            cartItems.appendChild(itemNode);
        });
        
        // Calcular totales
        const shipping = subtotal > 0 ? 10 : 0; // Costo de envío fijo de S/ 10
        const total = subtotal + shipping;
        
        // Actualizar los totales en el DOM
        cartSubtotal.textContent = `S/ ${subtotal.toFixed(2)}`;
        cartShipping.textContent = `S/ ${shipping.toFixed(2)}`;
        cartTotal.textContent = `S/ ${total.toFixed(2)}`;
    }
    
    // Actualizar la cantidad de un producto
    function updateItemQuantity(productId, newQuantity) {
        // Obtener el carrito actual
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        // Buscar el producto
        const productIndex = cart.findIndex(item => item.id === productId);
        
        if (productIndex !== -1) {
            // Actualizar cantidad
            cart[productIndex].quantity = newQuantity;
            
            // Guardar carrito actualizado
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Actualizar contador de carrito en el navbar
            updateCartCount();
            
            // Recargar los items del carrito
            loadCartItems();
        }
    }
    
    // Eliminar un producto del carrito
    function removeItemFromCart(productId) {
        // Obtener el carrito actual
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        // Filtrar el producto a eliminar
        cart = cart.filter(item => item.id !== productId);
        
        // Guardar carrito actualizado
        localStorage.setItem('cart', JSON.stringify(cart));
        
        // Actualizar contador de carrito en el navbar
        updateCartCount();
        
        // Recargar los items del carrito
        loadCartItems();
    }
    
    // Vaciar el carrito completamente
    function clearCart() {
        if (confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
            // Vaciar el carrito en localStorage
            localStorage.removeItem('cart');
            
            // Actualizar contador de carrito en el navbar
            updateCartCount();
            
            // Recargar los items del carrito
            loadCartItems();
        }
    }
    
    // Proceder al checkout
    function proceedToCheckout() {
        // Verificar si hay productos en el carrito
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        if (cart.length === 0) {
            alert('Tu carrito está vacío. Añade productos antes de proceder al pago.');
            return;
        }
        
        // Redirigir a la página de checkout
        window.location.href = '<?php echo BASE_URL; ?>checkout';
    }
    
    // Eventos de los botones
    clearCartButton.addEventListener('click', clearCart);
    checkoutButton.addEventListener('click', proceedToCheckout);
    
    // Actualizar el contador del carrito
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
        
        // Actualizar badge de carrito
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = cartCount;
        }
    }
    
    // Cargar carrito al iniciar la página
    loadCartItems();
});
</script>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>