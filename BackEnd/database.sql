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
DROP TABLE IF EXISTS tickets_generated;

DROP TABLE IF EXISTS order_items;

DROP TABLE IF EXISTS orders;

DROP TABLE IF EXISTS tickets;

DROP TABLE IF EXISTS favorites;

DROP TABLE IF EXISTS sessions;

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
    is_pending BOOLEAN DEFAULT TRUE,
    is_approved BOOLEAN DEFAULT FALSE,
    is_rejected BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    image_event VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des billets (types de billets pour un événement)
CREATE TABLE IF NOT EXISTS tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL,
    start_sale_date DATETIME,
    end_sale_date DATETIME,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

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
    ticket_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE
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
    token VARCHAR(16) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
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

-- Index sur la table TICKETS (soft delete)
CREATE INDEX idx_tickets_deleted ON tickets (is_deleted);

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
--   ✓ tickets.event_id
--   ✓ orders.user_id
--   ✓ order_items.order_id
--   ✓ order_items.ticket_id
--   ✓ tickets_generated.order_item_id
--   ✓ favorites.user_id
--   ✓ favorites.event_id
--   ✓ sessions.user_id
--   ✓ payments.order_id
--
-- Total: 29 index créés (9 explicites + 20 automatiques)
-- ============================================

-- Insertion de données de test
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
        'admin@eurofetes.com',
        'admin123',
        'Admin MemoriaEventia',
        TRUE,
        FALSE,
        FALSE,
        FALSE
    ),
    (
        'moderator@eurofetes.com',
        'moderator123',
        'Modérateur',
        FALSE,
        FALSE,
        TRUE,
        FALSE
    ),
    (
        'organizer@example.com',
        'organizer123',
        'Organisateur Test',
        FALSE,
        TRUE,
        FALSE,
        FALSE
    ),
    (
        'user@example.com',
        'user123',
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
        FALSE,
        TRUE,
        FALSE,
        FALSE,
        'sofiia-vytrishko-iK6g0pI0FE8-unsplash.jpg'
    );

-- Insertion de billets pour les événements
-- Carnaval de Venise (event_id = 1)
INSERT INTO
    tickets (
        event_id,
        name,
        description,
        price,
        quantity,
        start_sale_date,
        end_sale_date,
        is_deleted
    )
VALUES (
        1,
        'Billet Adulte',
        'Accès complet au carnaval pour adulte',
        45.00,
        400,
        '2025-12-01 00:00:00',
        '2026-02-14 23:59:59',
        FALSE
    ),
    (
        1,
        'Billet Enfant',
        'Accès complet au carnaval pour enfant (6-12 ans)',
        25.00,
        100,
        '2025-12-01 00:00:00',
        '2026-02-14 23:59:59',
        FALSE
    );

-- Oktoberfest (event_id = 2)
INSERT INTO
    tickets (
        event_id,
        name,
        description,
        price,
        quantity,
        start_sale_date,
        end_sale_date,
        is_deleted
    )
VALUES (
        2,
        'Pass 1 Jour',
        'Accès pour une journée',
        30.00,
        500,
        '2026-01-01 00:00:00',
        '2026-09-19 23:59:59',
        FALSE
    ),
    (
        2,
        'Pass Weekend',
        'Accès pour le weekend complet',
        50.00,
        300,
        '2026-01-01 00:00:00',
        '2026-09-19 23:59:59',
        FALSE
    ),
    (
        2,
        'Pass VIP',
        'Accès VIP avec table réservée',
        120.00,
        50,
        '2026-01-01 00:00:00',
        '2026-09-19 23:59:59',
        FALSE
    );

-- Festival Médiéval de Carcassonne (event_id = 3)
INSERT INTO
    tickets (
        event_id,
        name,
        description,
        price,
        quantity,
        start_sale_date,
        end_sale_date,
        is_deleted
    )
VALUES (
        3,
        'Billet Standard',
        'Accès au festival',
        25.00,
        250,
        '2026-05-01 00:00:00',
        '2026-07-04 23:59:59',
        FALSE
    ),
    (
        3,
        'Billet Famille',
        'Accès pour 2 adultes + 2 enfants',
        60.00,
        50,
        '2026-05-01 00:00:00',
        '2026-07-04 23:59:59',
        FALSE
    );

-- San Fermín (event_id = 4)
INSERT INTO
    tickets (
        event_id,
        name,
        description,
        price,
        quantity,
        start_sale_date,
        end_sale_date,
        is_deleted
    )
VALUES (
        4,
        'Billet Tribune',
        'Place en tribune pour voir la course',
        35.00,
        150,
        '2026-04-01 00:00:00',
        '2026-07-06 23:59:59',
        FALSE
    ),
    (
        4,
        'Billet Premium',
        'Tribune couverte avec boissons',
        75.00,
        50,
        '2026-04-01 00:00:00',
        '2026-07-06 23:59:59',
        FALSE
    );

-- Edinburgh Military Tattoo (event_id = 5)
INSERT INTO
    tickets (
        event_id,
        name,
        description,
        price,
        quantity,
        start_sale_date,
        end_sale_date,
        is_deleted
    )
VALUES (
        5,
        'Billet Standard',
        'Siège standard',
        50.00,
        600,
        '2026-03-01 00:00:00',
        '2026-07-31 23:59:59',
        FALSE
    ),
    (
        5,
        'Billet Premium',
        'Meilleurs sièges',
        85.00,
        150,
        '2026-03-01 00:00:00',
        '2026-07-31 23:59:59',
        FALSE
    ),
    (
        5,
        'Billet Enfant',
        'Pour les moins de 12 ans',
        30.00,
        50,
        '2026-03-01 00:00:00',
        '2026-07-31 23:59:59',
        FALSE
    );

