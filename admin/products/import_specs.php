<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Verificar si está iniciada la sesión
// Aquí iría el control de acceso cuando implementemos el login

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
            $encabezados = fgetcsv($handle, 0, ",");
            
            // Verificar que los encabezados sean correctos
            $encabezados_esperados = ["producto_id", "tipo_spec", "nombre", "valor"];
            if ($encabezados !== $encabezados_esperados) {
                $errores[] = "El formato del CSV no es correcto. Los encabezados deben ser: producto_id, tipo_spec, nombre, valor";
            } else {
                // Contador de filas procesadas y errores
                $filas_procesadas = 0;
                $filas_con_error = 0;
                
                // Iniciar transacción
                $conn->begin_transaction();
                
                try {
                    // Preparar la sentencia SQL para insertar especificaciones
                    $stmt = $conn->prepare("INSERT INTO especificaciones_producto (producto_id, tipo_spec, nombre, valor) VALUES (?, ?, ?, ?)");
                    
                    // Leer el resto de las líneas
                    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                        // Verificar que tenga todos los campos necesarios
                        if (count($data) < 4) {
                            $filas_con_error++;
                            continue;
                        }
                        
                        // Obtener datos del CSV
                        $sku = trim($data[0]);
                        $tipo_spec = trim($data[1]);
                        $nombre = trim($data[2]);
                        $valor = trim($data[3]);
                        
                        // Buscar el producto por SKU
                            $check = $conn->query("SELECT id FROM productos WHERE sku = '$sku'");
                            if ($check->num_rows == 0) {
                                $filas_con_error++;
                                continue;
                            }
                            $producto = $check->fetch_assoc();
                            $producto_id = $producto['id'];
                        
                        // Vincular parámetros
                        $stmt->bind_param("isss", $producto_id, $tipo_spec, $nombre, $valor);
                        
                        // Ejecutar la sentencia
                        if ($stmt->execute()) {
                            $filas_procesadas++;
                        } else {
                            $filas_con_error++;
                        }
                    }
                    
                    // Confirmar la transacción
                    $conn->commit();
                    
                    $mensajes[] = "Importación completada: {$filas_procesadas} especificaciones importadas correctamente. {$filas_con_error} especificaciones con error.";
                    
                } catch (Exception $e) {
                    // Revertir la transacción en caso de error
                    $conn->rollback();
                    $errores[] = "Error en la importación: " . $e->getMessage();
                }
                
                fclose($handle);
            }
        } else {
            $errores[] = "No se pudo abrir el archivo CSV.";
        }
    }
}

// Obtener tipos de especificaciones existentes
$tipos_spec = [];
$result = $conn->query("SELECT DISTINCT tipo_spec FROM especificaciones_producto ORDER BY tipo_spec");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tipos_spec[] = $row['tipo_spec'];
    }
}
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
                            <pre>sku,tipo_spec,nombre,valor</pre>
                            <p>Ejemplo:</p>
                            <pre>IMP-HP-SMART-TANK-530-001,impresora,Tipo,Multifuncional de inyección
                            IMP-HP-SMART-TANK-530-001,impresora,Funciones,Impresión, Escaneo, Copia
                            IMP-HP-SMART-TANK-530-001,impresora,Conectividad,USB, Wi-Fi, Ethernet</pre>
                            <p>Donde:</p>
                            <ul>
                                <li><strong>sku</strong>: SKU del producto en la base de datos</li>
                                <li><strong>tipo_spec</strong>: Tipo de especificación (tarjeta_grafica, procesador, monitor, etc.)</li>
                                <li><strong>nombre</strong>: Nombre del campo de especificación</li>
                                <li><strong>valor</strong>: Valor de la especificación</li>
                            </ul>
                        </div>
                        
                        <?php if (!empty($tipos_spec)): ?>
                        <div class="form-info">
                            <h3>Tipos de especificaciones existentes</h3>
                            <p>Para mantener consistencia, considera usar estos tipos de especificaciones ya existentes:</p>
                            <ul>
                                <?php foreach ($tipos_spec as $tipo): ?>
                                <li><code><?php echo $tipo; ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Importar CSV</button>
                        </div>
                    </form>
                </div>
                
                <div class="data-card">
                    <h2>Plantillas de Especificaciones</h2>
                    <p>Aquí tienes algunas plantillas de especificaciones para diferentes tipos de productos:</p>
                    
                    <div class="tabs">
                        <div class="tab-header">
                            <button class="tab-btn active" data-tab="tarjetas">Tarjetas Gráficas</button>
                            <button class="tab-btn" data-tab="procesadores">Procesadores</button>
                            <button class="tab-btn" data-tab="monitores">Monitores</button>
                            <button class="tab-btn" data-tab="impresoras">Impresoras</button>
                            <button class="tab-btn" data-tab="laptops">Laptops</button>
                            <button class="tab-btn" data-tab="placas">Placas Madre</button>
                            <button class="tab-btn" data-tab="gabinetes">Gabinetes</button>
                            <button class="tab-btn" data-tab="computadoras">Computadoras Completas</button>
                        </div>
                        
                        <div class="tab-content active" id="tarjetas">
                            <h3>Especificaciones para Tarjetas Gráficas</h3>
                            <pre>producto_id,tipo_spec,nombre,valor
