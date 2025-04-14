<?php
define('IN_COMPUYA', true);
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Verificar si está iniciada la sesión
// Aquí iría el control de acceso cuando implementemos el login

$db = Database::getInstance();
$conn = $db->getConnection();

// Mensajes y errores
$mensajes = [];
$errores = [];

// Obtener ID del producto
$producto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($producto_id <= 0) {
    // Redirigir si no hay ID válido
    header('Location: index.php');
    exit;
}

// Obtener información del producto
$stmt = $conn->prepare("SELECT p.*, c.nombre as categoria_nombre FROM productos p 
                        LEFT JOIN categorias c ON p.categoria_id = c.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Producto no encontrado
    header('Location: index.php');
    exit;
}

$producto = $result->fetch_assoc();

// Obtener categorías
$result_categorias = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
$categorias = [];
while ($row = $result_categorias->fetch_assoc()) {
    $categorias[] = $row;
}

// Obtener la imagen principal del producto
$imagen_producto = '';
$img_result = $conn->query("SELECT ruta FROM imagenes_producto WHERE producto_id = {$producto_id} AND principal = 1 LIMIT 1");
if ($img_result && $img_result->num_rows > 0) {
    $img_row = $img_result->fetch_assoc();
    $imagen_producto = $img_row['ruta'];
}

// Obtener especificaciones del producto
$especificaciones = [];
$stmt = $conn->prepare("SELECT * FROM especificaciones_producto WHERE producto_id = ? ORDER BY nombre");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result_specs = $stmt->get_result();

while ($spec = $result_specs->fetch_assoc()) {
    $especificaciones[] = $spec;
}

