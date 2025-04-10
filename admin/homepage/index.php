<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Procesar formularios
$mensajes = [];
$errores = [];

$db = Database::getInstance();
$conn = $db->getConnection();

// Proceso para destacar categorías
if (isset($_POST['destacar_categoria'])) {
    $categoria_id = (int)$_POST['categoria_id'];
    
    // Verificar si ya está destacada
    $check = $conn->query("SELECT * FROM categorias_destacadas WHERE categoria_id = $categoria_id");
    
    if ($check->num_rows == 0) {
        // Obtener el máximo orden actual
        $result = $conn->query("SELECT MAX(orden) as max_orden FROM categorias_destacadas");
        $row = $result->fetch_assoc();
        $nuevo_orden = $row['max_orden'] ? $row['max_orden'] + 1 : 1;
        
        // Insertar como destacada
        $conn->query("INSERT INTO categorias_destacadas (categoria_id, orden) VALUES ($categoria_id, $nuevo_orden)");
        $mensajes[] = "Categoría destacada correctamente.";
    } else {
        $errores[] = "Esta categoría ya está destacada.";
    }
}

// Proceso para quitar categoría destacada
if (isset($_GET['quitar_destacada'])) {
    $id = (int)$_GET['quitar_destacada'];
    $conn->query("DELETE FROM categorias_destacadas WHERE categoria_id = $id");
    $mensajes[] = "Categoría removida de destacados.";
}

