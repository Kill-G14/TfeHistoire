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
- ✅ ReservationService.php (3 méthodes)
- ✅ UserService.php (2 méthodes)
- ✅ AuthService.php (déjà OK)
- ✅ EventModificationService.php (déjà OK)

### 8. Page d'erreur générique

**Priorité : BASSE**

**✅ PARTIELLEMENT FAIT** : `router.js` a déjà une méthode `show404()` (ligne 145) qui affiche une page 404 basique.

**À améliorer** (optionnel) :

- [ ] Créer assets/templates/views/error.html (page dédiée plus design)
- [ ] Créer assets/js/views/error.js (gestion erreurs 404, 500, etc.)
- [ ] Remplacer le HTML inline de show404() par chargement de template

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

**✅ TRÈS BIEN SÉCURISÉ !** Validations multiples en place :

**✅ Implémenté (Frontend - imageValidator.js) :**

- ✅ Validation extension (jpg, jpeg, png, webp)
- ✅ Validation MIME type
- ✅ Limite de taille (5 MB max)
- ✅ Validation magic bytes (signatures binaires)
- ✅ Cohérence extension/contenu réel

**✅ Implémenté (Backend - uploadImageApi.php) :**

- ✅ Vérification MIME type ET extension (triple vérification)
- ✅ Limite de taille stricte (5 MB)
- ✅ Génération de noms aléatoires (`uniqid('event_', true)` + timestamp)
- ✅ Validation magic bytes JPEG/PNG/WEBP
- ✅ Protection path traversal (basename)
- ✅ Permissions fichiers (0644)
- ✅ Logging des uploads

**❌ Failles potentielles à corriger :**

