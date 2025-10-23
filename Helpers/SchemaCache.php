<?php
namespace ORM\Helpers;
use PDO;
class SchemaCache {
    private PDO $pdo;
    private string $dbName;
    private static array $cache = [];

    public function __construct(PDO $pdo, string $dbName = "mini_orm") {
        $this->pdo = $pdo;
        $this->dbName = $dbName;
    }

    public function hasTable(string $table): bool {
        if (empty(self::$cache['tables'])) {
            self::$cache['tables'] = $this->getDatabaseTables($this->pdo, $this->dbName);
        }
        return in_array($table, self::$cache['tables'], true);
    }

    public function hasColumn(string $table, string $column): bool {
        if (!isset(self::$cache['columns'][$table])) {
            self::$cache['columns'][$table] = $this->getTableColumns($this->pdo, $this->dbName, $table);
        }
        return in_array($column, self::$cache['columns'][$table], true);
    }


    function getDatabaseTables(PDO $pdo, string $dbName): array {
        $stmt = $pdo->prepare("
        SELECT TABLE_NAME 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = :db
    ");
        $stmt->execute(['db' => $dbName]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    function getTableColumns(PDO $pdo, string $dbName, string $table): array {
        $stmt = $pdo->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table
    ");
        $stmt->execute([
            'db' => $dbName,
            'table' => $table
        ]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }



}
