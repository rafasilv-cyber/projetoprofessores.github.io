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
