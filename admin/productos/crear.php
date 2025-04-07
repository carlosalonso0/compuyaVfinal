<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Añadir Producto';

// Obtener categorías para el formulario
$query = "SELECT * FROM categorias WHERE activo = true ORDER BY nombre ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    // Generar código único para el producto
    $codigo = strtoupper(substr(str_replace(' ', '', $nombre), 0, 3)) . '-' . substr(md5(time()), 0, 6);

    // Procesar imagen principal
    $imagen_principal = '';
    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['imagen_principal']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $newname = $slug . '-' . time() . '.' . $ext;
            $target = $upload_dir . $newname;
            
            if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $target)) {
                $imagen_principal = $newname;
            }
        }
    }

    try {
        // Insertar producto en la base de datos
        $query = "INSERT INTO productos 
                  (codigo, nombre, slug, descripcion, descripcion_corta, precio, precio_oferta, 
                   stock, imagen_principal, marca, modelo, caracteristicas, destacado, nuevo, activo, categoria_id) 
                  VALUES 
                  (:codigo, :nombre, :slug, :descripcion, :descripcion_corta, :precio, :precio_oferta, 
                   :stock, :imagen_principal, :marca, :modelo, :caracteristicas, :destacado, :nuevo, :activo, :categoria_id)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':codigo', $codigo);
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
        
        $stmt->execute();
        
        $producto_id = $db->lastInsertId();
        
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
        
        // Procesar especificaciones
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
        
        $mensaje = "Producto añadido correctamente.";
        $tipo_mensaje = "success";
        
        // Redireccionar a la lista de productos
        header("Location: index.php");
        exit;
        
    } catch (PDOException $e) {
        $mensaje = "Error al añadir el producto: " . $e->getMessage();
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
        <h1 class="h3">Añadir Producto</h1>
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
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="categoria_id" class="form-label">Categoría *</label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">Seleccionar categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>">
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
                            <input type="text" class="form-control" id="marca" name="marca">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modelo" class="form-label">Modelo</label>
                            <input type="text" class="form-control" id="modelo" name="modelo">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="descripcion_corta" class="form-label">Descripción Corta</label>
                            <textarea class="form-control" id="descripcion_corta" name="descripcion_corta" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción Completa</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="5"></textarea>
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
                            <input type="number" step="0.01" class="form-control" id="precio" name="precio" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="precio_oferta" class="form-label">Precio de Oferta (S/)</label>
                            <input type="number" step="0.01" class="form-control" id="precio_oferta" name="precio_oferta">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="stock" class="form-label">Stock *</label>
                            <input type="number" class="form-control" id="stock" name="stock" value="0" required>
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
                            <textarea class="form-control" id="caracteristicas" name="caracteristicas" rows="3" placeholder="Cada característica en una línea"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Especificaciones -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="border-bottom pb-2">Especificaciones Técnicas</h5>
                        <div id="specifications-container">
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
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="imagen_principal" class="form-label">Imagen Principal</label>
                            <input type="file" class="form-control" id="imagen_principal" name="imagen_principal" accept="image/*">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="imagenes" class="form-label">Imágenes Adicionales</label>
                            <input type="file" class="form-control" id="imagenes" name="imagenes[]" multiple accept="image/*">
                        </div>
                    </div>
                </div>

                <!-- Opciones -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="border-bottom pb-2">Opciones</h5>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="destacado" name="destacado">
                            <label class="form-check-label" for="destacado">
                                Producto Destacado
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="nuevo" name="nuevo" checked>
                            <label class="form-check-label" for="nuevo">
                                Etiqueta "Nuevo"
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                            <label class="form-check-label" for="activo">
                                Producto Activo
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-secondary me-md-2">Limpiar</button>
                    <button type="submit" class="btn btn-primary">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

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