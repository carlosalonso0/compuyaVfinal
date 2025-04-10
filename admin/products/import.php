<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Inicializar variables
$mensajes = [];
$errores = [];

// Procesar formulario de importación CSV
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["archivo_csv"])) {
    $archivo = $_FILES["archivo_csv"];
    
    // Verificar si es un archivo CSV
    $extension = pathinfo($archivo["name"], PATHINFO_EXTENSION);
    if ($extension != "csv") {
        $errores[] = "El archivo debe ser en formato CSV.";
    } else {
        // Procesar el archivo CSV
        $handle = fopen($_FILES["archivo_csv"]["tmp_name"], "r");
        
        if ($handle !== FALSE) {
            // Leer la primera línea (encabezados)
            $encabezados = fgetcsv($handle, 0, ",");
            
            // Contador de filas procesadas y errores
            $filas_procesadas = 0;
            $filas_con_error = 0;
            
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Mostrar los datos que estamos procesando
            echo "<h3>Datos procesados del CSV:</h3>";
            
            // Leer el resto de las líneas
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                // Verificar que tenga todos los campos necesarios
                if (count($data) < 13) {
                    $filas_con_error++;
                    echo "<p style='color:red'>Error: Fila con campos insuficientes</p>";
                    continue;
                }
                
                // Obtener datos del CSV
                $nombre = $conn->real_escape_string($data[0]);
                $precio = floatval($data[1]);
                $categoria_id = intval($data[2]);
                $descripcion = $conn->real_escape_string($data[3]);
                $descripcion_corta = $conn->real_escape_string($data[4]);
                $precio_oferta = !empty($data[5]) ? floatval($data[5]) : "NULL";
                $stock = intval($data[6]);
                $marca = $conn->real_escape_string($data[7]);
                $modelo = $conn->real_escape_string($data[8]);
                $caracteristicas = $conn->real_escape_string($data[9]);
                $destacado = ($data[10] === "true" || $data[10] === "1") ? 1 : 0;
                $nuevo = ($data[11] === "true" || $data[11] === "1") ? 1 : 0;
                $activo = ($data[12] === "true" || $data[12] === "1") ? 1 : 0;
                
                // Debug - mostrar la marca
                echo "<div style='margin:10px; padding:10px; border:1px solid #ccc'>";
                echo "<p><strong>Fila procesada:</strong></p>";
                echo "<p><strong>Nombre:</strong> $nombre</p>";
                echo "<p><strong>Marca:</strong> '$marca'</p>";
                echo "<p><strong>Modelo:</strong> $modelo</p>";
                echo "</div>";
                
                // Crear consulta SQL directa en lugar de usar bind_param
                $precio_oferta_sql = $precio_oferta === "NULL" ? "NULL" : "'$precio_oferta'";
                
                $sql = "INSERT INTO productos (nombre, precio, categoria_id, descripcion, descripcion_corta, precio_oferta, stock, marca, modelo, caracteristicas, destacado, nuevo, activo) 
                        VALUES ('$nombre', $precio, $categoria_id, '$descripcion', '$descripcion_corta', $precio_oferta_sql, $stock, '$marca', '$modelo', '$caracteristicas', $destacado, $nuevo, $activo)";
                
                // Ejecutar la sentencia
                if ($conn->query($sql)) {
                    $filas_procesadas++;
                } else {
                    $filas_con_error++;
                    echo "<p style='color:red'>Error al insertar: " . $conn->error . "</p>";
                    echo "<p>SQL: $sql</p>";
                }
            }
            
            $mensajes[] = "Importación completada: {$filas_procesadas} productos importados correctamente. {$filas_con_error} productos con error.";
            
            fclose($handle);
        } else {
            $errores[] = "No se pudo abrir el archivo CSV.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Productos - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <div class="container">
        <h1>Importar Productos desde CSV</h1>
        
        <?php
        // Mostrar mensajes
        foreach ($mensajes as $mensaje) {
            echo "<div class='mensaje exito'>{$mensaje}</div>";
        }
        
        // Mostrar errores
        foreach ($errores as $error) {
            echo "<div class='mensaje error'>{$error}</div>";
        }
        ?>
        
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="archivo_csv">Archivo CSV:</label>
                <input type="file" name="archivo_csv" id="archivo_csv" required>
            </div>
            
            <div class="form-info">
                <p>El archivo CSV debe tener el siguiente formato:</p>
                <pre>nombre,precio,categoria_id,descripcion,descripcion_corta,precio_oferta,stock,marca,modelo,caracteristicas,destacado,nuevo,activo</pre>
                <p>Ejemplo:</p>
                <pre>HP-SMART-TANK-530-MULTIFUNCIONAL-WIFI,699.00,4,"Descripción larga","Descripción corta",549.00,25,HP,Smart Tank 530,"Características",true,true,true</pre>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Importar CSV</button>
                <a href="index.php" class="btn btn-secondary">Volver</a>
            </div>
        </form>
    </div>
</body>
</html>