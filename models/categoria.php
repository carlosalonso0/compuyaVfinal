<?php
class Categoria {
    // Propiedades de la base de datos
    private $conn;
    private $table = 'categorias';

    // Propiedades de Categoria
    public $id;
    public $nombre;
    public $slug;
    public $descripcion;
    public $imagen;
    public $categoria_padre_id;
    public $activo;
    public $fecha_creacion;
    
    // Propiedades adicionales
    public $categoria_padre_nombre;

    // Constructor con DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener todas las categorías
    public function getAll() {
        // Crear query
        $query = 'SELECT c1.*, c2.nombre as categoria_padre_nombre 
                FROM ' . $this->table . ' c1
                LEFT JOIN ' . $this->table . ' c2 ON c1.categoria_padre_id = c2.id
                WHERE c1.activo = true
                ORDER BY c1.nombre ASC';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Ejecutar query
        $stmt->execute();

        return $stmt;
    }

    // Obtener categorías principales (sin padre)
    public function getMainCategories() {
        // Crear query
        $query = 'SELECT * FROM ' . $this->table . ' 
                WHERE categoria_padre_id IS NULL 
                AND activo = true
                ORDER BY nombre ASC';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Ejecutar query
        $stmt->execute();

        return $stmt;
    }

    // Obtener subcategorías por categoría padre
    public function getSubcategorias($categoria_padre_id) {
        // Crear query
        $query = 'SELECT * FROM ' . $this->table . ' 
                WHERE categoria_padre_id = :categoria_padre_id 
                AND activo = true
                ORDER BY nombre ASC';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bindParam(':categoria_padre_id', $categoria_padre_id);

        // Ejecutar query
        $stmt->execute();

        return $stmt;
    }

    // Obtener una sola categoría
    public function getSingle() {
        // Crear query
        $query = 'SELECT c1.*, c2.nombre as categoria_padre_nombre 
                FROM ' . $this->table . ' c1
                LEFT JOIN ' . $this->table . ' c2 ON c1.categoria_padre_id = c2.id
                WHERE c1.id = :id 
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
        $this->nombre = $row['nombre'];
        $this->slug = $row['slug'];
        $this->descripcion = $row['descripcion'];
        $this->imagen = $row['imagen'];
        $this->categoria_padre_id = $row['categoria_padre_id'];
        $this->categoria_padre_nombre = $row['categoria_padre_nombre'];
        $this->activo = $row['activo'];
        $this->fecha_creacion = $row['fecha_creacion'];

        return true;
    }

    // Obtener categoría por slug
    public function getBySlug($slug) {
        // Crear query
        $query = 'SELECT c1.*, c2.nombre as categoria_padre_nombre 
                FROM ' . $this->table . ' c1
                LEFT JOIN ' . $this->table . ' c2 ON c1.categoria_padre_id = c2.id
                WHERE c1.slug = :slug 
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
        $this->nombre = $row['nombre'];
        $this->slug = $row['slug'];
        $this->descripcion = $row['descripcion'];
        $this->imagen = $row['imagen'];
        $this->categoria_padre_id = $row['categoria_padre_id'];
        $this->categoria_padre_nombre = $row['categoria_padre_nombre'];
        $this->activo = $row['activo'];
        $this->fecha_creacion = $row['fecha_creacion'];

        return true;
    }

    // Crear una categoría
    public function create() {
        // Crear query
        $query = 'INSERT INTO ' . $this->table . '
                SET
                nombre = :nombre,
                slug = :slug,
                descripcion = :descripcion,
                imagen = :imagen,
                categoria_padre_id = :categoria_padre_id,
                activo = :activo';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->slug = htmlspecialchars(strip_tags($this->slug));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->imagen = htmlspecialchars(strip_tags($this->imagen));
        
        // Bind data
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':categoria_padre_id', $this->categoria_padre_id);
        $stmt->bindParam(':activo', $this->activo);

        // Ejecutar query
        if($stmt->execute()) {
            return true;
        }

        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);

        return false;
    }

    // Actualizar una categoría
    public function update() {
        // Crear query
        $query = 'UPDATE ' . $this->table . '
                SET
                nombre = :nombre,
                slug = :slug,
                descripcion = :descripcion,
                imagen = :imagen,
                categoria_padre_id = :categoria_padre_id,
                activo = :activo
                WHERE id = :id';

        // Preparar statement
        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->slug = htmlspecialchars(strip_tags($this->slug));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->imagen = htmlspecialchars(strip_tags($this->imagen));

        // Bind data
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':categoria_padre_id', $this->categoria_padre_id);
        $stmt->bindParam(':activo', $this->activo);
 
        // Ejecutar query
        if($stmt->execute()) {
            return true;
        }
 
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
 
        return false;
    }
 
    // Eliminar una categoría
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