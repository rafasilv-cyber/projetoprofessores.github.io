<?php
declare(strict_types=1);
namespace App\Models;

final class Room extends Model
{
    public const TYPES = ['Laboratório', 'Biblioteca', 'Auditório', 'Informática', 'Sala de Aula', 'Reunião'];
    public const STATUSES = ['Disponível', 'Ocupado', 'Manutenção'];
    protected string $table = 'rooms';
    protected array $fields = ['name', 'type', 'capacity', 'floor', 'features', 'image', 'status', 'description'];
    public function search(string $search = '', string $type = '', int $capacity = 0, bool $available = false): array
    {
        $sql = "SELECT * FROM rooms WHERE (name LIKE ? ESCAPE '!' OR type LIKE ? ESCAPE '!' OR features LIKE ? ESCAPE '!') AND capacity >= ?";
        $values = [$this->pattern($search), $this->pattern($search), $this->pattern($search), $capacity];
        if ($type !== '') { $sql .= ' AND type = ?'; $values[] = $type; }
        if ($available) { $sql .= " AND status = 'Disponível'"; }
        $query = $this->db->prepare($sql . ' ORDER BY id');
        $query->execute($values);
        return $query->fetchAll();
    }
}
