<?php
/**
 * Clase base para acceso a la Base de Datos
 * Usa PDO con prepared statements
 */

class Database {
    private $connection;
    private $host = 'localhost';
    private $db_name = 'bolsa_trabajo_utp';
    private $db_user = 'root';
    private $db_pass = '';
    
    public function __construct() {
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->connection = new PDO(
                $dsn,
                $this->db_user,
                $this->db_pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    /**
     * Ejecutar consulta con prepared statement
     */
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Obtener todos los registros
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Obtener un registro
     */
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Insertar registro
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $this->query($sql, array_values($data));
        return $this->connection->lastInsertId();
    }
    
    /**
     * Actualizar registro
     */
    public function update($table, $data, $where) {
        $setClause = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn($k) => "$k = ?", array_keys($where)));
        
        $sql = "UPDATE $table SET $setClause WHERE $whereClause";
        $params = array_merge(array_values($data), array_values($where));
        
        $this->query($sql, $params);
    }
    
    /**
     * Eliminar registro
     */
    public function delete($table, $where) {
        $whereClause = implode(' AND ', array_map(fn($k) => "$k = ?", array_keys($where)));
        $sql = "DELETE FROM $table WHERE $whereClause";
        
        $this->query($sql, array_values($where));
    }
    
    /**
     * Contar registros
     */
    public function count($table, $where = []) {
        if (empty($where)) {
            $sql = "SELECT COUNT(*) as total FROM $table";
            $result = $this->fetchOne($sql);
        } else {
            $whereClause = implode(' AND ', array_map(fn($k) => "$k = ?", array_keys($where)));
            $sql = "SELECT COUNT(*) as total FROM $table WHERE $whereClause";
            $result = $this->fetchOne($sql, array_values($where));
        }
        
        return $result['total'] ?? 0;
    }
}
?>