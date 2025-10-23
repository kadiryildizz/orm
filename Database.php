<?php
namespace ORM;

use PDO;
use PDOException;
use ORM\Exceptions\DatabaseException;

class Database {
    private static ?PDO $instance = null;
    private static ?Logger $logger = null;


    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'db';
            $db   = getenv('DB_DATABASE') ?: 'mini_orm';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASSWORD') ?: 'root';
            $charset = 'utf8mb4';
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            if (self::$logger === null) self::$logger = new Logger();
            try {
                 $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                self::$instance = new PDO($dsn, $user, $pass, $options);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                var_dump($e);die;
                self::$logger->error("DB connection failed", ['dsn' => $dsn, 'error' => $e->getMessage()]);
                throw new DatabaseException("Database connection could not be established.");

                //die("DB Connection Failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function setConnection(PDO $pdo): void {
        self::$instance = $pdo;
    }
}
