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
        <a href="/compuya/admin/products/index.php" class="sidebar-nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/products/') !== false && !strpos($_SERVER['PHP_SELF'], 'import_specs.php') && !strpos($_SERVER['PHP_SELF'], 'manage_specs.php') ? 'active' : ''; ?>">
            <div class="sidebar-icon">💻</div>
            <span>Productos</span>
        </a>
        
        <a href="/compuya/admin/products/import_specs.php" class="sidebar-nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/products/import_specs.php') !== false ? 'active' : ''; ?>">
            <div class="sidebar-icon">📥</div>
            <span>Importar Especificaciones</span>
        </a>
        
        <a href="/compuya/admin/products/manage_specs.php" class="sidebar-nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/products/manage_specs.php') !== false ? 'active' : ''; ?>">
            <div class="sidebar-icon">📋</div>
            <span>Gestionar Especificaciones</span>
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
        <div class="sidebar-nav-title">Usuario</div>
        <div class="sidebar-user-info">
            <?php if (isset($_SESSION['usuario_nombre'])): ?>
            <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></div>
            <div class="sidebar-user-role"><?php echo $_SESSION['usuario_rol'] === 'admin' ? 'Administrador' : 'Editor'; ?></div>
            <?php endif; ?>
        </div>
        <a href="../admin/logout.php" class="sidebar-nav-item">
            <div class="sidebar-icon">🚪</div>
            <span>Cerrar Sesión</span>
        </a>
    </nav>


    <div class="sidebar-footer">
        COMPU YA &copy; <?php echo date('Y'); ?>
    </div>
</aside>