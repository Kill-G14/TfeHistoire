# MIGRATION : SUPPRESSION DE STRIPE

## Date de migration

Mai 2026

## Objectif

Retirer toute la fonctionnalité Stripe pour présenter une version simplifiée du TFE avec un système de réservation simple sans paiement en ligne.

---

## CHANGEMENTS DE BASE DE DONNÉES

### Script de migration

Le fichier `update_database_reservations.sql` contient tous les changements SQL nécessaires.

### Étapes :

1. Exécuter le script SQL : `update_database_reservations.sql`
2. Ce script :
   - Supprime les tables : `orders`, `order_items`, `tickets_generated`, `payments`, `creator_earnings`, `stripe_connect_log`
   - Supprime les colonnes Stripe de la table `users` : `stripe_account_id`, `stripe_account_status`, `stripe_onboarding_completed`, `stripe_connected_at`
   - Supprime les colonnes Stripe de la table `events` : `requires_stripe_account`, `stripe_account_verified`
   - Crée une nouvelle table `reservations` (simple et légère)

### Nouvelle table : reservations

```sql
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

---

## CHANGEMENTS BACKEND

### Nouveaux fichiers créés

- **`Src/Models/Reservation.php`** : Modèle de réservation
- **`Src/Repositories/ReservationRepository.php`** : Accès base de données pour réservations
- **`Src/Services/ReservationService.php`** : Logique métier des réservations
- **`Api/reservationsApi.php`** : API REST pour gérer les réservations

### API disponibles

#### `reservationsApi.php`

- `create` : Créer une réservation (nécessite token)
- `getMyReservations` : Récupérer les réservations de l'utilisateur (nécessite token)
- `cancel` : Annuler une réservation (nécessite token)
- `getAvailableTickets` : Récupérer le nombre de places disponibles
- `checkReservation` : Vérifier si l'utilisateur a déjà réservé

### Fichiers Stripe à supprimer (si désiré)

Ces fichiers ne sont plus utilisés et peuvent être supprimés :

- `Api/stripeApi.php`
- `Api/stripeConnectApi.php`
- `Api/webhookStripeApi.php`
- `Api/ordersApi.php` (si plus nécessaire)
- `Src/Services/StripeService.php` (s'il existe)
- `Src/Services/OrderService.php` (peut être conservé pour historique)

---

## CHANGEMENTS FRONTEND

### Nouveaux fichiers créés

- **`assets/js/managers/ReservationManager.js`** : Manager pour appels API réservations
- **`assets/components/reservationModal.html`** : Template HTML de la modal de confirmation
- **`assets/js/components/reservationModal.js`** : Composant modal de confirmation

### Fichiers modifiés

- **`assets/js/components/eventDetail.js`** :
  - Import : `OrderManager` → `showReservationModal` (reservationModal.js)
  - Fonction `handleReservation()` : Affiche la modal au lieu de créer une commande
- **`assets/js/views/profile.js`** :
  - Import : `OrderManager` → `ReservationManager`
  - Fonction `loadReservations()` : Utilise `ReservationManager.getMyReservations()`
  - Fonction `displayReservations()` : Affiche les réservations avec statut confirmée/annulée
  - Fonction `window.cancelReservation()` : Nouvelle fonction pour annuler une réservation
  - Commenté : `loadStripeConnectStatus()` et `checkStripeReturnStatus()`

### Fichiers Stripe à supprimer ou archiver

Ces fichiers ne sont plus utilisés :

- `assets/js/managers/CheckoutManager.js`
- `assets/js/managers/OrderManager.js`
- `assets/js/managers/StripeConnectManager.js`
- `assets/js/views/checkout.js`
- `assets/js/views/paymentSuccess.js`
- `assets/js/views/paymentCancel.js`
- `assets/templates/views/checkout.html` (s'il existe)

**Note** : Ces fichiers peuvent être déplacés dans un dossier `_archive/` si vous souhaitez conserver l'historique.

---

## NOUVEAU FLUX DE RÉSERVATION

### 1. Utilisateur consulte un événement

- L'utilisateur voit les détails de l'événement dans la modal `eventDetail`

### 2. Clic sur "Réserver"

- Si non connecté : Affiche la modal de connexion
- Si connecté : Affiche la modal de confirmation de réservation

### 3. Modal de confirmation

**Contenu** :

- Titre : "Confirmer la réservation"
- Message : "Êtes-vous sûr de vouloir réserver une place pour cet événement ?"
- Informations de l'événement (titre, date, heure, lieu, prix)
- Boutons : "Non, annuler" | "Oui, réserver"

### 4. Validation

- **Oui** :
  - Appel API `reservationsApi.php` action `create`
  - Vérifications backend :
    - Événement existe et est approuvé
    - Places disponibles
    - Utilisateur n'a pas déjà réservé
  - Si succès : Toast success + réservation ajoutée au profil
  - Si erreur : Toast erreur + message explicite
- **Non** : Ferme la modal, rien ne se passe

### 5. Consultation des réservations

- Dans le profil utilisateur, onglet "Mes réservations"
- Affichage des réservations avec :
  - Titre de l'événement
  - Date et heure de l'événement
  - Lieu
  - Nombre de places réservées
  - Prix (ou "Gratuit")
  - Statut : Confirmée ou Annulée
  - Bouton "Annuler la réservation" (si confirmée)

---

## ROUTES À SUPPRIMER DU ROUTEUR

Dans `assets/js/app.js`, supprimer ou commenter ces routes :

```javascript
'/checkout': () => import('./views/checkout.js'),
'/payment-success': () => import('./views/paymentSuccess.js'),
'/payment-cancel': () => import('./views/paymentCancel.js'),
```

---

## VÉRIFICATIONS APRÈS MIGRATION

### Base de données

- [ ] Tables Stripe supprimées
- [ ] Table `reservations` créée
- [ ] Colonnes Stripe retirées de `users` et `events`

### Backend

- [ ] API `reservationsApi.php` fonctionnelle
- [ ] Tests des actions : create, getMyReservations, cancel, checkReservation
- [ ] Vérifications de sécurité (token, appartenance utilisateur)

### Frontend

- [ ] Modal de confirmation s'affiche correctement
- [ ] Réservation créée avec succès
- [ ] Réservations affichées dans le profil
- [ ] Annulation de réservation fonctionnelle
- [ ] Messages de toast appropriés
- [ ] Pas d'erreurs console liées à Stripe/Checkout

---

## AVANTAGES DE CETTE MIGRATION

✅ **Simplicité** : Plus de complexité liée aux paiements en ligne  
✅ **Présentation** : Version présentable pour le TFE sans dépendance externe  
✅ **Rapidité** : Réservation instantanée sans redirection  
✅ **UX améliorée** : Modal de confirmation claire et simple  
✅ **Maintenance** : Moins de code à maintenir

---

## RETOUR EN ARRIÈRE (si nécessaire)

Si vous souhaitez revenir à la version avec Stripe :

1. Restaurer la base de données depuis un backup
2. Rétablir les imports dans `eventDetail.js` et `profile.js`
3. Décommenter les routes checkout/payment dans `app.js`
4. Restaurer les fichiers Stripe depuis `_archive/`

---

## CONTACT / QUESTIONS

Pour toute question sur cette migration, consultez :

- Le script SQL : `update_database_reservations.sql`
- Le nouveau manager : `ReservationManager.js`
- L'API : `reservationsApi.php`
- Le service : `ReservationService.php`
