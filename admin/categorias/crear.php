<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Añadir Categoría';

// Obtener categorías para el selector de categoría padre
$query = "SELECT * FROM categorias WHERE activo = true AND categoria_padre_id IS NULL ORDER BY nombre ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias_padre = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar y crear directorio de uploads si no existe
    $upload_dir = "../../assets/uploads/categorias/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Recoger datos del formulario
    $nombre = trim($_POST['nombre']);
    $slug = generateSlug($nombre);
    $descripcion = trim($_POST['descripcion']);
    $categoria_padre_id = !empty($_POST['categoria_padre_id']) ? intval($_POST['categoria_padre_id']) : null;
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Procesar imagen
    $imagen = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['imagen']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $newname = 'categoria-' . $slug . '-' . time() . '.' . $ext;
            $target = $upload_dir . $newname;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) {
                $imagen = $newname;
            }
        }
    }

    try {
        // Insertar categoría en la base de datos
        $query = "INSERT INTO categorias (nombre, slug, descripcion, imagen, categoria_padre_id, activo) 
                  VALUES (:nombre, :slug, :descripcion, :imagen, :categoria_padre_id, :activo)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':imagen', $imagen);
        $stmt->bindParam(':categoria_padre_id', $categoria_padre_id);
        $stmt->bindParam(':activo', $activo);
        
        $stmt->execute();
        
        $mensaje = "Categoría añadida correctamente.";
        $tipo_mensaje = "success";
        
        // Redireccionar a la lista de categorías
        header("Location: index.php?mensaje=Categoría añadida correctamente&tipo=success");
        exit;
        
    } catch (PDOException $e) {
        $mensaje = "Error al añadir la categoría: " . $e->getMessage();
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
        <h1 class="h3">Añadir Categoría</h1>
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
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Categoría *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="categoria_padre_id" class="form-label">Categoría Padre</label>
                            <select class="form-select" id="categoria_padre_id" name="categoria_padre_id">
                                <option value="">Ninguna (Categoría Principal)</option>
                                <?php foreach ($categorias_padre as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>">
                                        <?php echo $categoria['nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Deja en blanco para crear una categoría principal</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen de la Categoría</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 mt-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                                <label class="form-check-label" for="activo">
                                    Categoría Activa
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-secondary me-md-2">Limpiar</button>
                    <button type="submit" class="btn btn-primary">Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir pie de página
include '../includes/footer.php';
?>