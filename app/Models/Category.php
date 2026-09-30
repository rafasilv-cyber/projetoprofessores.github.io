<?php
declare(strict_types=1);
namespace App\Models;

final class Category extends Model
{
    protected string $table = 'categories';
    protected array $fields = ['name', 'description'];

    public function search(string $search = ''): array
    {
        $query = $this->db->prepare("SELECT c.*, (SELECT COUNT(*) FROM tickets t WHERE t.category_id = c.id) AS ticket_count FROM categories c WHERE c.name LIKE ? ESCAPE '!' OR c.description LIKE ? ESCAPE '!' ORDER BY c.name");
        $query->execute([$this->pattern($search), $this->pattern($search)]);
        return $query->fetchAll();
    }

    public function nameTaken(string $name, ?int $except = null): bool
    {
        $query = $this->db->prepare('SELECT id FROM categories WHERE name = ? AND id <> ?');
        $query->execute([$name, $except ?? 0]);
        return (bool) $query->fetch();
    }
}
