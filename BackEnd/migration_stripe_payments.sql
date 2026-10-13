-- ============================================
-- Migration : paiement en ligne (Stripe Connect) et réservations payantes
-- À importer manuellement (phpMyAdmin / client MySQL), en local puis en production.
-- Idempotent : peut être ré-exécuté sans erreur.
-- Aucune donnée n'est supprimée ni modifiée : les événements existants restent gratuits.
-- ============================================

SET @dbname = DATABASE();

-- events.price
SET @stmt = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'events' AND COLUMN_NAME = 'price') = 0,
  'ALTER TABLE events ADD COLUMN price DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER ticket_quantity',
  'SELECT 1'
));
PREPARE addIfMissing FROM @stmt;
EXECUTE addIfMissing;
DEALLOCATE PREPARE addIfMissing;

-- users.stripe_account_id
SET @stmt = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'users' AND COLUMN_NAME = 'stripe_account_id') = 0,
  'ALTER TABLE users ADD COLUMN stripe_account_id VARCHAR(255) NULL AFTER is_moderator',
  'SELECT 1'
));
PREPARE addIfMissing FROM @stmt;
EXECUTE addIfMissing;
DEALLOCATE PREPARE addIfMissing;

-- users.stripe_onboarding_completed
SET @stmt = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'users' AND COLUMN_NAME = 'stripe_onboarding_completed') = 0,
  'ALTER TABLE users ADD COLUMN stripe_onboarding_completed TINYINT(1) NOT NULL DEFAULT 0 AFTER stripe_account_id',
  'SELECT 1'
));
PREPARE addIfMissing FROM @stmt;
EXECUTE addIfMissing;
DEALLOCATE PREPARE addIfMissing;

-- reservations.payment_status : not_required | pending | paid | failed | expired
SET @stmt = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'reservations' AND COLUMN_NAME = 'payment_status') = 0,
  'ALTER TABLE reservations ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT ''not_required'' AFTER status',
  'SELECT 1'
));
PREPARE addIfMissing FROM @stmt;
EXECUTE addIfMissing;
DEALLOCATE PREPARE addIfMissing;

-- reservations.stripe_session_id
SET @stmt = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'reservations' AND COLUMN_NAME = 'stripe_session_id') = 0,
  'ALTER TABLE reservations ADD COLUMN stripe_session_id VARCHAR(255) NULL AFTER payment_status',
  'SELECT 1'
));
PREPARE addIfMissing FROM @stmt;
EXECUTE addIfMissing;
DEALLOCATE PREPARE addIfMissing;

-- reservations.amount_total
SET @stmt = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'reservations' AND COLUMN_NAME = 'amount_total') = 0,
  'ALTER TABLE reservations ADD COLUMN amount_total DECIMAL(8,2) NULL AFTER stripe_session_id',
  'SELECT 1'
));
PREPARE addIfMissing FROM @stmt;
EXECUTE addIfMissing;
DEALLOCATE PREPARE addIfMissing;

-- reservations.paid_at
SET @stmt = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'reservations' AND COLUMN_NAME = 'paid_at') = 0,
  'ALTER TABLE reservations ADD COLUMN paid_at DATETIME NULL AFTER amount_total',
  'SELECT 1'
));
PREPARE addIfMissing FROM @stmt;
EXECUTE addIfMissing;
DEALLOCATE PREPARE addIfMissing;

-- Nouveau statut de réservation : places retenues pendant le paiement
ALTER TABLE reservations
  MODIFY COLUMN status ENUM('confirmed', 'cancelled', 'pending_payment') DEFAULT 'confirmed';

-- Recherche d'une réservation à partir de la session Stripe (webhook)
SET @stmt = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'reservations' AND INDEX_NAME = 'idx_stripe_session_id') = 0,
  'ALTER TABLE reservations ADD INDEX idx_stripe_session_id (stripe_session_id)',
  'SELECT 1'
));
PREPARE addIndexIfMissing FROM @stmt;
EXECUTE addIndexIfMissing;
DEALLOCATE PREPARE addIndexIfMissing;
