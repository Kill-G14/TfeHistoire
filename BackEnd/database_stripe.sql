-- Table des paiements Stripe
-- Cette table stocke l'historique complet des transactions Stripe

USE eurofetes_db;

-- Table des paiements
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

-- Index pour améliorer les performances
CREATE INDEX idx_payments_order ON payments (order_id);

CREATE INDEX idx_payments_stripe_pi ON payments (stripe_payment_intent_id);

CREATE INDEX idx_payments_stripe_cs ON payments (stripe_checkout_session_id);

CREATE INDEX idx_payments_status ON payments (status);

-- Insérer des données de test
-- (Décommenter après avoir exécuté le script principal database.sql)