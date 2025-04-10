<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Verificar si está iniciada la sesión
// Aquí iría el control de acceso cuando implementemos el login

$db = Database::getInstance();
$conn = $db->getConnection();

$mensajes = [];
$errores = [];

// Obtener ID del producto
$producto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($producto_id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener todas las categorías
$result_categorias = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
$categorias = [];
while ($row = $result_categorias->fetch_assoc()) {
    $categorias[] = $row;
}

// Obtener datos del producto
$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$producto = $result->fetch_assoc();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y validar datos
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $precio = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;
    $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $descripcion_corta = isset($_POST['descripcion_corta']) ? trim($_POST['descripcion_corta']) : '';
    $precio_oferta = !empty($_POST['precio_oferta']) ? (float)$_POST['precio_oferta'] : null;
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    $marca = isset($_POST['marca']) ? trim($_POST['marca']) : '';
    $modelo = isset($_POST['modelo']) ? trim($_POST['modelo']) : '';
    $caracteristicas = isset($_POST['caracteristicas']) ? trim($_POST['caracteristicas']) : '';
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $nuevo = isset($_POST['nuevo']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones básicas
    if (empty($nombre)) {
        $errores[] = "El nombre del producto es obligatorio.";
    }
    
    if ($precio <= 0) {
        $errores[] = "El precio debe ser mayor que cero.";
    }
    
    if ($categoria_id <= 0) {
        $errores[] = "Debe seleccionar una categoría.";
    }
    
    if ($stock < 0) {
        $errores[] = "El stock no puede ser negativo.";
    }
    
    // Si no hay errores, actualizar en la base de datos
    if (empty($errores)) {
        if ($precio_oferta === null) {
            $stmt = $conn->prepare("UPDATE productos SET nombre = ?, precio = ?, categoria_id = ?, descripcion = ?, descripcion_corta = ?, precio_oferta = NULL, stock = ?, marca = ?, modelo = ?, caracteristicas = ?, destacado = ?, nuevo = ?, activo = ? WHERE id = ?");
            $stmt->bind_param("sdissiissiii", $nombre, $precio, $categoria_id, $descripcion, $descripcion_corta, $stock, $marca, $modelo, $caracteristicas, $destacado, $nuevo, $activo, $producto_id);
        } else {
            $stmt = $conn->prepare("UPDATE productos SET nombre = ?, precio = ?, categoria_id = ?, descripcion = ?, descripcion_corta = ?, precio_oferta = ?, stock = ?, marca = ?, modelo = ?, caracteristicas = ?, destacado = ?, nuevo = ?, activo = ? WHERE id = ?");
            $stmt->bind_param("sdissdiissiiii", $nombre, $precio, $categoria_id, $descripcion, $descripcion_corta, $precio_oferta, $stock, $marca, $modelo, $caracteristicas, $destacado, $nuevo, $activo, $producto_id);
        }
        
        if ($stmt->execute()) {
            $mensajes[] = "Producto actualizado correctamente.";
            
            // Procesar imagen si se ha subido
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                $imagen = $_FILES['imagen'];
                $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
                $nombre_archivo = 'producto_' . $producto_id . '_' . uniqid() . '.' . $extension;
                $ruta_destino = '../../uploads/productos/' . $nombre_archivo;
                
                // Crear directorio si no existe
                if (!is_dir('../../uploads/productos/')) {
                    mkdir('../../uploads/productos/', 0777, true);
                }
                
                if (move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
                    // Verificar si ya tiene una imagen principal
                    $stmt = $conn->prepare("SELECT id FROM imagenes_producto WHERE producto_id = ? AND principal = 1");
                    $stmt->bind_param("i", $producto_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    $ruta_bd = 'uploads/productos/' . $nombre_archivo;
                    
                    if ($result->num_rows > 0) {
                        // Actualizar imagen existente
                        $imagen_id = $result->fetch_assoc()['id'];
                        $stmt = $conn->prepare("UPDATE imagenes_producto SET ruta = ? WHERE id = ?");
                        $stmt->bind_param("si", $ruta_bd, $imagen_id);
                    } else {
                        // Insertar nueva imagen
                        $stmt = $conn->prepare("INSERT INTO imagenes_producto (producto_id, ruta, principal) VALUES (?, ?, 1)");
                        $stmt->bind_param("is", $producto_id, $ruta_bd);
                    }
                    
                    $stmt->execute();
                    $mensajes[] = "Imagen actualizada correctamente.";
                } else {
                    $errores[] = "Error al subir la imagen.";
                }
            }
            
            // Actualizar la información del producto después de guardar
            $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $producto = $result->fetch_assoc();
        } else {
            $errores[] = "Error al actualizar el producto: " . $conn->error;
        }
    }
}

// Obtener imagen principal del producto
$imagen_producto = null;
$stmt = $conn->prepare("SELECT ruta FROM imagenes_producto WHERE producto_id = ? AND principal = 1 LIMIT 1");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $imagen_producto = $row['ruta'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Editar Producto</h1>
                <div class="admin-actions">
                    <a href="index.php" class="btn btn-secondary">Volver a Productos</a>
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
                    <form action="" method="post" enctype="multipart/form-data" class="product-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nombre">Nombre del Producto*:</label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                                <p class="form-help">Nombre completo del producto. Será visible en la tienda.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="categoria_id">Categoría*:</label>
                                <select id="categoria_id" name="categoria_id" required>
                                    <option value="">-- Seleccione una categoría --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $producto['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="form-help">Seleccione la categoría a la que pertenece el producto.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="marca">Marca:</label>
                                <input type="text" id="marca" name="marca" value="<?php echo htmlspecialchars($producto['marca']); ?>">
                                <p class="form-help">Marca del producto (HP, Dell, Logitech, etc.)</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="modelo">Modelo:</label>
                                <input type="text" id="modelo" name="modelo" value="<?php echo htmlspecialchars($producto['modelo']); ?>">
                                <p class="form-help">Número o nombre del modelo específico.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="precio">Precio Regular (S/)*:</label>
                                <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?php echo htmlspecialchars($producto['precio']); ?>" required>
                                <p class="form-help">Precio regular del producto en Soles.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="precio_oferta">Precio Oferta (S/):</label>
                                <input type="number" id="precio_oferta" name="precio_oferta" step="0.01" min="0" value="<?php echo $producto['precio_oferta'] ? htmlspecialchars($producto['precio_oferta']) : ''; ?>">
                                <p class="form-help">Precio de oferta (si aplica). Dejar en blanco si no hay oferta.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="stock">Stock Disponible*:</label>
                                <input type="number" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($producto['stock']); ?>" required>
                                <p class="form-help">Cantidad de unidades disponibles para venta.</p>
                            </div>
                            
                            <div class="form-group description-group">
                                <label for="descripcion_corta">Descripción Corta:</label>
                                <input type="text" id="descripcion_corta" name="descripcion_corta" value="<?php echo htmlspecialchars($producto['descripcion_corta']); ?>">
                                <p class="form-help">Breve descripción para listados (máx. 255 caracteres).</p>
                            </div>
                            
                            <div class="form-group description-group">
                                <label for="descripcion">Descripción Completa:</label>
                                <textarea id="descripcion" name="descripcion" rows="5"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                                <p class="form-help">Descripción detallada del producto.</p>
                            </div>
                            
                            <div class="form-group description-group">
                                <label for="caracteristicas">Características:</label>
                                <textarea id="caracteristicas" name="caracteristicas" rows="5" placeholder="Una característica por línea"><?php echo htmlspecialchars($producto['caracteristicas']); ?></textarea>
                                <p class="form-help">Lista de características técnicas, una por línea.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="imagen">Imagen del Producto:</label>
                                <?php if ($imagen_producto): ?>
                                    <div class="current-image">
                                        <img src="../../<?php echo $imagen_producto; ?>" alt="<?php echo $producto['nombre']; ?>">
                                        <p>Imagen actual</p>
                                    </div>
                                <?php endif; ?>
                                <input type="file" id="imagen" name="imagen" accept="image/*">
                                <p class="form-help">Tamaño recomendado: 800x800px. Formatos: JPG, PNG.</p>
                            </div>
                            
                            <div class="form-group options-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="destacado" <?php echo $producto['destacado'] ? 'checked' : ''; ?>>
                                    Producto Destacado
                                </label>
                                <p class="form-help">Aparecerá en la sección de destacados de la página principal.</p>
                            </div>
                            
                            <div class="form-group options-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="nuevo" <?php echo $producto['nuevo'] ? 'checked' : ''; ?>>
                                    Producto Nuevo
                                </label>
                                <p class="form-help">Aparecerá en la sección de nuevos productos.</p>
                            </div>
                            
                            <div class="form-group options-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="activo" <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                                    Producto Activo
                                </label>
                                <p class="form-help">El producto estará visible y disponible para compra.</p>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        // Preview de la imagen seleccionada
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Si ya existe una vista previa, la actualizamos
                    let preview = document.querySelector('.image-preview');
                    
                    if (!preview) {
                        // Si no existe, creamos los elementos
                        preview = document.createElement('div');
                        preview.className = 'image-preview';
                        
                        const img = document.createElement('img');
                        preview.appendChild(img);
                        
                        // Insertamos después del input de imagen
                        document.getElementById('imagen').parentNode.appendChild(preview);
                    }
                    
                    // Actualizamos la imagen
                    const img = preview.querySelector('img');
                    img.src = e.target.result;
                };
                
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>