-- Fête Médiévale de Bruges (event_id = 7)
INSERT INTO
    tickets (
        event_id,
        name,
        description,
        price,
        quantity,
        start_sale_date,
        end_sale_date,
        is_deleted
    )
VALUES (
        7,
        'Billet Journée',
        'Accès pour la journée',
        20.00,
        300,
        '2026-06-01 00:00:00',
        '2026-08-14 23:59:59',
        FALSE
    ),
    (
        7,
        'Billet Atelier',
        'Accès + atelier artisanat',
        35.00,
        50,
        '2026-06-01 00:00:00',
        '2026-08-14 23:59:59',
        FALSE
    );

-- Marché de Noël Médiéval (event_id = 9)
INSERT INTO
    tickets (
        event_id,
        name,
        description,
        price,
        quantity,
        start_sale_date,
        end_sale_date,
        is_deleted
    )
VALUES (
        9,
        'Billet Adulte',
        'Accès au marché de Noël',
        15.00,
        400,
        '2026-11-01 00:00:00',
        '2026-12-09 23:59:59',
        FALSE
    ),
    (
        9,
        'Billet Famille',
        '2 adultes + 3 enfants',
        40.00,
        100,
        '2026-11-01 00:00:00',
        '2026-12-09 23:59:59',
        FALSE
    ),
    (
        9,
        'Pass VIP',
        'Accès + dégustation de vin chaud et repas',
        45.00,
        80,
        '2026-11-01 00:00:00',
        '2026-12-09 23:59:59',
        FALSE
    );

-- Festival Viking de Bergen (event_id = 10)
INSERT INTO
    tickets (
        event_id,
        name,
        description,
        price,
        quantity,
        start_sale_date,
        end_sale_date,
        is_deleted
    )
VALUES (
        10,
        'Billet Standard',
        'Accès au festival viking',
        30.00,
        350,
        '2026-04-01 00:00:00',
        '2026-06-19 23:59:59',
        FALSE
    ),
    (
        10,
        'Billet Festin',
        'Accès + repas viking traditionnel',
        65.00,
        120,
        '2026-04-01 00:00:00',
        '2026-06-19 23:59:59',
        FALSE
    ),
    (
        10,
        'Pass Weekend',
        'Accès pour tout le weekend',
        50.00,
        200,
        '2026-04-01 00:00:00',
        '2026-06-19 23:59:59',
        FALSE
    );

-- Renaissance Florentine (event_id = 11)
INSERT INTO
    tickets (
        event_id,
        name,
        description,
        price,
        quantity,
        start_sale_date,
        end_sale_date,
        is_deleted
    )
VALUES (
        11,
        'Billet Journée',
        'Accès au festival Renaissance',
        28.00,
        400,
        '2026-05-01 00:00:00',
        '2026-06-23 23:59:59',
        FALSE
    ),
    (
        11,
        'Billet Calcio Storico',
        'Match de football historique + accès festival',
        55.00,
        150,
        '2026-05-01 00:00:00',
        '2026-06-23 23:59:59',
        FALSE
    ),
    (
        11,
        'Billet Premium',
        'Cortège historique VIP + banquet',
        95.00,
        60,
        '2026-05-01 00:00:00',
        '2026-06-23 23:59:59',
        FALSE
    );

-- Carnaval de Bâle (event_id = 12)
INSERT INTO
    tickets (
        event_id,
        name,
        description,
        price,
        quantity,
        start_sale_date,
        end_sale_date,
        is_deleted
    )
VALUES (
        12,
        'Billet 1 Jour',
        'Accès pour une journée',
        35.00,
        500,
        '2026-12-01 00:00:00',
        '2027-02-21 23:59:59',
        FALSE
    ),
    (
        12,
        'Pass 3 Jours',
        'Accès pour les 3 jours du carnaval',
        80.00,
        250,
        '2026-12-01 00:00:00',
        '2027-02-21 23:59:59',
        FALSE
    ),
    (
        12,
        'Billet Tribune',
        'Place en tribune pour le cortège',
        50.00,
        180,
        '2026-12-01 00:00:00',
        '2027-02-21 23:59:59',
        FALSE
    );

-- Festival de la Bière Tchèque (event_id = 13)
INSERT INTO
    tickets (
        event_id,
        name,
        description,
        price,
        quantity,
        start_sale_date,
        end_sale_date,
        is_deleted
    )
VALUES (
        13,
        'Pass Dégustation',
        'Accès + 5 dégustations de bières',
        32.00,
        450,
        '2026-03-01 00:00:00',
        '2026-05-15 23:59:59',
        FALSE
    ),
    (
        13,
        'Pass VIP',
        'Accès VIP + dégustations illimitées',
        75.00,
        100,
        '2026-03-01 00:00:00',
        '2026-05-15 23:59:59',
        FALSE
    ),
    (
        13,
        'Pass Gourmet',
        'Dégustations + menu gastronomique tchèque',
        95.00,
        80,
        '2026-03-01 00:00:00',
        '2026-05-15 23:59:59',
        FALSE
    );

-- Note: L'événement 6 (Fête de la Renaissance) et l'événement 8 (Fête de la Bastille) sont gratuits, donc pas de billets