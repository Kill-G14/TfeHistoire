-- Base de données PRODUCTION pour MemoriaEventia
-- Version: 1.0.0
-- Date: 2026-05-21

-- ============================================
-- INSTRUCTIONS D'INSTALLATION
-- ============================================
-- 1. Créer la base de données manuellement :
--    CREATE DATABASE memoriaeventia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 2. Exécuter ce fichier SQL :
--    mysql -u username -p memoriaeventia < database_production.sql
-- 3. Configurer le fichier .env avec les identifiants de base de données
-- ============================================

-- Configuration de la session
SET SESSION wait_timeout = 28800;

SET SESSION interactive_timeout = 28800;

SET SESSION net_read_timeout = 120;

SET SESSION net_write_timeout = 120;

USE memoriaeventia;

-- ============================================
-- SUPPRESSION DES TABLES EXISTANTES
-- ============================================
-- Ordre important : supprimer d'abord les tables avec foreign keys
DROP TABLE IF EXISTS reservations;

DROP TABLE IF EXISTS favorites;

DROP TABLE IF EXISTS rate_limiter;

DROP TABLE IF EXISTS sessions;

DROP TABLE IF EXISTS password_resets;

DROP TABLE IF EXISTS event_modifications;

DROP TABLE IF EXISTS events;

DROP TABLE IF EXISTS users;

-- ============================================
-- CRÉATION DES TABLES
-- ============================================

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    is_organizer BOOLEAN DEFAULT FALSE,
    is_moderator BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des réinitialisations de mot de passe
CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts INT DEFAULT 0 COMMENT 'Nombre de tentatives de vérification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_code (code),
    INDEX idx_expires (expires_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des événements
CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    country VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    date DATE NOT NULL,
    time TIME NOT NULL,
    category VARCHAR(100) NOT NULL,
    is_free BOOLEAN DEFAULT FALSE,
    ticket_price DECIMAL(10, 2) DEFAULT 0.00,
    ticket_quantity INT DEFAULT 0,
    is_pending BOOLEAN DEFAULT TRUE,
    is_approved BOOLEAN DEFAULT FALSE,
    is_rejected BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    has_pending_modification BOOLEAN DEFAULT FALSE COMMENT 'Si true, une modification de date/heure est en attente de validation',
    deletion_requested BOOLEAN DEFAULT FALSE COMMENT 'Si true, une demande de suppression est en attente',
    deletion_message TEXT NULL COMMENT 'Message de l organisateur expliquant la suppression',
    deletion_requested_at TIMESTAMP NULL COMMENT 'Date de la demande de suppression',
    image_event VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des modifications d'événements (date/heure) en attente
CREATE TABLE IF NOT EXISTS event_modifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    new_date DATE NOT NULL COMMENT 'Nouvelle date proposée',
    new_time TIME NOT NULL COMMENT 'Nouvelle heure proposée',
    old_date DATE NOT NULL COMMENT 'Ancienne date (pour historique)',
    old_time TIME NOT NULL COMMENT 'Ancienne heure (pour historique)',
    status ENUM(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending' COMMENT 'Statut de la modification',
    rejection_reason TEXT NULL COMMENT 'Raison du rejet par l admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de la demande',
    validated_at TIMESTAMP NULL COMMENT 'Date de validation/rejet par admin',
    FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_event_id (event_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Modifications de date/heure d événements en attente de validation';

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_event_id (event_id),
    INDEX idx_status (status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Réservations simples sans paiement';

-- Table des favoris
CREATE TABLE IF NOT EXISTS favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, event_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des sessions (tokens d'authentification)
CREATE TABLE IF NOT EXISTS sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token de session (64 caractères)',
    user_id INT NOT NULL,
    expires_at DATETIME NOT NULL COMMENT 'Date d expiration de la session',
    last_activity DATETIME NULL COMMENT 'Date de la dernière activité',
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_expires_at (expires_at),
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table de rate limiting (protection contre brute force)
CREATE TABLE IF NOT EXISTS rate_limiter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lookup_key VARCHAR(64) NOT NULL UNIQUE COMMENT 'Hash SHA256 de action:identifier:ip',
    attempts_count INT NOT NULL DEFAULT 0 COMMENT 'Nombre de tentatives',
    block_until INT NULL COMMENT 'Timestamp de fin de blocage',
    created_at INT NOT NULL COMMENT 'Timestamp de création',
    updated_at INT NOT NULL COMMENT 'Timestamp de dernière mise à jour',
    INDEX idx_lookup (lookup_key),
    INDEX idx_block (block_until),
    INDEX idx_updated (updated_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================
-- INDEX POUR AMÉLIORER LES PERFORMANCES
-- ============================================
CREATE INDEX idx_events_date ON events (date);

CREATE INDEX idx_events_country ON events (country);

CREATE INDEX idx_events_city ON events (city);

CREATE INDEX idx_events_category ON events (category);

CREATE INDEX idx_events_pending ON events (is_pending);

CREATE INDEX idx_events_approved ON events (is_approved);

CREATE INDEX idx_events_rejected ON events (is_rejected);

CREATE INDEX idx_events_deleted ON events (is_deleted);

CREATE INDEX idx_events_has_pending_modification ON events (has_pending_modification);

CREATE INDEX idx_events_deletion_requested ON events (deletion_requested);

CREATE INDEX idx_events_location ON events (latitude, longitude);

CREATE INDEX idx_users_deleted ON users (is_deleted);

-- ============================================
-- DONNÉES DE PRODUCTION
-- ============================================

-- Insertion du compte administrateur UNIQUEMENT
-- Email: admin@memoriaeventia.com
-- Mot de passe: @S76XVzqeAhFmEe
-- Hash BCrypt généré avec PASSWORD_DEFAULT de PHP
INSERT INTO
    users (
        email,
        password,
        name,
        is_admin,
        is_organizer,
        is_moderator,
        is_deleted
    )
VALUES (
        'admin@memoriaeventia.com',
        '$2y$10$M6Ck5/mcO4XYns8s/ZJQKuIsaWOIthOHyHZe3hxm246XSYtivYtbC',
        'Administrateur',
        TRUE,
        FALSE,
        FALSE,
        FALSE
    );

-- ============================================
-- FIN DE L'INSTALLATION
-- ============================================
-- Base de données prête pour la production !
-- Prochaines étapes :
-- 1. Configurer le fichier .env avec les bonnes informations
-- 2. Vérifier les permissions des dossiers storage/ et logs/
-- 3. Configurer SendGrid avec la clé API de production
-- 4. Tester la connexion avec admin@memoriaeventia.com
-- ============================================