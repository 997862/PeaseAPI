<?php

namespace NewApi\Database;

use NewApi\Database\Connection;
use PDO;

class QueryBuilder
{
    private string $table;
    private array $wheres = [];
    private array $whereBindings = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $select = ['*'];
    private array $joins = [];
    private ?string $groupBy = null;
    private array $having = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function select(array $columns): static
    {
        $this->select = $columns;
        return $this;
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        if ($value === null && is_string($operator)) {
            $this->wheres[] = "$column = ?";
            $this->whereBindings[] = $operator;
        } else {
            $this->wheres[] = "$column $operator ?";
            $this->whereBindings[] = $value;
        }
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            $this->wheres[] = "1 = 0";
            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "$column IN ($placeholders)";
        $this->whereBindings = array_merge($this->whereBindings, $values);
        return $this;
    }

    public function whereNotIn(string $column, array $values): static
    {
        if (empty($values)) {
            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "$column NOT IN ($placeholders)";
        $this->whereBindings = array_merge($this->whereBindings, $values);
        return $this;
    }

    public function whereLike(string $column, string $value): static
    {
        $this->wheres[] = "$column LIKE ?";
        $this->whereBindings[] = "%$value%";
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->wheres[] = "$column IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->wheres[] = "$column IS NOT NULL";
        return $this;
    }

    public function whereRaw(string $raw, array $bindings = []): static
    {
        $this->wheres[] = $raw;
        $this->whereBindings = array_merge($this->whereBindings, $bindings);
        return $this;
    }

    public function join(string $table, string $first, string $operator = '=', ?string $second = null): static
    {
        if ($second === null) {
            $this->joins[] = "JOIN $table ON $first";
        } else {
            $this->joins[] = "JOIN $table ON $first $operator $second";
        }
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator = '=', ?string $second = null): static
    {
        if ($second === null) {
            $this->joins[] = "LEFT JOIN $table ON $first";
        } else {
            $this->joins[] = "LEFT JOIN $table ON $first $operator $second";
        }
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    public function groupBy(string $column): static
    {
        $this->groupBy = $column;
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $db = Connection::getInstance();
        $sql = $this->buildSelectSql();
        $stmt = $db->prepare($sql);
        $stmt->execute($this->whereBindings);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function first(): array|false
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? false;
    }

    public function find(int $id): array|false
    {
        return $this->where('id', $id)->first();
    }

    public function count(string $column = '*'): int
    {
        $db = Connection::getInstance();
        $whereSql = !empty($this->wheres) ? "WHERE " . implode(' AND ', $this->wheres) : '';
        $sql = "SELECT COUNT($column) as count FROM $this->table $whereSql";
        $stmt = $db->prepare($sql);
        $stmt->execute($this->whereBindings);
        return (int) $stmt->fetchColumn();
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function pluck(string $column): array
    {
        $db = Connection::getInstance();
        $sql = $this->buildSelectSql([$column]);
        $stmt = $db->prepare($sql);
        $stmt->execute($this->whereBindings);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function value(string $column): mixed
    {
        $result = $this->select([$column])->first();
        return $result[$column] ?? null;
    }

    public function update(array $data): int
    {
        $db = Connection::getInstance();
        $sets = array_map(fn($col) => "$col = ?", array_keys($data));
        $bindings = array_values($data);
        $bindings = array_merge($bindings, $this->whereBindings);
        $whereSql = !empty($this->wheres) ? "WHERE " . implode(' AND ', $this->wheres) : '';
        $sql = "UPDATE $this->table SET " . implode(', ', $sets) . " $whereSql";
        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $db = Connection::getInstance();
        $whereSql = !empty($this->wheres) ? "WHERE " . implode(' AND ', $this->wheres) : '';
        $sql = "DELETE FROM $this->table $whereSql";
        $stmt = $db->prepare($sql);
        $stmt->execute($this->whereBindings);
        return $stmt->rowCount();
    }

    public function insert(array $data): int|string
    {
        $db = Connection::getInstance();
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $values = array_values($data);
        $sql = "INSERT INTO $this->table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        return $db->lastInsertId();
    }

    public function insertMultiple(array $data): int
    {
        if (empty($data)) {
            return 0;
        }
        $db = Connection::getInstance();
        $columns = array_keys($data[0]);
        $sql = "INSERT INTO $this->table (" . implode(', ', $columns) . ") VALUES ";
        $allBindings = [];
        foreach ($data as $row) {
            $placeholders = array_fill(0, count($row), '?');
            $sql .= "(" . implode(', ', $placeholders) . "), ";
            $allBindings = array_merge($allBindings, array_values($row));
        }
        $sql = rtrim($sql, ', ');
        $stmt = $db->prepare($sql);
        $stmt->execute($allBindings);
        return $stmt->rowCount();
    }

    private function buildSelectSql(?array $overrideSelect = null): string
    {
        $columns = $overrideSelect ?? $this->select;
        $selectStr = implode(', ', $columns);
        $sql = "SELECT $selectStr FROM $this->table";
        foreach ($this->joins as $join) {
            $sql .= " $join";
        }
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        if ($this->groupBy) {
            $sql .= " GROUP BY $this->groupBy";
        }
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }
        if ($this->limit !== null) {
            $sql .= " LIMIT $this->limit";
        }
        if ($this->offset !== null) {
            $sql .= " OFFSET $this->offset";
        }
        return $sql;
    }

    public function toSql(): string
    {
        return $this->buildSelectSql();
    }

    public function getBindings(): array
    {
        return $this->whereBindings;
    }

    public function clone(): static
    {
        $new = new static($this->table);
        $new->wheres = $this->wheres;
        $new->whereBindings = $this->whereBindings;
        $new->orderBy = $this->orderBy;
        $new->limit = $this->limit;
        $new->offset = $this->offset;
        $new->select = $this->select;
        $new->joins = $this->joins;
        $new->groupBy = $this->groupBy;
        return $new;
    }
}
