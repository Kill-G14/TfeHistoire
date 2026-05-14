-- Base de données pour MemoriaEventia
-- Création de la base de données

-- ============================================
-- INSTRUCTIONS D'EXÉCUTION
-- ============================================
-- OPTION 1 (Recommandée) : Créer la base manuellement d'abord
--   1. Dans phpMyAdmin ou ligne de commande MySQL :
--      CREATE DATABASE memoriaeventia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   2. Ensuite exécutez ce fichier SQL complet
--
-- OPTION 2 : Si vous avez les privilèges CREATE DATABASE
--   - Exécutez ce fichier SQL tel quel
--
-- OPTION 3 : Ligne de commande
--   mysql -u root -p < database.sql
-- ============================================

-- Configuration pour éviter les timeouts
SET SESSION wait_timeout = 28800;

SET SESSION interactive_timeout = 28800;

SET SESSION net_read_timeout = 120;

SET SESSION net_write_timeout = 120;

-- Création de la base de données (nécessite privilèges CREATE DATABASE)
CREATE DATABASE IF NOT EXISTS memoriaeventia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE memoriaeventia;

-- Suppression des tables existantes pour recréation propre
-- Ordre important : supprimer d'abord les tables avec foreign keys
DROP TABLE IF EXISTS stripe_connect_log;

DROP TABLE IF EXISTS creator_earnings;

DROP TABLE IF EXISTS payments;

DROP TABLE IF EXISTS tickets_generated;

DROP TABLE IF EXISTS order_items;

DROP TABLE IF EXISTS orders;

DROP TABLE IF EXISTS favorites;

DROP TABLE IF EXISTS rate_limiter;

DROP TABLE IF EXISTS sessions;

DROP TABLE IF EXISTS event_modifications;

DROP TABLE IF EXISTS events;

DROP TABLE IF EXISTS users;

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
    stripe_account_id VARCHAR(255) NULL COMMENT 'ID du compte Stripe Connect du créateur',
    stripe_account_status ENUM(
        'not_connected',
        'pending',
        'connected',
        'rejected'
    ) DEFAULT 'not_connected' COMMENT 'Statut de la connexion Stripe',
    stripe_onboarding_completed BOOLEAN DEFAULT FALSE COMMENT 'Si le processus d onboarding Stripe est terminé',
    stripe_connected_at DATETIME NULL COMMENT 'Date de connexion du compte Stripe',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    requires_stripe_account BOOLEAN DEFAULT FALSE COMMENT 'Si true, le créateur doit avoir un compte Stripe connecté',
    stripe_account_verified BOOLEAN DEFAULT FALSE COMMENT 'Si le compte Stripe du créateur était vérifié lors de la création',
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

