<?php
class Producto {
    // Propiedades de la base de datos
    private $conn;
    private $table = 'productos';

    // Propiedades de Producto
    public $id;
    public $codigo;
    public $nombre;
    public $slug;
    public $descripcion;
    public $descripcion_corta;
    public $precio;
    public $precio_oferta;
    public $stock;
    public $imagen_principal;
    public $marca;
    public $modelo;
    public $caracteristicas;
    public $destacado;
    public $nuevo;
    public $activo;
    public $categoria_id;
    public $fecha_creacion;
    
    // Propiedades adicionales
    public $categoria_nombre;

    // Constructor con DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener todos los productos
    public function getAll($limit = 0, $offset = 0) {
        // Crear query
        $query = 'SELECT p.*, c.nombre as categoria_nombre 
                FROM ' . $this->table . ' p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.activo = true ';

        // Añadir limit y offset si son proporcionados
        if($limit > 0) {
            $query .= 'LIMIT :limit ';
            if($offset > 0) {
                $query .= 'OFFSET :offset';
            }
        }

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        if($limit > 0) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            if($offset > 0) {
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
        }

        // Ejecutar query
        $stmt->execute();

        return $stmt;
    }

    // Obtener un solo producto
    public function getSingle() {
        // Crear query
        $query = 'SELECT p.*, c.nombre as categoria_nombre 
                FROM ' . $this->table . ' p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.id = :id 
                LIMIT 1';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bindParam(':id', $this->id);

        // Ejecutar query
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si no hay resultados
        if(!$row) {
            return false;
        }

        // Asignar propiedades
        $this->codigo = $row['codigo'];
        $this->nombre = $row['nombre'];
        $this->slug = $row['slug'];
        $this->descripcion = $row['descripcion'];
        $this->descripcion_corta = $row['descripcion_corta'];
        $this->precio = $row['precio'];
        $this->precio_oferta = $row['precio_oferta'];
        $this->stock = $row['stock'];
        $this->imagen_principal = $row['imagen_principal'];
        $this->marca = $row['marca'];
        $this->modelo = $row['modelo'];
        $this->caracteristicas = $row['caracteristicas'];
        $this->destacado = $row['destacado'];
        $this->nuevo = $row['nuevo'];
        $this->activo = $row['activo'];
        $this->categoria_id = $row['categoria_id'];
        $this->categoria_nombre = $row['categoria_nombre'];
        $this->fecha_creacion = $row['fecha_creacion'];

        return true;
    }

    // Obtener producto por slug
    public function getBySlug($slug) {
        // Crear query
        $query = 'SELECT p.*, c.nombre as categoria_nombre 
                FROM ' . $this->table . ' p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.slug = :slug 
                LIMIT 1';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Bind slug
        $stmt->bindParam(':slug', $slug);

        // Ejecutar query
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si no hay resultados
        if(!$row) {
            return false;
        }

        // Asignar propiedades
        $this->id = $row['id'];
        $this->codigo = $row['codigo'];
        $this->nombre = $row['nombre'];
        $this->slug = $row['slug'];
        $this->descripcion = $row['descripcion'];
        $this->descripcion_corta = $row['descripcion_corta'];
        $this->precio = $row['precio'];
        $this->precio_oferta = $row['precio_oferta'];
        $this->stock = $row['stock'];
        $this->imagen_principal = $row['imagen_principal'];
        $this->marca = $row['marca'];
        $this->modelo = $row['modelo'];
        $this->caracteristicas = $row['caracteristicas'];
        $this->destacado = $row['destacado'];
        $this->nuevo = $row['nuevo'];
        $this->activo = $row['activo'];
        $this->categoria_id = $row['categoria_id'];
        $this->categoria_nombre = $row['categoria_nombre'];
        $this->fecha_creacion = $row['fecha_creacion'];

        return true;
    }

    // Obtener productos por categoría
    public function getByCategoria($categoria_id, $limit = 0, $offset = 0) {
        // Crear query
        $query = 'SELECT p.*, c.nombre as categoria_nombre 
                FROM ' . $this->table . ' p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.categoria_id = :categoria_id 
                AND p.activo = true ';

        // Añadir limit y offset si son proporcionados
        if($limit > 0) {
            $query .= 'LIMIT :limit ';
            if($offset > 0) {
                $query .= 'OFFSET :offset';
            }
        }

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bindParam(':categoria_id', $categoria_id);
        
        if($limit > 0) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            if($offset > 0) {
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
        }

        // Ejecutar query
        $stmt->execute();

        return $stmt;
    }

    // Obtener productos destacados
    public function getDestacados($limit = 4) {
        // Crear query
        $query = 'SELECT p.*, c.nombre as categoria_nombre 
                FROM ' . $this->table . ' p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.destacado = true 
                AND p.activo = true 
                ORDER BY p.fecha_creacion DESC
                LIMIT :limit';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Bind limit
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

        // Ejecutar query
        $stmt->execute();

        return $stmt;
    }

    // Obtener productos nuevos
    public function getNuevos($limit = 4) {
        // Crear query
        $query = 'SELECT p.*, c.nombre as categoria_nombre 
                FROM ' . $this->table . ' p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.nuevo = true 
                AND p.activo = true 
                ORDER BY p.fecha_creacion DESC
                LIMIT :limit';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Bind limit
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

        // Ejecutar query
        $stmt->execute();

        return $stmt;
    }

