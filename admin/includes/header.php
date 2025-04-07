<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Admin Panel' : 'Admin Panel - CompuYA'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #001C8D;
            --secondary-color: #DD0B0B;
        }
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
        }
        .admin-container {
            display: flex;
        }
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }
        .sidebar-header {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header img {
            max-height: 40px;
        }
        .sidebar-menu {
            padding: 0;
            list-style: none;
        }
        .sidebar-menu li {
            margin: 0;
        }
        .sidebar-menu li a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar-menu li a:hover {
            background-color: rgba(255,255,255,0.1);
            border-left-color: var(--secondary-color);
        }
        .sidebar-menu li a.active {
            background-color: rgba(255,255,255,0.15);
            border-left-color: var(--secondary-color);
            font-weight: bold;
        }
        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 20px;
            transition: all 0.3s;
        }
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-radius: 5px;
        }
        .card-header {
            font-weight: 600;
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #001463;
            border-color: #001463;
        }
        .btn-danger {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .btn-danger:hover {
            background-color: #b30000;
            border-color: #b30000;
        }
        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            .sidebar.active {
                margin-left: 0;
            }
            .content {
                width: 100%;
                margin-left: 0;
            }
            .content.active {
                margin-left: var(--sidebar-width);
            }
            #sidebarCollapse {
                display: block;
            }
        }
        #sidebarCollapse {
            display: none;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/img/logo.svg" alt="CompuYA" class="img-fluid">
                <h6 class="mt-2">Panel de Administración</h6>
            </div>

            <ul class="sidebar-menu">
                <li>
                    <a href="<?php echo ADMIN_URL; ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && dirname($_SERVER['PHP_SELF']) == '/compuya/admin' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>productos/" class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/productos/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i> Productos
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>categorias/" class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/categorias/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i> Categorías
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>pedidos/" class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/pedidos/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i> Pedidos
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>usuarios/" class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/usuarios/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>config/" class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/config/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>logout.php">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Contenido de la página -->
        <div class="content">
            <nav class="navbar navbar-expand navbar-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="ms-auto">
                        <div class="dropdown">
                            <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['admin_nombre'] . ' ' . $_SESSION['admin_apellido']; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="../index.php" target="_blank"><i class="fas fa-store me-2"></i>Ver tienda</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>