<?php
declare(strict_types=1);
namespace App\Models;
use DomainException;

final class Reservation extends Model
{
    protected string $table = 'reservations';
    protected array $fields = ['room_id', 'user_id', 'date', 'start_time', 'end_time', 'purpose', 'notes', 'status'];
    public function listing(?int $user = null, string $status = '', ?string $from = null, ?string $to = null, ?int $room = null): array
    {
        $sql = 'SELECT b.*, r.name AS room_name, r.type AS room_type, r.image AS room_image, u.name AS teacher FROM reservations b JOIN rooms r ON r.id = b.room_id JOIN users u ON u.id = b.user_id WHERE 1=1';
        $values = [];
        foreach (['b.user_id' => $user, 'b.status' => $status ?: null, 'b.room_id' => $room] as $column => $value) {
            if ($value !== null) { $sql .= " AND {$column} = ?"; $values[] = $value; }
        }
        if ($from !== null) { $sql .= ' AND b.date >= ?'; $values[] = $from; }
        if ($to !== null) { $sql .= ' AND b.date <= ?'; $values[] = $to; }
        $query = $this->db->prepare($sql . ' ORDER BY b.date, b.start_time, b.id');
        $query->execute($values);
        return $query->fetchAll();
    }
    public function conflicts(int $room, string $date, string $start, string $end, ?int $except = null): bool
    {
        $query = $this->db->prepare("SELECT id FROM reservations WHERE room_id = ? AND date = ? AND status IN ('Pendente', 'Confirmado') AND start_time < ? AND end_time > ? AND id <> ? LIMIT 1");
        $query->execute([$room, $date, $end, $start, $except ?? 0]);
        return (bool) $query->fetch();
    }
    public function book(array $data, ?int $id = null): int
    {
        // O bloqueio da sala impede reservas simultâneas no mesmo horário.
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT status FROM rooms WHERE id = ? FOR UPDATE');
            $lock->execute([$data['room_id']]);
            $room = $lock->fetch();
            if (!$room || $room['status'] === 'Manutenção') { throw new DomainException('Esta sala está em manutenção ou não está disponível para reserva.'); }
            if ($this->conflicts((int) $data['room_id'], $data['date'], $data['start_time'], $data['end_time'], $id)) {
                throw new DomainException('Este horário já está reservado. Escolha outro período.');
            }
            $saved = $this->save($data, $id);
            $this->db->commit();
            return $saved;
        } catch (\Throwable $e) { $this->db->rollBack(); throw $e; }
    }
    public function changeStatus(int $id, string $status): void
    {
        $initial = $this->find($id);
        if (!$initial) { throw new DomainException('Reserva não encontrada.'); }
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT status FROM rooms WHERE id = ? FOR UPDATE');
            $lock->execute([$initial['room_id']]);
            $room = $lock->fetch();
            $fresh = $this->db->prepare('SELECT * FROM reservations WHERE id = ? FOR UPDATE');
            $fresh->execute([$id]);
            $record = $fresh->fetch();
            if (!$record || !in_array($status, ['Confirmado','Recusado','Cancelado'], true) || !in_array($record['status'], $status==='Cancelado'?['Pendente','Confirmado']:['Pendente'], true)) { throw new DomainException('Esta reserva já foi analisada ou finalizada.'); }
            if ($status === 'Confirmado' && ($room['status'] === 'Manutenção' || $this->conflicts((int) $record['room_id'], $record['date'], $record['start_time'], $record['end_time'], $id))) {
                throw new DomainException('Não é possível aprovar: sala em manutenção ou horário em conflito.');
            }
            $this->save(['status' => $status], $id);
            $this->db->commit();
        } catch (\Throwable $e) { $this->db->rollBack(); throw $e; }
    }
    public function notifications(int $user, bool $admin): array
    {
        return array_slice($this->listing($admin ? null : $user, $admin ? 'Pendente' : '', date('Y-m-d')), 0, 5);
    }
}
