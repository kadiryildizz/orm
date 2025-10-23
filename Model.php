<?php
namespace ORM;

use PDO;
use ORM\Exceptions\QueryException;
use PDOException;


abstract class Model {
    protected string $table;
    protected array $fillable = [];
    protected array $relations = [];
    protected ?PDO $pdo = null;

    protected array $attributes = [];

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function __get($key) {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value) {
        $this->attributes[$key] = $value;
    }

    /** ---------------- CRUD ---------------- */
    public static function create(array $data): static {
        $instance = new static();
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(',:', array_keys($data));
        try {
            $stmt = $instance->pdo->prepare("INSERT INTO {$instance->table} ($columns) VALUES ($placeholders)");
            foreach ($data as $k => $v) $stmt->bindValue(":$k", $v);
            $stmt->execute();
            $id = $instance->pdo->lastInsertId();
            return static::find($id);
        } catch (PDOException $e) {
            (new Logger())->error("Insert failed", ['table' => $instance->table, 'data' => $data, 'error' => $e->getMessage()]);
            throw new QueryException("Insert operation failed.");
        }
    }

    public static function find(int $id): ?static {
        $instance = new static();
        try {
            $stmt = $instance->pdo->prepare("SELECT * FROM {$instance->table} WHERE id=:id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) return null;

            foreach ($row as $key => $value) {
                $instance->$key = $value;
            }

            return $instance;
        } catch (PDOException $e) {
            (new Logger())->error("Select failed", ['table' => $instance->table, 'id' => $id, 'error' => $e->getMessage()]);
            throw new QueryException("Select operation failed.");
        }
    }

    public static function update(int $id, array $data): static {
        $instance = new static();
        $sets = [];
        $data = array_intersect_key($data, array_flip($instance->fillable));
        foreach ($data as $k => $v) $sets[] = "$k=:$k";
        $sql = "UPDATE {$instance->table} SET " . implode(',', $sets) . " WHERE id=:id";
        try {
            $stmt = $instance->pdo->prepare($sql);
            foreach ($data as $k => $v) $stmt->bindValue(":$k", $v);
            $stmt->bindValue(":id", $id);
            $stmt->execute();
            return static::find($id);
        } catch (PDOException $e) {
            (new Logger())->error("Update failed", ['table' => $instance->table, 'id' => $id, 'data' => $data, 'error' => $e->getMessage()]);
            throw new QueryException("Update operation failed.");
        }
    }


    public static function delete(int $id): bool {
        $instance = new static();
        try {
            $stmt = $instance->pdo->prepare("DELETE FROM {$instance->table} WHERE id=:id");
            $stmt->bindValue(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            (new Logger())->error("Delete failed", ['table' => $instance->table, 'id' => $id, 'data' => $data, 'error' => $e->getMessage()]);
            throw new QueryException("Delete operation failed.");
        }
    }


    /** ---------------- Query Builder ---------------- */
    public static function query(): QueryBuilder {
        $instance = new static();
        return (new QueryBuilder($instance->pdo))->table($instance->table);
    }

    public static function where(string $col, string $op, mixed $val = null): QueryBuilder {
        if ($val === null) {
            $val = $op;
            $op = '=';
        }
        return static::query()->where($col,$op,$val);
    }

    public static function with(string ...$relations): QueryBuilder {
        $instance = new static();
        $qb = new QueryBuilder($instance->pdo);
        $qb->table($instance->table)->setTableClass(static::class);
        $qb->with = $relations; // eager loading relations
        return $qb;
    }

    /** ---------------- Relations ---------------- */
    protected function belongsTo(string $relatedClass, string $foreignKey = null, string $ownerKey = 'id', bool $batch = false, array $keys = []): ?array
    {
        $related = new $relatedClass($this->pdo);
        $foreignKey ??= strtolower((new \ReflectionClass($relatedClass))->getShortName()) . '_id';

        if ($batch && $keys) {
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $sql = "SELECT * FROM {$related->table} WHERE $ownerKey IN ($placeholders)";
            try {
                $stmt = $related->pdo->prepare($sql);
                $stmt->execute(array_values($keys));
                $all = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                (new Logger())->error("Eager load failed", [
                    'sql' => $sql,
                    'keys' => $keys,
                    'error' => $e->getMessage()
                ]);
                throw new QueryException("Eager loading failed.");
            }

            $result = [];
            foreach ($all as $r) {
                $result[$r[$ownerKey]] = $r;
            }
            return $result;
        }

        // 👇 attributes üzerinden erişim yapıyoruz
        $foreignKeyValue = $this->$foreignKey ?? ($this->attributes[$foreignKey] ?? null);
        if ($foreignKeyValue === null) {
            (new Logger())->warning("BelongsTo: '$foreignKey' not found in attributes", [
                'model' => static::class,
                'attributes' => $this->attributes
            ]);
            return [];
        }

        try {
            return $related->find((int)$foreignKeyValue)?->toArray();
        } catch (\PDOException $e) {
            (new Logger())->error("BelongsTo relation failed", [
                'foreignKey' => $foreignKey,
                'error' => $e->getMessage()
            ]);
            throw new QueryException("BelongsTo relation failed.");
        }
    }

    protected function hasOne(string $relatedClass, string $foreignKey = null, string $localKey = 'id'): ?array {
        $related = new $relatedClass($this->pdo);
        $foreignKey ??= strtolower((new \ReflectionClass($this))->getShortName()).'_id';
        $localKeyValue = $this->$localKey ?? ($this->attributes[$localKey] ?? null);

        if ($localKeyValue === null) {
            (new Logger())->warning("hasOne: '$foreignKey' not found in attributes", [
                'model' => static::class,
                'attributes' => $this->attributes
            ]);
            return [];
        }
        $sql = "SELECT * FROM {$related->table} WHERE $foreignKey=:local LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['local' => $localKeyValue]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            (new Logger())->error("HasOne relation failed", [
                'sql' => $sql,
                'foreignKey' => $foreignKey,
                'local' => $localKeyValue,
                'error' => $e->getMessage()
            ]);
            throw new QueryException("HasOne relation failed.");
        }
    }

    protected function hasMany(
        string $relatedClass,
        string $foreignKey = null,
        string $localKey = 'id'
    ): ?array {
        $related = new $relatedClass($this->pdo);
        $foreignKey ??= strtolower((new \ReflectionClass($this))->getShortName()) . '_id';

        $localKeyValue = $this->$localKey ?? ($this->attributes[$localKey] ?? null);

        if ($localKeyValue === null) {
            (new Logger())->warning("hasMany: '$foreignKey' not found in attributes", [
                'model' => static::class,
                'attributes' => $this->attributes
            ]);
            return [];
        }

        $sql = "SELECT * FROM {$related->table} WHERE $foreignKey=:local";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['local' => $localKeyValue]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            (new Logger())->error("HasMany relation failed", [
                'sql' => $sql,
                'foreignKey' => $foreignKey,
                'local' => $localKeyValue,
                'error' => $e->getMessage()
            ]);
            throw new QueryException("HasMany relation failed.");
        }
    }

    protected function belongsToMany(
        string $relatedClass,
        string $pivotTable,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $localKey = 'id',
        string $relatedKey = 'id'
    ): array {
        $related = new $relatedClass($this->pdo);

        $localKeyValue = $this->$localKey ?? ($this->attributes[$localKey] ?? null);

        if ($localKeyValue === null) {
            (new Logger())->warning("belongsToMany: '$foreignPivotKey and $relatedPivotKey' not found in attributes", [
                'model' => static::class,
                'attributes' => $this->attributes
            ]);
            return [];
        }

        $sql = "SELECT r.* FROM {$related->table} r
            JOIN $pivotTable p ON p.$relatedPivotKey = r.$relatedKey
            WHERE p.$foreignPivotKey = :local";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['local' => $localKeyValue]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            (new Logger())->error("BelongsToMany relation failed", [
                'sql' => $sql,
                'pivot' => $pivotTable,
                'local' => $localKeyValue,
                'error' => $e->getMessage()
            ]);
            throw new QueryException("BelongsToMany relation failed.");
        }
    }

    /** ---------------- Helper ---------------- */
    public static function count(): int
    {
        $instance = new static();
        return static::query()->count();
    }


    public function toArray(): array {
        return $this->attributes;
    }

    public function toJson(): string {
        return json_encode($this->toArray());
    }

}
