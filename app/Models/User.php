<?php
declare(strict_types=1);
namespace App\Models;

final class User extends Model
{
    protected string $table = 'users';
    protected array $fields = ['name', 'email', 'role', 'password_hash'];

    public function byEmail(string $email): ?array
    {
        $query = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $query->execute([$email]);
        return $query->fetch() ?: null;
    }

    public function search(string $search = ''): array
    {
        $query = $this->db->prepare("SELECT u.*, (SELECT COUNT(*) FROM tickets t WHERE t.user_id = u.id) AS ticket_count FROM users u WHERE u.name LIKE ? ESCAPE '!' OR u.email LIKE ? ESCAPE '!' ORDER BY u.name");
        $query->execute([$this->pattern($search), $this->pattern($search)]);
        return $query->fetchAll();
    }

    public function emailTaken(string $email, ?int $except = null): bool
    {
        $query = $this->db->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $query->execute([$email, $except ?? 0]);
        return (bool) $query->fetch();
    }
}
