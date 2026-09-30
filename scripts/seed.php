<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !isset($pdo)) { exit; }
// Só adiciona a demonstração em banco sem salas; não substitui dados existentes.
if ((int) $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn() > 0) { echo "Salas existentes: demonstração não reinserida.\n"; return; }
$pdo->beginTransaction();
try {
    $people = [
        ['Ana Lima', 'ana.lima@escola.edu.br', 'Usuário'],
        ['Maria Santos', 'maria.santos@escola.edu.br', 'Administrador'],
        ['Carlos Mendes', 'carlos.mendes@escola.edu.br', 'Usuário'],
        ['Ricardo Alves', 'ricardo.alves@escola.edu.br', 'Usuário'],
    ];
    $userIds = [];
    foreach ($people as [$name, $email, $role]) {
        $query = $pdo->prepare('SELECT id FROM users WHERE email = ?'); $query->execute([$email]);
        $existing = $query->fetchColumn();
        if ($existing) { $userIds[] = (int) $existing; continue; }
        $pdo->prepare('INSERT INTO users (name,email,role,password_hash) VALUES (?,?,?,?)')->execute([$name,$email,$role,password_hash('SalaHub@2026', PASSWORD_DEFAULT)]);
        $userIds[] = (int) $pdo->lastInsertId();
    }
    $rooms = [
        ['Laboratório de Ciências','Laboratório',30,'2º Andar','Bancadas, Microscópios, Projetor, Pia','scene-1.jpg','Disponível','Laboratório equipado para aulas de Química, Física e Biologia. Bancadas individuais, microscópios ópticos, kit de vidraria e projetor multimídia fixo.'],
        ['Biblioteca','Biblioteca',50,'Térreo','Wi-Fi, Computadores, Mesas individuais, Silêncio','scene-2.jpg','Ocupado','Biblioteca com acervo de 12 000 títulos, 8 computadores para pesquisa, mesas de estudo individual e em grupo. Ambiente silencioso obrigatório.'],
        ['Auditório Principal','Auditório',150,'Térreo','Palco, Sonorização, Projeção, Ar-condicionado','scene-3.jpg','Disponível','Auditório principal com palco, sistema de sonorização profissional, dois projetores e sistema de ar-condicionado central. Ideal para palestras e eventos.'],
        ['Sala de Informática 1','Informática',28,'1º Andar','32 Computadores, Projetor, Wi-Fi, Ar-condicionado','scene-4.jpg','Disponível','Sala equipada com 32 computadores atualizados, projetor central e internet de alta velocidade. Softwares educativos instalados.'],
        ['Sala Multiuso A','Sala de Aula',35,'1º Andar','Projetor, Quadro Branco, Ar-condicionado','scene-5.jpg','Disponível','Sala multiuso com mobiliário modular para diversas configurações. Projetor, quadro branco interativo e sistema de ar-condicionado.'],
        ['Sala de Reuniões','Reunião',12,'3º Andar','TV 65", Videoconferência, Café, Quadro Branco','scene-6.jpg','Manutenção','Sala de reuniões com TV 65", sistema de videoconferência, quadro branco e espaço para café. Em manutenção preventiva.'],
    ];
    $roomIds=[];
    foreach ($rooms as $room) {
        $pdo->prepare('INSERT INTO rooms (name,type,capacity,floor,features,image,status,description) VALUES (?,?,?,?,?,?,?,?)')->execute($room);
        $roomIds[]=(int)$pdo->lastInsertId();
    }
    $bookings = [
        [0,0,1,'08:00','10:00','Aula prática de Química — Reações ácido-base','Confirmado'],
        [3,0,2,'14:00','16:00','Pesquisa orientada — 9º Ano','Confirmado'],
        [2,0,6,'09:00','11:00','Apresentação de projetos — Feira de Ciências','Pendente'],
        [1,0,-6,'13:00','14:00','Pesquisa bibliográfica — 8º Ano','Confirmado'],
        [4,2,1,'10:00','12:00','Oficina de Matemática','Pendente'],
        [0,3,2,'09:00','11:00','Experimento de Física — Ondas','Pendente'],
        [2,1,8,'14:00','17:00','Palestra — Semana da Saúde','Confirmado'],
    ];
    foreach ($bookings as [$room,$user,$offset,$start,$end,$purpose,$status]) {
        $date=(new DateTimeImmutable('today'))->modify(($offset>=0?'+':'').$offset.' days')->format('Y-m-d');
        $pdo->prepare("INSERT INTO reservations (room_id,user_id,date,start_time,end_time,purpose,notes,status) VALUES (?,?,?,?,?,?,'',?)")->execute([$roomIds[$room],$userIds[$user],$date,$start,$end,$purpose,$status]);
    }
    foreach ([['Infraestrutura','Manutenção e conservação dos ambientes escolares.'],['Equipamentos','Projetores, computadores e recursos audiovisuais.'],['Suporte pedagógico','Apoio às atividades de professores e coordenação.']] as $category) {
        $pdo->prepare('INSERT IGNORE INTO categories (name,description) VALUES (?,?)')->execute($category);
    }
    $pdo->commit();
    echo "Demonstração criada: 6 salas, 7 reservas e 4 usuários. Senha inicial: SalaHub@2026\n";
} catch (Throwable $e) { $pdo->rollBack(); throw $e; }