// Procesar formulario de actualización
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
    $en_oferta = isset($_POST['en_oferta']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
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
        // Actualizar slug si ha cambiado el nombre
        if ($nombre != $producto['nombre']) {
            $slug = generarSlugUnico($nombre, $conn, $producto_id);
        } else {
            $slug = $producto['slug'];
        }
        
        // Preparar la consulta de actualización
        $sql = "UPDATE productos SET 
                nombre = ?, 
                slug = ?,
                precio = ?, 
                categoria_id = ?, 
                descripcion = ?, 
                descripcion_corta = ?, 
                precio_oferta = ?, 
                stock = ?, 
                marca = ?, 
                modelo = ?, 
                caracteristicas = ?, 
                destacado = ?, 
                nuevo = ?,
                en_oferta = ?,
                activo = ? 
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        
        if ($precio_oferta === null) {
            $stmt->bind_param("ssdississsiiiii", 
                $nombre, 
                $slug,
                $precio, 
                $categoria_id, 
                $descripcion, 
                $descripcion_corta, 
                $stock,  // Note: no precio_oferta here 
                $marca, 
                $modelo, 
                $caracteristicas, 
                $destacado, 
                $nuevo,
                $en_oferta,
                $activo,
                $producto_id
            );
        } else {
            $stmt->bind_param("ssdissdisssiiiii", 
                $nombre, 
                $slug,
                $precio, 
                $categoria_id, 
                $descripcion, 
                $descripcion_corta, 
                $precio_oferta, 
                $stock, 
                $marca, 
                $modelo, 
                $caracteristicas, 
                $destacado, 
                $nuevo,
                $en_oferta,
                $activo,
                $producto_id
            );
        }
        
        if ($stmt->execute()) {
            $mensajes[] = "Producto actualizado correctamente.";
            
            // Actualizar especificaciones
            if (isset($_POST['spec_nombres']) && isset($_POST['spec_valores'])) {
                // Primero, eliminar especificaciones existentes
                $conn->query("DELETE FROM especificaciones_producto WHERE producto_id = $producto_id");
                
                // Luego, insertar las nuevas
                $spec_nombres = $_POST['spec_nombres'];
                $spec_valores = $_POST['spec_valores'];
                
                $stmt_spec = $conn->prepare("INSERT INTO especificaciones_producto (producto_id, nombre, valor) VALUES (?, ?, ?)");
                
                for ($i = 0; $i < count($spec_nombres); $i++) {
                    if (!empty($spec_nombres[$i]) && !empty($spec_valores[$i])) {
                        $stmt_spec->bind_param("iss", $producto_id, $spec_nombres[$i], $spec_valores[$i]);
                        $stmt_spec->execute();
                    }
                }
                
                $mensajes[] = "Especificaciones actualizadas correctamente.";
            }
            
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
                    // Actualizar imagen en la base de datos
                    $ruta_bd = 'uploads/productos/' . $nombre_archivo;
                    
                    // Verificar si ya tiene una imagen principal
                    $result = $conn->query("SELECT id FROM imagenes_producto WHERE producto_id = $producto_id AND principal = 1");
                    
                    if ($result->num_rows > 0) {
                        // Actualizar imagen existente
                        $stmt = $conn->prepare("UPDATE imagenes_producto SET ruta = ? WHERE producto_id = ? AND principal = 1");
                        $stmt->bind_param("si", $ruta_bd, $producto_id);
                        $stmt->execute();
                    } else {
                        // Insertar nueva imagen
                        $stmt = $conn->prepare("INSERT INTO imagenes_producto (producto_id, ruta, principal) VALUES (?, ?, 1)");
                        $stmt->bind_param("is", $producto_id, $ruta_bd);
                        $stmt->execute();
                    }
                    
                    $mensajes[] = "Imagen actualizada correctamente.";
                    $imagen_producto = $ruta_bd; // Actualizar para mostrar
                } else {
                    $errores[] = "Error al subir la imagen.";
                }
            }
            
            // Recargar datos del producto
            $stmt = $conn->prepare("SELECT p.*, c.nombre as categoria_nombre FROM productos p 
                                 LEFT JOIN categorias c ON p.categoria_id = c.id 
                                 WHERE p.id = ?");
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $producto = $result->fetch_assoc();
        } else {
            $errores[] = "Error al actualizar el producto: " . $conn->error;
        }
    }
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
                                    <input type="checkbox" name="en_oferta" <?php echo $producto['en_oferta'] ? 'checked' : ''; ?>>
                                    Mostrar en Ofertas
                                </label>
                                <p class="form-help">Aparecerá en la sección de ofertas de la página principal (debe tener precio de oferta).</p>
                            </div>
                            
                            <div class="form-group options-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="activo" <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                                    Producto Activo
                                </label>
                                <p class="form-help">El producto estará visible y disponible para compra.</p>
                            </div>
                        </div>
                        
                        <div class="form-group specs-container">
                            <h3>Especificaciones Técnicas</h3>
                            <p class="form-help">Gestione las especificaciones técnicas del producto. Puede agregar especificaciones manualmente o seleccionar una categoría para cargar campos predefinidos.</p>
                            
                            <div id="specs-fields">
                                <?php if (empty($especificaciones)): ?>
                                    <div class="no-specs">No hay especificaciones para este producto. Seleccione una categoría para cargar campos predefinidos o añada manualmente.</div>
                                <?php else: ?>
                                    <?php foreach ($especificaciones as $spec): ?>
                                        <div class="spec-field-row custom-field">
                                            <div class="spec-field-group">
                                                <label>Nombre:</label>
                                                <input type="text" name="spec_nombres[]" value="<?php echo htmlspecialchars($spec['nombre']); ?>" class="spec-name" required>
                                            </div>
                                            <div class="spec-field-group">
                                                <label>Valor:</label>
                                                <input type="text" name="spec_valores[]" value="<?php echo htmlspecialchars($spec['valor']); ?>" class="spec-value" required>
                                            </div>
                                            <button type="button" class="btn-remove-spec" onclick="this.parentElement.remove()">×</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <button type="button" id="add-spec-field" class="btn btn-secondary">Añadir especificación</button>
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
// Mapeo de categorías a tipos de especificaciones
const categoriasSpecs = {
    '8': 'procesador',          // Categoría ID para procesadores
    '9': 'tarjeta_grafica',     // Categoría ID para tarjetas gráficas
    '2': 'monitor',             // Categoría ID para monitores
    '4': 'impresora',           // Categoría ID para impresoras
    '3': 'laptop',              // Categoría ID para laptops
    '10': 'placa_madre',        // Categoría ID para placas madre
    '7': 'gabinete',            // Categoría ID para gabinetes
    '6': 'computadora_completa' // Categoría ID para computadoras completas
};

// Mapeo de tipos a campos de especificaciones
const camposSpecs = {
    'procesador': [
        { nombre: 'Núcleos', placeholder: 'Ej: 6' },
        { nombre: 'Hilos', placeholder: 'Ej: 12' },
        { nombre: 'Frecuencia base', placeholder: 'Ej: 3.7 GHz' },
        // El resto de campos sigue igual...
    ],
    // El resto de categorías sigue igual...
};

