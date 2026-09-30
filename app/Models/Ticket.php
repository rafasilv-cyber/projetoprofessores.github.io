<?php
declare(strict_types=1);
namespace App\Models;

final class Ticket extends Model
{
    public const STATUSES = ['Aberto', 'Em andamento', 'Resolvido', 'Fechado'];
    public const PRIORITIES = ['Baixa', 'Média', 'Alta', 'Urgente'];
    protected string $table = 'tickets';
    protected array $fields = ['subject', 'description', 'user_id', 'category_id', 'assignee_id', 'status', 'priority'];

    public function search(string $search = '', string $status = '', string $priority = '', string $category = '', ?int $visibleTo = null): array
    {
        $sql = "SELECT t.*, u.name AS user_name, c.name AS category_name, a.name AS assignee_name FROM tickets t JOIN users u ON u.id = t.user_id JOIN categories c ON c.id = t.category_id LEFT JOIN users a ON a.id = t.assignee_id WHERE (t.subject LIKE ? ESCAPE '!' OR t.description LIKE ? ESCAPE '!' OR u.name LIKE ? ESCAPE '!' OR c.name LIKE ? ESCAPE '!' OR CAST(t.id AS CHAR) = ?)";
        $values = [$this->pattern($search), $this->pattern($search), $this->pattern($search), $this->pattern($search), ltrim($search, '#0')];
        if ($visibleTo !== null) {
            $sql .= ' AND (t.user_id = ? OR t.assignee_id = ?)';
            $values[] = $visibleTo;
            $values[] = $visibleTo;
        }
        foreach (['status' => $status, 'priority' => $priority, 'category_id' => $category] as $key => $value) {
            if ($value !== '') {
                $sql .= " AND t.{$key} = ?";
                $values[] = $value;
            }
        }
        $query = $this->db->prepare($sql . ' ORDER BY t.created_at DESC, t.id DESC');
        $query->execute($values);
        return $query->fetchAll();
    }

    public function detail(int $id, ?int $visibleTo = null): ?array
    {
        $sql = 'SELECT t.*, u.name AS user_name, u.email AS user_email, c.name AS category_name, a.name AS assignee_name FROM tickets t JOIN users u ON u.id = t.user_id JOIN categories c ON c.id = t.category_id LEFT JOIN users a ON a.id = t.assignee_id WHERE t.id = ?';
        $values = [$id];
        if ($visibleTo !== null) {
            $sql .= ' AND (t.user_id = ? OR t.assignee_id = ?)';
            $values[] = $visibleTo;
            $values[] = $visibleTo;
        }
        $query = $this->db->prepare($sql);
        $query->execute($values);
        return $query->fetch() ?: null;
    }

    public function summary(): array
    {
        return $this->db->query("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'Aberto'), 0) AS open, COALESCE(SUM(status = 'Em andamento'), 0) AS progress, COALESCE(SUM(status IN ('Resolvido', 'Fechado')), 0) AS resolved FROM tickets")->fetch();
    }
}
