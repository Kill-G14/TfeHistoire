# Archives - Documentation historique

Ce dossier contient la documentation historique des migrations effectuées sur le projet.

## 📂 Contenu

### Guides de migration Stripe → Réservations (Mai 2026)

Ces documents décrivent la migration du système de paiement Stripe vers un système de réservations simples sans paiement.

- **`MIGRATION_STRIPE_REMOVED.md`** : Documentation technique complète de la migration
- **`GUIDE_MIGRATION_RESERVATIONS.md`** : Guide pas-à-pas pour appliquer la migration
- **`RESUME_MIGRATION.md`** : Résumé rapide des changements effectués

### Configuration SendGrid

- **`ACTIONS_A_FAIRE_SENDGRID.md`** : Instructions d'installation de SendGrid
- **`ERREURS_SENDGRID_AVANT_COMPOSER.md`** : Historique des erreurs avant l'installation via Composer

## ⚠️ Statut

Ces documents sont **archivés** et conservés à titre historique uniquement.

La migration a été complétée avec succès. Le projet utilise maintenant :

- ✅ Table `reservations` (au lieu de `orders`, `order_items`, `tickets_generated`)
- ✅ `ReservationService`, `ReservationRepository`, `ReservationDTO`
- ✅ `reservationsApi.php` (au lieu de `ordersApi.php`, `ticketsApi.php`)
- ✅ SendGrid pour les emails (installé via Composer)

## 📚 Documentation actuelle

Pour la documentation à jour du projet, consulter :

- `/BackEnd/README.md` - Documentation du backend
- `/README.md` - Documentation générale du projet
- `/Documentation/` - Documentation technique active
