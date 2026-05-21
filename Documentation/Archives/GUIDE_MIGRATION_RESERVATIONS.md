# GUIDE DE MIGRATION - SUPPRESSION DE STRIPE

## 📋 AVANT DE COMMENCER

### Pré-requis

- Accès à phpMyAdmin ou ligne de commande MySQL
- Backup de la base de données (recommandé)
- Serveur local lancé (WAMP/XAMPP)

### Backup recommandé

```bash
mysqldump -u root -p memoriaeventia > backup_avant_migration.sql
```

---

## 🗃️ ÉTAPE 1 : MIGRATION DE LA BASE DE DONNÉES

### Option A : Via phpMyAdmin

1. Ouvrir phpMyAdmin : `http://localhost/phpmyadmin`
2. Sélectionner la base `memoriaeventia`
3. Cliquer sur l'onglet "SQL"
4. Copier-coller le contenu du fichier `BackEnd/update_database_reservations.sql`
5. Cliquer sur "Exécuter"
6. Vérifier les messages de succès

### Option B : Via ligne de commande

```bash
mysql -u root -p memoriaeventia < BackEnd/update_database_reservations.sql
```

### Vérifications après migration

Dans phpMyAdmin, vérifier que :

- ✅ Table `reservations` existe
- ✅ Tables `orders`, `order_items`, `tickets_generated`, `payments` sont supprimées
- ✅ Table `users` ne contient plus les colonnes Stripe
- ✅ Table `events` ne contient plus les colonnes Stripe

---

## 🚀 ÉTAPE 2 : TESTER LE NOUVEAU SYSTÈME

### 1. Redémarrer le serveur

```bash
# Si nécessaire, redémarrer Apache et MySQL
```

### 2. Ouvrir l'application

```
http://localhost/tfeHistoire/
```

### 3. Tests à effectuer

#### Test 1 : Réservation simple

1. Parcourir les événements sur la page d'accueil
2. Cliquer sur un événement pour voir les détails
3. Cliquer sur "Réserver"
4. **Si non connecté** : Modal de connexion apparaît
5. **Si connecté** : Modal de confirmation apparaît
6. Cliquer sur "Oui, réserver"
7. ✅ Toast de succès
8. Vérifier dans le profil → Onglet "Mes réservations"

#### Test 2 : Annulation de réservation

1. Aller dans Profil → Mes réservations
2. Cliquer sur "Annuler la réservation"
3. Confirmer l'annulation
4. ✅ La réservation passe à l'état "Annulée"

#### Test 3 : Vérifications

1. Pas d'erreurs dans la console du navigateur (F12)
2. Les toasts s'affichent correctement
3. Les réservations s'affichent dans le profil
4. Le nombre de places disponibles diminue après réservation

---

## 📦 ÉTAPE 3 : ARCHIVER LES FICHIERS STRIPE (OPTIONNEL)

### Fichiers backend à archiver

Ces fichiers peuvent être déplacés dans un dossier `_archive/` :

```
BackEnd/Api/
  ├── stripeApi.php
  ├── stripeConnectApi.php
  ├── webhookStripeApi.php
  └── ordersApi.php (optionnel)
```

### Fichiers frontend à archiver

```
assets/js/managers/
  ├── CheckoutManager.js
  ├── OrderManager.js
  └── StripeConnectManager.js

assets/js/views/
  ├── checkout.js
  ├── paymentSuccess.js
  └── paymentCancel.js
```

### Commande pour créer l'archive

```bash
# Depuis la racine du projet
mkdir _archive_stripe
mkdir _archive_stripe/backend
mkdir _archive_stripe/frontend

# Backend
mv BackEnd/Api/stripeApi.php _archive_stripe/backend/
mv BackEnd/Api/stripeConnectApi.php _archive_stripe/backend/
mv BackEnd/Api/webhookStripeApi.php _archive_stripe/backend/

# Frontend
mv assets/js/managers/CheckoutManager.js _archive_stripe/frontend/
mv assets/js/managers/OrderManager.js _archive_stripe/frontend/
mv assets/js/managers/StripeConnectManager.js _archive_stripe/frontend/
mv assets/js/views/checkout.js _archive_stripe/frontend/
mv assets/js/views/paymentSuccess.js _archive_stripe/frontend/
mv assets/js/views/paymentCancel.js _archive_stripe/frontend/
```

---

## 🧪 ÉTAPE 4 : TESTS COMPLETS

### Checklist de tests

