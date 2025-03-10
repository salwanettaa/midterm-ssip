<?php
// includes/db.php
require_once 'config.php';

class Database {
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
   public function fetchAll($sql, $params = []) {
    try {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $e) {
        error_log("Database error in fetchAll: " . $e->getMessage() . "\nSQL: " . $sql);
        return [];
    }
}
    
 public function fetch($sql, $params = []) {
    try {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $e) {
        error_log("Database error in fetch: " . $e->getMessage() . "\nSQL: " . $sql);
        return null;
    }
}
    
    public function rowCount($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database error in rowCount: " . $e->getMessage() . "\nSQL: " . $sql);
            return 0;
        }
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}

// Initialize database
$db = new Database();
?>