- [ ] **Rate limiting sur upload** (spam d'images possible)
- [ ] **Validation dimensions** (image 50000x50000px = RAM overflow)
- [ ] **Nettoyage métadonnées EXIF** (données GPS, copyright)
- [ ] **Stockage hors webroot** (actuellement storage/images/ accessible via web)
- [ ] **Incohérence** : GIF supporté dans imageApi.php mais pas uploadImageApi.php

**Recommandations :**

```php
// 1. Ajouter validation dimensions
$imageInfo = getimagesize($file['tmp_name']);
if ($imageInfo[0] > 4000 || $imageInfo[1] > 4000) {
  echo json_encode(['success' => false, 'message' => 'Dimensions trop grandes (max 4000x4000)']);
  exit;
}

// 2. Strip EXIF avec Imagick
$image = new Imagick($file['tmp_name']);
$image->stripImage();
$image->writeImage($destinationPath);

// 3. Rate limiting (voir RateLimiter.php)
$checkLimit = RateLimiter::check('upload', $userId);
```

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

**✅ PARTIELLEMENT FAIT** : `/Documentation/` existe avec :

- ✅ README_CONFIG.md (configuration backend)
- ✅ INSTALLATION_DATABASE.md (installation BDD)
- ✅ DATABASE_OPTIMIZATION.md (optimisation SQL)
- ✅ ETAT_PROJET.md (état du projet)
- ✅ MIGRATION_STRIPE_REMOVED.md (documentation migration)

**À créer** (manquants) :

- [ ] Architecture technique (schéma MVC, flux API)
- [ ] Guide API complet (tous les endpoints avec exemples)
- [ ] Guide développeur (conventions AGENTS.md déjà en place, mais manque exemples)
- [ ] Guide déploiement production
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

**✅ PARTIELLEMENT FAIT** : `EmailService.php` existe déjà avec :

- ✅ Email modification d'événement
- ✅ Email suppression d'événement
- ✅ Envoi groupé aux admins

**À ajouter** (emails manquants) :

- [ ] Nouveau compte créé
- [ ] Nouvel événement publié
- [ ] Nouvelle réservation confirmée
- [ ] Réservation scannée

**Framework** : Actuellement utilise `mail()` PHP natif. Possibilité d'upgrade vers Mailjet/Brevo.

### 15. Amélioration des messages d'erreur

**Priorité : BASSE**

**✅ FAIT !** Messages améliorés pour être plus explicites et encourageants.

**Fichiers modifiés :**

- ✅ BackEnd/Src/Validators/UserValidator.php (register, login, update)
- ✅ BackEnd/Src/Validators/EventValidator.php (create event)

**Exemples de messages améliorés :**

- ❌ Avant : "L'email est requis"
- ✅ Après : "Veuillez saisir votre adresse email pour créer votre compte"

- ❌ Avant : "Le titre doit contenir au moins 3 caractères"
- ✅ Après : "Le titre est trop court. Veuillez saisir au moins 3 caractères pour décrire votre événement"

**Impact utilisateur :** Messages plus humains, moins robotiques, avec contexte et encouragements.

---

## ⚙️ OPTIMISATION PERFORMANCE

### 16. Cache

**Priorité : BASSE**

**❌ PAS FAIT (cache applicatif)** : Actuellement seul cache HTTP existe pour images (imageApi.php ligne 67).

**À implémenter** :

- [ ] Liste des événements publics (5 minutes) → APCu ou Redis
- [ ] Profil utilisateur (session) → $\_SESSION ou APCu
- [ ] Résultats de recherche (cache court)

**Solution simple** : Utiliser APCu (extension PHP) pour cache mémoire.

### 17. Lazy loading des images

**Priorité : BASSE**

**✅ FAIT !** Attribut `loading="lazy"` ajouté sur toutes les images.

**Fichiers modifiés :**

- ✅ assets/components/eventCard.html (ligne 5)
- ✅ assets/components/eventDetail.html (ligne 11)
- ✅ assets/components/header.html (ligne 16)
- ✅ assets/templates/views/createEvent.html (ligne 93)

---

## 📚 QU'EST-CE QUE LE LAZY LOADING ?

Le **lazy loading** (chargement différé) est une technique d'optimisation qui retarde le chargement des images jusqu'à ce qu'elles soient **visibles à l'écran** (dans le viewport).

### 🎯 Principe de fonctionnement

```html
<!-- SANS lazy loading : l'image charge immédiatement -->
<img src="image.jpg" alt="Description" />

<!-- AVEC lazy loading : l'image charge quand elle devient visible -->
<img src="image.jpg" alt="Description" loading="lazy" />
```

**Comportement navigateur :**

1. **Au chargement de la page** : seules les images visibles à l'écran sont téléchargées
2. **Lors du scroll** : les images deviennent visibles → le navigateur les charge automatiquement
3. **Anticipation** : le navigateur charge les images **un peu avant** qu'elles soient visibles (seuil de ~1000-2000px)

---

## ✨ AVANTAGES

### 1. 🚀 **Performance initiale améliorée**

- **Temps de chargement réduit** : seules 3-5 images chargées au lieu de 20-50
- **First Contentful Paint (FCP) plus rapide** : la page s'affiche plus vite
- **Time to Interactive (TTI) amélioré** : l'utilisateur peut interagir plus tôt

**Exemple concret :**

- Page avec 50 événements (50 images de 200 KB chacune) = **10 MB total**
- Sans lazy loading : télécharge **10 MB** au chargement initial ⚠️
- Avec lazy loading : télécharge **1 MB** (5 images visibles) ✅ **90% d'économie !**

### 2. 📶 **Économie de bande passante**

- **Économie de données mobiles** : crucial pour les utilisateurs avec forfaits limités
- **Réduction des coûts serveur** : moins de transfert de données si l'utilisateur ne scrolle pas
- **Écologique** : moins de data = moins de consommation énergétique

**Cas d'usage :**

- Utilisateur arrive sur la page d'accueil
- Voit les 5 premiers événements
- Quitte la page → **45 images non chargées** = économie de 9 MB

### 3. 🎨 **Meilleure expérience utilisateur**

- **Scroll fluide** : pas de blocage pendant le chargement des images
- **Pas de saut de contenu** : les images apparaissent au bon moment
- **Sensation de rapidité** : la page "se construit" au fur et à mesure

### 4. 📊 **Meilleur SEO**

- **Google PageSpeed Insights** : score amélioré (facteur de classement)
- **Core Web Vitals** : améliore le LCP (Largest Contentful Paint)
- **Taux de rebond réduit** : utilisateurs restent plus longtemps

---

## ⚙️ SUPPORT NAVIGATEURS

| Navigateur   | Support natif |
| ------------ | ------------- |
| Chrome 77+   | ✅            |
| Firefox 75+  | ✅            |
| Edge 79+     | ✅            |
| Safari 15.4+ | ✅            |
| Opera 64+    | ✅            |

**Fallback automatique :** Les navigateurs anciens ignorent l'attribut et chargent normalement (pas de bug).

---

## 🔧 BONNES PRATIQUES

### ✅ À FAIRE

```html
<!-- Images de contenu (événements, profil, galerie) -->
<img src="event.jpg" alt="Description" loading="lazy" />

<!-- Spécifier width/height pour éviter le layout shift -->
<img
  src="event.jpg"
  alt="Description"
  loading="lazy"
  width="400"
  height="300"
/>
```

### ❌ À ÉVITER

```html
<!-- NE PAS lazy load sur les images critiques -->
<!-- Logo, hero image, première image visible -->
<img src="logo.png" alt="Logo" />
<!-- PAS de loading="lazy" -->

<!-- Image du hero banner -->
<img src="hero.jpg" alt="Bannière" />
<!-- PAS de loading="lazy" -->
```

---

## 📈 IMPACT SUR CE PROJET

### Pages impactées

1. **Page d'accueil** (`/`) : 12-50 cartes d'événements
2. **Page calendrier** (`/calendar`) : 20-100 événements
3. **Page carte** (`/map`) : images dans les popups
4. **Détail événement** : image principale + galerie (si ajoutée)

### Gains estimés

- **Chargement initial** : -70% du poids (10 MB → 3 MB)
- **PageSpeed Score** : +15 points (estimé)
- **Temps de chargement mobile 3G** : -4 secondes

---

**Exemple** : `loading="lazy"` est maintenant appliqué sur toutes les images du projet.

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

**✅ PARTIELLEMENT FAIT** : `Logger.php` existe avec :

- ✅ Méthodes info(), error(), warning(), debug()
- ✅ Contexte JSON dans logs
- ✅ Séparation app.log / error.log

**À améliorer** :

- [ ] Rotation des logs (par jour) → `app-2026-05-14.log`
- [ ] Niveau CRITICAL manquant (ajouter critical() method)
- [ ] Format JSON pur (actuellement format mixte texte+JSON)

**Exemple format JSON pur** :

```json
{"timestamp":"2026-05-14 10:30:45","level":"ERROR","message":"...","context":{...}}
```

### 20. Dashboard admin

**Priorité : BASSE**

**✅ PARTIELLEMENT FAIT** : `dashboard.js` affiche déjà :

- ✅ Événements en attente
- ✅ Événements approuvés
- ✅ Modifications en attente
- ✅ Suppressions en attente

**À ajouter** (métriques avancées) :

- [ ] Statistiques sessions actives (requête sur table `sessions`)
- [ ] Tentatives de connexion bloquées (requête sur table `rate_limiter`)
- [ ] Événements créés par période (graphique)
- [ ] Statistiques réservations par mois

---

## ✅ CHECKLIST AVANT PRODUCTION

- [ ] Exécuter migration SQL
- [ ] Configurer .env en production
- [ ] Tester rate limiting
- [ ] Vérifier HTTPS actif
- [ ] Configurer cronjobs
- [ ] Backup base de données
- [ ] Logs accessibles mais protégés
- [x] Retirer tous les console.log

---

**Estimation temps total** : 6-10h pour les tâches prioritaires (6-8).

**Date de création** : 11 mai 2026  
**Dernière mise à jour** : 14 mai 2026

---

## 📝 RÉSUMÉ DES DERNIÈRES MODIFICATIONS (14 mai 2026)

### ✅ Point 15 - Amélioration des messages d'erreur (TERMINÉ)

- UserValidator.php : 9 messages améliorés (register, login, update)
- EventValidator.php : 13 messages améliorés (create event)
- Messages plus humains, explicites et encourageants

### ✅ Point 17 - Lazy loading des images (TERMINÉ)

- 4 fichiers HTML modifiés avec `loading="lazy"`
- Documentation technique complète ajoutée
- Gain estimé : -70% du poids initial (10 MB → 3 MB)
