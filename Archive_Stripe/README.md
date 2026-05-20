# Archive Stripe - MemoriaEventia

## Date d'archivage
20 mai 2026

## Raison
Migration du système de paiement Stripe vers un système de réservations simples sans paiement pour la présentation du TFE.

## Fichiers archivés

### Backend API (5 fichiers)
- `stripeApi.php` - API Stripe Checkout
- `stripeConnectApi.php` - API Stripe Connect pour organisateurs
- `webhookStripeApi.php` - Webhooks Stripe
- `ordersApi.php` - API de gestion des commandes
- `ticketsGeneratedApi.php` - API de génération de tickets PDF

### Backend Services (3 fichiers)
- `StripeService.php` - Logique métier Stripe Checkout
- `StripeConnectService.php` - Logique métier Stripe Connect
- `OrderService.php` - Logique métier des commandes

### Backend Repositories (3 fichiers)
- `OrderRepository.php` - Accès base de données pour les commandes
- `OrderItemRepository.php` - Accès base de données pour les items de commande
- `PaymentRepository.php` - Accès base de données pour les paiements

### Backend Models (4 fichiers)
- `Order.php` - Entité Commande
- `OrderItem.php` - Entité Item de commande
- `Payment.php` - Entité Paiement
- `TicketGenerated.php` - Entité Ticket généré

### Backend DTOs (5 fichiers)
- `OrderDTO.php`
- `OrderItemDTO.php`
- `PaymentDTO.php`
- `TicketGeneratedDTO.php`
- `TicketDTO.php`

### Backend Validators (1 fichier)
- `OrderValidator.php`

### Frontend Managers (3 fichiers)
- `CheckoutManager.js` - Gestion du checkout Stripe
- `OrderManager.js` - Gestion des commandes
- `StripeConnectManager.js` - Gestion Stripe Connect

### Frontend Views (3 fichiers)
- `checkout.js` - Page de checkout
- `paymentSuccess.js` - Page de succès de paiement
- `paymentCancel.js` - Page d'annulation de paiement

## Tables de base de données supprimées
- `orders` - Commandes
- `order_items` - Items de commande
- `payments` - Paiements Stripe
- `tickets_generated` - Tickets PDF générés
- `creator_earnings` - Gains des créateurs
- `stripe_connect_log` - Logs Stripe Connect

## Colonnes supprimées
### Table `users`
- `stripe_customer_id`
- `stripe_account_id`
- `stripe_account_enabled`

### Table `events`
- `requires_stripe_account`
- `stripe_account_verified`

## Nouveau système
Remplacé par le système de **réservations simples** :
- Table `reservations` avec statut (confirmed/cancelled)
- Pas de paiement en ligne
- Validation par modal "Êtes-vous sûr ?"

## Restauration
Pour restaurer le système Stripe :
1. Copier les fichiers de `Archive_Stripe/` vers leurs emplacements d'origine
2. Exécuter `composer dump-autoload`
3. Restaurer la base de données avec les tables Stripe
4. Décommenter les routes checkout/payment dans `app.js`
5. Réactiver les imports dans les fichiers modifiés

## Documentation
- Migration complète : `Documentation/MIGRATION_STRIPE_REMOVED.md`
- Guide utilisateur : `GUIDE_MIGRATION_RESERVATIONS.md`
- Résumé : `RESUME_MIGRATION.md`
- Commandes : `COMMANDES_RAPIDES.md`