// Proceso para destacar/quitar producto
if (isset($_POST['toggle_producto'])) {
    $producto_id = (int)$_POST['producto_id'];
    $tipo = $_POST['tipo']; // destacado, nuevo u oferta
    $tab = isset($_POST['tab']) ? $_POST['tab'] : 'destacados'; // Obtener la pestaña actual
    
    if ($tipo == 'destacado') {
        $campo = 'destacado';
    } elseif ($tipo == 'nuevo') {
        $campo = 'nuevo';
    } else {
        $errores[] = "Tipo de modificación inválido.";
    }
    
    if (empty($errores)) {
        // Verificar estado actual
        $result = $conn->query("SELECT $campo FROM productos WHERE id = $producto_id");
        if ($result && $row = $result->fetch_assoc()) {
            $nuevo_valor = $row[$campo] ? 0 : 1;
            $conn->query("UPDATE productos SET $campo = $nuevo_valor WHERE id = $producto_id");
            $estado = $nuevo_valor ? "añadido a" : "removido de";
            $mensajes[] = "Producto $estado " . ucfirst($tipo) . "s.";
            
            // Redirigir a la misma pestaña
            header("Location: index.php?tab=" . $tab);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de Página de Inicio - Panel de Administración</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .admin-header {
            background: #001CBD;
            color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .admin-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .messages {
            margin: 20px 0;
        }
        .message {
            padding: 10px 15px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .section {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h2 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input[type="text"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #001CBD;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #0016a0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background: #f5f5f5;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            margin-bottom: -1px;
        }
        .tab.active {
            background: white;
            border: 1px solid #ddd;
            border-bottom-color: white;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h1>Editor de Página de Inicio</h1>
        </div>
        
        <div class="messages">
            <?php foreach ($mensajes as $mensaje): ?>
                <div class="message success"><?php echo $mensaje; ?></div>
            <?php endforeach; ?>
            
            <?php foreach ($errores as $error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endforeach; ?>
        </div>
        
        <div class="tabs">
            <div class="tab active" data-tab="categorias">Categorías Destacadas</div>
            <div class="tab" data-tab="destacados">Productos Destacados</div>
            <div class="tab" data-tab="ofertas">Ofertas</div>
            <div class="tab" data-tab="nuevos">Productos Nuevos</div>
        </div>
        
        <!-- Categorías Destacadas -->
        <div class="tab-content active" id="categorias">
            <div class="section">
                <h2>Categorías Destacadas</h2>
                
                <form action="" method="post">
                    <div class="form-group">
                        <label for="categoria_id">Seleccionar Categoría:</label>
                        <select name="categoria_id" id="categoria_id" required>
                            <option value="">-- Seleccione una categoría --</option>
                            <?php
                            $result = $conn->query("SELECT * FROM categorias ORDER BY nombre");
                            while ($cat = $result->fetch_assoc()) {
                                echo '<option value="' . $cat['id'] . '">' . $cat['nombre'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" name="destacar_categoria">Destacar Categoría</button>
                </form>
                
                <h3 style="margin-top: 30px;">Categorías Destacadas Actuales</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("
                            SELECT c.id, c.nombre 
                            FROM categorias c
                            JOIN categorias_destacadas cd ON c.id = cd.categoria_id
                            ORDER BY cd.orden
                        ");
                        
                        if ($result->num_rows > 0) {
                            while ($cat = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $cat['id'] . '</td>';
                                echo '<td>' . $cat['nombre'] . '</td>';
                                echo '<td class="actions">';
                                echo '<a href="?quitar_destacada=' . $cat['id'] . '" class="btn-small btn-danger" onclick="return confirm(\'¿Estás seguro?\')">Quitar</a>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3">No hay categorías destacadas.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Productos Destacados -->
        <div class="tab-content" id="destacados">
            <div class="section">
                <h2>Productos Destacados</h2>
                
                <h3>Productos Actuales</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Categoría</th>
                            <th>Destacado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("
                            SELECT p.*, c.nombre as categoria_nombre 
                            FROM productos p
                            JOIN categorias c ON p.categoria_id = c.id
                            WHERE p.activo = 1
                            ORDER BY p.destacado DESC, p.id DESC
                            LIMIT 20
                        ");
                        
                        while ($prod = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $prod['id'] . '</td>';
                            echo '<td>' . $prod['nombre'] . '</td>';
                            echo '<td>S/ ' . number_format($prod['precio'], 2) . '</td>';
                            echo '<td>' . $prod['categoria_nombre'] . '</td>';
                            echo '<td>' . ($prod['destacado'] ? 'Sí' : 'No') . '</td>';
                            echo '<td class="actions">';
                            echo '<form action="" method="post" style="display:inline;">';
                            echo '<input type="hidden" name="producto_id" value="' . $prod['id'] . '">';
                            echo '<input type="hidden" name="tipo" value="destacado">';
                            
                            if ($prod['destacado']) {
                                echo '<button type="submit" name="toggle_producto" class="btn-small btn-danger">Quitar Destacado</button>';
                            } else {
                                echo '<button type="submit" name="toggle_producto" class="btn-small btn-success">Destacar</button>';
                            }
                            
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                
                <p style="margin-top: 20px;">Nota: La tabla muestra los 20 productos más recientes. Use el buscador de productos para encontrar productos específicos.</p>
                
                <!-- Buscador de productos -->
                <h3 style="margin-top: 30px;">Buscar Productos</h3>
                <form action="" method="get">
                    <div class="form-group">
                        <input type="text" name="q" placeholder="Nombre o modelo del producto" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    </div>
                    <button type="submit">Buscar</button>
                </form>
                
                <?php
                if (isset($_GET['q']) && !empty($_GET['q'])) {
                    $q = $conn->real_escape_string($_GET['q']);
                    $result = $conn->query("
                        SELECT p.*, c.nombre as categoria_nombre 
                        FROM productos p
                        JOIN categorias c ON p.categoria_id = c.id
                        WHERE p.activo = 1 AND (p.nombre LIKE '%$q%' OR p.modelo LIKE '%$q%')
                        ORDER BY p.nombre
                        LIMIT 20
                    ");
                    
                    echo '<h4 style="margin-top: 20px;">Resultados de la búsqueda</h4>';
                    echo '<table>';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>ID</th>';
                    echo '<th>Nombre</th>';
                    echo '<th>Precio</th>';
                    echo '<th>Categoría</th>';
                    echo '<th>Destacado</th>';
                    echo '<th>Acciones</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    
                    if ($result->num_rows > 0) {
                        while ($prod = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $prod['id'] . '</td>';
                            echo '<td>' . $prod['nombre'] . '</td>';
                            echo '<td>S/ ' . number_format($prod['precio'], 2) . '</td>';
                            echo '<td>' . $prod['categoria_nombre'] . '</td>';
                            echo '<td>' . ($prod['destacado'] ? 'Sí' : 'No') . '</td>';
                            echo '<td class="actions">';
                            echo '<form action="" method="post" style="display:inline;">';
                            echo '<input type="hidden" name="producto_id" value="' . $prod['id'] . '">';
                            echo '<input type="hidden" name="tipo" value="destacado">';
                            echo '<input type="hidden" name="tab" value="destacados">';
                            
                            if ($prod['destacado']) {
                                echo '<button type="submit" name="toggle_producto" class="btn-small btn-danger">Quitar Destacado</button>';
                            } else {
                                echo '<button type="submit" name="toggle_producto" class="btn-small btn-success">Destacar</button>';
                            }
                            
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6">No se encontraron productos.</td></tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                }
                ?>
            </div>
        </div>
        
        <!-- Ofertas (Productos con precio_oferta) -->
        <div class="tab-content" id="ofertas">
            <div class="section">
                <h2>Productos en Oferta</h2>
                
                <p>Esta sección muestra los productos que tienen un precio de oferta establecido. Los productos aparecerán automáticamente en la sección de ofertas de la página de inicio si tienen un precio de oferta.</p>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio Regular</th>
                            <th>Precio Oferta</th>
                            <th>Descuento</th>
                            <th>Categoría</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("
                            SELECT p.*, c.nombre as categoria_nombre 
                            FROM productos p
                            JOIN categorias c ON p.categoria_id = c.id
                            WHERE p.activo = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0
                            ORDER BY p.id DESC
                        ");
                        
                        if ($result->num_rows > 0) {
                            while ($prod = $result->fetch_assoc()) {
                                $descuento = round(100 - (($prod['precio_oferta'] / $prod['precio']) * 100));
                                
                                echo '<tr>';
                                echo '<td>' . $prod['id'] . '</td>';
                                echo '<td>' . $prod['nombre'] . '</td>';
                                echo '<td>S/ ' . number_format($prod['precio'], 2) . '</td>';
                                echo '<td>S/ ' . number_format($prod['precio_oferta'], 2) . '</td>';
                                echo '<td>' . $descuento . '%</td>';
                                echo '<td>' . $prod['categoria_nombre'] . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6">No hay productos en oferta.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
                
                <p style="margin-top: 20px;">Para establecer un precio de oferta, edite el producto desde el gestor de productos.</p>
            </div>
        </div>
        
        <!-- Productos Nuevos -->
        <div class="tab-content" id="nuevos">
            <div class="section">
                <h2>Productos Nuevos</h2>
                
                <h3>Productos Actuales</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Categoría</th>
                            <th>Nuevo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("
                            SELECT p.*, c.nombre as categoria_nombre 
                            FROM productos p
                            JOIN categorias c ON p.categoria_id = c.id
                            WHERE p.activo = 1
                            ORDER BY p.nuevo DESC, p.id DESC
                            LIMIT 20
                        ");
                        
                        while ($prod = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $prod['id'] . '</td>';
                            echo '<td>' . $prod['nombre'] . '</td>';
                            echo '<td>S/ ' . number_format($prod['precio'], 2) . '</td>';
                            echo '<td>' . $prod['categoria_nombre'] . '</td>';
                            echo '<td>' . ($prod['nuevo'] ? 'Sí' : 'No') . '</td>';
                            echo '<td class="actions">';
                            echo '<form action="" method="post" style="display:inline;">';
                            echo '<input type="hidden" name="producto_id" value="' . $prod['id'] . '">';
                            echo '<input type="hidden" name="tipo" value="nuevo">';
                            echo '<input type="hidden" name="tab" value="nuevos">';
                            
                            if ($prod['nuevo']) {
                                echo '<button type="submit" name="toggle_producto" class="btn-small btn-danger">Quitar Nuevo</button>';
                            } else {
                                echo '<button type="submit" name="toggle_producto" class="btn-small btn-success">Marcar como Nuevo</button>';
                            }
                            
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                
                <p style="margin-top: 20px;">Nota: La tabla muestra los 20 productos más recientes. Use el buscador de productos para encontrar productos específicos.</p>
                
                <!-- Buscador de productos (igual al de Destacados) -->
                <h3 style="margin-top: 30px;">Buscar Productos</h3>
                <form action="" method="get">
                    <input type="hidden" name="tab" value="nuevos">
                    <div class="form-group">
                        <input type="text" name="qn" placeholder="Nombre o modelo del producto" value="<?php echo isset($_GET['qn']) ? htmlspecialchars($_GET['qn']) : ''; ?>">
                    </div>
                    <button type="submit">Buscar</button>
                </form>
                
                <?php
                if (isset($_GET['qn']) && !empty($_GET['qn'])) {
                    $q = $conn->real_escape_string($_GET['qn']);
                    $result = $conn->query("
                        SELECT p.*, c.nombre as categoria_nombre 
                        FROM productos p
                        JOIN categorias c ON p.categoria_id = c.id
                        WHERE p.activo = 1 AND (p.nombre LIKE '%$q%' OR p.modelo LIKE '%$q%')
                        ORDER BY p.nombre
                        LIMIT 20
                    ");
                    
                    echo '<h4 style="margin-top: 20px;">Resultados de la búsqueda</h4>';
                    echo '<table>';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>ID</th>';
                    echo '<th>Nombre</th>';
                    echo '<th>Precio</th>';
                    echo '<th>Categoría</th>';
                    echo '<th>Nuevo</th>';
                    echo '<th>Acciones</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    
                    if ($result->num_rows > 0) {
                        while ($prod = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $prod['id'] . '</td>';
                            echo '<td>' . $prod['nombre'] . '</td>';
                            echo '<td>S/ ' . number_format($prod['precio'], 2) . '</td>';
                            echo '<td>' . $prod['categoria_nombre'] . '</td>';
                            echo '<td>' . ($prod['nuevo'] ? 'Sí' : 'No') . '</td>';
                            echo '<td class="actions">';
                            echo '<form action="" method="post" style="display:inline;">';
                            echo '<input type="hidden" name="producto_id" value="' . $prod['id'] . '">';
                            echo '<input type="hidden" name="tipo" value="nuevo">';
                            
                            if ($prod['nuevo']) {
                                echo '<button type="submit" name="toggle_producto" class="btn-small btn-danger">Quitar Nuevo</button>';
                            } else {
                                echo '<button type="submit" name="toggle_producto" class="btn-small btn-success">Marcar como Nuevo</button>';
                            }
                            
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6">No se encontraron productos.</td></tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
        // Script para las pestañas
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Si hay un parámetro de tab en la URL, activar esa pestaña
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam) {
                const targetTab = document.querySelector(`.tab[data-tab="${tabParam}"]`);
                if (targetTab) {
                    tabs.forEach(tab => tab.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    targetTab.classList.add('active');
                    document.getElementById(tabParam).classList.add('active');
                }
            }
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>