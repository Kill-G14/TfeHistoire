-- Base de données pour EuroFêtes Historiques
-- Création de la base de données

CREATE DATABASE IF NOT EXISTS eurofetes_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE eurofetes_db;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
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
    date DATE NOT NULL,
    time TIME NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100) NOT NULL,
    available_tickets INT NOT NULL DEFAULT 0,
    image_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des réservations
CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    tickets_count INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10, 2) NOT NULL,
    booking_status ENUM(
        'pending',
        'confirmed',
        'cancelled'
    ) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des sessions (tokens d'authentification)
CREATE TABLE IF NOT EXISTS sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(16) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Index pour améliorer les performances
CREATE INDEX idx_events_date ON events (date);

CREATE INDEX idx_events_country ON events (country);

CREATE INDEX idx_events_city ON events (city);

CREATE INDEX idx_events_category ON events (category);

CREATE INDEX idx_bookings_user ON bookings (user_id);

CREATE INDEX idx_bookings_event ON bookings (event_id);

CREATE INDEX idx_sessions_token ON sessions (token);

-- Insertion de données de test
INSERT INTO
    users (email, password, name)
VALUES (
        'admin@eurofetes.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Admin EuroFêtes'
    ),
    (
        'user@example.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Utilisateur Test'
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
        date,
        time,
        price,
        category,
        available_tickets,
        image_url
    )
VALUES (
        1,
        'Carnaval de Venise',
        'Le célèbre carnaval vénitien avec ses masques élaborés et costumes somptueux. Une tradition datant du Moyen Âge qui transforme Venise en un théâtre vivant.',
        'Italie',
        'Venise',
        '30100',
        'Piazza San Marco',
        '2026-02-15',
        '10:00:00',
        45.00,
        'Carnaval',
        500,
        'https://images.unsplash.com/photo-1709717146395-6d368ebc8231'
    ),
    (
        1,
        'Oktoberfest',
        'La plus grande fête de la bière au monde à Munich. Une célébration bavaroise traditionnelle avec musique, danse et gastronomie depuis 1810.',
        'Allemagne',
        'Munich',
        '80331',
        'Theresienwiese',
        '2026-09-20',
        '09:00:00',
        30.00,
        'Fête Traditionnelle',
        1000,
        'https://images.unsplash.com/photo-1669778631871-7bb6d5411c4b'
    ),
    (
        1,
        'Festival Médiéval de Carcassonne',
        'Plongez dans l\'histoire médiévale avec des tournois de chevaliers, des marchés d\'artisans et des spectacles d\'époque dans la cité fortifiée.',
        'France',
        'Carcassonne',
        '11000',
        'Cité de Carcassonne',
        '2026-07-05',
        '14:00:00',
        25.00,
        'Festival Médiéval',
        300,
        'https://images.unsplash.com/photo-1660892367133-82d376bce4fe'
    ),
    (
        1,
        'San Fermín - Course des Taureaux',
        'La célèbre fête de Pampelune avec sa course de taureaux traditionnelle, une tradition controversée mais historique depuis 1591.',
        'Espagne',
        'Pampelune',
        '31001',
        'Plaza del Ayuntamiento',
        '2026-07-07',
        '08:00:00',
        35.00,
        'Fête Traditionnelle',
        200,
        'https://images.unsplash.com/photo-1527728180910-ce0511918c1f'
    ),
    (
        1,
        'Edinburgh Military Tattoo',
        'Un spectacle militaire impressionnant au château d\'Édimbourg avec des fanfares, des cornemuses et des performances internationales.',
        'Royaume-Uni',
        'Édimbourg',
        'EH1 2NG',
        'Edinburgh Castle',
        '2026-08-01',
        '21:00:00',
        50.00,
        'Reconstitution Historique',
        800,
        'https://images.unsplash.com/photo-1619429303894-4b40ee7810ba'
    ),
    (
        1,
        'Fête de la Renaissance',
        'Célébration historique européenne avec costumes d\'époque, danses Renaissance et reconstitutions historiques authentiques.',
        'France',
        'Lyon',
        '69001',
        'Place Bellecour',
        '2026-06-12',
        '11:00:00',
        20.00,
        'Festival Médiéval',
        400,
        'https://images.unsplash.com/photo-1767128312636-de243003b0fe'
    );