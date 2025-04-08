<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #001C8D;">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
            <img src="<?php echo BASE_URL; ?>assets/img/logo.svg" alt="CompuYA" height="40">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item navbar__menu-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>">Inicio</a>
                </li>
                <li class="nav-item dropdown navbar__menu-item">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Categorías
                    </a>
                    <ul class="dropdown-menu navbar__dropdown" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>categoria/perifericos">Periféricos</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>categoria/monitores">Monitores</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>categoria/laptops">Laptops</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>categoria/impresoras">Impresoras</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>categoria/componentes">Componentes</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>categoria/pcs-armadas">PCs Armadas</a></li>
                    </ul>
                </li>
                <li class="nav-item navbar__menu-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>contacto">Contacto</a>
                </li>
            </ul>
            <form class="d-flex me-2" action="<?php echo BASE_URL; ?>buscar.php" method="GET">
                <input class="form-control me-2" type="search" name="q" placeholder="Buscar productos..." aria-label="Buscar">
                <button class="btn btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
            </form>
            <div class="d-flex">
                <a href="<?php echo BASE_URL; ?>carrito" class="btn btn-outline-light me-2">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="badge bg-danger rounded-pill" id="cart-count">0</span>
                </a>
                <a href="<?php echo BASE_URL; ?>cuenta" class="btn btn-outline-light">
                    <i class="fas fa-user"></i>
                </a>
            </div>
        </div>
    </div>
</nav>