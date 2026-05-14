# 📦 Installation de la Base de Données

## ✅ UN SEUL FICHIER : `database.sql`

Tout est maintenant dans **un seul fichier SQL** qui contient :

- ✅ Création de la base `memoriaeventia`
- ✅ Toutes les 14 tables (users, events, orders, sessions, rate_limiter, etc.)
- ✅ Tous les index d'optimisation
- ✅ Données de test (4 utilisateurs + 13 événements)
- ✅ Configuration Stripe Connect
- ✅ Système de sessions avec expiration
- ✅ Rate Limiter (protection brute force)

---

## 🚀 Installation COMPLÈTE (Base neuve)

### Option 1 : phpMyAdmin (Recommandé)

1. Ouvrir phpMyAdmin
2. **Ne pas** sélectionner de base de données
3. Onglet **SQL**
4. Copier le contenu de `reset_database.sql` et exécuter
5. Sélectionner la base `memoriaeventia` dans le menu gauche
6. Onglet **Importer**
7. Sélectionner le fichier `database.sql`
8. Cliquer sur **Exécuter**

### Option 2 : Ligne de commande

```bash
# Créer la base et importer tout d'un coup
mysql -u root -p < BackEnd/database.sql
```

---

## 🔄 Réinitialisation (Supprimer et recréer)

Si vous voulez tout effacer et recommencer :

### phpMyAdmin :

```sql
-- Exécuter ceci dans l'onglet SQL (sans sélectionner de base)
DROP DATABASE IF EXISTS memoriaeventia;
CREATE DATABASE memoriaeventia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Puis importer `database.sql` dans la base `memoriaeventia`.

### Ou utiliser le fichier `reset_database.sql` :

1. Exécuter `reset_database.sql` en premier
2. Puis importer `database.sql`

---

## 📊 Contenu de la Base

### Tables principales (9)

- `users` - Utilisateurs (avec Stripe Connect)
- `events` - Événements historiques
- `orders` - Commandes
- `order_items` - Articles de commandes
- `tickets_generated` - Billets individuels avec QR codes
- `favorites` - Favoris des utilisateurs
- `sessions` - Sessions d'authentification avec expiration
- `payments` - Paiements Stripe
- `event_modifications` - Modifications d'événements en attente

### Tables Stripe Connect (2)

- `creator_earnings` - Gains des créateurs
- `stripe_connect_log` - Historique des connexions Stripe

### Tables système (1)

- `rate_limiter` - Protection contre brute force

---

## 👤 Comptes de test disponibles

**Mot de passe pour TOUS** : `password`

| Email                        | Rôle           | Description              |
| ---------------------------- | -------------- | ------------------------ |
| admin@memoriaeventia.com     | Administrateur | Accès backoffice complet |
| moderator@memoriaeventia.com | Modérateur     | Gestion des contenus     |
| organizer@example.com        | Organisateur   | Créateur d'événements    |
| user@example.com             | Utilisateur    | Compte standard          |

---

## 🎯 Événements de test

13 événements historiques sont déjà créés et approuvés :

- Carnaval de Venise (Italie)
- Oktoberfest (Allemagne)
- Festival Médiéval de Carcassonne (France)
- San Fermín (Espagne)
- Edinburgh Military Tattoo (Écosse)
- Et 8 autres...

---

## 🔍 Vérification

Pour vérifier que tout est bien installé :

```sql
-- Lister toutes les tables
SHOW TABLES;

-- Doit afficher 12 tables

-- Compter les utilisateurs
SELECT COUNT(*) FROM users;
-- Doit afficher : 4

-- Compter les événements
SELECT COUNT(*) FROM events;
-- Doit afficher : 13

-- Vérifier la structure de sessions
SHOW COLUMNS FROM sessions;
-- Doit avoir : token VARCHAR(64), expires_at, last_activity

-- Vérifier que rate_limiter existe
SHOW COLUMNS FROM rate_limiter;
```

---

## ⚠️ Notes importantes

1. **Fichiers obsolètes supprimés** :
   - ~~migration_add_expires_at_sessions.sql~~ (intégré dans database.sql)
   - ~~migration_sessions_ratelimiter.sql~~ (intégré dans database.sql)
   - ~~add_stripe_connect.sql~~ (intégré dans database.sql)

2. **Un seul fichier nécessaire** : `database.sql`

3. **reset_database.sql** : Utilitaire optionnel pour réinitialiser rapidement

4. **Données de test** : Conservées pour faciliter le développement

---

✅ **C'est tout ! Un seul fichier, une seule importation, tout fonctionne !**
