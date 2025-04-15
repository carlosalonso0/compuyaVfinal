<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Inicializar variables
$mensajes = [];
$errores = [];
$producto_id = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : 0;
$sku = isset($_GET['sku']) ? trim($_GET['sku']) : '';

// Añadir depuración
echo "<!-- DEBUG: producto_id=$producto_id, sku=$sku -->";

// Si tenemos SKU pero no ID, buscamos el ID
if (empty($producto_id) && !empty($sku)) {
    $sku_safe = $conn->real_escape_string($sku);
    $result = $conn->query("SELECT id FROM productos WHERE sku = '$sku_safe'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $producto_id = $row['id'];
        echo "<!-- DEBUG: Encontrado producto_id=$producto_id para sku=$sku -->";
    } else {
        $errores[] = "No se encontró un producto con el SKU: $sku";
        echo "<!-- DEBUG: No se encontró producto para sku=$sku -->";
    }
}

// Procesar formulario para añadir especificación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $valor = isset($_POST['valor']) ? trim($_POST['valor']) : '';
    $pid = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
    
    echo "<!-- DEBUG: Añadiendo especificación: nombre=$nombre, valor=$valor, pid=$pid -->";
    
    if (empty($nombre) || empty($valor) || $pid <= 0) {
        $errores[] = "Todos los campos son obligatorios.";
    } else {
        $stmt = $conn->prepare("INSERT INTO especificaciones_producto (producto_id, nombre, valor) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $pid, $nombre, $valor);
        
        if ($stmt->execute()) {
            $mensajes[] = "Especificación añadida correctamente.";
        } else {
            $errores[] = "Error al añadir la especificación: " . $conn->error;
        }
    }
}

// Procesar formulario para editar especificación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $spec_id = isset($_POST['spec_id']) ? (int)$_POST['spec_id'] : 0;
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $valor = isset($_POST['valor']) ? trim($_POST['valor']) : '';
    
    echo "<!-- DEBUG: Editando especificación: id=$spec_id, nombre=$nombre, valor=$valor -->";
    
    if (empty($nombre) || empty($valor) || $spec_id <= 0) {
        $errores[] = "Todos los campos son obligatorios.";
    } else {
        $stmt = $conn->prepare("UPDATE especificaciones_producto SET nombre = ?, valor = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $valor, $spec_id);
        
        if ($stmt->execute()) {
            $mensajes[] = "Especificación actualizada correctamente.";
        } else {
            $errores[] = "Error al actualizar la especificación: " . $conn->error;
        }
    }
}

// Procesar eliminación de especificación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $spec_id = isset($_POST['spec_id']) ? (int)$_POST['spec_id'] : 0;
    
    echo "<!-- DEBUG: Eliminando especificación: id=$spec_id -->";
    
    if ($spec_id <= 0) {
        $errores[] = "ID de especificación inválido.";
    } else {
        $stmt = $conn->prepare("DELETE FROM especificaciones_producto WHERE id = ?");
        $stmt->bind_param("i", $spec_id);
        
        if ($stmt->execute()) {
            $mensajes[] = "Especificación eliminada correctamente.";
        } else {
            $errores[] = "Error al eliminar la especificación: " . $conn->error;
        }
    }
}

// Obtener información del producto
$producto = null;
if ($producto_id > 0) {
    $result = $conn->query("SELECT * FROM productos WHERE id = $producto_id");
    echo "<!-- DEBUG: Consultando producto con id=$producto_id, resultado=" . ($result ? $result->num_rows : 'null') . " -->";
    if ($result && $result->num_rows > 0) {
        $producto = $result->fetch_assoc();
    }
}

// Obtener especificaciones del producto
$especificaciones = [];
if ($producto_id > 0) {
    $result = $conn->query("SELECT * FROM especificaciones_producto WHERE producto_id = $producto_id ORDER BY nombre");
    echo "<!-- DEBUG: Consultando especificaciones para producto_id=$producto_id, resultado=" . ($result ? $result->num_rows : 'null') . " -->";
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $especificaciones[] = $row;
        }
    }
}

