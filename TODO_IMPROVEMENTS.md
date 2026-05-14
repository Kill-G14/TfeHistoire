# 📝 LISTE DES AMÉLIORATIONS - TODO

## ✅ DÉJÀ IMPLÉMENTÉ

### 1. Configuration & Environnement

- ✅ Système .env avec EnvLoader.php
- ✅ config.php adapté pour charger .env
- ✅ .env.example documenté
- ✅ .gitignore mis à jour (exclut .env et logs)
- ✅ Séparation dev/production automatique

### 2. Sécurité des sessions

- ✅ Tokens de 64 caractères (32 bytes)
- ✅ Expiration automatique (14 jours)
- ✅ Renouvellement automatique
- ✅ SessionService.php mis à jour
- ✅ SessionRepository.php mis à jour
- ✅ Migration SQL créée

### 3. Rate Limiting

- ✅ RateLimiter.php avec commentaires détaillés
- ✅ Protection login (5 tentatives / 15 min)
- ✅ Protection register (3 tentatives / 30 min)
- ✅ Protection password_reset (3 tentatives / 60 min)
- ✅ Table rate_limiter dans migration SQL
- ✅ authApi.php intégré avec rate limiting

### 4. LocalStorage - Cohérence des clés

- ✅ Clés localStorage unifiées vers `memoriaeventia_*`
- ✅ assets/js/utils/auth.js mis à jour
- ✅ AdminOffice/assets/js/utils/auth.js mis à jour
- ✅ AdminOffice/assets/js/utils/helpers.js mis à jour

### 5. Nettoyage console.log/error

- ✅ Suppression de tous les console.log() et console.error()
- ✅ 15+ fichiers JavaScript nettoyés
- ✅ Messages utilisateur préservés (showToast, retours API)
- ✅ Migration scripts conservés (feedback intentionnel)

---

## 🔨 À FAIRE RAPIDEMENT

### 6. Scripts utilitaires (cron jobs)

**Priorité : MOYENNE**

Créer les scripts de nettoyage automatique :

- [ ] cleanup_sessions.php (supprimer sessions expirées)
- [ ] cleanup_ratelimiter.php (nettoyer rate_limiter ancien)
- [ ] GUIDE_MIGRATION.md (documentation migration)

### 7. Standardiser les réponses API

**Priorité : MOYENNE**

**✅ TERMINÉ !** Toutes les réponses ont maintenant la structure standardisée :

```json
{
  "success": true|false,
  "message": "Message explicite",
  "data": { ... },
  "errors": [ ... ]
}
```

**Services uniformisés :**

- ✅ EventService.php (8 méthodes)
- ✅ FavoriteService.php (3 méthodes)
- ✅ OrderService.php (2 méthodes)
- ✅ UserService.php (2 méthodes)
- ✅ StripeService.php (1 méthode)
- ✅ StripeConnectService.php (2 méthodes)
- ✅ AuthService.php (déjà OK)
- ✅ EventModificationService.php (déjà OK)
- ✅ PdfService.php (déjà OK)

### 8. Page d'erreur générique

**Priorité : BASSE**

Créer une page d'erreur sympathique :

- [ ] assets/templates/views/error.html
- [ ] assets/js/views/error.js
- [ ] Intégrer dans router.js pour les erreurs 404, 500, etc.

---

## 🔐 SÉCURITÉ AVANCÉE (Optionnel mais recommandé)

### 9. Sanitization innerHTML (XSS)

**Priorité : HAUTE SI HTML UTILISATEUR**

Si vous insérez du HTML venant d'utilisateurs :

- [ ] Ajouter DOMPurify via CDN ou npm
- [ ] Nettoyer tout innerHTML avec DOMPurify.sanitize()

**Exemple** :

```javascript
// AVANT
element.innerHTML = userContent;

// APRÈS
element.innerHTML = DOMPurify.sanitize(userContent);
```

### 10. Validation stricte des uploads d'images

**Priorité : MOYENNE**

Actuellement géré dans `imageApi.php`, vérifier :

- [ ] Vérification MIME type ET extension
- [ ] Limite de taille stricte
- [ ] Génération de noms aléatoires
- [ ] Stockage hors webroot si possible

### 11. Protection CSRF

**Priorité : MOYENNE**

Ajouter une protection CSRF pour les formulaires sensibles :

- [ ] Générer un token CSRF à la connexion
- [ ] Valider le token sur les actions critiques
- [ ] Créer CsrfManager.php

---

## 📚 DOCUMENTATION

### 12. Dossier DOC complet

**Priorité : MOYENNE**

Créer un dossier `/DOC/` avec :

- [ ] Architecture technique (schéma)
- [ ] Guide API (tous les endpoints)
- [ ] Guide développeur (convention de code)
- [ ] Guide déploiement (prod)
- [ ] FAQ utilisateur

### 13. Tests unitaires

**Priorité : BASSE**

Créer `/BackEnd/Tests/` avec :

- [ ] Tests AuthService
- [ ] Tests SessionService
- [ ] Tests RateLimiter
- [ ] Tests Validators

**Framework** : PHPUnit

---

## 🎨 EXPÉRIENCE UTILISATEUR

### 14. Template d'email pour admin

**Priorité : BASSE**

Créer un template email professionnel :

- [ ] Nouveau compte créé
- [ ] Nouvel événement publié
- [ ] Nouveau paiement reçu
- [ ] Ticket scanné

**Framework** : Utiliser Mailjet ou Brevo (ex-Sendinblue)

### 15. Amélioration des messages d'erreur

**Priorité : BASSE**

Rendre les messages plus explicites côté utilisateur :

- [ ] Erreurs de formulaire détaillées
- [ ] Messages de succès encourageants
- [ ] Instructions claires

---

## ⚙️ OPTIMISATION PERFORMANCE

### 16. Cache

**Priorité : BASSE**

Mettre en cache :

- [ ] Liste des événements publics (5 minutes)
- [ ] Profil utilisateur (session)
- [ ] Résultats de recherche

### 17. Lazy loading des images

**Priorité : BASSE**

Ajouter `loading="lazy"` sur toutes les images événements.

---

## 🚀 DÉPLOIEMENT

### 18. Pipeline CI/CD

**Priorité : BASSE**

Si hébergement via Git :

- [ ] Créer .github/workflows/deploy.yml
- [ ] Tests automatiques avant déploiement
- [ ] Déploiement automatique sur push main

---

## 📊 MONITORING

### 19. Logs structurés

**Priorité : MOYENNE**

Améliorer Logger.php :

- [ ] Rotation des logs (par jour)
- [ ] Niveaux : DEBUG, INFO, WARNING, ERROR, CRITICAL
- [ ] Logs JSON pour parsing facile

### 20. Dashboard admin

**Priorité : BASSE**

Ajouter dans AdminOffice :

- [ ] Statistiques sessions actives
- [ ] Tentatives de connexion bloquées
- [ ] Événements créés par période
- [ ] CA Stripe par mois

---

## ✅ CHECKLIST AVANT PRODUCTION

- [ ] Exécuter migration SQL
- [ ] Configurer .env en production
- [ ] Tester rate limiting
- [ ] Vérifier HTTPS actif
- [ ] Configurer cronjobs
- [ ] Backup base de données
- [ ] Tester paiements Stripe (live keys)
- [ ] Logs accessibles mais protégés
- [ ] Changer les clés localStorage
- [x] Retirer tous les console.log

---

**Estimation temps total** : 6-10h pour les tâches prioritaires (6-8).

**Date de création** : 11 mai 2026
**Dernière mise à jour** : 14 mai 2026
