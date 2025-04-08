<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Añadir Slider';

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

    // Obtener el orden máximo actual
    $query = "SELECT MAX(orden) as max_orden FROM home_sliders";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $orden = ($resultado['max_orden'] !== null) ? $resultado['max_orden'] + 1 : 0;

    // Procesar imagen
    $imagen = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['imagen']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $newname = 'slider-' . time() . '.' . $ext;
            $target = $upload_dir . $newname;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) {
                $imagen = $newname;
            }
        }
    }

    // Validar que se haya subido una imagen
    if (empty($imagen)) {
        $mensaje = "Debes subir una imagen para el slider.";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Insertar slider en la base de datos
            $query = "INSERT INTO home_sliders (titulo, subtitulo, link, imagen, orden, activo) 
                    VALUES (:titulo, :subtitulo, :link, :imagen, :orden, :activo)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':subtitulo', $subtitulo);
            $stmt->bindParam(':link', $link);
            $stmt->bindParam(':imagen', $imagen);
            $stmt->bindParam(':orden', $orden);
            $stmt->bindParam(':activo', $activo);
            
            $stmt->execute();
            
            $mensaje = "Slider añadido correctamente.";
            $tipo_mensaje = "success";
            
            // Redireccionar a la lista de sliders
            header("Location: index.php?mensaje=$mensaje&tipo=$tipo_mensaje");
            exit;
            
        } catch (PDOException $e) {
            $mensaje = "Error al añadir el slider: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

// Incluir encabezado
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Añadir Slider</h1>
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
                            <input type="text" class="form-control" id="titulo" name="titulo">
                            <small class="text-muted">El título principal que se mostrará en el slider</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="subtitulo" class="form-label">Subtítulo</label>
                            <input type="text" class="form-control" id="subtitulo" name="subtitulo">
                            <small class="text-muted">El texto secundario que se mostrará debajo del título</small>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="link" class="form-label">Enlace</label>
                    <input type="text" class="form-control" id="link" name="link">
                    <small class="text-muted">La URL a la que se redirigirá al hacer clic en el slider</small>
                </div>
                
                <div class="mb-3">
                    <label for="imagen" class="form-label">Imagen *</label>
                    <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*" required>
                    <small class="text-muted">Tamaño recomendado: 1920x600 píxeles</small>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="activo" name="activo" checked>
                    <label class="form-check-label" for="activo">Activo</label>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-secondary me-md-2">Limpiar</button>
                    <button type="submit" class="btn btn-primary">Guardar Slider</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir pie de página
include '../includes/footer.php';
?>