<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Gestión de Categorías';

// Incluir encabezado
include '../includes/header.php';

// Obtener todas las categorías
$query = "SELECT c1.*, c2.nombre as categoria_padre_nombre 
          FROM categorias c1
          LEFT JOIN categorias c2 ON c1.categoria_padre_id = c2.id
          ORDER BY c1.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Gestión de Categorías</h1>
        <a href="crear.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Añadir Categoría
        </a>
    </div>

    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-<?php echo isset($_GET['tipo']) ? $_GET['tipo'] : 'info'; ?>" role="alert">
            <?php echo $_GET['mensaje']; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Slug</th>
                            <th>Categoría Padre</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categorias as $categoria): ?>
                            <tr>
                                <td><?php echo $categoria['id']; ?></td>
                                <td><?php echo $categoria['nombre']; ?></td>
                                <td><?php echo $categoria['slug']; ?></td>
                                <td>
                                    <?php if (!is_null($categoria['categoria_padre_id'])): ?>
                                        <?php echo $categoria['categoria_padre_nombre']; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Categoría Principal</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($categoria['activo']): ?>
                                        <span class="badge bg-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="editar.php?id=<?php echo $categoria['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../../categoria/<?php echo $categoria['slug']; ?>" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger delete-category" data-id="<?php echo $categoria['id']; ?>" data-name="<?php echo $categoria['nombre']; ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
                <p>¿Estás seguro de que deseas eliminar la categoría <span id="category-name"></span>?</p>
                <p class="text-danger"><strong>Atención:</strong> Eliminar una categoría también eliminará todas sus subcategorías y podría afectar a los productos asociados.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="eliminar.php" method="POST" id="delete-form">
                    <input type="hidden" name="id" id="category-id">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Modal de confirmación para eliminar categoría
        const deleteButtons = document.querySelectorAll('.delete-category');
        const categoryNameElement = document.getElementById('category-name');
        const categoryIdInput = document.getElementById('category-id');
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.getAttribute('data-id');
                const categoryName = this.getAttribute('data-name');
                
                categoryNameElement.textContent = categoryName;
                categoryIdInput.value = categoryId;
                
                deleteModal.show();
            });
        });
    });
</script>

<?php
// Incluir pie de página
include '../includes/footer.php';
?>