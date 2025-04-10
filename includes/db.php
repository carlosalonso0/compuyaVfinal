<?php
require_once 'config.php';

class Database {
    private $connection;
    private static $instance;
    
    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            die('Error de conexión a la base de datos: ' . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function escapeString($value) {
        return $this->connection->real_escape_string($value);
    }
    
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }
    
    public function getAffectedRows() {
        return $this->connection->affected_rows;
    }
    
    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }
    
    public function __destruct() {
        $this->closeConnection();
    }
}
?>