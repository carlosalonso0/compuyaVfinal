<?php
if (!defined('IN_COMPUYA')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header Principal -->
    <header class="main-header">
        <div class="container">
            <a href="<?php echo BASE_URL; ?>" class="logo-container">
                <img src="<?php echo BASE_URL; ?>/assets/img/logo.svg" alt="<?php echo SITE_NAME; ?>" class="logo">
            </a>
            
            <button class="mobile-menu-toggle" aria-label="Menú">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-bar">
                <form action="<?php echo BASE_URL; ?>/search.php" method="get">
                    <input type="text" name="q" placeholder="¿Qué estás buscando?" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <div class="header-actions">
                <a href="<?php echo BASE_URL; ?>/login.php" class="header-action">
                    <i class="fas fa-user"></i>
                    <span>Mi cuenta</span>
                </a>
                
                <a href="<?php echo BASE_URL; ?>/cart.php" class="header-action">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Carrito</span>
                    <?php
                    // Mostrar contador de carrito si hay items
                    $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                    if ($cart_count > 0):
                    ?>
                    <div class="cart-count"><?php echo $cart_count; ?></div>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Menú principal -->
    <nav class="main-menu">
        <div class="container">
            <ul>
                <li><a href="<?php echo BASE_URL; ?>/index.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?>><i class="fas fa-home"></i> Inicio</a></li>
                
                <?php
                // Usar la función original para obtener categorías
                $categorias = obtenerCategorias();
                foreach ($categorias as $categoria_menu) {
                    // Solo mostrar categorías padre (sin padre_id)
                    if ($categoria_menu['padre_id'] === NULL || $categoria_menu['padre_id'] === 0) {
                        echo '<li><a href="' . BASE_URL . '/category.php?id=' . $categoria_menu['id'] . '">' . $categoria_menu['nombre'] . '</a></li>';
                    }
                }
                ?>
                
                <li><a href="<?php echo BASE_URL; ?>/contact.php"><i class="fas fa-envelope"></i> Contacto</a></li>
            </ul>
        </div>
    </nav>
    
    <!-- Contenido principal -->
    <main>