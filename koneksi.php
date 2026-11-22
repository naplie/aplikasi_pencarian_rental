<?php
class Database {
    private $host     = '127.0.0.1';
    private $dbname   = 'rental_console';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Koneksi Gagal: " . $e->getMessage());
        }
        return $this->conn;
    }
}

// Instansiasi objek agar variabel $pdo langsung tersedia di file lain
$database = new Database();
$pdo = $database->getConnection();
?>