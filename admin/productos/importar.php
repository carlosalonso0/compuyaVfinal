<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Importar Productos';

// Incluir encabezado
include '../includes/header.php';

// Procesar importación
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    // Verificar si se ha subido un archivo
    if ($_FILES['csv_file']['error'] == 0) {
        $file_name = $_FILES['csv_file']['name'];
        $file_tmp = $_FILES['csv_file']['tmp_name'];
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        // Verificar extensión
        if (strtolower($ext) == 'csv') {
            // Abrir el archivo
            $handle = fopen($file_tmp, 'r');
            
            if ($handle !== FALSE) {
                // Leer la primera línea (encabezados)
                $headers = fgetcsv($handle, 0, ',');
                
                // Verificar encabezados requeridos
                $required_headers = ['nombre', 'precio', 'categoria_id'];
                $missing_headers = array_diff($required_headers, $headers);
                
                if (empty($missing_headers)) {
                    // Procesar el archivo
                    $row = 1; // Empezamos en 1 porque la fila 0 son los encabezados
                    $imported = 0;
                    $errors = 0;
                    
                    while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
                        $row++;
                        
                        // Crear array asociativo con los datos
                        $product_data = [];
                        foreach ($headers as $index => $header) {
                            $product_data[$header] = isset($data[$index]) ? $data[$index] : '';
                        }
                        
                        // Validar datos requeridos
                        if (empty($product_data['nombre']) || empty($product_data['precio']) || empty($product_data['categoria_id'])) {
                            $errors++;
                            continue;
                        }
                        
                        // Preparar datos para inserción
                        $nombre = trim($product_data['nombre']);
                        $slug = generateSlug($nombre);
                        $descripcion = isset($product_data['descripcion']) ? trim($product_data['descripcion']) : '';
                        $descripcion_corta = isset($product_data['descripcion_corta']) ? trim($product_data['descripcion_corta']) : '';
                        $precio = floatval($product_data['precio']);
                        $precio_oferta = isset($product_data['precio_oferta']) && !empty($product_data['precio_oferta']) ? floatval($product_data['precio_oferta']) : null;
                        $stock = isset($product_data['stock']) ? intval($product_data['stock']) : 0;
                        $marca = isset($product_data['marca']) ? trim($product_data['marca']) : '';
                        $modelo = isset($product_data['modelo']) ? trim($product_data['modelo']) : '';
                        $caracteristicas = isset($product_data['caracteristicas']) ? trim($product_data['caracteristicas']) : '';
                        $destacado = isset($product_data['destacado']) && ($product_data['destacado'] == '1' || strtolower($product_data['destacado']) == 'true') ? 1 : 0;
                        $nuevo = isset($product_data['nuevo']) && ($product_data['nuevo'] == '1' || strtolower($product_data['nuevo']) == 'true') ? 1 : 0;
                        $activo = isset($product_data['activo']) ? (($product_data['activo'] == '1' || strtolower($product_data['activo']) == 'true') ? 1 : 0) : 1;
                        $categoria_id = intval($product_data['categoria_id']);
                        
                        // Generar código único para el producto
                        $codigo = strtoupper(substr(str_replace(' ', '', $nombre), 0, 3)) . '-' . substr(md5(time() . $row), 0, 6);
                        
                        try {
                            // Insertar producto en la base de datos
                            $query = "INSERT INTO productos 
                                      (codigo, nombre, slug, descripcion, descripcion_corta, precio, precio_oferta, 
                                       stock, marca, modelo, caracteristicas, destacado, nuevo, activo, categoria_id) 
                                      VALUES 
                                      (:codigo, :nombre, :slug, :descripcion, :descripcion_corta, :precio, :precio_oferta, 
                                       :stock, :marca, :modelo
:stock, :marca, :modelo, :caracteristicas, :destacado, :nuevo, :activo, :categoria_id)";
                           
                           $stmt = $db->prepare($query);
                           $stmt->bindParam(':codigo', $codigo);
                           $stmt->bindParam(':nombre', $nombre);
                           $stmt->bindParam(':slug', $slug);
                           $stmt->bindParam(':descripcion', $descripcion);
                           $stmt->bindParam(':descripcion_corta', $descripcion_corta);
                           $stmt->bindParam(':precio', $precio);
                           $stmt->bindParam(':precio_oferta', $precio_oferta);
                           $stmt->bindParam(':stock', $stock);
                           $stmt->bindParam(':marca', $marca);
                           $stmt->bindParam(':modelo', $modelo);
                           $stmt->bindParam(':caracteristicas', $caracteristicas);
                           $stmt->bindParam(':destacado', $destacado);
                           $stmt->bindParam(':nuevo', $nuevo);
                           $stmt->bindParam(':activo', $activo);
                           $stmt->bindParam(':categoria_id', $categoria_id);
                           
                           $stmt->execute();
                           $imported++;
                           
                       } catch (PDOException $e) {
                           $errors++;
                       }
                   }
                   
                   fclose($handle);
                   
                   if ($imported > 0) {
                       $mensaje = "Se importaron correctamente $imported productos. Errores: $errors.";
                       $tipo_mensaje = "success";
                   } else {
                       $mensaje = "No se pudo importar ningún producto. Errores: $errors.";
                       $tipo_mensaje = "danger";
                   }
               } else {
                   $mensaje = "El archivo CSV no tiene los encabezados requeridos: " . implode(', ', $missing_headers);
                   $tipo_mensaje = "danger";
               }
           } else {
               $mensaje = "No se pudo abrir el archivo.";
               $tipo_mensaje = "danger";
           }
       } else {
           $mensaje = "El archivo debe ser CSV.";
           $tipo_mensaje = "danger";
       }
   } else {
       $mensaje = "Error al subir el archivo.";
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
?>

<div class="container-fluid">
   <div class="d-flex justify-content-between align-items-center mb-4">
       <h1 class="h3">Importar Productos</h1>
       <a href="index.php" class="btn btn-secondary">
           <i class="fas fa-arrow-left"></i> Volver
       </a>
   </div>

   <?php if (!empty($mensaje)): ?>
       <div class="alert alert-<?php echo $tipo_mensaje; ?>" role="alert">
           <?php echo $mensaje; ?>
       </div>
   <?php endif; ?>

   <div class="row">
       <div class="col-md-6">
           <div class="card shadow mb-4">
               <div class="card-header py-3">
                   <h6 class="m-0 font-weight-bold">Subir Archivo CSV</h6>
               </div>
               <div class="card-body">
                   <form action="" method="POST" enctype="multipart/form-data">
                       <div class="mb-3">
                           <label for="csv_file" class="form-label">Archivo CSV</label>
                           <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                       </div>
                       <div class="d-grid">
                           <button type="submit" class="btn btn-primary">Importar Productos</button>
                       </div>
                   </form>
               </div>
           </div>
       </div>
       
       <div class="col-md-6">
           <div class="card shadow mb-4">
               <div class="card-header py-3">
                   <h6 class="m-0 font-weight-bold">Instrucciones</h6>
               </div>
               <div class="card-body">
                   <p>Para importar productos, crea un archivo CSV con los siguientes encabezados:</p>
                   
                   <h6 class="font-weight-bold">Campos requeridos:</h6>
                   <ul>
                       <li><strong>nombre</strong>: Nombre del producto</li>
                       <li><strong>precio</strong>: Precio en soles (usar punto como separador decimal)</li>
                       <li><strong>categoria_id</strong>: ID de la categoría a la que pertenece el producto</li>
                   </ul>
                   
                   <h6 class="font-weight-bold">Campos opcionales:</h6>
                   <ul>
                       <li><strong>descripcion</strong>: Descripción completa del producto</li>
                       <li><strong>descripcion_corta</strong>: Descripción resumida</li>
                       <li><strong>precio_oferta</strong>: Precio de oferta si existe</li>
                       <li><strong>stock</strong>: Cantidad disponible (por defecto 0)</li>
                       <li><strong>marca</strong>: Marca del producto</li>
                       <li><strong>modelo</strong>: Modelo del producto</li>
                       <li><strong>caracteristicas</strong>: Características principales</li>
                       <li><strong>destacado</strong>: Si es un producto destacado (1 o true para sí)</li>
                       <li><strong>nuevo</strong>: Si es un producto nuevo (1 o true para sí)</li>
                       <li><strong>activo</strong>: Si el producto está activo (1 o true para sí, por defecto)</li>
                   </ul>
                   
                   <p class="mb-0"><a href="#" class="btn btn-sm btn-info" id="download-template">Descargar Plantilla CSV</a></p>
               </div>
           </div>
       </div>
   </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
   // Generar y descargar plantilla CSV
   document.getElementById('download-template').addEventListener('click', function(e) {
       e.preventDefault();
       
       // Encabezados de la plantilla
       const headers = ['nombre', 'precio', 'categoria_id', 'descripcion', 'descripcion_corta', 
                        'precio_oferta', 'stock', 'marca', 'modelo', 'caracteristicas', 
                        'destacado', 'nuevo', 'activo'];
       
       // Datos de ejemplo
       const exampleData = [
           'NVIDIA GeForce RTX 4060 OC 8GB', '1799.00', '9', 'Descripción completa del producto...', 
           'Tarjeta gráfica para gaming de alto rendimiento', '', '10', 'NVIDIA', 'GeForce RTX 4060', 
           'Ray Tracing\nDLSS 3\n8GB GDDR6', '1', '1', '1'
       ];
       
       // Crear contenido CSV
       let csvContent = headers.join(',') + '\n';
       csvContent += exampleData.join(',');
       
       // Crear y descargar archivo
       const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
       const link = document.createElement('a');
       const url = URL.createObjectURL(blob);
       link.setAttribute('href', url);
       link.setAttribute('download', 'plantilla_productos.csv');
       link.style.visibility = 'hidden';
       document.body.appendChild(link);
       link.click();
       document.body.removeChild(link);
   });
});
</script>

<?php
// Incluir pie de página
include '../includes/footer.php';
?>