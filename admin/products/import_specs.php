<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

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
            $encabezados = fgetcsv($handle, 0, ",", '"', "\\");
            
            // Verificar que los encabezados sean correctos
            $encabezados_esperados = ["sku", "nombre", "valor"];
            if (count(array_intersect($encabezados, $encabezados_esperados)) !== count($encabezados_esperados)) {
                $errores[] = "El formato del CSV no es correcto. Los encabezados deben ser: sku, nombre, valor";
            } else {
                // Contador de filas procesadas y errores
                $filas_procesadas = 0;
                $filas_con_error = 0;
                
                // Preparar la sentencia SQL
                $stmt = $conn->prepare("INSERT INTO especificaciones_producto (producto_id, nombre, valor) VALUES (?, ?, ?)");
                
                // Leer el resto de las líneas
                while (($data = fgetcsv($handle, 0, ",", '"', "\\")) !== FALSE) {
                    // Verificar que tenga todos los campos necesarios
                    if (count($data) < 3) {
                        $filas_con_error++;
                        continue;
                    }
                    
                    // Obtener datos del CSV
                    $sku = trim($data[0]);
                    $nombre = trim($data[1]);
                    $valor = trim($data[2]);
                    
                    // Buscar el producto por SKU para obtener el ID
                    $sku_seguro = $conn->real_escape_string($sku);
                    $result = $conn->query("SELECT id FROM productos WHERE sku = '$sku_seguro'");
                    
                    if ($result->num_rows == 0) {
                        // No se encontró el producto con ese SKU
                        $filas_con_error++;
                        continue;
                    }
                    
                    // Obtener el ID del producto
                    $producto = $result->fetch_assoc();
                    $producto_id = $producto['id'];
                    
                    // Insertar la especificación
                    $stmt->bind_param("iss", $producto_id, $nombre, $valor);
                    
                    if ($stmt->execute()) {
                        $filas_procesadas++;
                    } else {
                        $filas_con_error++;
                    }
                }
                
                $mensajes[] = "Importación completada: {$filas_procesadas} especificaciones importadas correctamente. {$filas_con_error} especificaciones con error.";
            }
            
            fclose($handle);
        } else {
            $errores[] = "No se pudo abrir el archivo CSV.";
        }
    }
}

// Incluir cabecera
include '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Especificaciones - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Importar Especificaciones de Productos</h1>
                <div class="admin-actions">
                    <a href="index.php" class="btn btn-secondary">Volver a Productos</a>
                </div>
            </header>
            
            <?php if (!empty($mensajes)): ?>
                <div class="mensajes-container">
                    <?php foreach ($mensajes as $mensaje): ?>
                        <div class="mensaje exito"><?php echo $mensaje; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errores)): ?>
                <div class="mensajes-container">
                    <?php foreach ($errores as $error): ?>
                        <div class="mensaje error"><?php echo $error; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <section class="admin-section">
                <div class="form-card">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="archivo_csv">Archivo CSV:</label>
                            <input type="file" name="archivo_csv" id="archivo_csv" required>
                        </div>
                        
                        <div class="form-info">
                            <h3>Instrucciones</h3>
                            <p>El archivo CSV debe tener el siguiente formato:</p>
                            <pre>sku,nombre,valor</pre>
                            <p>Ejemplo:</p>
                            <pre>IMP-HP-SMART-TANK-530-001,Tipo,Multifuncional de inyección
IMP-HP-SMART-TANK-530-001,Funciones,"Impresión, Escaneo, Copia"
IMP-HP-SMART-TANK-530-001,Conectividad,"USB, Wi-Fi, Bluetooth"</pre>
                            <p>Donde:</p>
                            <ul>
                                <li><strong>sku</strong>: SKU del producto en la base de datos</li>
                                <li><strong>nombre</strong>: Nombre de la especificación</li>
                                <li><strong>valor</strong>: Valor de la especificación</li>
                            </ul>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Importar CSV</button>
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html></parameter>
</invoke>