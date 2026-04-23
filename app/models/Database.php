<?php
/**
 * Clase base para acceso a la Base de Datos
 * Usa PDO con prepared statements
 */

require_once __DIR__ . '/../helpers/Security.php';

class Database {
    private $connection;
    private $host;
    private $db_name;
    private $db_user;
    private $db_pass;
    private $db_port;
    
    public function __construct() {
        $this->host = getenv('APP_DB_HOST') ?: getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('APP_DB_NAME') ?: getenv('DB_NAME') ?: 'bolsa_trabajo_utp';
        $this->db_user = getenv('APP_DB_USER') ?: getenv('DB_USER') ?: 'root';
        $this->db_pass = getenv('APP_DB_PASS') ?: getenv('DB_PASS') ?: '';
        $this->db_port = getenv('APP_DB_PORT') ?: getenv('DB_PORT') ?: '3306';

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->db_port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
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
        $params = $this->sanitizeQueryParams($params);
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Sanitizar parámetros para evitar inyecciones y payloads HTML.
     */
    private function sanitizeQueryParams($params) {
        if (!is_array($params)) {
            return $params;
        }

        $cleanParams = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $cleanParams[$key] = Security::sanitizeRecursive($value);
                continue;
            }

            if (is_string($value)) {
                $cleanParams[$key] = Security::sanitizeForStorage($value);
                continue;
            }

            $cleanParams[$key] = $value;
        }

        return $cleanParams;
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