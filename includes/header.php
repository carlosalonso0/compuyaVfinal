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
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/category.css">

</head>
<body>
    <!-- resto del código igual -->
    <header>
        <div class="top-bar">
            <div class="container">
                <div class="contact-info">
                    <span>Teléfono: (01) 123-4567</span>
                    <span>Email: ventas@compuya.com</span>
                </div>
            </div>
        </div>
        
        <div class="main-header" style="background-color: #001CBD;">
            <div class="container">
                <div class="logo">
                    <a href="index.php">
                        <img src="assets/img/logo.svg" alt="<?php echo SITE_NAME; ?>">
                    </a>
                </div>
                
                <div class="search-bar">
                    <form action="search.php" method="get">
                        <input type="text" name="q" placeholder="¿Qué estás buscando?" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" required>
                        <button type="submit">Buscar</button>
                    </form>
                </div>
                
                <div class="header-actions">
                    <a href="cart.php" class="cart-icon">Carrito (0)</a>
                    <a href="login.php" class="user-icon">Mi Cuenta</a>
                </div>
            </div>
        </div>
        
        <nav class="main-menu">
            <div class="container">
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <?php
                    $categorias = obtenerCategorias();
                    foreach ($categorias as $categoria) {
                        // Solo mostrar categorías padre (sin padre_id)
                        if ($categoria['padre_id'] === NULL) {
                            echo '<li><a href="category.php?id=' . $categoria['id'] . '">' . $categoria['nombre'] . '</a></li>';
                        }
                    }
                    ?>
                    <li><a href="contact.php">Contacto</a></li>
                </ul>
            </div>
        </nav>
    </header>
    
    <main>