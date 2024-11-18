<?php
namespace Config;

use Dotenv\Dotenv;

class Database
{
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private ?\PDO $conn = null;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/../");
        $dotenv->load();

        $this->host = $_ENV['DB_HOST'] ?? '';
        $this->db_name = $_ENV['DB_NAME'] ?? '';
        $this->username = $_ENV['DB_USER'] ?? '';
        $this->password = $_ENV['DB_PASS'] ?? '';

        if (empty($this->host) || empty($this->db_name) || empty($this->username) || empty($this->password)) {
            throw new \Exception("Database configuration is incomplete. Please check .env settings.");
        }
    }

    public function getConnection(): ?\PDO
    {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
                $this->conn = new \PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $exception) {
                throw new \PDOException("Database connection error: " . $exception->getMessage());
            }
        }
        return $this->conn;
    }
}
