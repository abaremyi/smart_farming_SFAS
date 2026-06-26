<?php
/**
 * SFAS — Database Connection
 * File: config/database.php
 */
class Database {
    private static $connection = null;

    public static function getInstance() { return self::getConnection(); }

    public static function getConnection(): ?PDO {
        if (self::$connection === null) {
            // Try .env first, fallback to hardcoded for XAMPP dev
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3308';
            $db   = $_ENV['DB_NAME'] ?? 'sfas_db';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            try {
                self::$connection = new PDO(
                    "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
                    $user, $pass
                );
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['success'=>false,'message'=>'Database connection failed: '.$e->getMessage()]));
            }
        }
        return self::$connection;
    }
}
