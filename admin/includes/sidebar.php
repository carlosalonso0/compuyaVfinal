<aside class="admin-sidebar">
    <div class="sidebar-logo">
        <h1>COMPU YA</h1>
        <div style="font-size: 12px; color: rgba(255,255,255,0.7);">Panel de Administración</div>
    </div>
    
    <nav class="sidebar-nav">
    <div class="sidebar-nav-title">Principal</div>
    <a href="/compuya/admin/index.php" class="sidebar-nav-item">
        <div class="sidebar-icon">📊</div>
        <span>Dashboard</span>
    </a>
    
    <div class="sidebar-nav-title">Catálogo</div>
    <a href="/compuya/admin/products/index.php" class="sidebar-nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/products/') !== false ? 'active' : ''; ?>">
        <div class="sidebar-icon">💻</div>
        <span>Productos</span>
    </a>
    
    <div class="sidebar-nav-title">Tienda</div>
    <a href="/compuya/admin/homepage/index.php" class="sidebar-nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/homepage/') !== false ? 'active' : ''; ?>">
        <div class="sidebar-icon">🏠</div>
        <span>Página de Inicio</span>
    </a>
    
    <a href="/compuya/admin/orders/index.php" class="sidebar-nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/orders/') !== false ? 'active' : ''; ?>">
        <div class="sidebar-icon">🛒</div>
        <span>Pedidos</span>
    </a>
</nav>


    <div class="sidebar-footer">
        COMPU YA &copy; <?php echo date('Y'); ?>
    </div>
</aside>