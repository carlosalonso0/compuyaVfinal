<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Gestión de Sliders';

// Incluir encabezado
include '../includes/header.php';

// Obtener sliders
$query = "SELECT * FROM home_sliders ORDER BY orden ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Gestión de Sliders</h1>
        <a href="crear.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Añadir Slider
        </a>
    </div>

    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-<?php echo isset($_GET['tipo']) ? $_GET['tipo'] : 'info'; ?>" role="alert">
            <?php echo $_GET['mensaje']; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <?php if (count($sliders) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="80">Orden</th>
                                <th width="120">Imagen</th>
                                <th>Título</th>
                                <th>Subtítulo</th>
                                <th>Link</th>
                                <th>Estado</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-sliders">
                            <?php foreach($sliders as $slider): ?>
                                <tr data-id="<?php echo $slider['id']; ?>">
                                    <td>
                                        <span class="handle btn btn-sm btn-light">
                                            <i class="fas fa-arrows-alt"></i>
                                        </span>
                                    </td>
                                    <td>
                                        <img src="../../assets/uploads/sliders/<?php echo $slider['imagen']; ?>" alt="Slider" class="img-thumbnail" style="max-height: 60px;">
                                    </td>
                                    <td><?php echo $slider['titulo']; ?></td>
                                    <td><?php echo $slider['subtitulo']; ?></td>
                                    <td><?php echo $slider['link']; ?></td>
                                    <td>
                                        <?php if ($slider['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="editar.php?id=<?php echo $slider['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-slider" data-id="<?php echo $slider['id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No hay sliders para mostrar. Añade uno haciendo clic en el botón "Añadir Slider".
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar este slider?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="eliminar.php" method="POST" id="delete-form">
                    <input type="hidden" name="id" id="slider-id">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
$(document).ready(function() {
    // Sortable para reordenar sliders
    $("#sortable-sliders").sortable({
        handle: ".handle",
        update: function(event, ui) {
            // Obtener el nuevo orden
            var order = {};
            $("#sortable-sliders tr").each(function(index) {
                order[$(this).data('id')] = index;
            });
            
            // Enviar por AJAX
            $.ajax({
                url: 'actualizar_orden.php',
                type: 'POST',
                data: {
                    order: order,
                    tipo: 'slider'
                },
                success: function(response) {
                    console.log('Orden actualizado');
                }
            });
        }
    });
    
    // Botón eliminar
    $('.delete-slider').click(function() {
        var id = $(this).data('id');
        $('#slider-id').val(id);
    });
});
</script>

<?php
// Incluir pie de página
include '../includes/footer.php';
?>