    // Buscar productos
    public function search($keyword, $limit = 0, $offset = 0) {
        // Crear query
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                FROM " . $this->table . " p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE (p.nombre LIKE :keyword 
                OR p.descripcion LIKE :keyword 
                OR p.marca LIKE :keyword 
                OR p.modelo LIKE :keyword)
                AND p.activo = true ";

        // Añadir limit y offset si son proporcionados
        if($limit > 0) {
            $query .= 'LIMIT :limit ';
            if($offset > 0) {
                $query .= 'OFFSET :offset';
            }
        }

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Sanitize keyword
        $keyword = '%' . $keyword . '%';
        
        // Bind parameters
        $stmt->bindParam(':keyword', $keyword);
        
        if($limit > 0) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            if($offset > 0) {
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
        }

        // Ejecutar query
        $stmt->execute();

        return $stmt;
    }

    // Crear un producto
    public function create() {
        // Crear query
        $query = 'INSERT INTO ' . $this->table . '
                SET
                codigo = :codigo,
                nombre = :nombre,
                slug = :slug,
                descripcion = :descripcion,
                descripcion_corta = :descripcion_corta,
                precio = :precio,
                precio_oferta = :precio_oferta,
                stock = :stock,
                imagen_principal = :imagen_principal,
                marca = :marca,
                modelo = :modelo,
                caracteristicas = :caracteristicas,
                destacado = :destacado,
                nuevo = :nuevo,
                activo = :activo,
                categoria_id = :categoria_id';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->codigo = htmlspecialchars(strip_tags($this->codigo));
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->slug = htmlspecialchars(strip_tags($this->slug));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->descripcion_corta = htmlspecialchars(strip_tags($this->descripcion_corta));
        $this->precio = htmlspecialchars(strip_tags($this->precio));
        $this->precio_oferta = htmlspecialchars(strip_tags($this->precio_oferta));
        $this->stock = htmlspecialchars(strip_tags($this->stock));
        $this->imagen_principal = htmlspecialchars(strip_tags($this->imagen_principal));
        $this->marca = htmlspecialchars(strip_tags($this->marca));
        $this->modelo = htmlspecialchars(strip_tags($this->modelo));
        $this->caracteristicas = htmlspecialchars(strip_tags($this->caracteristicas));
        
        // Bind data
        $stmt->bindParam(':codigo', $this->codigo);
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':descripcion_corta', $this->descripcion_corta);
        $stmt->bindParam(':precio', $this->precio);
        $stmt->bindParam(':precio_oferta', $this->precio_oferta);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':imagen_principal', $this->imagen_principal);
        $stmt->bindParam(':marca', $this->marca);
        $stmt->bindParam(':modelo', $this->modelo);
        $stmt->bindParam(':caracteristicas', $this->caracteristicas);
        $stmt->bindParam(':destacado', $this->destacado);
        $stmt->bindParam(':nuevo', $this->nuevo);
        $stmt->bindParam(':activo', $this->activo);
        $stmt->bindParam(':categoria_id', $this->categoria_id);

        // Ejecutar query
        if($stmt->execute()) {
            return true;
        }

        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);

        return false;
    }

    // Actualizar un producto
    public function update() {
        // Crear query
        $query = 'UPDATE ' . $this->table . '
                SET
                codigo = :codigo,
                nombre = :nombre,
                slug = :slug,
                descripcion = :descripcion,
                descripcion_corta = :descripcion_corta,
                precio = :precio,
                precio_oferta = :precio_oferta,
                stock = :stock,
                imagen_principal = :imagen_principal,
                marca = :marca,
                modelo = :modelo,
                caracteristicas = :caracteristicas,
                destacado = :destacado,
                nuevo = :nuevo,
                activo = :activo,
                categoria_id = :categoria_id
                WHERE id = :id';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->codigo = htmlspecialchars(strip_tags($this->codigo));
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->slug = htmlspecialchars(strip_tags($this->slug));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->descripcion_corta = htmlspecialchars(strip_tags($this->descripcion_corta));
        $this->precio = htmlspecialchars(strip_tags($this->precio));
        $this->precio_oferta = htmlspecialchars(strip_tags($this->precio_oferta));
        $this->stock = htmlspecialchars(strip_tags($this->stock));
        $this->imagen_principal = htmlspecialchars(strip_tags($this->imagen_principal));
        $this->marca = htmlspecialchars(strip_tags($this->marca));
        $this->modelo = htmlspecialchars(strip_tags($this->modelo));
        $this->caracteristicas = htmlspecialchars(strip_tags($this->caracteristicas));

        // Bind data
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':codigo', $this->codigo);
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':descripcion_corta', $this->descripcion_corta);
        $stmt->bindParam(':precio', $this->precio);
        $stmt->bindParam(':precio_oferta', $this->precio_oferta);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':imagen_principal', $this->imagen_principal);
        $stmt->bindParam(':marca', $this->marca);
        $stmt->bindParam(':modelo', $this->modelo);
        $stmt->bindParam(':caracteristicas', $this->caracteristicas);
        $stmt->bindParam(':destacado', $this->destacado);
        $stmt->bindParam(':nuevo', $this->nuevo);
        $stmt->bindParam(':activo', $this->activo);
        $stmt->bindParam(':categoria_id', $this->categoria_id);

        // Ejecutar query
        if($stmt->execute()) {
            return true;
        }

        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);

        return false;
    }

    // Eliminar un producto
    public function delete() {
        // Crear query
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bindParam(':id', $this->id);

        // Ejecutar query
        if($stmt->execute()) {
            return true;
        }

        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);

        return false;
    }
}
?>