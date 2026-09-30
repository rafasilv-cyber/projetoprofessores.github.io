<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;
use PDO;

abstract class Model
{
    protected string $table;
    protected array $fields;
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function find(int $id): ?array
    {
        $query = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function save(array $data, ?int $id = null): int
    {
        $data = array_intersect_key($data, array_flip($this->fields));
        if ($id !== null) {
            $assignments = implode(', ', array_map(static fn ($key) => "`{$key}` = ?", array_keys($data)));
            $sql = "UPDATE {$this->table} SET {$assignments} WHERE id = ?";
            $values = [...array_values($data), $id];
        } else {
            $columns = implode('`, `', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            $sql = "INSERT INTO {$this->table} (`{$columns}`) VALUES ({$placeholders})";
            $values = array_values($data);
        }
        $this->db->prepare($sql)->execute($values);
        return $id ?? (int) $this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
    }

    public function options(): array
    {
        return $this->db->query("SELECT id, name FROM {$this->table} ORDER BY name")->fetchAll();
    }

    protected function pattern(string $search): string
    {
        return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
    }
}
