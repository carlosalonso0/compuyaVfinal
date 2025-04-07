<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Editar Categoría';

// Verificar que se ha enviado un ID de categoría
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$categoria_id = intval($_GET['id']);

// Obtener información de la categoría
$query = "SELECT * FROM categorias WHERE id = :id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $categoria_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: index.php');
    exit;
}

$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener categorías padre para el selector
$query = "SELECT * FROM categorias WHERE activo = true AND categoria_padre_id IS NULL AND id != :id ORDER BY nombre ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $categoria_id);
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

    // Validar que no se seleccione a sí misma como categoría padre
    if ($categoria_padre_id == $categoria_id) {
        $mensaje = "Error: Una categoría no puede ser su propia categoría padre.";
        $tipo_mensaje = "danger";
    } else {
        // Procesar imagen
        $imagen = $categoria['imagen']; // Mantener la imagen actual por defecto
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            $filename = $_FILES['imagen']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                $newname = 'categoria-' . $slug . '-' . time() . '.' . $ext;
                $target = $upload_dir . $newname;
                
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) {
                    // Eliminar la imagen anterior si existe
                    if (!empty($categoria['imagen'])) {
                        $imagen_anterior = $upload_dir . $categoria['imagen'];
                        if (file_exists($imagen_anterior)) {
                            unlink($imagen_anterior);
                        }
                    }
                    $imagen = $newname;
                }
            }
        }

        try {
            // Actualizar categoría en la base de datos
            $query = "UPDATE categorias SET 
                      nombre = :nombre, 
                      slug = :slug, 
                      descripcion = :descripcion, 
                      imagen = :imagen, 
                      categoria_padre_id = :categoria_padre_id, 
                      activo = :activo 
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':imagen', $imagen);
            $stmt->bindParam(':categoria_padre_id', $categoria_padre_id);
            $stmt->bindParam(':activo', $activo);
            $stmt->bindParam(':id', $categoria_id);
            
            $stmt->execute();
            
            $mensaje = "Categoría actualizada correctamente.";
            $tipo_mensaje = "success";
            
            // Actualizar la información de la categoría para mostrar los cambios
            $query = "SELECT * FROM categorias WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $categoria_id);
            $stmt->execute();
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar la categoría: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
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
        <h1 class="h3">Editar Categoría: <?php echo $categoria['nombre']; ?></h1>
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
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $categoria['nombre']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="categoria_padre_id" class="form-label">Categoría Padre</label>
                            <select class="form-select" id="categoria_padre_id" name="categoria_padre_id">
                                <option value="">Ninguna (Categoría Principal)</option>
                                <?php foreach ($categorias_padre as $cat_padre): ?>
                                    <option value="<?php echo $cat_padre['id']; ?>" <?php echo ($categoria['categoria_padre_id'] == $cat_padre['id']) ? 'selected' : ''; ?>>
                                        <?php echo $cat_padre['nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Deja en blanco para establecer como categoría principal</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo $categoria['descripcion']; ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen de la Categoría</label>
                            <div class="input-group">
                                <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                                <?php if (!empty($categoria['imagen'])): ?>
                                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#imagenModal">
                                        Ver actual
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($categoria['imagen'])): ?>
                                <small class="text-muted">Deja en blanco para mantener la imagen actual</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 mt-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" <?php echo $categoria['activo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">
                                    Categoría Activa
                                </label>
                            </div>
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

<!-- Modal para ver imagen -->
<?php if (!empty($categoria['imagen'])): ?>
    <div class="modal fade" id="imagenModal" tabindex="-1" aria-labelledby="imagenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imagenModalLabel">Imagen Actual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="../../assets/uploads/categorias/<?php echo $categoria['imagen']; ?>" alt="Imagen de categoría" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// Incluir pie de página
include '../includes/footer.php';
?>