ID,tarjeta_grafica,Chipset,NOMBRE DEL CHIPSET
ID,tarjeta_grafica,Memoria,CANTIDAD Y TIPO (ej: 8GB GDDR6)
ID,tarjeta_grafica,Bus de memoria,ANCHO (ej: 128 bits)
ID,tarjeta_grafica,Interfaz,TIPO (ej: PCI Express 4.0)
ID,tarjeta_grafica,Conectores,DETALLE (ej: 1x HDMI 2.1, 3x DisplayPort 1.4a)
ID,tarjeta_grafica,Velocidad de memoria,DETALLE (ej: 16 Gbps)
ID,tarjeta_grafica,CUDA Cores,CANTIDAD (ej: 3584)
ID,tarjeta_grafica,DirectX,VERSIÓN (ej: 12)
ID,tarjeta_grafica,Consumo,DETALLE (ej: 170W)
ID,tarjeta_grafica,Alimentación,DETALLE (ej: 1x 8-pin)</pre>
                        </div>
                        
                        <div class="tab-content" id="procesadores">
                            <h3>Especificaciones para Procesadores</h3>
                            <pre>producto_id,tipo_spec,nombre,valor
ID,procesador,Núcleos,CANTIDAD (ej: 6)
ID,procesador,Hilos,CANTIDAD (ej: 12)
ID,procesador,Frecuencia base,DETALLE (ej: 3.7 GHz)
ID,procesador,Frecuencia turbo,DETALLE (ej: 4.6 GHz)
ID,procesador,Caché,DETALLE (ej: 12MB)
ID,procesador,Socket,TIPO (ej: LGA 1200)
ID,procesador,Arquitectura,DETALLE (ej: 10nm)
ID,procesador,TDP,DETALLE (ej: 65W)
ID,procesador,Gráficos integrados,DETALLE (ej: Intel UHD Graphics 730)
ID,procesador,Compatibilidad memoria,DETALLE (ej: DDR4-3200)</pre>
                        </div>
                        
                        <div class="tab-content" id="monitores">
                            <h3>Especificaciones para Monitores</h3>
                            <pre>producto_id,tipo_spec,nombre,valor
ID,monitor,Tamaño,DETALLE (ej: 27 pulgadas)
ID,monitor,Resolución,DETALLE (ej: 1920 x 1080)
ID,monitor,Tipo de panel,DETALLE (ej: IPS)
ID,monitor,Tasa de refresco,DETALLE (ej: 144 Hz)
ID,monitor,Tiempo de respuesta,DETALLE (ej: 1ms)
ID,monitor,Relación de aspecto,DETALLE (ej: 16:9)
ID,monitor,Brillo,DETALLE (ej: 300 cd/m²)
ID,monitor,Contraste,DETALLE (ej: 1000:1)
ID,monitor,Entradas,DETALLE (ej: 1x HDMI, 1x DisplayPort)
ID,monitor,Altavoces,DETALLE (ej: 2 x 2W)</pre>
                        </div>
                        
                        <div class="tab-content" id="impresoras">
                            <h3>Especificaciones para Impresoras</h3>
                            <pre>producto_id,tipo_spec,nombre,valor
ID,impresora,Tipo,DETALLE (ej: Multifuncional de inyección)
ID,impresora,Funciones,DETALLE (ej: Impresión, Escaneo, Copia)
ID,impresora,Conectividad,DETALLE (ej: USB, Wi-Fi, Ethernet)
ID,impresora,Velocidad B/N,DETALLE (ej: 20 ppm)
ID,impresora,Velocidad Color,DETALLE (ej: 15 ppm)
ID,impresora,Resolución,DETALLE (ej: 4800 x 1200 dpi)
ID,impresora,Tamaño de papel,DETALLE (ej: A4, Carta, Oficio)
ID,impresora,Capacidad de bandeja,DETALLE (ej: 250 hojas)
ID,impresora,Pantalla,DETALLE (ej: LCD 2.7 pulgadas)
ID,impresora,Compatibilidad,DETALLE (ej: Windows, macOS)</pre>
                        </div>
                        
                        <div class="tab-content" id="laptops">
                            <h3>Especificaciones para Laptops</h3>
                            <pre>producto_id,tipo_spec,nombre,valor
