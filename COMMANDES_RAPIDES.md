# COMMANDES RAPIDES - MIGRATION RÉSERVATIONS

## 🔧 MIGRATION BASE DE DONNÉES

### Via MySQL (ligne de commande)

```bash
# Backup avant migration
mysqldump -u root -p memoriaeventia > backup_avant_migration.sql

# Exécuter la migration
mysql -u root -p memoriaeventia < BackEnd/update_database_reservations.sql

# Vérifier les tables
mysql -u root -p memoriaeventia -e "SHOW TABLES;"
```

### Via phpMyAdmin

1. Ouvrir : `http://localhost/phpmyadmin`
2. Sélectionner : `memoriaeventia`
3. Onglet : **SQL**
4. Copier le contenu de : `BackEnd/update_database_reservations.sql`
5. Cliquer : **Exécuter**

---

## 🧪 TESTS RAPIDES

### Test 1 : Vérifier la migration

```sql
-- Table reservations existe ?
SELECT * FROM reservations LIMIT 1;

-- Tables Stripe supprimées ?
SHOW TABLES LIKE 'orders';
SHOW TABLES LIKE 'payments';
SHOW TABLES LIKE 'stripe_connect_log';

-- Colonnes Stripe supprimées de users ?
DESCRIBE users;
```

### Test 2 : Tester l'application

```bash
# Ouvrir dans le navigateur
http://localhost/tfeHistoire/

# Actions à tester :
# 1. Cliquer sur un événement
# 2. Cliquer sur "Réserver"
# 3. Confirmer dans la modal
# 4. Vérifier dans Profil → Mes réservations
```

---

## 🗑️ ARCHIVER FICHIERS STRIPE (optionnel)

```bash
# Créer le dossier d'archive
mkdir -p _archive_stripe/backend
mkdir -p _archive_stripe/frontend

# Backend
mv BackEnd/Api/stripeApi.php _archive_stripe/backend/ 2>/dev/null
mv BackEnd/Api/stripeConnectApi.php _archive_stripe/backend/ 2>/dev/null
mv BackEnd/Api/webhookStripeApi.php _archive_stripe/backend/ 2>/dev/null

# Frontend Managers
mv assets/js/managers/CheckoutManager.js _archive_stripe/frontend/ 2>/dev/null
mv assets/js/managers/OrderManager.js _archive_stripe/frontend/ 2>/dev/null
mv assets/js/managers/StripeConnectManager.js _archive_stripe/frontend/ 2>/dev/null

# Frontend Views
mv assets/js/views/checkout.js _archive_stripe/frontend/ 2>/dev/null
mv assets/js/views/paymentSuccess.js _archive_stripe/frontend/ 2>/dev/null
mv assets/js/views/paymentCancel.js _archive_stripe/frontend/ 2>/dev/null

echo "✅ Fichiers Stripe archivés dans _archive_stripe/"
```

---

## 🔍 VÉRIFICATIONS SQL

### Compter les réservations

```sql
SELECT COUNT(*) as total_reservations FROM reservations;
```

### Voir toutes les réservations

```sql
SELECT r.*, u.name, e.title
FROM reservations r
JOIN users u ON r.user_id = u.id
JOIN events e ON r.event_id = e.id
ORDER BY r.created_at DESC;
```

### Places disponibles pour un événement

```sql
SELECT
  e.id,
  e.title,
  e.ticket_quantity as total,
  COALESCE(SUM(r.quantity), 0) as reserved,
  (e.ticket_quantity - COALESCE(SUM(r.quantity), 0)) as available
FROM events e
LEFT JOIN reservations r ON e.id = r.event_id
  AND r.status = 'confirmed'
  AND r.is_deleted = FALSE
WHERE e.id = 1  -- Remplacer par l'ID de l'événement
GROUP BY e.id;
```

### Réservations d'un utilisateur

```sql
SELECT r.*, e.title, e.date, e.time
FROM reservations r
JOIN events e ON r.event_id = e.id
WHERE r.user_id = 1  -- Remplacer par l'ID de l'utilisateur
  AND r.is_deleted = FALSE
ORDER BY r.created_at DESC;
```

---

## 🔄 RETOUR EN ARRIÈRE

```bash
# Restaurer la base de données
mysql -u root -p memoriaeventia < backup_avant_migration.sql

# Restaurer les fichiers (si archivés)
cp _archive_stripe/backend/* BackEnd/Api/
cp _archive_stripe/frontend/*Manager.js assets/js/managers/
cp _archive_stripe/frontend/*.js assets/js/views/

# Décommenter les routes dans app.js
# Restaurer les imports dans eventDetail.js et profile.js
```

---

## 📊 STATUT DE LA MIGRATION

### ✅ Fait

- Migration SQL créée
- Backend API réservations créé
- Frontend Manager réservations créé
- Modal de confirmation créée
- Vues modifiées (eventDetail, profile)
- Routes Stripe commentées
- Documentation complète

### 🔜 À faire (par vous)

1. [ ] Exécuter le script SQL
2. [ ] Tester la réservation
3. [ ] Vérifier le profil
4. [ ] (Optionnel) Archiver fichiers Stripe

---

## 🆘 DÉPANNAGE EXPRESS

| Erreur                         | Solution                                      |
| ------------------------------ | --------------------------------------------- |
| Table reservations not found   | Exécuter `update_database_reservations.sql`   |
| ReservationManager not defined | Vider cache navigateur (Ctrl+Shift+R)         |
| 500 sur reservationsApi.php    | Vérifier logs : `BackEnd/logs/error.log`      |
| Modal ne s'affiche pas         | Vérifier console (F12), Bootstrap JS chargé ? |
| Cannot create reservation      | Vérifier token auth, user connecté ?          |

---

## 📞 RESSOURCES

- Guide complet : `GUIDE_MIGRATION_RESERVATIONS.md`
- Doc technique : `Documentation/MIGRATION_STRIPE_REMOVED.md`
- Résumé : `RESUME_MIGRATION.md`

---

**Dernière mise à jour : 20 mai 2026**