-- Table des commandes
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    is_pending BOOLEAN DEFAULT TRUE,
    is_paid BOOLEAN DEFAULT FALSE,
    is_failed BOOLEAN DEFAULT FALSE,
    is_cancelled BOOLEAN DEFAULT FALSE,
    payment_provider VARCHAR(50) DEFAULT 'stripe',
    payment_id VARCHAR(255),
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des articles de commande
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    event_id INT NOT NULL,
    ticket_name VARCHAR(255) NOT NULL DEFAULT 'Billet Standard',
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des billets générés (billets individuels avec QR code)
CREATE TABLE IF NOT EXISTS tickets_generated (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_item_id INT NOT NULL,
    qr_code TEXT NOT NULL,
    unique_code VARCHAR(255) NOT NULL UNIQUE,
    is_used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

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

-- Table des paiements (Stripe)
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    stripe_payment_intent_id VARCHAR(255) UNIQUE,
    stripe_checkout_session_id VARCHAR(255) UNIQUE,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    status VARCHAR(50) NOT NULL, -- pending, succeeded, failed, canceled, refunded
    payment_method VARCHAR(50), -- card, bank_transfer, etc.
    receipt_url TEXT,
    refund_id VARCHAR(255),
    refund_amount DECIMAL(10, 2),
    refunded_at TIMESTAMP NULL,
    metadata TEXT, -- JSON pour stocker infos supplémentaires
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des gains des créateurs (Stripe Connect)
CREATE TABLE IF NOT EXISTS creator_earnings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'Créateur de l événement',
    event_id INT NOT NULL COMMENT 'Événement concerné',
    order_id INT NOT NULL COMMENT 'Commande associée',
    gross_amount DECIMAL(10, 2) NOT NULL COMMENT 'Montant brut (prix du billet)',
    platform_fee DECIMAL(10, 2) DEFAULT 0 COMMENT 'Commission de la plateforme',
    stripe_fee DECIMAL(10, 2) DEFAULT 0 COMMENT 'Frais Stripe',
    net_amount DECIMAL(10, 2) NOT NULL COMMENT 'Montant net pour le créateur',
    status ENUM(
        'pending',
        'transferred',
        'failed'
    ) DEFAULT 'pending',
    stripe_transfer_id VARCHAR(255) NULL COMMENT 'ID du transfert Stripe',
    transferred_at DATETIME NULL COMMENT 'Date du transfert',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_event (event_id),
    INDEX idx_created (created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Suivi des gains des créateurs d événements';

-- Table de l'historique des connexions Stripe Connect
CREATE TABLE IF NOT EXISTS stripe_connect_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action ENUM(
        'onboarding_started',
        'onboarding_completed',
        'account_updated',
        'account_disconnected'
    ) NOT NULL,
    stripe_account_id VARCHAR(255) NULL,
    details TEXT NULL COMMENT 'JSON avec détails supplémentaires',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Historique des actions Stripe Connect';

-- ============================================
-- INDEX POUR AMÉLIORER LES PERFORMANCES
-- ============================================
-- Note: Les index suivants ne sont PAS créés automatiquement par MySQL.
-- Les FOREIGN KEY et UNIQUE créent automatiquement leurs propres index.
-- Nous définissons ici uniquement les index additionnels nécessaires.
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
-- Index composite pour recherche géographique

-- Index sur la table ORDERS (filtrage par statut)
CREATE INDEX idx_orders_pending ON orders (is_pending);

CREATE INDEX idx_orders_paid ON orders (is_paid);

CREATE INDEX idx_orders_failed ON orders (is_failed);

CREATE INDEX idx_orders_cancelled ON orders (is_cancelled);

CREATE INDEX idx_orders_deleted ON orders (is_deleted);

-- Index sur la table PAYMENTS (filtrage par statut)
CREATE INDEX idx_payments_status ON payments (status);

-- Index sur la table USERS (soft delete)
CREATE INDEX idx_users_deleted ON users (is_deleted);

-- ============================================
-- INDEX AUTOMATIQUEMENT CRÉÉS (documentation)
-- ============================================
-- Les index suivants sont créés automatiquement par MySQL/InnoDB
-- et n'ont PAS besoin d'être définis manuellement :
--
-- PRIMARY KEY crée automatiquement un index unique :
--   ✓ users.id, events.id, tickets.id, orders.id, order_items.id,
--   ✓ tickets_generated.id, favorites.id, sessions.id, payments.id
--
-- UNIQUE crée automatiquement un index unique :
--   ✓ users.email
--   ✓ tickets_generated.unique_code
--   ✓ sessions.token
--   ✓ favorites.unique_favorite (composite: user_id + event_id)
--   ✓ payments.stripe_payment_intent_id
--   ✓ payments.stripe_checkout_session_id
--
-- FOREIGN KEY crée automatiquement un index (dans InnoDB) :
--   ✓ events.user_id
--   ✓ orders.user_id
--   ✓ order_items.order_id
--   ✓ order_items.event_id
--   ✓ tickets_generated.order_item_id
--   ✓ favorites.user_id
--   ✓ favorites.event_id
--   ✓ sessions.user_id
--   ✓ payments.order_id
--
-- Total: 27 index créés (8 explicites + 19 automatiques)
-- ============================================

-- Insertion de données de test
-- NOTE: Les mots de passe sont hashés avec BCrypt (PASSWORD_DEFAULT de PHP)
-- Mot de passe pour TOUS les utilisateurs de test : password
-- Hash BCrypt : $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
--
-- Comptes disponibles :
--   admin@memoriaeventia.com    → Administrateur
--   moderator@memoriaeventia.com → Modérateur
--   organizer@example.com       → Organisateur
--   user@example.com            → Utilisateur simple
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
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Admin MemoriaEventia',
        TRUE,
        FALSE,
        FALSE,
        FALSE
    ),
    (
        'moderator@memoriaeventia.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Modérateur',
        FALSE,
        FALSE,
        TRUE,
        FALSE
    ),
    (
        'organizer@example.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Organisateur Test',
        FALSE,
        TRUE,
        FALSE,
        FALSE
    ),
    (
        'user@example.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Utilisateur Test',
        FALSE,
        FALSE,
        FALSE,
        FALSE
    );

INSERT INTO
    events (
        user_id,
        title,
        description,
        country,
        city,
        postal_code,
        address,
        latitude,
        longitude,
        date,
        time,
        category,
        is_free,
        ticket_price,
        ticket_quantity,
        is_pending,
        is_approved,
        is_rejected,
        is_deleted,
        image_event
    )
VALUES (
        3,
        'Carnaval de Venise',
        'Le célèbre carnaval vénitien avec ses masques élaborés et costumes somptueux. Une tradition datant du Moyen Âge qui transforme Venise en un théâtre vivant.',
        'Italie',
        'Venise',
        '30100',
        'Piazza San Marco',
        45.43713,
        12.33265,
        '2026-02-15',
        '10:00:00',
        'Carnaval',
        FALSE,
        35.00,
        500,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'photo-1709717146395-6d368ebc8231.jpg'
    ),
    (
        3,
        'Oktoberfest',
        'La plus grande fête de la bière au monde à Munich. Une célébration bavaroise traditionnelle avec musique, danse et gastronomie depuis 1810.',
        'Allemagne',
        'Munich',
        '80331',
        'Theresienwiese',
        48.13154,
        11.54990,
        '2026-09-20',
        '09:00:00',
        'Fête Traditionnelle',
        FALSE,
        30.00,
        500,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'photo-1669778631871-7bb6d5411c4b.jpg'
    ),
    (
        3,
        'Festival Médiéval de Carcassonne',
        'Plongez dans l\'histoire médiévale avec des tournois de chevaliers, des marchés d\'artisans et des spectacles d\'époque dans la cité fortifiée.',
        'France',
        'Carcassonne',
        '11000',
        'Cité de Carcassonne',
        43.20611,
        2.36231,
        '2026-07-05',
        '14:00:00',
        'Festival Médiéval',
        FALSE,
        20.00,
        400,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'photo-1660892367133-82d376bce4fe.jpg'
    ),
    (
        3,
        'San Fermín - Course des Taureaux',
        'La célèbre fête de Pampelune avec sa course de taureaux traditionnelle, une tradition controversée mais historique depuis 1591.',
        'Espagne',
        'Pampelune',
        '31001',
        'Plaza del Ayuntamiento',
        42.81687,
        -1.64323,
        '2026-07-07',
        '08:00:00',
        'Fête Traditionnelle',
        FALSE,
        25.00,
        300,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'photo-1527728180910-ce0511918c1f.jpg'
    ),
    (
        3,
        'Edinburgh Military Tattoo',
        'Un spectacle militaire impressionnant au château d\'Édimbourg avec des fanfares, des cornemuses et des performances internationales.',
        'Royaume-Uni',
        'Édimbourg',
        'EH1 2NG',
        'Edinburgh Castle',
        55.94873,
        -3.20009,
        '2026-08-01',
        '21:00:00',
        'Reconstitution Historique',
        FALSE,
        40.00,
        450,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'photo-1619429303894-4b40ee7810ba.jpg'
    ),
    (
        3,
        'Fête de la Renaissance',
        'Célébration historique européenne avec costumes d\'époque, danses Renaissance et reconstitutions historiques authentiques.',
        'France',
        'Lyon',
        '69001',
        'Place Bellecour',
        45.75740,
        4.83201,
        '2026-06-12',
        '11:00:00',
        'Festival Médiéval',
        TRUE,
        0.00,
        0,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'photo-1767128312636-de243003b0fe.jpg'
    ),
    (
        3,
        'Fête Médiévale de Bruges',
        'Reconstitution historique dans les rues médiévales de Bruges avec artisans, jongleurs et musiciens d\'époque.',
        'Belgique',
        'Bruges',
        '8000',
        'Markt',
        51.20892,
        3.22424,
        '2026-08-15',
        '10:00:00',
        'Festival Médiéval',
        FALSE,
        18.00,
        350,
        TRUE,
        FALSE,
        FALSE,
        FALSE,
        'roman-gvvLRfuJjzs-unsplash.jpg'
    ),
    (
        3,
        'Fête de la Bastille',
        'Célébration nationale française commémorant la prise de la Bastille en 1789. Défilé militaire, feux d\'artifice et festivités patriotiques.',
        'France',
        'Paris',
        '75001',
        'Champs-Élysées',
        48.86993,
        2.30769,
        '2026-07-14',
        '10:00:00',
        'Fête Nationale',
        TRUE,
        0.00,
        0,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'andreas-rasmussen-wtxPbYHxa5I-unsplash.jpg'
    ),
    (
        3,
        'Marché de Noël Médiéval',
        'Marché de Noël traditionnel dans le décor médiéval de Vienne avec artisans, vin chaud et spécialités autrichiennes.',
        'Autriche',
        'Vienne',
        '1010',
        'Rathausplatz',
        48.21020,
        16.35756,
        '2026-12-10',
        '15:00:00',
        'Festival Médiéval',
        FALSE,
        15.00,
        400,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'gabriel-martin-bjRrbevBO-4-unsplash.jpg'
    ),
    (
        3,
        'Festival Viking de Bergen',
        'Reconstitution historique de la vie viking avec combats, artisanat traditionnel et festins nordiques authentiques.',
        'Norvège',
        'Bergen',
        '5003',
        'Bryggen',
        60.39745,
        5.32415,
        '2026-06-20',
        '12:00:00',
        'Reconstitution Historique',
        FALSE,
        28.00,
        350,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'mayer-tawfik-QYdSBsLLQ2A-unsplash.jpg'
    ),
    (
        3,
        'Renaissance Florentine',
        'Festival célébrant l\'âge d\'or de Florence avec costumes d\'époque, cortèges historiques et reconstitutions du Calcio Storico.',
        'Italie',
        'Florence',
        '50122',
        'Piazza della Signoria',
        43.76956,
        11.25581,
        '2026-06-24',
        '16:00:00',
        'Festival Médiéval',
        FALSE,
        22.00,
        400,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'menderes-kahraman-T4VWZZ6IoZ4-unsplash.jpg'
    ),
    (
        3,
        'Carnaval de Bâle',
        'Le Fasnacht de Bâle, l\'un des plus grands carnavals de Suisse, avec ses lanternes colorées, masques et cortèges traditionnels depuis le Moyen Âge.',
        'Suisse',
        'Bâle',
        '4001',
        'Marktplatz',
        47.55814,
        7.57324,
        '2027-02-22',
        '04:00:00',
        'Carnaval',
        FALSE,
        32.00,
        450,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'shutter-speed-qMu2LTRZHiA-unsplash.jpg'
    ),
    (
        3,
        'Festival de la Bière Tchèque',
        'Célébration de la tradition brassicole tchèque millénaire avec dégustations, musique folklorique et gastronomie traditionnelle.',
        'République Tchèque',
        'Prague',
        '11000',
        'Letná Park',
        50.09717,
        14.41635,
        '2026-05-16',
        '14:00:00',
        'Fête Traditionnelle',
        FALSE,
        26.00,
        500,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'sofiia-vytrishko-iK6g0pI0FE8-unsplash.jpg'
    );

-- ===============================================
-- FIN DE L'INITIALISATION
-- ===============================================

);