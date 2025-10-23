<?php
namespace ORM;

use ORM\Helpers\Validator;
use PDO;
use ORM\Exceptions\QueryException;
use PDOException;
use ORM\Helpers\SchemaCache;


class QueryBuilder {
    protected string $table = '';
    protected array $wheres = [];
    protected array $bindings = [];
    protected string $orderBy = '';
    protected ?int $limit = null;
    protected ?int $offset = null;
    public array $with = [];
    public string $tableClass = '';
    protected array $selects = ['*'];


    private SchemaCache $schemaCache;

    public function __construct(protected PDO $pdo) {
        $this->schemaCache = new SchemaCache($pdo);
    }

    public function table(string $table): static {
        if (!Validator::validateTableCheckName($table) || !$this->schemaCache->hasTable($table)) {
            throw new \InvalidArgumentException("Invalid table name: $table");
        }
        $this->table = $table;
        return $this;
    }

    public function setTableClass(string $class): static {
        if (!Validator::validateTableClassCheckName($class)) {
            throw new \InvalidArgumentException("Invalid table class name: $class");
        }
        $this->tableClass = $class;
        return $this;
    }

    public function select(array|string $columns = ['*']): static {
        if (is_string($columns)) {
            $columns = array_map('trim', explode(',', $columns));
        }

        foreach ($columns as $col) {
            if (!\ORM\Helpers\Validator::validateTableCheckName($col) && $col !== '*') {
                throw new \InvalidArgumentException("Invalid column name in select: $col");
            }
            if ($col !== '*' && !$this->schemaCache->hasColumn($this->table, $col)) {
                throw new \InvalidArgumentException("Column '$col' does not exist in table '{$this->table}'.");
            }
        }

        $this->selects = $columns;
        return $this;
    }

    public function where(string $column, string $operator, mixed $value = null): static {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        if (!Validator::validateTableCheckName($column) || !$this->schemaCache->hasColumn($this->table, $column)) {
            throw new \InvalidArgumentException("Invalid column: $column");
        }
        $allowedOperators = ['=', '<>', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'IS', 'IS NOT'];
        $operator = strtoupper(trim($operator));
        if (!in_array($operator, $allowedOperators)) {
            throw new \InvalidArgumentException("Invalid SQL operator: $operator");
        }

        if (in_array($operator, ['IN', 'NOT IN']) && !is_array($value)) {
            throw new \InvalidArgumentException("The value for operator '$operator' must be an array.");
        }

        if ($operator === 'BETWEEN' && (!is_array($value) || count($value) !== 2)) {
            throw new \InvalidArgumentException("The value for the BETWEEN operator must be an array with exactly 2 elements.");
        }

        $param = ':' . str_replace('.', '_', $column) . count($this->bindings);

        if (in_array($operator, ['IN', 'NOT IN'])) {
            $placeholders = [];
            foreach ($value as $i => $v) {
                $ph = $param . "_$i";
                $placeholders[] = $ph;
                $this->bindings[$ph] = $v;
            }
            $this->wheres[] = "$column $operator (" . implode(',', $placeholders) . ")";
        } elseif ($operator === 'BETWEEN') {
            $ph1 = $param . "_1";
            $ph2 = $param . "_2";
            $this->wheres[] = "$column BETWEEN $ph1 AND $ph2";
            $this->bindings[$ph1] = $value[0];
            $this->bindings[$ph2] = $value[1];
        } elseif (in_array($operator, ['IS', 'IS NOT']) && $value === null) {
            $this->wheres[] = "$column $operator NULL";
        } else {
            $this->wheres[] = "$column $operator $param";
            $this->bindings[$param] = $value;
        }

        return $this;
    }


    public function orderBy(string $column, string $direction = 'ASC'): static {
        $this->orderBy = "$column $direction";
        return $this;
    }

    public function limit(int $limit, int $offset = 0): static {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function get(): array {
        try {
            $sql = 'SELECT ' . implode(', ', $this->selects) . ' FROM ' . $this->table;

            if ($this->wheres) {
                $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
            }

            if ($this->orderBy) {
                $sql .= " ORDER BY {$this->orderBy}";
            }

            if ($this->limit !== null) {
                $sql .= " LIMIT {$this->limit}";
                if ($this->offset !== null) {
                    $sql .= " OFFSET {$this->offset}";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            foreach ($this->bindings as $key => $val) $stmt->bindValue($key, $val);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // ------------------ Eager loading (optimize) ------------------
            if ($this->with && $this->tableClass) {
                foreach ($this->with as $relation) {
                    $this->eagerLoad($rows, $relation);
                }
            }

            return $rows;
        } catch (PDOException $e) {
            (new \ORM\Logger())->error("Query failed", ['sql' => $sql, 'bindings' => $this->bindings, 'error' => $e->getMessage()]);
            throw new QueryException("Database query failed.");
        }
    }

    /** ------------------ Eager loading helper ------------------ */
    protected function eagerLoad(array &$rows, string $relation): void {
        if (!$this->tableClass) return;

        $modelInstance = new $this->tableClass($this->pdo);

        if (!method_exists($modelInstance, $relation)) return;

        // Collect all foreign keys for N+1 optimization
        $keys = array_map(fn($r) => $r[$relation.'_id'] ?? null, $rows);
        $keys = array_filter($keys);

        // Call relation method on model to fetch all related at once
        $relatedData = $modelInstance->$relation(batch: true, keys: $keys);

        // Merge related data into rows
        foreach ($rows as &$row) {
            $id = $row[$relation.'_id'] ?? null;
            $row[$relation] = $relatedData[$id] ?? null;
        }
    }

    public function exists(): bool {
        $sql = "SELECT 1 FROM {$this->table}";

        if ($this->wheres) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($this->bindings as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            (new \ORM\Logger())->error("Exists query failed", [
                'sql' => $sql,
                'bindings' => $this->bindings,
                'error' => $e->getMessage()
            ]);
            throw new QueryException("Exists query failed.");
        }
    }


    public function first2(): ?array {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function first(): ?object {
        $this->limit(1);
        $results = $this->get();

        if (empty($results)) return null;

        $instance = new $this->tableClass($this->pdo);
        foreach ($results[0] as $k => $v) {
            $instance->$k = $v;
        }

        return $instance;
    }


    public function count(): int {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table}";
        if ($this->wheres) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($this->bindings as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['cnt'] ?? 0);
        } catch (PDOException $e) {
            (new \ORM\Logger())->error("Count query failed", ['sql' => $sql, 'error' => $e->getMessage()]);
            throw new QueryException("Count query failed.");
        }
    }
}

