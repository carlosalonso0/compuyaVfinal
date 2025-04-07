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
            
            // Pre-procesar el archivo para manejar comas en valores numéricos
            $file_content = file_get_contents($file_tmp);
            $processed_content = '';
            $in_quotes = false;
            $current_char = '';
            $prev_char = '';

            // Recorremos cada carácter del archivo
            for ($i = 0; $i < strlen($file_content); $i++) {
                $current_char = $file_content[$i];
                
                // Si encontramos comillas, cambiamos el estado de in_quotes
                if ($current_char === '"' && $prev_char !== '\\') {
                    $in_quotes = !$in_quotes;
                }
                
                // Si estamos dentro de comillas y hay una coma (posible separador de miles)
                if ($in_quotes && $current_char === ',' && ctype_digit($prev_char) && 
                    $i + 1 < strlen($file_content) && ctype_digit($file_content[$i + 1])) {
                    // Reemplazar la coma por un carácter especial temporalmente
                    $processed_content .= '#COMMA#';
                } else {
                    $processed_content .= $current_char;
                }
                
                $prev_char = $current_char;
            }

            // Guardar el contenido procesado en un archivo temporal
            $temp_file = tempnam(sys_get_temp_dir(), 'csv_import_');
            file_put_contents($temp_file, $processed_content);

            // Ahora abrir el archivo procesado
            $handle = fopen($temp_file, 'r');
            
            if ($handle !== FALSE) {
                // Leer la primera línea (encabezados)
                $headers = fgetcsv($handle, 0, ',', '"', '"');
                if (!$headers) {
                    writeLog('ERROR: No se pudieron leer los encabezados del archivo');
                    $error_log[] = 'No se pudieron leer los encabezados del archivo';
                } else {
                    writeLog('Encabezados encontrados: ' . implode(', ', $headers));
                    
                    // Verificar encabezados requeridos
                    $required_headers = ['nombre', 'precio', 'categoria_id'];
                    $missing_headers = array_diff($required_headers, $headers);
                    
                    if (empty($missing_headers)) {
                        // Procesar el archivo
                        $row = 1; // Empezamos en 1 porque la fila 0 son los encabezados
                        
                        while (($data = fgetcsv($handle, 0, ',', '"', '"')) !== FALSE) {
                            $row++;
                            writeLog("Procesando fila $row");
                            
                            // Verificar que el número de columnas sea consistente
                            if (count($data) != count($headers)) {
                                writeLog("ERROR en fila $row: Número de columnas inconsistente. Se esperaban " . count($headers) . ", se encontraron " . count($data));
                                $detailed_errors[] = "Fila $row: Número de columnas inconsistente. Se esperaban " . count($headers) . ", se encontraron " . count($data);
                                $error_count++;
                                continue;
                            }
                            
                            // Crear array asociativo con los datos
                            $product_data = [];
                            foreach ($headers as $index => $header) {
                                if (isset($data[$index])) {
                                    // Restaurar las comas en los valores
                                    $value = trim($data[$index]);
                                    $value = str_replace('#COMMA#', ',', $value);
                                    $product_data[$header] = $value;
                                } else {
                                    $product_data[$header] = '';
                                }
                            }
                            
                            // Validar datos requeridos
                            $validation_errors = [];
                            
                            if (empty($product_data['nombre'])) {
                                $validation_errors[] = 'El nombre es obligatorio';
                            }
                            
                            if (empty($product_data['precio']) || !is_numeric($product_data['precio'])) {
                                $validation_errors[] = 'El precio debe ser un número válido';
                            }
                            
                            if (empty($product_data['categoria_id']) || !is_numeric($product_data['categoria_id'])) {
                                $validation_errors[] = 'El ID de categoría debe ser un número válido';
                            } else {
                                // Verificar si la categoría existe
                                $query = "SELECT id FROM categorias WHERE id = :id";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':id', $product_data['categoria_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                if ($stmt->rowCount() == 0) {
                                    $validation_errors[] = 'La categoría con ID ' . $product_data['categoria_id'] . ' no existe';
                                }
                            }
                            
                            // Si hay errores de validación, registrarlos y continuar con la siguiente fila
                            if (!empty($validation_errors)) {
                                writeLog("ERROR en fila $row: " . implode(', ', $validation_errors));
                                $detailed_errors[] = "Fila $row: " . implode(', ', $validation_errors);
                                $error_count++;
                                continue;
                            }
                            
                            // Preparar datos para inserción
                            $nombre = $product_data['nombre'];
                            $slug = generateSlug($nombre);
                            $descripcion = isset($product_data['descripcion']) ? $product_data['descripcion'] : '';
                            $descripcion_corta = isset($product_data['descripcion_corta']) ? $product_data['descripcion_corta'] : '';
                            $precio = floatval(cleanNumericValue($product_data['precio']));
                            $precio_oferta = isset($product_data['precio_oferta']) && !empty($product_data['precio_oferta']) ? floatval(cleanNumericValue($product_data['precio_oferta'])) : null;
                            $stock = isset($product_data['stock']) ? intval($product_data['stock']) : 0;
                            $marca = isset($product_data['marca']) ? $product_data['marca'] : '';
                            $modelo = isset($product_data['modelo']) ? $product_data['modelo'] : '';
                            $caracteristicas = isset($product_data['caracteristicas']) ? $product_data['caracteristicas'] : '';
                            
                            // Valores booleanos
                            $destacado = 0;
                            if (isset($product_data['destacado'])) {
                                if ($product_data['destacado'] == '1' || strtolower($product_data['destacado']) == 'true') {
                                    $destacado = 1;
                                }
                            }
                            
                            $nuevo = 0;
                            if (isset($product_data['nuevo'])) {
                                if ($product_data['nuevo'] == '1' || strtolower($product_data['nuevo']) == 'true') {
                                    $nuevo = 1;
                                }
                            }
                            
                            $activo = 1; // Por defecto activo
                            if (isset($product_data['activo'])) {
                                if ($product_data['activo'] == '0' || strtolower($product_data['activo']) == 'false') {
                                    $activo = 0;
                                }
                            }
                            
                            // Generar código único para el producto
                            $codigo = strtoupper(substr(str_replace(' ', '', $nombre), 0, 3)) . '-' . substr(md5(time() . $row), 0, 6);
                            
                            try {
                                // Debug
                                writeLog("Intentando insertar producto: $nombre");
                                
                                // Insertar producto en la base de datos
                                $query = "INSERT INTO productos 
                                          (codigo, nombre, slug, descripcion, descripcion_corta, precio, precio_oferta, 
                                           stock, marca, modelo, caracteristicas, destacado, nuevo, activo, categoria_id) 
                                          VALUES 
                                          (:codigo, :nombre, :slug, :descripcion, :descripcion_corta, :precio, :precio_oferta, 
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
                                $stmt->bindParam(':categoria_id', $product_data['categoria_id']);
                                
                                $result = $stmt->execute();
                                
                                if ($result) {
                                    $success_count++;
                                    writeLog("Producto insertado correctamente: $nombre");
                                } else {
                                    $error_count++;
                                    $error_info = $stmt->errorInfo();
                                    $detailed_errors[] = "Fila $row: Error SQL - " . $error_info[2];
                                    writeLog("Error al insertar producto: " . $error_info[2]);
                                }
                                
                            } catch (PDOException $e) {
                                $error_count++;
                                $detailed_errors[] = "Fila $row: Error en la base de datos - " . $e->getMessage();
                                writeLog("ERROR en fila $row: " . $e->getMessage());
                            }
                        }
                        
                        writeLog("Proceso de importación completado: $success_count productos importados, $error_count errores");
                        
                        fclose($handle);
                        
                        if ($success_count > 0) {
                            $mensaje = "Se importaron correctamente $success_count productos. Errores: $error_count.";
                            $tipo_mensaje = "success";
                            
                            if ($error_count > 0) {
                                $tipo_mensaje = "warning";
                            }
                        } else {
                            $mensaje = "No se pudo importar ningún producto. Errores: $error_count.";
                            $tipo_mensaje = "danger";
                        }
                    } else {
                        $missing = implode(', ', $missing_headers);
                        $mensaje = "El archivo CSV no tiene los encabezados requeridos: $missing";
                        $tipo_mensaje = "danger";
                        $error_log[] = "Faltan encabezados requeridos: $missing";
                        writeLog("ERROR: Faltan encabezados requeridos: $missing");
                    }
                }
            } else {
                $mensaje = "No se pudo abrir el archivo.";
                $tipo_mensaje = "danger";
                $error_log[] = "No se pudo abrir el archivo.";
                writeLog("ERROR: No se pudo abrir el archivo");
            }
        } else {
            $mensaje = "El archivo debe ser CSV.";
            $tipo_mensaje = "danger";
            $error_log[] = "El archivo debe ser CSV, se recibió: $ext";
        }
    } else {
        $mensaje = "Error al subir el archivo: " . $_FILES['csv_file']['error'];
        $tipo_mensaje = "danger";
        $error_log[] = "Error al subir el archivo: " . $_FILES['csv_file']['error'];
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

// Función para limpiar valores numéricos (eliminar comas de separadores de miles)
function cleanNumericValue($value) {
    // Si el valor es numérico con comas como separadores de miles
    if (preg_match('/^[0-9,]+(\.[0-9]+)?$/', $value)) {
        return str_replace(',', '', $value);
    }
    return $value;
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
                    
                    <h6 class="font-weight-bold mt-3">Reglas a considerar:</h6>
                    <ul>
                        <li>Usa <strong>comas (,)</strong> como separador de campos</li>
                        <li>Si un campo tiene comas, debe estar <strong>entre comillas dobles</strong></li>
                        <li>Usa <strong>punto (.)</strong> como separador decimal para precios</li>
                        <li>Asegúrate que el archivo esté en <strong>formato UTF-8</strong></li>
                        <li>Para campos booleanos, usa <strong>1 o true</strong> para sí, <strong>0 o false</strong> para no</li>
                    </ul>
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
            'AMD-RYZEN-5-8600G-8VA', '799.00', '10', 'Descripción completa del procesador...', 
            'Procesador AMD Ryzen 5 de última generación', '', '10', 'AMD', 'Ryzen 5 8600G', 
            'Núcleos: 6\nHilos: 12\nFrecuencia: 4.3GHz', '1', '1', '1'
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