// Función para generar campos de especificaciones
function generarCamposSpecs(tipo) {
    const specsFields = document.getElementById('specs-fields');
    
    // No borrar los campos existentes (las especificaciones ya agregadas)
    // Solo agregar campos predefinidos que no existan ya
    
    if (!tipo || !camposSpecs[tipo]) {
        if (specsFields.children.length === 0) {
            specsFields.innerHTML = '<div class="no-specs">No hay especificaciones disponibles para esta categoría.</div>';
        }
        document.getElementById('add-spec-field').style.display = 'inline-block';
        return;
    }
    
    // Obtener nombres de especificaciones ya existentes
    const existingNames = Array.from(specsFields.querySelectorAll('.spec-name'))
        .map(input => input.value.toLowerCase());
    
    // Eliminar mensaje de no specs si existe
    const noSpecsMsg = specsFields.querySelector('.no-specs');
    if (noSpecsMsg) {
        noSpecsMsg.remove();
    }
    
    // Mostrar campos predefinidos que no existan ya
    camposSpecs[tipo].forEach((campo) => {
        // Verificar si ya existe esta especificación (ignorando mayúsculas/minúsculas)
        if (!existingNames.includes(campo.nombre.toLowerCase())) {
            const fieldHtml = `
                <div class="spec-field-row">
                    <div class="spec-field-group">
                        <label>Nombre:</label>
                        <input type="text" name="spec_nombres[]" value="${campo.nombre}" class="spec-name" required>
                    </div>
                    <div class="spec-field-group">
                        <label>Valor:</label>
                        <input type="text" name="spec_valores[]" placeholder="${campo.placeholder}" class="spec-value" required>
                    </div>
                    <button type="button" class="btn-remove-spec" onclick="this.parentElement.remove()">×</button>
                </div>
            `;
            specsFields.insertAdjacentHTML('beforeend', fieldHtml);
        }
    });
    
    // Mostrar botón para añadir campo personalizado
    document.getElementById('add-spec-field').style.display = 'inline-block';
}

// Función para añadir un campo personalizado
function agregarCampoSpec() {
    const specsFields = document.getElementById('specs-fields');
    
    // Eliminar mensaje de no specs si existe
    const noSpecsMsg = specsFields.querySelector('.no-specs');
    if (noSpecsMsg) {
        noSpecsMsg.remove();
    }
    
    const fieldHtml = `
        <div class="spec-field-row custom-field">
            <div class="spec-field-group">
                <label>Nombre:</label>
                <input type="text" name="spec_nombres[]" placeholder="Nombre de la especificación" class="spec-name" required>
            </div>
            <div class="spec-field-group">
                <label>Valor:</label>
                <input type="text" name="spec_valores[]" placeholder="Valor de la especificación" class="spec-value" required>
            </div>
            <button type="button" class="btn-remove-spec" onclick="this.parentElement.remove()">×</button>
        </div>
    `;
    specsFields.insertAdjacentHTML('beforeend', fieldHtml);
}

// Función para añadir especificaciones con valores
function agregarCampoSpecConValores(nombre, valor) {
    const specsFields = document.getElementById('specs-fields');
    
    // Eliminar mensaje de no specs si existe
    const noSpecsMsg = specsFields.querySelector('.no-specs');
    if (noSpecsMsg) {
        noSpecsMsg.remove();
    }
    
    const fieldHtml = `
        <div class="spec-field-row custom-field">
            <div class="spec-field-group">
                <label>Nombre:</label>
                <input type="text" name="spec_nombres[]" value="${nombre}" class="spec-name" required>
            </div>
            <div class="spec-field-group">
                <label>Valor:</label>
                <input type="text" name="spec_valores[]" value="${valor}" class="spec-value" required>
            </div>
            <button type="button" class="btn-remove-spec" onclick="this.parentElement.remove()">×</button>
        </div>
    `;
    specsFields.insertAdjacentHTML('beforeend', fieldHtml);
}

// Evento para cambio de categoría
document.getElementById('categoria_id').addEventListener('change', function() {
    const categoriaId = this.value;
    const tipo = categoriasSpecs[categoriaId] || '';
    generarCamposSpecs(tipo);
});

// Evento para añadir campo de especificación
document.getElementById('add-spec-field').addEventListener('click', agregarCampoSpec);

// Aplicar los estilos
document.head.insertAdjacentHTML('beforeend', `
    <style>
        .specs-container {
            grid-column: 1 / -1;
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-top: 20px;
        }
        
        .specs-container h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .no-specs {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            color: #666;
            font-style: italic;
        }
        
        .spec-field-row {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            align-items: center;
        }
        
        .spec-field-group {
            flex: 1;
        }
        
        .custom-field {
            background-color: #e9ecef;
        }
        
        .btn-remove-spec {
            background: none;
            border: none;
            color: #dc3545;
            font-size: 20px;
            cursor: pointer;
            padding: 0 5px;
        }
    </style>
`);

// Cargar especificaciones existentes
document.addEventListener('DOMContentLoaded', function() {
    const categoriaId = document.getElementById('categoria_id').value;
    const tipo = categoriasSpecs[categoriaId] || '';
    
    if (tipo) {
        generarCamposSpecs(tipo);
    }
    
    // Si no hay especificaciones y no hay campos de especificaciones predefinidos, mostrar mensaje
    const specsFields = document.getElementById('specs-fields');
    if (specsFields.children.length === 0) {
        specsFields.innerHTML = '<div class="no-specs">No hay especificaciones disponibles. Seleccione una categoría o añada manualmente.</div>';
    }
    
    // Mostrar siempre el botón para añadir especificaciones manualmente
    document.getElementById('add-spec-field').style.display = 'inline-block';
});
</script>


</body>
</html>