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

// Obtener todas las categorías
$result_categorias = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
$categorias = [];
while ($row = $result_categorias->fetch_assoc()) {
    $categorias[] = $row;
}

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
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errores)) {
        $stmt = $conn->prepare("INSERT INTO productos (nombre, precio, categoria_id, descripcion, descripcion_corta, precio_oferta, stock, marca, modelo, caracteristicas, destacado, nuevo, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Manejar NULL para precio_oferta
        if ($precio_oferta === null) {
            $stmt->bind_param("sdissisdssiii", $nombre, $precio, $categoria_id, $descripcion, $descripcion_corta, $null_value, $stock, $marca, $modelo, $caracteristicas, $destacado, $nuevo, $activo);
            $null_value = null;
        } else {
            $stmt->bind_param("sdissdissiii", $nombre, $precio, $categoria_id, $descripcion, $descripcion_corta, $precio_oferta, $stock, $marca, $modelo, $caracteristicas, $destacado, $nuevo, $activo);
        }
        
        if ($stmt->execute()) {
            $nuevo_id = $conn->insert_id;
            $mensajes[] = "Producto añadido correctamente.";
            
            // Procesar imagen si se ha subido
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                $imagen = $_FILES['imagen'];
                $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
                $nombre_archivo = 'producto_' . $nuevo_id . '_' . uniqid() . '.' . $extension;
                $ruta_destino = '../../uploads/productos/' . $nombre_archivo;
                
                // Crear directorio si no existe
                if (!is_dir('../../uploads/productos/')) {
                    mkdir('../../uploads/productos/', 0777, true);
                }
                
                if (move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
                    // Guardar referencia en la base de datos
                    $ruta_bd = 'uploads/productos/' . $nombre_archivo;
                    $stmt = $conn->prepare("INSERT INTO imagenes_producto (producto_id, ruta, principal) VALUES (?, ?, 1)");
                    $stmt->bind_param("is", $nuevo_id, $ruta_bd);
                    $stmt->execute();
                    
                    $mensajes[] = "Imagen subida correctamente.";
                } else {
                    $errores[] = "Error al subir la imagen. El producto se ha guardado sin imagen.";
                }
            }
            
            // Limpiar el formulario después de guardar correctamente
            if (empty($errores)) {
                // Redirigir a la lista de productos si todo está bien
                header("Location: index.php?mensaje=Producto añadido correctamente");
                exit;
            }
        } else {
            $errores[] = "Error al guardar el producto: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Producto - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Añadir Nuevo Producto</h1>
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
                                <input type="text" id="nombre" name="nombre" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
                                <p class="form-help">Nombre completo del producto. Será visible en la tienda.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="categoria_id">Categoría*:</label>
                                <select id="categoria_id" name="categoria_id" required>
                                    <option value="">-- Seleccione una categoría --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo isset($_POST['categoria_id']) && $_POST['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="form-help">Seleccione la categoría a la que pertenece el producto.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="marca">Marca:</label>
                                <input type="text" id="marca" name="marca" value="<?php echo isset($_POST['marca']) ? htmlspecialchars($_POST['marca']) : ''; ?>">
                                <p class="form-help">Marca del producto (HP, Dell, Logitech, etc.)</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="modelo">Modelo:</label>
                                <input type="text" id="modelo" name="modelo" value="<?php echo isset($_POST['modelo']) ? htmlspecialchars($_POST['modelo']) : ''; ?>">
                                <p class="form-help">Número o nombre del modelo específico.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="precio">Precio Regular (S/)*:</label>
                                <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?php echo isset($_POST['precio']) ? htmlspecialchars($_POST['precio']) : ''; ?>" required>
                                <p class="form-help">Precio regular del producto en Soles.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="precio_oferta">Precio Oferta (S/):</label>
                                <input type="number" id="precio_oferta" name="precio_oferta" step="0.01" min="0" value="<?php echo isset($_POST['precio_oferta']) ? htmlspecialchars($_POST['precio_oferta']) : ''; ?>">
                                <p class="form-help">Precio de oferta (si aplica). Dejar en blanco si no hay oferta.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="stock">Stock Disponible*:</label>
                                <input type="number" id="stock" name="stock" min="0" value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '0'; ?>" required>
                                <p class="form-help">Cantidad de unidades disponibles para venta.</p>
                            </div>
                            
                            <div class="form-group description-group">
                                <label for="descripcion_corta">Descripción Corta:</label>
                                <input type="text" id="descripcion_corta" name="descripcion_corta" value="<?php echo isset($_POST['descripcion_corta']) ? htmlspecialchars($_POST['descripcion_corta']) : ''; ?>">
                                <p class="form-help">Breve descripción para listados (máx. 255 caracteres).</p>
                            </div>
                            
                            <div class="form-group description-group">
                                <label for="descripcion">Descripción Completa:</label>
                                <textarea id="descripcion" name="descripcion" rows="5"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                                <p class="form-help">Descripción detallada del producto.</p>
                            </div>
                            
                            <div class="form-group description-group">
                                <label for="caracteristicas">Características:</label>
                                <textarea id="caracteristicas" name="caracteristicas" rows="5" placeholder="Una característica por línea"><?php echo isset($_POST['caracteristicas']) ? htmlspecialchars($_POST['caracteristicas']) : ''; ?></textarea>
                                <p class="form-help">Lista de características técnicas, una por línea.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="imagen">Imagen del Producto:</label>
                                <input type="file" id="imagen" name="imagen" accept="image/*">
                                <p class="form-help">Tamaño recomendado: 800x800px. Formatos: JPG, PNG.</p>
                            </div>
                            
                            <div class="form-group options-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="destacado" <?php echo isset($_POST['destacado']) ? 'checked' : ''; ?>>
                                    Producto Destacado
                                </label>
                                <p class="form-help">Aparecerá en la sección de destacados de la página principal.</p>
                            </div>
                            
                            <div class="form-group options-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="nuevo" <?php echo isset($_POST['nuevo']) ? 'checked' : ''; ?>>
                                    Producto Nuevo
                                </label>
                                <p class="form-help">Aparecerá en la sección de nuevos productos.</p>
                            </div>
                            
                            <div class="form-group options-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="activo" checked>
                                    Producto Activo
                                </label>
                                <p class="form-help">El producto estará visible y disponible para compra.</p>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Guardar Producto</button>
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