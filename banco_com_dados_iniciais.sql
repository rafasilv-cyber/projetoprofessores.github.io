-- SalaHub: estrutura e dados iniciais. Importar em banco novo/vazio.
-- Contas de demonstração: senha SalaHub@2026 (armazenada como hash).
SET NAMES utf8mb4;
-- MySQL 8+ / MariaDB 10.4+. Não apaga registros existentes.
CREATE DATABASE IF NOT EXISTS portal_chamados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portal_chamados;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NULL,
    role ENUM('Administrador', 'Técnico', 'Usuário') NOT NULL DEFAULT 'Usuário',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_name CHECK (CHAR_LENGTH(TRIM(name)) > 0),
    CONSTRAINT chk_users_email CHECK (CHAR_LENGTH(TRIM(email)) > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    type ENUM('Laboratório','Biblioteca','Auditório','Informática','Sala de Aula','Reunião') NOT NULL,
    capacity SMALLINT UNSIGNED NOT NULL,
    floor VARCHAR(60) NOT NULL,
    features VARCHAR(500) NOT NULL,
    image VARCHAR(100) NOT NULL DEFAULT 'scene-5.jpg',
    status ENUM('Disponível','Ocupado','Manutenção') NOT NULL DEFAULT 'Disponível',
    description TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_room_name CHECK (CHAR_LENGTH(TRIM(name)) > 0),
    CONSTRAINT chk_room_capacity CHECK (capacity > 0),
    CONSTRAINT uq_room_name UNIQUE (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose VARCHAR(180) NOT NULL,
    notes TEXT NOT NULL,
    status ENUM('Pendente','Confirmado','Recusado','Cancelado') NOT NULL DEFAULT 'Pendente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    CONSTRAINT fk_booking_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_booking_hours CHECK (end_time > start_time AND start_time >= '07:00:00' AND end_time <= '18:00:00'),
    CONSTRAINT chk_booking_purpose CHECK (CHAR_LENGTH(TRIM(purpose)) > 0),
    INDEX idx_booking_slot (room_id, date, status, start_time, end_time),
    INDEX idx_booking_user_date (user_id,date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_categories_name UNIQUE (name),
    CONSTRAINT chk_categories_name CHECK (CHAR_LENGTH(TRIM(name)) > 0),
    CONSTRAINT chk_categories_description CHECK (CHAR_LENGTH(TRIM(description)) > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    assignee_id INT UNSIGNED NULL,
    status ENUM('Aberto', 'Em andamento', 'Resolvido', 'Fechado') NOT NULL DEFAULT 'Aberto',
    priority ENUM('Baixa', 'Média', 'Alta', 'Urgente') NOT NULL DEFAULT 'Média',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tickets_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tickets_assignee FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_tickets_subject CHECK (CHAR_LENGTH(TRIM(subject)) > 0),
    CONSTRAINT chk_tickets_description CHECK (CHAR_LENGTH(TRIM(description)) > 0),
    INDEX idx_tickets_status (status),
    INDEX idx_tickets_created_at (created_at),
    INDEX idx_tickets_priority (priority)
) ENGINE=InnoDB;

START TRANSACTION;
INSERT INTO users (id,name,email,role,password_hash) VALUES (1,'Ana Lima','ana.lima@escola.edu.br','Usuário','$2y$10$x0ZUB0zpMOexBX/sMEP5Ve.jNvBBR.ovxweMQ5Oicx9G7eDlitg/K');
INSERT INTO users (id,name,email,role,password_hash) VALUES (2,'Maria Santos','maria.santos@escola.edu.br','Administrador','$2y$10$Ns9HMPxbIUEkOQgvuA2S8uZBRXuSyASyh2.avlLgonUx055NALq62');
INSERT INTO users (id,name,email,role,password_hash) VALUES (3,'Carlos Mendes','carlos.mendes@escola.edu.br','Usuário','$2y$10$gAhH0ldLscegJysDkZadMOIF0UN2hkShY8qtQEWyocXlnSqZEGaI.');
INSERT INTO users (id,name,email,role,password_hash) VALUES (4,'Ricardo Alves','ricardo.alves@escola.edu.br','Usuário','$2y$10$OT0anv1JuO85G8jW5.ZIYe4JOn.bVNFQkvWTZCWAaTXgAaaLAaJ.6');
INSERT INTO rooms (id,name,type,capacity,floor,features,image,status,description) VALUES (1,'Laboratório de Ciências','Laboratório',30,'2º Andar','Bancadas, Microscópios, Projetor, Pia','scene-1.jpg','Disponível','Laboratório equipado para aulas de Química, Física e Biologia. Bancadas individuais, microscópios ópticos, kit de vidraria e projetor multimídia fixo.');
INSERT INTO rooms (id,name,type,capacity,floor,features,image,status,description) VALUES (2,'Biblioteca','Biblioteca',50,'Térreo','Wi-Fi, Computadores, Mesas individuais, Silêncio','scene-2.jpg','Ocupado','Biblioteca com acervo de 12 000 títulos, 8 computadores para pesquisa, mesas de estudo individual e em grupo. Ambiente silencioso obrigatório.');
INSERT INTO rooms (id,name,type,capacity,floor,features,image,status,description) VALUES (3,'Auditório Principal','Auditório',150,'Térreo','Palco, Sonorização, Projeção, Ar-condicionado','scene-3.jpg','Disponível','Auditório principal com palco, sistema de sonorização profissional, dois projetores e sistema de ar-condicionado central. Ideal para palestras e eventos.');
INSERT INTO rooms (id,name,type,capacity,floor,features,image,status,description) VALUES (4,'Sala de Informática 1','Informática',28,'1º Andar','32 Computadores, Projetor, Wi-Fi, Ar-condicionado','scene-4.jpg','Disponível','Sala equipada com 32 computadores atualizados, projetor central e internet de alta velocidade. Softwares educativos instalados.');
INSERT INTO rooms (id,name,type,capacity,floor,features,image,status,description) VALUES (5,'Sala Multiuso A','Sala de Aula',35,'1º Andar','Projetor, Quadro Branco, Ar-condicionado','scene-5.jpg','Disponível','Sala multiuso com mobiliário modular para diversas configurações. Projetor, quadro branco interativo e sistema de ar-condicionado.');
INSERT INTO rooms (id,name,type,capacity,floor,features,image,status,description) VALUES (6,'Sala de Reuniões','Reunião',12,'3º Andar','TV 65", Videoconferência, Café, Quadro Branco','scene-6.jpg','Manutenção','Sala de reuniões com TV 65", sistema de videoconferência, quadro branco e espaço para café. Em manutenção preventiva.');
INSERT INTO reservations (id,room_id,user_id,date,start_time,end_time,purpose,notes,status) VALUES (1,1,1,'2026-10-01','08:00','10:00','Aula prática de Química — Reações ácido-base','','Confirmado');
INSERT INTO reservations (id,room_id,user_id,date,start_time,end_time,purpose,notes,status) VALUES (2,4,1,'2026-10-02','14:00','16:00','Pesquisa orientada — 9º Ano','','Confirmado');
INSERT INTO reservations (id,room_id,user_id,date,start_time,end_time,purpose,notes,status) VALUES (3,3,1,'2026-10-06','09:00','11:00','Apresentação de projetos — Feira de Ciências','','Pendente');
INSERT INTO reservations (id,room_id,user_id,date,start_time,end_time,purpose,notes,status) VALUES (4,2,1,'2026-09-24','13:00','14:00','Pesquisa bibliográfica — 8º Ano','','Confirmado');
INSERT INTO reservations (id,room_id,user_id,date,start_time,end_time,purpose,notes,status) VALUES (5,5,3,'2026-10-01','10:00','12:00','Oficina de Matemática','','Pendente');
INSERT INTO reservations (id,room_id,user_id,date,start_time,end_time,purpose,notes,status) VALUES (6,1,4,'2026-10-02','09:00','11:00','Experimento de Física — Ondas','','Pendente');
INSERT INTO reservations (id,room_id,user_id,date,start_time,end_time,purpose,notes,status) VALUES (7,3,2,'2026-10-08','14:00','17:00','Palestra — Semana da Saúde','','Confirmado');
INSERT IGNORE INTO categories (id,name,description) VALUES (1,'Infraestrutura','Manutenção e conservação dos ambientes escolares.');
INSERT IGNORE INTO categories (id,name,description) VALUES (2,'Equipamentos','Projetores, computadores e recursos audiovisuais.');
INSERT IGNORE INTO categories (id,name,description) VALUES (3,'Suporte pedagógico','Apoio às atividades de professores e coordenação.');
COMMIT;
