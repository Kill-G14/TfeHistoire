-- ============================================
-- SCRIPT DE RÉINITIALISATION COMPLÈTE
-- ============================================
-- À exécuter dans phpMyAdmin (onglet SQL)
-- Sans sélectionner de base de données
-- ============================================

-- 1. Supprimer la base si elle existe
DROP DATABASE IF EXISTS memoriaeventia;

-- 2. Recréer la base
CREATE DATABASE memoriaeventia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Message de confirmation
SELECT 'Base de données créée avec succès!' AS Message;

-- ============================================
-- Après avoir exécuté ce script :
-- 1. Sélectionnez la base 'memoriaeventia' dans le menu de gauche
-- 2. Onglet "Importer"
-- 3. Importez le fichier 'database.sql'
-- ============================================