// Obtener productos para el selector
$productos = [];
$result = $conn->query("SELECT id, sku, nombre FROM productos ORDER BY nombre");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Incluir cabecera
include '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Especificaciones - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th, .data-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .btn-action {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Gestionar Especificaciones</h1>
                <div class="admin-actions">
                    <a href="index.php" class="btn btn-secondary">Volver a Productos</a>
                    <a href="import_specs.php" class="btn btn-primary">Importar Especificaciones CSV</a>
                </div>
            </header>
            
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
            
            <section class="admin-section">
                <div class="form-card">
                    <h2>Seleccionar Producto</h2>
                    <form action="" method="get">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label for="producto_id">Producto:</label>
                                <select name="producto_id" id="producto_id" class="form-control">
                                    <option value="">-- Seleccione un producto --</option>
                                    <?php foreach ($productos as $prod): ?>
                                        <option value="<?php echo $prod['id']; ?>" <?php echo $producto_id == $prod['id'] ? 'selected' : ''; ?>>
                                            <?php echo $prod['nombre'] . ' (' . $prod['sku'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="sku">O buscar por SKU:</label>
                                <input type="text" name="sku" id="sku" value="<?php echo htmlspecialchars($sku); ?>" class="form-control">
                            </div>
                            <div class="form-group" style="display: flex; align-items: flex-end;">
                                <button type="submit" class="btn btn-primary">Buscar</button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if ($producto): ?>
                    <div class="data-card">
                        <h2>Especificaciones para: <?php echo htmlspecialchars($producto['nombre']); ?></h2>
                        <p><strong>SKU:</strong> <?php echo htmlspecialchars($producto['sku']); ?></p>
                        
                        <div style="margin-top: 20px;">
                            <h3>Añadir nueva especificación</h3>
                            <form action="" method="post" style="margin-bottom: 20px;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
                                <div class="form-row">
                                    <div class="form-group" style="flex: 1;">
                                        <label for="nombre">Nombre:</label>
                                        <input type="text" name="nombre" id="nombre" required class="form-control">
                                    </div>
                                    <div class="form-group" style="flex: 2;">
                                        <label for="valor">Valor:</label>
                                        <input type="text" name="valor" id="valor" required class="form-control">
                                    </div>
                                    <div class="form-group" style="display: flex; align-items: flex-end;">
                                        <button type="submit" class="btn btn-primary">Añadir</button>
                                    </div>
                                </div>
                            </form>
                            
                            <h3>Especificaciones actuales</h3>
                            <?php if (empty($especificaciones)): ?>
                                <p>No hay especificaciones para este producto.</p>
                            <?php else: ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Valor</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($especificaciones as $spec): ?>
                                            <tr>
                                                <td><?php echo $spec['id']; ?></td>
                                                <td><?php echo htmlspecialchars($spec['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($spec['valor']); ?></td>
                                                <td class="actions">
                                                    <button type="button" class="btn-action edit" onclick="editarSpec(<?php echo $spec['id']; ?>, '<?php echo htmlspecialchars(addslashes($spec['nombre'])); ?>', '<?php echo htmlspecialchars(addslashes($spec['valor'])); ?>')">✏️</button>
                                                    
                                                    <form action="" method="post" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta especificación?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="spec_id" value="<?php echo $spec['id']; ?>">
                                                        <button type="submit" class="btn-action delete">🗑️</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($producto_id > 0): ?>
                    <div class="data-card">
                        <p>Producto no encontrado. Verifique el ID o SKU proporcionado.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    
    <!-- Modal para editar especificación -->
    <div id="modal-editar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; padding: 20px; border-radius: 8px; width: 500px; max-width: 90%;">
            <h3>Editar Especificación</h3>
            <form action="" method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="spec_id" id="edit_spec_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_nombre">Nombre:</label>
                    <input type="text" name="nombre" id="edit_nombre" required class="form-control">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_valor">Valor:</label>
                    <input type="text" name="valor" id="edit_valor" required class="form-control">
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editarSpec(id, nombre, valor) {
            document.getElementById('edit_spec_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_valor').value = valor;
            document.getElementById('modal-editar').style.display = 'block';
        }
        
        function cerrarModal() {
            document.getElementById('modal-editar').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera del contenido
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('modal-editar');
            if (event.target === modal) {
                cerrarModal();
            }
        });
        
        // Auto-submit al seleccionar un producto del dropdown
        document.getElementById('producto_id').addEventListener('change', function() {
            if (this.value) {
                this.form.submit();
            }
        });
    </script>
</body>
</html></parameter>
</invoke>