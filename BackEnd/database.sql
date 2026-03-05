-- Base de données pour EuroFêtes Historiques
-- Création de la base de données

CREATE DATABASE IF NOT EXISTS eurofetes_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE eurofetes_db;

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
    image_url TEXT,
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
    payment_provider VARCHAR(50) DEFAULT 'mollie',
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

-- Index pour améliorer les performances
CREATE INDEX idx_events_date ON events (date);

CREATE INDEX idx_events_country ON events (country);

CREATE INDEX idx_events_city ON events (city);

CREATE INDEX idx_events_category ON events (category);

CREATE INDEX idx_events_pending ON events (is_pending);

CREATE INDEX idx_events_approved ON events (is_approved);

CREATE INDEX idx_events_rejected ON events (is_rejected);

CREATE INDEX idx_events_location ON events (latitude, longitude);

CREATE INDEX idx_tickets_event ON tickets (event_id);

CREATE INDEX idx_orders_user ON orders (user_id);

CREATE INDEX idx_orders_pending ON orders (is_pending);

CREATE INDEX idx_orders_paid ON orders (is_paid);

CREATE INDEX idx_orders_failed ON orders (is_failed);

CREATE INDEX idx_orders_cancelled ON orders (is_cancelled);

CREATE INDEX idx_order_items_order ON order_items (order_id);

CREATE INDEX idx_order_items_ticket ON order_items (ticket_id);

CREATE INDEX idx_tickets_generated_unique_code ON tickets_generated (unique_code);

CREATE INDEX idx_tickets_generated_order_item ON tickets_generated (order_item_id);

CREATE INDEX idx_favorites_user ON favorites (user_id);

CREATE INDEX idx_favorites_event ON favorites (event_id);

CREATE INDEX idx_sessions_token ON sessions (token);

CREATE INDEX idx_users_deleted ON users (is_deleted);

CREATE INDEX idx_events_deleted ON events (is_deleted);

CREATE INDEX idx_tickets_deleted ON tickets (is_deleted);

CREATE INDEX idx_orders_deleted ON orders (is_deleted);

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
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Admin EuroFêtes',
        TRUE,
        FALSE,
        FALSE,
        FALSE
    ),
    (
        'moderator@eurofetes.com',
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
        is_pending,
        is_approved,
        is_rejected,
        is_deleted,
        image_url
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
        'https://images.unsplash.com/photo-1709717146395-6d368ebc8231'
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
        'https://images.unsplash.com/photo-1669778631871-7bb6d5411c4b'
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
        'https://images.unsplash.com/photo-1660892367133-82d376bce4fe'
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
        'https://images.unsplash.com/photo-1527728180910-ce0511918c1f'
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
        'https://images.unsplash.com/photo-1619429303894-4b40ee7810ba'
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
        'https://images.unsplash.com/photo-1767128312636-de243003b0fe'
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
        'https://images.unsplash.com/photo-1599424419180-e6980d5ee72c'
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

-- Note: L'événement 6 (Fête de la Renaissance) est gratuit, donc pas de billets