-- ============================================
-- Migration : suppression des colonnes de paiement résiduelles (is_free, ticket_price)
-- ticket_quantity est conservé et devient la capacité maximale de l'événement (0 = illimité).
-- À importer manuellement (phpMyAdmin / client MySQL) sur une base existante.
-- Idempotent : peut être ré-exécuté sans erreur même si déjà appliqué.
-- ============================================

-- Suppression de la colonne is_free (si présente)
SET @dbname = DATABASE();
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'events'
      AND COLUMN_NAME = 'is_free'
  ) > 0,
  'ALTER TABLE events DROP COLUMN is_free',
  'SELECT 1'
));
PREPARE dropIfExists FROM @preparedStatement;
EXECUTE dropIfExists;
DEALLOCATE PREPARE dropIfExists;

-- Suppression de la colonne ticket_price (si présente)
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'events'
      AND COLUMN_NAME = 'ticket_price'
  ) > 0,
  'ALTER TABLE events DROP COLUMN ticket_price',
  'SELECT 1'
));
PREPARE dropIfExists FROM @preparedStatement;
EXECUTE dropIfExists;
DEALLOCATE PREPARE dropIfExists;
