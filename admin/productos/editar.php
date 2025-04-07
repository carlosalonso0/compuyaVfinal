<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Editar Producto';

// Verificar que se ha enviado un ID de producto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$producto_id = intval($_GET['id']);

// Obtener categorías para el formulario
$query = "SELECT * FROM categorias WHERE activo = true ORDER BY nombre ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener información del producto
$query = "SELECT * FROM productos WHERE id = :id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $producto_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: index.php');
    exit;
}

$producto = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener especificaciones del producto
$query = "SELECT * FROM especificaciones WHERE producto_id = :producto_id ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':producto_id', $producto_id);
$stmt->execute();
$especificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener imágenes adicionales del producto
$query = "SELECT * FROM imagenes_producto WHERE producto_id = :producto_id ORDER BY orden ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':producto_id', $producto_id);
$stmt->execute();
$imagenes_adicionales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar y crear directorio de uploads si no existe
    $upload_dir = "../../assets/uploads/productos/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Recoger datos del formulario
    $nombre = trim($_POST['nombre']);
    $slug = generateSlug($nombre);
    $descripcion = trim($_POST['descripcion']);
    $descripcion_corta = trim($_POST['descripcion_corta']);
    $precio = floatval($_POST['precio']);
    $precio_oferta = !empty($_POST['precio_oferta']) ? floatval($_POST['precio_oferta']) : null;
    $stock = intval($_POST['stock']);
    $marca = trim($_POST['marca']);
    $modelo = trim($_POST['modelo']);
    $caracteristicas = trim($_POST['caracteristicas']);
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $nuevo = isset($_POST['nuevo']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;
    $categoria_id = intval($_POST['categoria_id']);

    // Procesar imagen principal
    $imagen_principal = $producto['imagen_principal']; // Mantener la imagen actual por defecto
    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['imagen_principal']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $newname = $slug . '-' . time() . '.' . $ext;
            $target = $upload_dir . $newname;
            
            if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $target)) {
                // Eliminar la imagen anterior si existe
                if (!empty($producto['imagen_principal'])) {
                    $imagen_anterior = $upload_dir . $producto['imagen_principal'];
                    if (file_exists($imagen_anterior)) {
                        unlink($imagen_anterior);
                    }
                }
                $imagen_principal = $newname;
            }
        }
    }

    try {
        // Actualizar producto en la base de datos
        $query = "UPDATE productos SET 
                  nombre = :nombre, 
                  slug = :slug, 
                  descripcion = :descripcion, 
                  descripcion_corta = :descripcion_corta, 
                  precio = :precio, 
                  precio_oferta = :precio_oferta, 
                  stock = :stock, 
                  imagen_principal = :imagen_principal, 
                  marca = :marca, 
                  modelo = :modelo, 
                  caracteristicas = :caracteristicas, 
                  destacado = :destacado, 
                  nuevo = :nuevo, 
                  activo = :activo, 
                  categoria_id = :categoria_id
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':descripcion_corta', $descripcion_corta);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':precio_oferta', $precio_oferta);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':imagen_principal', $imagen_principal);
        $stmt->bindParam(':marca', $marca);
        $stmt->bindParam(':modelo', $modelo);
        $stmt->bindParam(':caracteristicas', $caracteristicas);
        $stmt->bindParam(':destacado', $destacado);
        $stmt->bindParam(':nuevo', $nuevo);
        $stmt->bindParam(':activo', $activo);
        $stmt->bindParam(':categoria_id', $categoria_id);
        $stmt->bindParam(':id', $producto_id);
        
        $stmt->execute();
        
        // Procesar imágenes adicionales
        if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            $imagenes_count = count($_FILES['imagenes']['name']);
            
            for ($i = 0; $i < $imagenes_count; $i++) {
                if ($_FILES['imagenes']['error'][$i] == 0) {
                    $filename = $_FILES['imagenes']['name'][$i];
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    
                    if (in_array(strtolower($ext), $allowed)) {
                        $newname = $slug . '-' . ($i+1) . '-' . time() . '.' . $ext;
                        $target = $upload_dir . $newname;
                        
                        if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$i], $target)) {
                            // Guardar en la tabla de imágenes
                            $query = "INSERT INTO imagenes_producto (producto_id, url_imagen, orden) 
                                      VALUES (:producto_id, :url_imagen, :orden)";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':producto_id', $producto_id);
                            $stmt->bindParam(':url_imagen', $newname);
                            $stmt->bindValue(':orden', $i);
                            $stmt->execute();
                        }
                    }
                }
            }
        }
        
        // Eliminar imágenes marcadas para eliminación
        if (isset($_POST['eliminar_imagen']) && !empty($_POST['eliminar_imagen'])) {
            foreach ($_POST['eliminar_imagen'] as $imagen_id) {
                // Obtener la URL de la imagen
                $query = "SELECT url_imagen FROM imagenes_producto WHERE id = :id AND producto_id = :producto_id LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $imagen_id);
                $stmt->bindParam(':producto_id', $producto_id);
                $stmt->execute();
                $imagen = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($imagen) {
                    // Eliminar archivo físico
                    $imagen_path = $upload_dir . $imagen['url_imagen'];
                    if (file_exists($imagen_path)) {
                        unlink($imagen_path);
                    }
                    
                    // Eliminar registro de la base de datos
                    $query = "DELETE FROM imagenes_producto WHERE id = :id AND producto_id = :producto_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $imagen_id);
                    $stmt->bindParam(':producto_id', $producto_id);
                    $stmt->execute();
                }
            }
        }
        
        // Procesar especificaciones - Primero eliminar las existentes
        $query = "DELETE FROM especificaciones WHERE producto_id = :producto_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':producto_id', $producto_id);
        $stmt->execute();
        
        // Luego añadir las nuevas
        if (isset($_POST['spec_nombre']) && isset($_POST['spec_valor'])) {
            $specs_count = count($_POST['spec_nombre']);
            
            for ($i = 0; $i < $specs_count; $i++) {
                if (!empty($_POST['spec_nombre'][$i]) && !empty($_POST['spec_valor'][$i])) {
                    $spec_nombre = trim($_POST['spec_nombre'][$i]);
                    $spec_valor = trim($_POST['spec_valor'][$i]);
                    
                    $query = "INSERT INTO especificaciones (producto_id, nombre, valor) 
                              VALUES (:producto_id, :nombre, :valor)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':producto_id', $producto_id);
                    $stmt->bindParam(':nombre', $spec_nombre);
                    $stmt->bindParam(':valor', $spec_valor);
                    $stmt->execute();
                }
            }
        }
        
        $mensaje = "Producto actualizado correctamente.";
        $tipo_mensaje = "success";
        
        // Actualizar la información del producto para mostrar los cambios
        $query = "SELECT * FROM productos WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $producto_id);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Actualizar especificaciones
        $query = "SELECT * FROM especificaciones WHERE producto_id = :producto_id ORDER BY id ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':producto_id', $producto_id);
        $stmt->execute();
        $especificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Actualizar imágenes
        $query = "SELECT * FROM imagenes_producto WHERE producto_id = :producto_id ORDER BY orden ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':producto_id', $producto_id);
        $stmt->execute();
        $imagenes_adicionales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar el producto: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Función para generar slug
