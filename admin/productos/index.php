<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Gestión de Productos';

// Incluir encabezado
include '../includes/header.php';

// Obtener todos los productos
$query = "SELECT p.*, c.nombre as categoria_nombre 
          FROM productos p
          LEFT JOIN categorias c ON p.categoria_id = c.id
          ORDER BY p.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Gestión de Productos</h1>
        <div>
            <a href="importar.php" class="btn btn-info me-2">
                <i class="fas fa-file-import"></i> Importar
            </a>
            <a href="crear.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Añadir Producto
            </a>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Gestión de Productos</h1>
        <div>
            <a href="importar.php" class="btn btn-info me-2">
                <i class="fas fa-file-import"></i> Importar
            </a>
            <a href="crear.php" class="btn btn-primary me-2">
                <i class="fas fa-plus-circle"></i> Añadir Producto
            </a>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                <i class="fas fa-trash-alt"></i> Eliminar Todos
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($productos as $producto): ?>
                            <tr>
                                <td><?php echo $producto['id']; ?></td>
                                <td>
                                    <?php if(!empty($producto['imagen_principal'])): ?>
                                        <img src="../../assets/uploads/productos/<?php echo $producto['imagen_principal']; ?>" alt="<?php echo $producto['nombre']; ?>" width="50">
                                    <?php else: ?>
                                        <img src="../../assets/img/no-image.png" alt="Sin imagen" width="50">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $producto['nombre']; ?>
                                    <?php if($producto['destacado']): ?>
                                        <span class="badge bg-success ms-1">Destacado</span>
                                    <?php endif; ?>
                                    <?php if($producto['nuevo']): ?>
                                        <span class="badge bg-info ms-1">Nuevo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $producto['categoria_nombre']; ?></td>
                                <td>
                                    <?php if(!empty($producto['precio_oferta'])): ?>
                                        <span class="text-decoration-line-through text-muted">S/ <?php echo number_format($producto['precio'], 2); ?></span>
                                       <span class="text-danger">S/ <?php echo number_format($producto['precio_oferta'], 2); ?></span>
                                   <?php else: ?>
                                       S/ <?php echo number_format($producto['precio'], 2); ?>
                                   <?php endif; ?>
                               </td>
                               <td>
                                   <?php if($producto['stock'] <= 0): ?>
                                       <span class="badge bg-danger">Agotado</span>
                                   <?php elseif($producto['stock'] <= 5): ?>
                                       <span class="badge bg-warning text-dark"><?php echo $producto['stock']; ?></span>
                                   <?php else: ?>
                                       <span class="badge bg-success"><?php echo $producto['stock']; ?></span>
                                   <?php endif; ?>
                               </td>
                               <td>
                                   <?php if($producto['activo']): ?>
                                       <span class="badge bg-success">Activo</span>
                                   <?php else: ?>
                                       <span class="badge bg-secondary">Inactivo</span>
                                   <?php endif; ?>
                               </td>
                               <td>
                                   <a href="editar.php?id=<?php echo $producto['id']; ?>" class="btn btn-sm btn-primary">
                                       <i class="fas fa-edit"></i>
                                   </a>
                                   <a href="../../producto/<?php echo $producto['slug']; ?>" target="_blank" class="btn btn-sm btn-info">
                                       <i class="fas fa-eye"></i>
                                   </a>
                                   <button type="button" class="btn btn-sm btn-danger delete-product" data-id="<?php echo $producto['id']; ?>" data-name="<?php echo $producto['nombre']; ?>">
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
               ¿Estás seguro de que deseas eliminar el producto <span id="product-name"></span>?
           </div>
           <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
               <form action="eliminar.php" method="POST" id="delete-form">
                   <input type="hidden" name="id" id="product-id">
                   <button type="submit" class="btn btn-danger">Eliminar</button>
               </form>
           </div>
       </div>
   </div>
</div>
<!-- Modal de confirmación para eliminar todos los productos -->
<div class="modal fade" id="deleteAllModal" tabindex="-1" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAllModalLabel">Confirmar eliminación masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger fw-bold">¡ADVERTENCIA! Esta acción eliminará TODOS los productos de la base de datos.</p>
                    <p>Esta operación no se puede deshacer. ¿Estás seguro de que deseas continuar?</p>
                    <div class="alert alert-warning">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmDeleteAll" required>
                            <label class="form-check-label" for="confirmDeleteAll">
                                Entiendo que todos los productos serán eliminados permanentemente
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="eliminar_todos.php" method="POST" id="delete-all-form">
                        <button type="submit" class="btn btn-danger" id="btnDeleteAll" disabled>Eliminar Todos los Productos</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<script>
   document.addEventListener('DOMContentLoaded', function() {
       // Modal de confirmación para eliminar producto
       const deleteButtons = document.querySelectorAll('.delete-product');
       const productNameElement = document.getElementById('product-name');
       const productIdInput = document.getElementById('product-id');
       const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

       deleteButtons.forEach(button => {
           button.addEventListener('click', function() {
               const productId = this.getAttribute('data-id');
               const productName = this.getAttribute('data-name');
               
               productNameElement.textContent = productName;
               productIdInput.value = productId;
               
               deleteModal.show();
           });
       });
   });

    // Habilitar/deshabilitar botón de eliminar todos según checkbox
const confirmDeleteAllCheckbox = document.getElementById('confirmDeleteAll');
const btnDeleteAll = document.getElementById('btnDeleteAll');

if (confirmDeleteAllCheckbox && btnDeleteAll) {
    confirmDeleteAllCheckbox.addEventListener('change', function() {
        btnDeleteAll.disabled = !this.checked;
    });
}
</script>

<?php
// Incluir pie de página
include '../includes/footer.php';
?>