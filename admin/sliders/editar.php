<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Editar Slider';

// Verificar que se ha proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$slider_id = intval($_GET['id']);

// Obtener información del slider
$query = "SELECT * FROM home_sliders WHERE id = :id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $slider_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: index.php');
    exit;
}

$slider = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar y crear directorio de uploads si no existe
    $upload_dir = "../../assets/uploads/sliders/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Recoger datos del formulario
    $titulo = trim($_POST['titulo'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Procesar imagen
    $imagen = $slider['imagen']; // Mantener la imagen actual por defecto
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['imagen']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $newname = 'slider-' . time() . '.' . $ext;
            $target = $upload_dir . $newname;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) {
                // Eliminar la imagen anterior si existe
                if (!empty($slider['imagen'])) {
                    $imagen_anterior = $upload_dir . $slider['imagen'];
                    if (file_exists($imagen_anterior)) {
                        unlink($imagen_anterior);
                    }
                }
                $imagen = $newname;
            }
        }
    }

    try {
        // Actualizar slider en la base de datos
        $query = "UPDATE home_sliders SET 
                 titulo = :titulo, 
                 subtitulo = :subtitulo, 
                 link = :link, 
                 imagen = :imagen,
                 activo = :activo
                 WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':subtitulo', $subtitulo);
        $stmt->bindParam(':link', $link);
        $stmt->bindParam(':imagen', $imagen);
        $stmt->bindParam(':activo', $activo);
        $stmt->bindParam(':id', $slider_id);
        
        $stmt->execute();
        
        $mensaje = "Slider actualizado correctamente.";
        $tipo_mensaje = "success";
        
        // Actualizar la información del slider para mostrar los cambios
        $query = "SELECT * FROM home_sliders WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $slider_id);
        $stmt->execute();
        $slider = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar el slider: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Incluir encabezado
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Editar Slider</h1>
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
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo $slider['titulo']; ?>">
                            <small class="text-muted">El título principal que se mostrará en el slider</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="subtitulo" class="form-label">Subtítulo</label>
                            <input type="text" class="form-control" id="subtitulo" name="subtitulo" value="<?php echo $slider['subtitulo']; ?>">
                            <small class="text-muted">El texto secundario que se mostrará debajo del título</small>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="link" class="form-label">Enlace</label>
                    <input type="text" class="form-control" id="link" name="link" value="<?php echo $slider['link']; ?>">
                    <small class="text-muted">La URL a la que se redirigirá al hacer clic en el slider</small>
                </div>
                
                <div class="mb-3">
                    <label for="imagen" class="form-label">Imagen</label>
                    <div class="input-group mb-3">
                        <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#imagenModal">
                            Ver actual
                        </button>
                    </div>
                    <small class="text-muted">Tamaño recomendado: 1920x600 píxeles. Deja en blanco para mantener la imagen actual.</small>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="activo" name="activo" <?php echo $slider['activo'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="activo">Activo</label>
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
<div class="modal fade" id="imagenModal" tabindex="-1" aria-labelledby="imagenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagenModalLabel">Imagen Actual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="../../assets/uploads/sliders/<?php echo $slider['imagen']; ?>" alt="Slider" class="img-fluid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir pie de página
include '../includes/footer.php';
?>