#### Fonctionnalités de réservation

- [ ] Affichage de la modal de confirmation
- [ ] Création d'une réservation (événement gratuit)
- [ ] Création d'une réservation (événement payant)
- [ ] Vérification : impossible de réserver 2 fois le même événement
- [ ] Vérification : impossible de réserver si plus de places
- [ ] Affichage des réservations dans le profil
- [ ] Annulation d'une réservation
- [ ] Nombre de places disponibles mis à jour

#### Interface utilisateur

- [ ] Pas d'erreurs console
- [ ] Toasts affichés correctement
- [ ] Modal se ferme après réservation
- [ ] Navigation fluide
- [ ] Responsive (mobile/tablet/desktop)

#### Sécurité

- [ ] Impossible de réserver sans être connecté
- [ ] Token requis pour toutes les actions
- [ ] Impossible d'annuler la réservation d'un autre utilisateur

---

## 🔧 DÉPANNAGE

### Erreur : Table reservations n'existe pas

**Solution** : Exécuter le script SQL `update_database_reservations.sql`

### Erreur : ReservationManager is not defined

**Solution** : Vider le cache du navigateur (Ctrl+Shift+R)

### Erreur : Cannot read property 'getMyReservations'

**Solution** : Vérifier que le fichier `ReservationManager.js` est bien chargé

### Erreur 500 sur reservationsApi.php

**Solution** :

1. Vérifier les logs Apache : `logs/error.log`
2. Vérifier que l'autoload Composer fonctionne
3. Vérifier que la connexion DB est correcte

### Modal de réservation ne s'affiche pas

**Solution** :

1. Ouvrir la console (F12)
2. Vérifier qu'il n'y a pas d'erreur JavaScript
3. Vérifier que le fichier `reservationModal.html` existe
4. Vérifier que Bootstrap JS est chargé

---

## 📊 VÉRIFICATION DE LA MIGRATION

### SQL : Vérifier les données

```sql
-- Compter les réservations
SELECT COUNT(*) as total_reservations FROM reservations;

-- Voir les réservations
SELECT r.*, u.name as user_name, e.title as event_title
FROM reservations r
JOIN users u ON r.user_id = u.id
JOIN events e ON r.event_id = e.id
ORDER BY r.created_at DESC;

-- Vérifier les places disponibles pour un événement
SELECT
  e.title,
  e.ticket_quantity as total,
  COALESCE(SUM(r.quantity), 0) as reserved,
  (e.ticket_quantity - COALESCE(SUM(r.quantity), 0)) as available
FROM events e
LEFT JOIN reservations r ON e.id = r.event_id AND r.status = 'confirmed' AND r.is_deleted = FALSE
WHERE e.id = 1
GROUP BY e.id;
```

---

## 📝 NOTES IMPORTANTES

### Ce qui a changé

✅ Plus de paiement en ligne  
✅ Réservation instantanée avec confirmation  
✅ Base de données simplifiée  
✅ Moins de code à maintenir

### Ce qui n'a PAS changé

✅ Authentification  
✅ Gestion des événements  
✅ Favoris  
✅ Profil utilisateur  
✅ Carte et calendrier

---

## 🔙 RETOUR EN ARRIÈRE

Si vous souhaitez revenir à la version avec Stripe :

### 1. Restaurer la base de données

```bash
mysql -u root -p memoriaeventia < backup_avant_migration.sql
```

### 2. Restaurer les fichiers

```bash
# Déplacer les fichiers depuis _archive_stripe/
```

### 3. Décommenter dans app.js

```javascript
"/checkout": () => import("./views/checkout.js"),
"/payment/success": () => import("./views/paymentSuccess.js"),
"/payment/cancel": () => import("./views/paymentCancel.js"),
```

### 4. Restaurer les imports

- Dans `eventDetail.js` : réimporter `OrderManager`
- Dans `profile.js` : réimporter `OrderManager`, décommenter Stripe

---

## 📞 SUPPORT

Pour toute question :

- Consulter : `Documentation/MIGRATION_STRIPE_REMOVED.md`
- Vérifier les logs : `BackEnd/logs/`
- Console navigateur : F12

---

## ✅ MIGRATION TERMINÉE

Une fois tous les tests réussis :

1. ✅ Base de données migrée
2. ✅ Réservations fonctionnelles
3. ✅ Aucune erreur console
4. ✅ Interface utilisateur fluide

**Félicitations ! Votre application utilise maintenant un système de réservation simple sans paiement.**
