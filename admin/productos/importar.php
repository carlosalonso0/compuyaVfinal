<?php
// Incluir autenticación
require_once '../includes/auth.php';

// Título de la página
$page_title = 'Importar Productos';

// Incluir encabezado
include '../includes/header.php';

// Inicializar variables para el log de errores
$error_log = [];
$success_count = 0;
$error_count = 0;
$detailed_errors = [];

// Procesar importación
$mensaje = '';
$tipo_mensaje = '';

// Función para limpiar valores numéricos (manejar comas de miles)
function cleanNumericValue($value) {
    // Eliminar comas de miles y espacios en blanco
    $value = trim(str_replace(',', '', $value));
    
    // Convertir a número de punto flotante
    return $value !== '' ? floatval($value) : null;
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

// Procesar formulario de importación
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    // Verificar si se ha subido un archivo
    if ($_FILES['csv_file']['error'] == 0) {
        $file_name = $_FILES['csv_file']['name'];
        $file_tmp = $_FILES['csv_file']['tmp_name'];
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        // Verificar extensión
        if (strtolower($ext) == 'csv') {
            // Crear un archivo de log
            $log_file = '../logs/import_' . date('Y-m-d_H-i-s') . '.log';
            if (!file_exists('../logs/')) {
                mkdir('../logs/', 0777, true);
            }
            
            // Función para escribir en el log
            function writeLog($message) {
                global $log_file;
                file_put_contents($log_file, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
            }
            
            writeLog('Iniciando proceso de importación');
            writeLog('Archivo: ' . $file_name);
            
            // Definir categorías válidas
            $categorias_validas = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
            writeLog("Categorías válidas para importación: " . implode(', ', $categorias_validas));
            
            // Leer contenido del archivo
            $file_content = file_get_contents($file_tmp);
            
            // Usar str_getcsv con opciones para manejar comillas y comas
            $lines = explode(PHP_EOL, $file_content);
            
            // Remover líneas vacías
            $lines = array_filter($lines);
            
            // Obtener encabezados
            $headers = str_getcsv(array_shift($lines));
            writeLog('Encabezados encontrados: ' . implode(', ', $headers));
            
            // Verificar encabezados requeridos
            $required_headers = ['nombre', 'precio', 'categoria_id'];
            $missing_headers = array_diff($required_headers, $headers);
            
            if (empty($missing_headers)) {
                // Preparar statement para inserción de productos
                $query = "INSERT INTO productos 
                          (codigo, nombre, slug, precio, precio_oferta, categoria_id, 
                           descripcion, descripcion_corta, stock, marca, modelo, 
                           caracteristicas, destacado, nuevo, activo) 
                          VALUES 
                          (:codigo, :nombre, :slug, :precio, :precio_oferta, :categoria_id, 
                           :descripcion, :descripcion_corta, :stock, :marca, :modelo, 
                           :caracteristicas, :destacado, :nuevo, :activo)";
                
                $stmt = $db->prepare($query);
                
                // Procesar cada fila
                foreach ($lines as $row => $line) {
                    // Saltar líneas vacías
                    if (trim($line) === '') continue;
                    
                    // Parsear la línea actual
                    $data = str_getcsv($line);
                    
                    // Asegurar que tenga el mismo número de campos que los encabezados
                    $data = array_slice($data, 0, count($headers));
                    while (count($data) < count($headers)) {
                        $data[] = '';
                    }
                    
                    // Crear array asociativo
                    $product_data = array_combine($headers, $data);
                    
                    try {
                        // Datos básicos
                        $nombre = trim($product_data['nombre'] ?? '');
                        $slug = generateSlug($nombre);
                        $precio = cleanNumericValue($product_data['precio'] ?? 0);
                        
                        // Validar categoría
                        $categoria_id = intval(preg_replace('/[^0-9]/', '', $product_data['categoria_id'] ?? '0'));
                        
                        // Verificar categoría válida
                        if (!in_array($categoria_id, $categorias_validas)) {
                            $categoria_id = $categorias_validas[0]; // Default a Periféricos
                            $detailed_errors[] = "Fila " . ($row + 2) . ": Categoría no válida. Usando categoría por defecto.";
                        }
                        
                        // Campos opcionales
                        $precio_oferta = !empty($product_data['precio_oferta']) ? 
                            cleanNumericValue($product_data['precio_oferta']) : 
                            null;
                        $descripcion = $product_data['descripcion'] ?? '';
                        $descripcion_corta = $product_data['descripcion_corta'] ?? '';
                        $stock = intval($product_data['stock'] ?? 0);
                        $marca = $product_data['marca'] ?? '';
                        $modelo = $product_data['modelo'] ?? '';
                        $caracteristicas = $product_data['caracteristicas'] ?? '';
                        
                        // Valores booleanos
                        $destacado = !empty($product_data['destacado']) && 
                            ($product_data['destacado'] === '1' || 
                             strtolower($product_data['destacado']) === 'true') ? 1 : 0;
                        
                        $nuevo = !empty($product_data['nuevo']) && 
                            ($product_data['nuevo'] === '1' || 
                             strtolower($product_data['nuevo']) === 'true') ? 1 : 0;
                        
                        $activo = !isset($product_data['activo']) || 
                            $product_data['activo'] === '1' || 
                            strtolower($product_data['activo']) === 'true' ? 1 : 0;
                        
                        // Generar código único
                        $codigo = strtoupper(substr(str_replace(' ', '', $nombre), 0, 3)) . 
                            '-' . substr(md5(time() . $row), 0, 6);
                        
                        // Validaciones
                        $validation_errors = [];
                        
                        if (empty($nombre)) {
                            $validation_errors[] = 'Nombre es obligatorio';
                        }
                        
                        if ($precio <= 0) {
                            $validation_errors[] = 'Precio debe ser mayor a 0';
                        }
                        
                        // Si hay errores, registrar y continuar con la siguiente fila
                        if (!empty($validation_errors)) {
                            $error_message = "Fila " . ($row + 2) . ": " . implode(', ', $validation_errors);
                            writeLog($error_message);
                            $detailed_errors[] = $error_message;
                            $error_count++;
                            continue;
                        }
                        
                        // Bind y ejecutar
                        $stmt->bindParam(':codigo', $codigo);
                        $stmt->bindParam(':nombre', $nombre);
                        $stmt->bindParam(':slug', $slug);
                        $stmt->bindParam(':precio', $precio);
                        $stmt->bindParam(':precio_oferta', $precio_oferta);
                        $stmt->bindParam(':categoria_id', $categoria_id);
                        $stmt->bindParam(':descripcion', $descripcion);
                        $stmt->bindParam(':descripcion_corta', $descripcion_corta);
                        $stmt->bindParam(':stock', $stock);
                        $stmt->bindParam(':marca', $marca);
                        $stmt->bindParam(':modelo', $modelo);
                        $stmt->bindParam(':caracteristicas', $caracteristicas);
                        $stmt->bindParam(':destacado', $destacado);
                        $stmt->bindParam(':nuevo', $nuevo);
                        $stmt->bindParam(':activo', $activo);
                        
                        // Ejecutar inserción
                        $result = $stmt->execute();
                        
                        if ($result) {
                            $success_count++;
                            writeLog("Producto importado: $nombre");
                        } else {
                            $error_count++;
                            writeLog("Error al importar producto: $nombre");
                        }
                        
                    } catch (Exception $e) {
                        $error_count++;
                        $detailed_errors[] = "Fila " . ($row + 2) . ": " . $e->getMessage();
                        writeLog("Error en fila " . ($row + 2) . ": " . $e->getMessage());
                    }
                }
                
                // Resumen de importación
                writeLog("Proceso de importación completado: $success_count productos importados, $error_count errores");
                
                if ($success_count > 0) {
                    $mensaje = "Se importaron correctamente $success_count productos.";
                    $tipo_mensaje = $error_count > 0 ? 'warning' : 'success';
                } else {
                    $mensaje = "No se pudo importar ningún producto.";
                    $tipo_mensaje = 'danger';
                }
            } else {
                $missing = implode(', ', $missing_headers);
                $mensaje = "El archivo CSV no tiene los encabezados requeridos: $missing";
                $tipo_mensaje = 'danger';
                writeLog("ERROR: Faltan encabezados requeridos: $missing");
            }
        } else {
            $mensaje = "El archivo debe ser CSV.";
            $tipo_mensaje = 'danger';
        }
    } else {
        $mensaje = "Error al subir el archivo.";
        $tipo_mensaje = 'danger';
    }
}
?>

<!-- Contenido HTML (igual que en versiones anteriores) -->
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

    <?php if (!empty($detailed_errors)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="m-0">Detalles de los errores</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <?php foreach ($detailed_errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
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
                        <li><strong>precio</strong>: Precio en soles</li>
                        <li><strong>categoria_id</strong>: ID de la categoría</li>
                    </ul>
                    
                    <h6 class="font-weight-bold">IDs de Categoría Válidos:</h6>
                    <ul>
                        <li>1: Periféricos</li>
                        <li>2: Monitores</li>
                        <li>3: Laptops</li>
                        <li>4: Impresoras</li>
                        <li>5: Componentes</li>
                        <li>6: PCs Armadas</li>
                        <li>7: Teclados</li>
                        <li>8: Mouse</li>
                        <li>9: Tarjetas de Video</li>
                        <li>10: Procesadores</li>
                    </ul>
                    <h6 class="font-weight-bold">Campos opcionales:</h6>
                    <ul>
                        <li><strong>descripcion</strong>: Descripción completa</li>
                        <li><strong>descripcion_corta</strong>: Descripción resumida</li>
                        <li><strong>precio_oferta</strong>: Precio de oferta</li>
                        <li><strong>stock</strong>: Cantidad disponible</li>
                        <li><strong>marca</strong>: Marca del producto</li>
                        <li><strong>modelo</strong>: Modelo específico</li>
                        <li><strong>caracteristicas</strong>: Características principales</li>
                        <li><strong>destacado</strong>: true/false (producto destacado)</li>
                        <li><strong>nuevo</strong>: true/false (producto nuevo)</li>
                        <li><strong>activo</strong>: true (siempre true)</li>
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
            'NVIDIA GeForce RTX 4060 TUF Gaming 8GB', '1,599.00', '9', 
            'Tarjeta de video de última generación con capacidades de ray tracing y alto rendimiento en juegos.', 
            'Potente tarjeta gráfica para gaming de alta calidad', '', '20', 'NVIDIA', 'RTX 4060', 
            'Memoria 8GB GDDR6\nPCIe 4.0\nRay Tracing\nDLSS', 'true', 'true', 'true'
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
                            </document_content>
</invoke>