ID,laptop,Procesador,DETALLE (ej: Intel Core i5-1135G7)
ID,laptop,Memoria RAM,DETALLE (ej: 8GB DDR4)
ID,laptop,Almacenamiento,DETALLE (ej: SSD 512GB NVMe)
ID,laptop,Pantalla,DETALLE (ej: 15.6" Full HD IPS)
ID,laptop,Tarjeta gráfica,DETALLE (ej: NVIDIA GeForce MX350 2GB)
ID,laptop,Sistema operativo,DETALLE (ej: Windows 11 Home)
ID,laptop,Batería,DETALLE (ej: 3 celdas, 45Wh)
ID,laptop,Puertos,DETALLE (ej: 2x USB 3.2, 1x HDMI, 1x USB-C)
ID,laptop,Conectividad,DETALLE (ej: Wi-Fi 6, Bluetooth 5.1)
ID,laptop,Peso,DETALLE (ej: 1.8 kg)</pre>
                        </div>

                        <div class="tab-content" id="placas">
    <h3>Especificaciones para Placas Madre</h3>
    <pre>producto_id,tipo_spec,nombre,valor
ID,placa_madre,Socket,LGA 1700
ID,placa_madre,Chipset,Intel Z690
ID,placa_madre,Formato,ATX
ID,placa_madre,Ranuras RAM,4x DDR4
ID,placa_madre,Capacidad máxima RAM,128GB
ID,placa_madre,Velocidad RAM,DDR4-5333 (OC)
ID,placa_madre,Ranuras PCIe,1x PCIe 5.0 x16, 2x PCIe 3.0 x16
ID,placa_madre,Conectores SATA,6x SATA III
ID,placa_madre,Conectores M.2,3x M.2 PCIe 4.0 x4
ID,placa_madre,Puertos USB,2x USB 3.2 Gen2, 4x USB 3.2 Gen1, 4x USB 2.0
ID,placa_madre,LAN,Intel 2.5Gb Ethernet
ID,placa_madre,Audio,Realtek ALC1220
ID,placa_madre,Iluminación,RGB Fusion 2.0</pre>
</div>

<div class="tab-content" id="gabinetes">
    <h3>Especificaciones para Gabinetes</h3>
    <pre>producto_id,tipo_spec,nombre,valor
ID,gabinete,Formato,Mid-Tower
ID,gabinete,Compatibilidad,ATX, Micro-ATX, Mini-ITX
ID,gabinete,Material,Acero SPCC, Vidrio templado
ID,gabinete,Bahías,2x 3.5", 2x 2.5"
ID,gabinete,Ventiladores incluidos,3x 120mm ARGB
ID,gabinete,Ventiladores soportados,Frontal: 3x 120mm, Superior: 2x 140mm, Trasero: 1x 120mm
ID,gabinete,Refrigeración líquida,Frontal: 360mm, Superior: 240mm
ID,gabinete,Espacio GPU,Hasta 330mm
ID,gabinete,Espacio CPU cooler,Hasta 165mm
ID,gabinete,Puertos frontales,1x USB 3.1 Type-C, 2x USB 3.0, Audio
ID,gabinete,Filtros polvo,Frontal, Inferior, Superior (magnéticos)
ID,gabinete,Iluminación,ARGB con controlador</pre>
</div>

<div class="tab-content" id="computadoras">
    <h3>Especificaciones para Computadoras Completas</h3>
    <pre>producto_id,tipo_spec,nombre,valor
ID,computadora_completa,Procesador,Intel Core i7-12700K
ID,computadora_completa,Placa madre,ASUS ROG Strix Z690-A
ID,computadora_completa,Memoria RAM,32GB (2x16GB) DDR4 3600MHz
ID,computadora_completa,Tarjeta gráfica,NVIDIA GeForce RTX 3080 10GB
ID,computadora_completa,Almacenamiento,1TB NVMe SSD + 2TB HDD
ID,computadora_completa,Refrigeración,AIO Liquid Cooling 360mm
ID,computadora_completa,Fuente de poder,850W 80+ Gold
ID,computadora_completa,Gabinete,Corsair 4000D Airflow
ID,computadora_completa,Sistema operativo,Windows 11 Pro
ID,computadora_completa,Conectividad,Wi-Fi 6, Bluetooth 5.2, Ethernet 2.5Gb
ID,computadora_completa,Garantía,3 años en piezas, 1 año en mano de obra</pre>
</div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    <style>
        .form-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .form-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .form-info pre {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
        }
        
        .form-info ul {
            margin-left: 20px;
        }
        
        .tabs {
            margin-top: 20px;
        }
        
        .tab-header {
            display: flex;
            border-bottom: 1px solid #ddd;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .tab-btn {
            padding: 10px 15px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-weight: 500;
        }
        
        .tab-btn.active {
            border-bottom-color: #001CBD;
            color: #001CBD;
        }
        
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .tab-content h3 {
            margin-top: 0;
            margin-bottom: 15px;
        }
    </style>
    
    <script>
        // Cambio de pestañas
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                // Desactivar todas las pestañas
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Activar la pestaña seleccionada
                button.classList.add('active');
                document.getElementById(button.getAttribute('data-tab')).classList.add('active');
            });
        });
    </script>
</body>
</html>