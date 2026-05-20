<?php

namespace NewApi\Database;

use NewApi\Database\Connection;
use PDO;

/**
 * Base Model class providing CRUD operations
 */
abstract class Model implements \JsonSerializable
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];
    protected static array $casts = [];
    protected static array $defaults = [];
    protected static bool $isPostgres = true;

    protected array $attributes = [];
    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, static::$fillable) || empty(static::$fillable)) {
                $this->attributes[$key] = $this->castAttribute($key, $value);
            }
        }
        return $this;
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        if (in_array($name, static::$fillable) || empty(static::$fillable)) {
            $this->attributes[$name] = $this->castAttribute($name, $value);
        }
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (!isset(static::$casts[$key])) {
            return $value;
        }

        $castType = static::$casts[$key];
        return match ($castType) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string' => (string) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : $value,
            'timestamp' => $value !== null ? (int) $value : null,
            default => $value,
        };
    }

    protected function prepareForDatabase(array $data): array
    {
        $prepared = [];
        foreach ($data as $key => $value) {
            if (isset(static::$casts[$key]) && in_array(static::$casts[$key], ['array', 'json'])) {
                $prepared[$key] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
            } elseif ($value === null && isset(static::$defaults[$key])) {
                $prepared[$key] = static::$defaults[$key];
            } else {
                $prepared[$key] = $value;
            }
        }
        return $prepared;
    }

    // ===== Query Builder Methods =====

    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::$table);
    }

    public static function table(): string
    {
        return static::$table;
    }

    // ===== CRUD Operations =====

    public static function find(int $id): ?static
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ? LIMIT 1");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }
        $instance = new static($data);
        $instance->exists = true;
        return $instance;
    }

    public static function findWhere(array $conditions): ?static
    {
        $db = Connection::getInstance();
        $where = [];
        $values = [];
        foreach ($conditions as $key => $value) {
            $where[] = "$key = ?";
            $values[] = $value;
        }
        $sql = "SELECT * FROM " . static::$table . " WHERE " . implode(' AND ', $where) . " LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }
        $instance = new static($data);
        $instance->exists = true;
        return $instance;
    }

    public static function findAll(array $conditions = [], string $orderBy = '', int $limit = 0, int $offset = 0): array
    {
        $db = Connection::getInstance();
        $where = [];
        $values = [];
        foreach ($conditions as $key => $value) {
            $where[] = "$key = ?";
            $values[] = $value;
        }
        $sql = "SELECT * FROM " . static::$table;
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(function ($data) {
            $instance = new static($data);
            $instance->exists = true;
            // Debug: check if fill worked
            $attrs = $instance->getAttributes();
            if (empty($attrs) && !empty($data)) {
                error_log('Model::findAll FILL FAILED: data=' . json_encode($data) . ' fillable=' . json_encode(static::$fillable) . ' instance_class=' . static::class);
            }
            return $instance;
        }, $results);
    }

    public static function count(array $conditions = []): int
    {
        $db = Connection::getInstance();
        $where = [];
        $values = [];
        foreach ($conditions as $key => $value) {
            $where[] = "$key = ?";
            $values[] = $value;
        }
        $sql = "SELECT COUNT(*) as count FROM " . static::$table;
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        return (int) $stmt->fetchColumn();
    }

    public static function paginate(int $page = 1, int $perPage = 10, array $conditions = [], string $orderBy = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $total = static::count($conditions);
        $items = static::findAll($conditions, $orderBy, $perPage, $offset);
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    public static function firstWhere(string $column, mixed $value): ?static
    {
        return static::findWhere([$column => $value]);
    }

    public static function where(string $column, mixed $value): array
    {
        return static::findAll([$column => $value]);
    }

    public static function create(array $attributes): static
    {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }

    public function save(): bool
    {
        $data = $this->prepareForDatabase($this->attributes);

        if ($this->exists) {
            return $this->updateRecord($data);
        } else {
            return $this->insertRecord($data);
        }
    }

    protected function insertRecord(array $data): bool
    {
        $db = Connection::getInstance();
        $columns = array_keys($data);
        // Quote column names for PostgreSQL
        $quotedColumns = [];
        foreach ($columns as $col) {
            $quotedColumns[] = static::$isPostgres ? "\"$col\"" : $col;
        }
        $placeholders = array_fill(0, count($columns), '?');
        $values = array_values($data);

        $sql = "INSERT INTO " . static::$table . " (" . implode(', ', $quotedColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($values);

        if ($result) {
            $this->exists = true;
            // 仅当主键是 'id' 且表可能有自增序列时才调用 lastInsertId()
            // PostgreSQL 的 lastInsertId() 调用 lastval()，对无自增序列的表会报错
            if (static::$primaryKey === 'id') {
                try {
                    $lastId = $db->lastInsertId();
                    if ($lastId !== false && $lastId !== '0') {
                        $this->attributes['id'] = (int) $lastId;
                    }
                } catch (\Exception $e) {
                    // 忽略 lastval 未定义错误（表无自增序列时）
                }
            }
        }
        return $result;
    }

    protected function updateRecord(array $data): bool
    {
        $db = Connection::getInstance();
        $primaryKey = $this->attributes[static::$primaryKey] ?? $this->attributes['id'] ?? null;
        if ($primaryKey === null) {
            return false;
        }

        unset($data[static::$primaryKey]);
        unset($data['id']);

        if (empty($data)) {
            return true;
        }

        // Quote column names for PostgreSQL
        $sets = [];
        $values = [];
        foreach ($data as $col => $val) {
            $quoted = static::$isPostgres ? "\"$col\"" : $col;
            $sets[] = "$quoted = ?";
            $values[] = $val;
        }
        $values[] = $primaryKey;

        $pkCol = static::$isPostgres ? "\"" . static::$primaryKey . "\"" : static::$primaryKey;
        $sql = "UPDATE " . static::$table . " SET " . implode(', ', $sets) . " WHERE $pkCol = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(): bool
    {
        $db = Connection::getInstance();
        $primaryKey = $this->attributes[static::$primaryKey] ?? $this->attributes['id'] ?? null;
        if ($primaryKey === null) {
            return false;
        }

        $pkCol = static::$isPostgres ? "\"" . static::$primaryKey . "\"" : static::$primaryKey;
        $sql = "DELETE FROM " . static::$table . " WHERE $pkCol = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$primaryKey]);
        if ($result) {
            $this->exists = false;
        }
        return $result;
    }

    public static function deleteWhere(array $conditions): int
    {
        $db = Connection::getInstance();
        $where = [];
        $values = [];
        foreach ($conditions as $key => $value) {
            $where[] = "$key = ?";
            $values[] = $value;
        }
        $sql = "DELETE FROM " . static::$table . " WHERE " . implode(' AND ', $where);
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        return $stmt->rowCount();
    }

    public static function updateWhere(array $conditions, array $data): int
    {
        $db = Connection::getInstance();
        $sets = array_map(fn($col) => "$col = ?", array_keys($data));
        $where = array_map(fn($col) => "$col = ?", array_keys($conditions));
        $values = array_values($data);
        $values = array_merge($values, array_values($conditions));

        $sql = "UPDATE " . static::$table . " SET " . implode(', ', $sets) . " WHERE " . implode(' AND ', $where);
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        return $stmt->rowCount();
    }

    public static function increment(string $column, int $amount = 1, array $conditions = []): int
    {
        $db = Connection::getInstance();
        $where = [];
        $values = [$amount];
        foreach ($conditions as $key => $value) {
            $where[] = "$key = ?";
            $values[] = $value;
        }
        $sql = "UPDATE " . static::$table . " SET $column = $column + ? WHERE " . implode(' AND ', $where);
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        return $stmt->rowCount();
    }

    public static function decrement(string $column, int $amount = 1, array $conditions = []): int
    {
        return static::increment($column, -$amount, $conditions);
    }

    public static function pluck(string $column, array $conditions = []): array
    {
        $db = Connection::getInstance();
        $where = [];
        $values = [];
        foreach ($conditions as $key => $value) {
            $where[] = "$key = ?";
            $values[] = $value;
        }
        $sql = "SELECT $column FROM " . static::$table;
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }

    public function toJson(): string
    {
        return json_encode($this->attributes, JSON_UNESCAPED_UNICODE);
    }
}
