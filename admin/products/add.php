<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Verificar si está iniciada la sesión
// Aquí iría el control de acceso cuando implementemos el login

$db = Database::getInstance();
$conn = $db->getConnection();

$mensajes = [];
$errores = [];

// Obtener todas las categorías
$result_categorias = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
$categorias = [];
while ($row = $result_categorias->fetch_assoc()) {
    $categorias[] = $row;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y validar datos
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $precio = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;
    $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $descripcion_corta = isset($_POST['descripcion_corta']) ? trim($_POST['descripcion_corta']) : '';
    $precio_oferta = !empty($_POST['precio_oferta']) ? (float)$_POST['precio_oferta'] : null;
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    $marca = isset($_POST['marca']) ? trim($_POST['marca']) : '';
    $modelo = isset($_POST['modelo']) ? trim($_POST['modelo']) : '';
    $caracteristicas = isset($_POST['caracteristicas']) ? trim($_POST['caracteristicas']) : '';
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $nuevo = isset($_POST['nuevo']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones básicas
    if (empty($nombre)) {
        $errores[] = "El nombre del producto es obligatorio.";
    }
    
    if ($precio <= 0) {
        $errores[] = "El precio debe ser mayor que cero.";
    }
    
    if ($categoria_id <= 0) {
        $errores[] = "Debe seleccionar una categoría.";
    }
    
    if ($stock < 0) {
        $errores[] = "El stock no puede ser negativo.";
    }
    
   // Si no hay errores, guardar en la base de datos
if (empty($errores)) {
    $stmt = $conn->prepare("INSERT INTO productos (nombre, precio, categoria_id, descripcion, descripcion_corta, precio_oferta, stock, marca, modelo, caracteristicas, destacado, nuevo, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Corregir el bind_param
    if ($precio_oferta === null) {
        // Para precio_oferta NULL
        $null_value = null;
        $stmt->bind_param("sdissdissiiii", 
    $nombre, 
    $precio, 
    $categoria_id, 
    $descripcion, 
    $descripcion_corta, 
    $precio_oferta, 
    $stock, 
    $marca, 
    $modelo, 
    $caracteristicas, 
    $destacado, 
    $nuevo, 
    $activo
);
    } else {
        // Para precio_oferta con valor
        $stmt->bind_param("sdissdissiiii", 
            $nombre, 
            $precio, 
            $categoria_id, 
            $descripcion, 
            $descripcion_corta, 
            $precio_oferta, 
            $stock, 
            $marca, 
            $modelo, 
            $caracteristicas, 
            $destacado, 
            $nuevo, 
            $activo
        );
    }
        
        if ($stmt->execute()) {
            $nuevo_id = $conn->insert_id;
            $mensajes[] = "Producto añadido correctamente.";
            
            // Procesar especificaciones
            if (isset($_POST['spec_nombres']) && isset($_POST['spec_valores']) && isset($_POST['spec_tipos'])) {
                $spec_nombres = $_POST['spec_nombres'];
                $spec_valores = $_POST['spec_valores'];
                $spec_tipos = $_POST['spec_tipos'];
                
                // Preparar la consulta
                $stmt_spec = $conn->prepare("INSERT INTO especificaciones_producto (producto_id, tipo_spec, nombre, valor) VALUES (?, ?, ?, ?)");
                
                for ($i = 0; $i < count($spec_nombres); $i++) {
                    if (!empty($spec_nombres[$i]) && !empty($spec_valores[$i])) {
                        $stmt_spec->bind_param("isss", $nuevo_id, $spec_tipos[$i], $spec_nombres[$i], $spec_valores[$i]);
                        $stmt_spec->execute();
                    }
                }
                
                $mensajes[] = "Especificaciones guardadas correctamente.";
            }
            
            // Procesar imagen si se ha subido
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                $imagen = $_FILES['imagen'];
                $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
                $nombre_archivo = 'producto_' . $nuevo_id . '_' . uniqid() . '.' . $extension;
                $ruta_destino = '../../uploads/productos/' . $nombre_archivo;
                
                // Crear directorio si no existe
                if (!is_dir('../../uploads/productos/')) {
                    mkdir('../../uploads/productos/', 0777, true);
                }
                
                if (move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
                    // Guardar referencia en la base de datos
                    $ruta_bd = 'uploads/productos/' . $nombre_archivo;
                    $stmt = $conn->prepare("INSERT INTO imagenes_producto (producto_id, ruta, principal) VALUES (?, ?, 1)");
                    $stmt->bind_param("is", $nuevo_id, $ruta_bd);
                    $stmt->execute();
                    
                    $mensajes[] = "Imagen subida correctamente.";
                } else {
                    $errores[] = "Error al subir la imagen. El producto se ha guardado sin imagen.";
                }
            }
            
            // Limpiar el formulario después de guardar correctamente
            if (empty($errores)) {
                // Redirigir a la lista de productos si todo está bien
                header("Location: index.php?mensaje=Producto añadido correctamente");
                exit;
            }
        } else {
            $errores[] = "Error al guardar el producto: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Producto - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Añadir Nuevo Producto</h1>
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
                    <form action="" method="post" enctype="multipart/form-data" class="product-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nombre">Nombre del Producto*:</label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
                                <p class="form-help">Nombre completo del producto. Será visible en la tienda.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="categoria_id">Categoría*:</label>
                                <select id="categoria_id" name="categoria_id" required>
                                    <option value="">-- Seleccione una categoría --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo isset($_POST['categoria_id']) && $_POST['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="form-help">Seleccione la categoría a la que pertenece el producto.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="marca">Marca:</label>
                                <input type="text" id="marca" name="marca" value="<?php echo isset($_POST['marca']) ? htmlspecialchars($_POST['marca']) : ''; ?>">
                                <p class="form-help">Marca del producto (HP, Dell, Logitech, etc.)</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="modelo">Modelo:</label>
                                <input type="text" id="modelo" name="modelo" value="<?php echo isset($_POST['modelo']) ? htmlspecialchars($_POST['modelo']) : ''; ?>">
                                <p class="form-help">Número o nombre del modelo específico.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="precio">Precio Regular (S/)*:</label>
                                <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?php echo isset($_POST['precio']) ? htmlspecialchars($_POST['precio']) : ''; ?>" required>
                                <p class="form-help">Precio regular del producto en Soles.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="precio_oferta">Precio Oferta (S/):</label>
                                <input type="number" id="precio_oferta" name="precio_oferta" step="0.01" min="0" value="<?php echo isset($_POST['precio_oferta']) ? htmlspecialchars($_POST['precio_oferta']) : ''; ?>">
                                <p class="form-help">Precio de oferta (si aplica). Dejar en blanco si no hay oferta.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="stock">Stock Disponible*:</label>
                                <input type="number" id="stock" name="stock" min="0" value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '0'; ?>" required>
                                <p class="form-help">Cantidad de unidades disponibles para venta.</p>
                            </div>
                            
                            <div class="form-group description-group">
                                <label for="descripcion_corta">Descripción Corta:</label>
                                <input type="text" id="descripcion_corta" name="descripcion_corta" value="<?php echo isset($_POST['descripcion_corta']) ? htmlspecialchars($_POST['descripcion_corta']) : ''; ?>">
                                <p class="form-help">Breve descripción para listados (máx. 255 caracteres).</p>
                            </div>
                            
                            <div class="form-group description-group">
                                <label for="descripcion">Descripción Completa:</label>
                                <textarea id="descripcion" name="descripcion" rows="5"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                                <p class="form-help">Descripción detallada del producto.</p>
                            </div>
                            
                            <div class="form-group description-group">
                                <label for="caracteristicas">Características:</label>
                                <textarea id="caracteristicas" name="caracteristicas" rows="5" placeholder="Una característica por línea"><?php echo isset($_POST['caracteristicas']) ? htmlspecialchars($_POST['caracteristicas']) : ''; ?></textarea>
                                <p class="form-help">Lista de características técnicas, una por línea.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="imagen">Imagen del Producto:</label>
                                <input type="file" id="imagen" name="imagen" accept="image/*">
                                <p class="form-help">Tamaño recomendado: 800x800px. Formatos: JPG, PNG.</p>
                            </div>
                            
                            <div class="form-group options-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="destacado" <?php echo isset($_POST['destacado']) ? 'checked' : ''; ?>>
                                    Producto Destacado
                                </label>
                                <p class="form-help">Aparecerá en la sección de destacados de la página principal.</p>
                            </div>
                            
                            <div class="form-group options-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="nuevo" <?php echo isset($_POST['nuevo']) ? 'checked' : ''; ?>>
                                    Producto Nuevo
                                </label>
                                <p class="form-help">Aparecerá en la sección de nuevos productos.</p>
                            </div>
                            
                            <div class="form-group options-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="activo" checked>
                                    Producto Activo
                                </label>
                                <p class="form-help">El producto estará visible y disponible para compra.</p>
                            </div>
                        </div>
                        
                        <div class="form-group specs-container">
                            <h3>Especificaciones Técnicas</h3>
                            <p class="form-help">Las especificaciones se cargarán según la categoría seleccionada.</p>
                            
                            <div id="specs-fields">
                                <div class="no-specs">Por favor, seleccione una categoría para cargar las especificaciones.</div>
                            </div>
                            
                            <button type="button" id="add-spec-field" class="btn btn-secondary" style="display:none;">Añadir especificación</button>
                        </div>


                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Guardar Producto</button>
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        // Preview de la imagen seleccionada
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Si ya existe una vista previa, la actualizamos
                    let preview = document.querySelector('.image-preview');
                    
                    if (!preview) {
                        // Si no existe, creamos los elementos
                        preview = document.createElement('div');
                        preview.className = 'image-preview';
                        
                        const img = document.createElement('img');
                        preview.appendChild(img);
                        
                        // Insertamos después del input de imagen
                        document.getElementById('imagen').parentNode.appendChild(preview);
                    }
                    
                    // Actualizamos la imagen
                    const img = preview.querySelector('img');
                    img.src = e.target.result;
                };
                
                reader.readAsDataURL(file);
            }
        });
    </script>

<script>

    
// Mapeo de categorías a tipos de especificaciones
const categoriasSpecs = {
    '8': 'procesador',          // Categoría ID para procesadores
    '9': 'tarjeta_grafica',     // Categoría ID para tarjetas gráficas
    '2': 'monitor',             // Categoría ID para monitores
    '4': 'impresora',           // Categoría ID para impresoras
    '3': 'laptop',              // Categoría ID para laptops
    '10': 'placa_madre',        // Categoría ID para placas madre
    '7': 'gabinete',            // Categoría ID para gabinetes
    '6': 'computadora_completa' // Categoría ID para computadoras completas
};

// Mapeo de tipos a campos de especificaciones
const camposSpecs = {
    'procesador': [
        { nombre: 'Núcleos', placeholder: 'Ej: 6' },
        { nombre: 'Hilos', placeholder: 'Ej: 12' },
        { nombre: 'Frecuencia base', placeholder: 'Ej: 3.7 GHz' },
        { nombre: 'Frecuencia turbo', placeholder: 'Ej: 4.6 GHz' },
        { nombre: 'Caché', placeholder: 'Ej: 12MB' },
        { nombre: 'Socket', placeholder: 'Ej: LGA 1200' },
        { nombre: 'Arquitectura', placeholder: 'Ej: 10nm' },
        { nombre: 'TDP', placeholder: 'Ej: 65W' },
        { nombre: 'Gráficos integrados', placeholder: 'Ej: Intel UHD Graphics 730' }
    ],
    'tarjeta_grafica': [
        { nombre: 'Chipset', placeholder: 'Ej: NVIDIA GeForce RTX 4060' },
        { nombre: 'Memoria', placeholder: 'Ej: 8GB GDDR6' },
        { nombre: 'Bus de memoria', placeholder: 'Ej: 128 bits' },
        { nombre: 'Interfaz', placeholder: 'Ej: PCI Express 4.0' },
        { nombre: 'Conectores', placeholder: 'Ej: 1x HDMI 2.1, 3x DisplayPort 1.4a' },
        { nombre: 'CUDA Cores', placeholder: 'Ej: 3584' },
        { nombre: 'Consumo', placeholder: 'Ej: 170W' }
    ],
    'monitor': [
        { nombre: 'Tamaño', placeholder: 'Ej: 27 pulgadas' },
        { nombre: 'Resolución', placeholder: 'Ej: 1920 x 1080' },
        { nombre: 'Tipo de panel', placeholder: 'Ej: IPS' },
        { nombre: 'Tasa de refresco', placeholder: 'Ej: 144 Hz' },
        { nombre: 'Tiempo de respuesta', placeholder: 'Ej: 1ms' }
    ],
    'impresora': [
        { nombre: 'Tipo', placeholder: 'Ej: Multifuncional de inyección' },
        { nombre: 'Funciones', placeholder: 'Ej: Impresión, Escaneo, Copia' },
        { nombre: 'Conectividad', placeholder: 'Ej: USB, Wi-Fi, Ethernet' },
        { nombre: 'Velocidad B/N', placeholder: 'Ej: 20 ppm' },
        { nombre: 'Velocidad Color', placeholder: 'Ej: 15 ppm' },
        { nombre: 'Resolución', placeholder: 'Ej: 4800 x 1200 dpi' }
    ],
    'laptop': [
        { nombre: 'Procesador', placeholder: 'Ej: Intel Core i5-1135G7' },
        { nombre: 'Memoria RAM', placeholder: 'Ej: 8GB DDR4' },
        { nombre: 'Almacenamiento', placeholder: 'Ej: SSD 512GB NVMe' },
        { nombre: 'Pantalla', placeholder: 'Ej: 15.6" Full HD IPS' },
        { nombre: 'Tarjeta gráfica', placeholder: 'Ej: NVIDIA GeForce MX350 2GB' },
        { nombre: 'Sistema operativo', placeholder: 'Ej: Windows 11 Home' },
        { nombre: 'Batería', placeholder: 'Ej: 3 celdas, 45Wh' },
        { nombre: 'Puertos', placeholder: 'Ej: 2x USB 3.2, 1x HDMI, 1x USB-C' }
    ],
    'placa_madre': [
        { nombre: 'Socket', placeholder: 'Ej: LGA 1700' },
        { nombre: 'Chipset', placeholder: 'Ej: Intel Z690' },
        { nombre: 'Formato', placeholder: 'Ej: ATX' },
        { nombre: 'Ranuras RAM', placeholder: 'Ej: 4x DDR4' },
        { nombre: 'Capacidad máxima RAM', placeholder: 'Ej: 128GB' },
        { nombre: 'Velocidad RAM', placeholder: 'Ej: DDR4-5333 (OC)' },
        { nombre: 'Ranuras PCIe', placeholder: 'Ej: 1x PCIe 5.0 x16, 2x PCIe 3.0 x16' },
        { nombre: 'Conectores SATA', placeholder: 'Ej: 6x SATA III' },
        { nombre: 'Conectores M.2', placeholder: 'Ej: 3x M.2 PCIe 4.0 x4' },
        { nombre: 'Puertos USB', placeholder: 'Ej: 2x USB 3.2 Gen2, 4x USB 3.2 Gen1, 4x USB 2.0' },
        { nombre: 'LAN', placeholder: 'Ej: Intel 2.5Gb Ethernet' },
        { nombre: 'Audio', placeholder: 'Ej: Realtek ALC1220' },
        { nombre: 'Iluminación', placeholder: 'Ej: RGB Fusion 2.0' }
    ],
    'gabinete': [
        { nombre: 'Formato', placeholder: 'Ej: Mid-Tower' },
        { nombre: 'Compatibilidad', placeholder: 'Ej: ATX, Micro-ATX, Mini-ITX' },
        { nombre: 'Material', placeholder: 'Ej: Acero SPCC, Vidrio templado' },
        { nombre: 'Bahías', placeholder: 'Ej: 2x 3.5", 2x 2.5"' },
        { nombre: 'Ventiladores incluidos', placeholder: 'Ej: 3x 120mm ARGB' },
        { nombre: 'Ventiladores soportados', placeholder: 'Ej: Frontal: 3x 120mm, Superior: 2x 140mm, Trasero: 1x 120mm' },
        { nombre: 'Refrigeración líquida', placeholder: 'Ej: Frontal: 360mm, Superior: 240mm' },
        { nombre: 'Espacio GPU', placeholder: 'Ej: Hasta 330mm' },
        { nombre: 'Espacio CPU cooler', placeholder: 'Ej: Hasta 165mm' },
        { nombre: 'Puertos frontales', placeholder: 'Ej: 1x USB 3.1 Type-C, 2x USB 3.0, Audio' },
        { nombre: 'Filtros polvo', placeholder: 'Ej: Frontal, Inferior, Superior (magnéticos)' },
        { nombre: 'Iluminación', placeholder: 'Ej: ARGB con controlador' }
    ],
    'computadora_completa': [
        { nombre: 'Procesador', placeholder: 'Ej: Intel Core i7-12700K' },
        { nombre: 'Placa madre', placeholder: 'Ej: ASUS ROG Strix Z690-A' },
        { nombre: 'Memoria RAM', placeholder: 'Ej: 32GB (2x16GB) DDR4 3600MHz' },
        { nombre: 'Tarjeta gráfica', placeholder: 'Ej: NVIDIA GeForce RTX 3080 10GB' },
        { nombre: 'Almacenamiento', placeholder: 'Ej: 1TB NVMe SSD + 2TB HDD' },
        { nombre: 'Refrigeración', placeholder: 'Ej: AIO Liquid Cooling 360mm' },
        { nombre: 'Fuente de poder', placeholder: 'Ej: 850W 80+ Gold' },
        { nombre: 'Gabinete', placeholder: 'Ej: Corsair 4000D Airflow' },
        { nombre: 'Sistema operativo', placeholder: 'Ej: Windows 11 Pro' },
        { nombre: 'Conectividad', placeholder: 'Ej: Wi-Fi 6, Bluetooth 5.2, Ethernet 2.5Gb' },
        { nombre: 'Garantía', placeholder: 'Ej: 3 años en piezas, 1 año en mano de obra' }
    ]
};
// Función para generar campos de especificaciones
function generarCamposSpecs(tipo) {
    const specsFields = document.getElementById('specs-fields');
    specsFields.innerHTML = '';
    
    if (!tipo || !camposSpecs[tipo]) {
        specsFields.innerHTML = '<div class="no-specs">No hay especificaciones disponibles para esta categoría.</div>';
        document.getElementById('add-spec-field').style.display = 'none';
        return;
    }
    
    // Mostrar campos predefinidos
    camposSpecs[tipo].forEach((campo, index) => {
        const fieldHtml = `
            <div class="spec-field-row">
                <input type="hidden" name="spec_nombres[]" value="${campo.nombre}">
                <div class="spec-field-group">
                    <label>${campo.nombre}:</label>
                    <input type="text" name="spec_valores[]" placeholder="${campo.placeholder}" class="spec-value">
                </div>
                <input type="hidden" name="spec_tipos[]" value="${tipo}">
            </div>
        `;
        specsFields.insertAdjacentHTML('beforeend', fieldHtml);
    });
    
    // Mostrar botón para añadir campo personalizado
    document.getElementById('add-spec-field').style.display = 'inline-block';
}

// Función para añadir un campo personalizado
function agregarCampoSpec() {
    const categoriaId = document.getElementById('categoria_id').value;
    const tipo = categoriasSpecs[categoriaId] || '';
    
    if (!tipo) return;
    
    const specsFields = document.getElementById('specs-fields');
    const fieldHtml = `
        <div class="spec-field-row custom-field">
            <div class="spec-field-group">
                <label>Nombre:</label>
                <input type="text" name="spec_nombres[]" placeholder="Nombre de la especificación" class="spec-name" required>
            </div>
            <div class="spec-field-group">
                <label>Valor:</label>
                <input type="text" name="spec_valores[]" placeholder="Valor de la especificación" class="spec-value" required>
            </div>
            <input type="hidden" name="spec_tipos[]" value="${tipo}">
            <button type="button" class="btn-remove-spec" onclick="this.parentElement.remove()">×</button>
        </div>
    `;
    specsFields.insertAdjacentHTML('beforeend', fieldHtml);
}

// Evento para cambio de categoría
document.getElementById('categoria_id').addEventListener('change', function() {
    const categoriaId = this.value;
    const tipo = categoriasSpecs[categoriaId] || '';
    generarCamposSpecs(tipo);
});

// Evento para añadir campo de especificación
document.getElementById('add-spec-field').addEventListener('click', agregarCampoSpec);

// Aplicar los estilos
document.head.insertAdjacentHTML('beforeend', `
    <style>
        .specs-container {
            grid-column: 1 / -1;
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-top: 20px;
        }
        
        .specs-container h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .no-specs {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            color: #666;
            font-style: italic;
        }
        
        .spec-field-row {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            align-items: center;
        }
        
        .spec-field-group {
            flex: 1;
        }
        
        .custom-field {
            background-color: #e9ecef;
        }
        
        .btn-remove-spec {
            background: none;
            border: none;
            color: #dc3545;
            font-size: 20px;
            cursor: pointer;
            padding: 0 5px;
        }
    </style>
`);
</script>
</body>
</html>