function generateSlug($text) {
    // Reemplazar caracteres especiales
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterar
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Eliminar caracteres no deseados
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Eliminar duplicados
    $text = preg_replace('~-+~', '-', $text);
    // Convertir a minúsculas
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}

// Incluir encabezado
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Editar Producto: <?php echo $producto['nombre']; ?></h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>" role="alert">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <!-- Información básica -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="border-bottom pb-2">Información Básica</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Producto *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $producto['nombre']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="categoria_id" class="form-label">Categoría *</label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">Seleccionar categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" <?php echo ($producto['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                        <?php echo $categoria['nombre']; ?>
                                        <?php if (!is_null($categoria['categoria_padre_id'])): ?>
                                            (Subcategoría)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="marca" class="form-label">Marca</label>
                            <input type="text" class="form-control" id="marca" name="marca" value="<?php echo $producto['marca']; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modelo" class="form-label">Modelo</label>
                            <input type="text" class="form-control" id="modelo" name="modelo" value="<?php echo $producto['modelo']; ?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="descripcion_corta" class="form-label">Descripción Corta</label>
                            <textarea class="form-control" id="descripcion_corta" name="descripcion_corta" rows="2"><?php echo $producto['descripcion_corta']; ?></textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción Completa</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="5"><?php echo $producto['descripcion']; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Precios y Stock -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="border-bottom pb-2">Precios y Stock</h5>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="precio" class="form-label">Precio (S/) *</label>
                            <input type="number" step="0.01" class="form-control" id="precio" name="precio" value="<?php echo $producto['precio']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="precio_oferta" class="form-label">Precio de Oferta (S/)</label>
                            <input type="number" step="0.01" class="form-control" id="precio_oferta" name="precio_oferta" value="<?php echo $producto['precio_oferta']; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="stock" class="form-label">Stock *</label>
                            <input type="number" class="form-control" id="stock" name="stock" value="<?php echo $producto['stock']; ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Características -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="border-bottom pb-2">Características</h5>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="caracteristicas" class="form-label">Características Principales</label>
                            <textarea class="form-control" id="caracteristicas" name="caracteristicas" rows="3" placeholder="Cada característica en una línea"><?php echo $producto['caracteristicas']; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Especificaciones -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="border-bottom pb-2">Especificaciones Técnicas</h5>
                        <div id="specifications-container">
                            <?php if (count($especificaciones) > 0): ?>
                                <?php foreach ($especificaciones as $index => $spec): ?>
                                    <div class="row spec-row mb-2">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" name="spec_nombre[]" value="<?php echo $spec['nombre']; ?>" placeholder="Nombre (ej: Procesador)">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" name="spec_valor[]" value="<?php echo $spec['valor']; ?>" placeholder="Valor (ej: Intel Core i5)">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger remove-spec"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="row spec-row mb-2">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="spec_nombre[]" placeholder="Nombre (ej: Procesador)">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="spec_valor[]" placeholder="Valor (ej: Intel Core i5)">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger remove-spec"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-info" id="add-spec">
                                <i class="fas fa-plus"></i> Añadir Especificación
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Imágenes -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="border-bottom pb-2">Imágenes</h5>
                    </div>
                    
                    <!-- Imagen Principal -->
                    <div class="col-md-6 mb-3">
                        <label for="imagen_principal" class="form-label">Imagen Principal</label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="imagen_principal" name="imagen_principal" accept="image/*">
                            <?php if (!empty($producto['imagen_principal'])): ?>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#imagenPrincipalModal">
                                    Ver actual
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($producto['imagen_principal'])): ?>
                            <small class="text-muted">Deja en blanco para mantener la imagen actual</small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Imágenes Adicionales -->
                    <div class="col-md-6 mb-3">
                        <label for="imagenes" class="form-label">Añadir Imágenes Adicionales</label>
                        <input type="file" class="form-control" id="imagenes" name="imagenes[]" multiple accept="image/*">
                    </div>
                    
                    <!-- Imágenes Adicionales Actuales -->
                    <?php if (count($imagenes_adicionales) > 0): ?>
                        <div class="col-12 mt-3">
                            <h6>Imágenes Adicionales Actuales</h6>
                            <div class="row">
                                <?php foreach ($imagenes_adicionales as $imagen): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="card">
                                            <img src="../../assets/uploads/productos/<?php echo $imagen['url_imagen']; ?>" class="card-img-top" alt="Imagen adicional" style="height: 150px; object-fit: contain;">
                                            <div class="card-body text-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="eliminar_imagen[]" value="<?php echo $imagen['id']; ?>" id="eliminar_imagen_<?php echo $imagen['id']; ?>">
                                                    <label class="form-check-label" for="eliminar_imagen_<?php echo $imagen['id']; ?>">
                                                        Eliminar
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Opciones -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="border-bottom pb-2">Opciones</h5>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="destacado" name="destacado" <?php echo $producto['destacado'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="destacado">
                                Producto Destacado
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="nuevo" name="nuevo" <?php echo $producto['nuevo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="nuevo">
                                Etiqueta "Nuevo"
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activo">
                                Producto Activo
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="index.php" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ver imagen principal -->
<?php if (!empty($producto['imagen_principal'])): ?>
    <div class="modal fade" id="imagenPrincipalModal" tabindex="-1" aria-labelledby="imagenPrincipalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imagenPrincipalModalLabel">Imagen Principal Actual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="../../assets/uploads/productos/<?php echo $producto['imagen_principal']; ?>" alt="Imagen principal" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Añadir especificación
    document.getElementById('add-spec').addEventListener('click', function() {
        const container = document.getElementById('specifications-container');
        const newRow = document.createElement('div');
        newRow.className = 'row spec-row mb-2';
        newRow.innerHTML = `
            <div class="col-md-5">
                <input type="text" class="form-control" name="spec_nombre[]" placeholder="Nombre (ej: Procesador)">
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" name="spec_valor[]" placeholder="Valor (ej: Intel Core i5)">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger remove-spec"><i class="fas fa-times"></i></button>
            </div>
        `;
        container.appendChild(newRow);

        // Añadir evento a los botones de eliminar
        newRow.querySelector('.remove-spec').addEventListener('click', function() {
            container.removeChild(newRow);
        });
    });

    // Eliminar especificación
    document.querySelectorAll('.remove-spec').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('.spec-row');
            row.parentNode.removeChild(row);
        });
    });
});
</script>

<?php
// Incluir pie de página
include '../includes/footer.php';
?>