# 📋 CHECKLIST DE MISE EN PRODUCTION - MemoriaEventia

**Date de préparation:** 21 Mai 2026  
**Version:** 1.0.0  
**Hébergeur:** À déterminer (Linux/Apache requis)

---

## ✅ ÉTAPE 1 : PRÉPARATION DES FICHIERS

### 1.1 Fichiers archivés ✅
Les fichiers de développement suivants ont été archivés dans `Archive_Dev/` :
- ✅ AGENTS.md
- ✅ COMMANDES_RAPIDES.md
- ✅ Guidelines.md
- ✅ todo
- ✅ TODO_IMPROVEMENTS.md
- ✅ Documentation/ (complet)
- ✅ BackEnd/reset_database.sql
- ✅ BackEnd/README.md
- ✅ BackEnd/logs/*.log (copiés et vidés)

**⚠️ IMPORTANT:** Ne PAS uploader le dossier `Archive_Dev/` sur le serveur de production !

### 1.2 Fichiers à NE PAS uploader
- ❌ `.git/` (historique Git - inutile en production)
- ❌ `Archive_Dev/` (fichiers de développement)
- ❌ `BackEnd/.env` (créer nouveau sur serveur)
- ❌ `BackEnd/vendor/` (réinstaller avec Composer sur serveur)
- ❌ `.htaccess` de développement (adapter pour production)

---

## 🗄️ ÉTAPE 2 : BASE DE DONNÉES

### 2.1 Installation de la base de données
1. **Créer la base de données** sur votre hébergeur :
   ```sql
   CREATE DATABASE memoriaeventia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Importer le schéma de production** :
   - Utiliser le fichier `BackEnd/database_production.sql`
   - Via phpMyAdmin ou ligne de commande :
   ```bash
   mysql -u votre_user -p memoriaeventia < database_production.sql
   ```

3. **Vérifier les données** :
   - ✅ Un seul utilisateur créé : `admin@memoriaeventia.com`
   - ✅ Mot de passe admin : `@S76XVzqeAhFmEe`
   - ✅ Toutes les tables créées (8 tables au total)
   - ✅ Tous les index créés automatiquement

### 2.2 Sécurité de la base
- [ ] Créer un utilisateur MySQL dédié (ne PAS utiliser root)
- [ ] Limiter les privilèges : SELECT, INSERT, UPDATE, DELETE uniquement
- [ ] Noter les identifiants pour le `.env`

---

## ⚙️ ÉTAPE 3 : CONFIGURATION DU SERVEUR

### 3.1 Fichier .env (CRITIQUE)
1. **Copier le template** :
   ```bash
   cp BackEnd/.env.example BackEnd/.env
   ```

2. **Modifier TOUTES les valeurs** :

```env
# ENVIRONNEMENT
APP_ENV=production
APP_DEBUG=false

# URLS (ADAPTER À VOTRE DOMAINE)
FRONTEND_URL=https://memoriaeventia.com
API_URL=https://memoriaeventia.com/BackEnd/Api
ALLOWED_ORIGINS=https://memoriaeventia.com

# BASE DE DONNÉES (VOS IDENTIFIANTS)
DB_HOST=localhost
DB_NAME=memoriaeventia
DB_USER=votre_user_mysql
DB_PASSWORD=votre_mot_de_passe_mysql
DB_CHARSET=utf8mb4

# OPENROUTE API (VOTRE CLÉ)
OPENROUTE_API_KEY=votre_cle_api_openroute

# SÉCURITÉ
SESSION_LIFETIME_DAYS=14
MAX_LOGIN_ATTEMPTS=5
LOGIN_BLOCK_DURATION_MINUTES=15

# SENDGRID (VOS IDENTIFIANTS)
SENDGRID_API_KEY=votre_cle_api_sendgrid
SENDGRID_FROM_EMAIL=contact@memoriaeventia.com
SENDGRID_FROM_NAME=MemoriaEventia
SENDGRID_ENABLED=true

# LOGS
LOG_PATH=../logs
LOG_LEVEL=info
```

3. **Sécuriser le fichier** :
   ```bash
   chmod 600 BackEnd/.env
   ```

### 3.2 Composer (Dépendances PHP)
1. **Installer Composer** sur le serveur (si pas déjà installé)
2. **Installer les dépendances** :
   ```bash
   cd BackEnd/
   composer install --no-dev --optimize-autoloader
   ```

### 3.3 Permissions des dossiers
```bash
# Dossiers d'upload et logs DOIVENT être writables
chmod 755 BackEnd/storage/
chmod 755 BackEnd/storage/images/
chmod 755 BackEnd/storage/tickets/
chmod 755 BackEnd/logs/

# Vérifier le propriétaire (doit être l'utilisateur Apache/www-data)
chown -R www-data:www-data BackEnd/storage/
chown -R www-data:www-data BackEnd/logs/
```

---

## 🌐 ÉTAPE 4 : CONFIGURATION DES URLs

### 4.1 Frontend - assets/js/utils/config.js
**Modifier la section PRODUCTION** :
```javascript
production: {
  API_URL: 'https://memoriaeventia.com/BackEnd/Api',  // ← Votre domaine
  BASE_PATH: '',  // ← Vide si à la racine, sinon '/sous-dossier'
  FRONTEND_URL: 'https://memoriaeventia.com'  // ← Votre domaine
}
```

### 4.2 AdminOffice - AdminOffice/assets/js/utils/config.js
**Modifier la section PRODUCTION** :
```javascript
production: {
  API_URL: 'https://memoriaeventia.com/BackEnd/Api',  // ← Votre domaine
  BASE_PATH: '/AdminOffice',
  FRONTEND_URL: 'https://memoriaeventia.com/AdminOffice'  // ← Votre domaine
}
```

### 4.3 .htaccess
**Modifier RewriteBase** selon votre installation :
- Si à la racine du domaine : `RewriteBase /`
- Si dans un sous-dossier : `RewriteBase /memoriaeventia/`

---

## 📧 ÉTAPE 5 : CONFIGURATION EMAIL (SENDGRID)

### 5.1 Vérification du domaine
1. **Accéder à SendGrid** : https://app.sendgrid.com/settings/sender_auth/domains
2. **Vérifier que le domaine est authentifié** (status "Verified")
3. **Si non vérifié**, ajouter les enregistrements DNS :
   - 1 enregistrement TXT pour SPF
   - 3 enregistrements CNAME (em639, s1._domainkey, s2._domainkey)
4. **Attendre la propagation DNS** (peut prendre jusqu'à 24h)

### 5.2 Test d'envoi
1. Se connecter en tant qu'admin
2. Tester la réservation d'un événement
3. Vérifier la réception de l'email
4. Consulter SendGrid Activity Feed si problème

---

## 🔐 ÉTAPE 6 : SÉCURITÉ

### 6.1 Fichiers sensibles
**Vérifier que ces fichiers sont bien protégés** :
```apache
# Dans .htaccess BackEnd/
<Files ".env">
    Require all denied
</Files>

<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

### 6.2 SSL/HTTPS
- [ ] Installer un certificat SSL (Let's Encrypt gratuit)
- [ ] Forcer HTTPS dans .htaccess :
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 6.3 Désactiver le debug
- [ ] Vérifier `APP_DEBUG=false` dans .env
- [ ] Vérifier `LOG_LEVEL=info` (pas "debug")
- [ ] Retirer les console.log dans config.js (lignes 64-66)

---

## 🧪 ÉTAPE 7 : TESTS POST-DÉPLOIEMENT

### 7.1 Tests fonctionnels
- [ ] **Page d'accueil** : Chargement correct
- [ ] **Connexion utilisateur** : Login/logout fonctionnel
- [ ] **Inscription** : Création de compte
- [ ] **Création d'événement** : Upload d'image OK
- [ ] **Réservation** : Réserver et annuler
- [ ] **Email** : Réception confirmation/annulation
- [ ] **Favoris** : Ajouter/retirer des favoris
- [ ] **Carte** : Affichage des événements
- [ ] **Calendrier** : Vue mensuelle
- [ ] **AdminOffice** : Login avec admin@memoriaeventia.com

### 7.2 Tests de performance
- [ ] **Temps de chargement** < 3 secondes
- [ ] **Images** : Affichage correct
- [ ] **API** : Réponses rapides

### 7.3 Tests de sécurité
- [ ] **Accès .env** : https://domaine.com/BackEnd/.env → 403 Forbidden
- [ ] **SQL Injection** : Tester avec ' OR '1'='1
- [ ] **XSS** : Tester avec <script>alert(1)</script>
- [ ] **Rate limiting** : 5 tentatives de login → blocage

---

## 📱 ÉTAPE 8 : RESPONSIVE & COMPATIBILITÉ

### 8.1 Tests multi-navigateurs
- [ ] Chrome (dernière version)
- [ ] Firefox (dernière version)
- [ ] Safari (MacOS/iOS)
- [ ] Edge (dernière version)

### 8.2 Tests mobile
- [ ] iPhone (Safari)
- [ ] Android (Chrome)
- [ ] Tablette (iPad/Android)

---

## 🚨 PROBLÈMES COURANTS & SOLUTIONS

### Problème 1 : Erreur 500 Internal Server Error
**Solutions** :
1. Vérifier les logs : `BackEnd/logs/error.log`
2. Vérifier que `.env` existe et est configuré
3. Vérifier que Composer a installé les dépendances
4. Vérifier les permissions des dossiers (755/644)

### Problème 2 : Images ne s'affichent pas
**Solutions** :
1. Vérifier permissions de `BackEnd/storage/images/` (755)
2. Vérifier propriétaire : `chown www-data:www-data -R BackEnd/storage/`
3. Vérifier URL dans config.js

### Problème 3 : 404 sur les routes SPA
**Solutions** :
1. Vérifier que `.htaccess` est présent à la racine
2. Vérifier que `mod_rewrite` est activé :
   ```bash
   a2enmod rewrite
   systemctl restart apache2
   ```
3. Vérifier `AllowOverride All` dans Apache config

### Problème 4 : Emails non reçus
**Solutions** :
1. Vérifier clé API SendGrid dans `.env`
2. Vérifier domaine vérifié dans SendGrid
3. Consulter SendGrid Activity Feed
4. Vérifier DNS : SPF et DKIM

### Problème 5 : Case sensitivity (Linux)
**Rappel** : Linux est sensible à la casse contrairement à Windows !
- ✅ `AuthManager.js` ≠ `authmanager.js`
- ✅ Vérifier TOUS les imports de fichiers
- ✅ Les chemins doivent correspondre EXACTEMENT

---

## 📝 CHECKLIST FINALE AVANT MISE EN LIGNE

```
[x] Archive_Dev créé et non uploadé
[x] database_production.sql prêt avec admin uniquement
[x] .env.example mis à jour pour production
[x] config.js créé avec détection auto environnement
[x] Values HTML retirées (AdminOffice login)
[x] Logs vidés

[ ] Base de données importée sur serveur
[ ] .env créé et configuré avec vraies valeurs
[ ] Composer install exécuté
[ ] Permissions dossiers configurées (755/644)
[ ] URLs production configurées dans config.js (x2)
[ ] .htaccess adapté (RewriteBase)
[ ] SSL installé et HTTPS forcé
[ ] SendGrid domaine vérifié
[ ] Tests fonctionnels OK
[ ] Tests sécurité OK
[ ] Backup de la base créé
```

---

## 🎯 COMMANDES UTILES

### Voir les logs en temps réel
```bash
tail -f BackEnd/logs/error.log
tail -f BackEnd/logs/app.log
```

### Vider le cache Composer
```bash
composer clear-cache
composer dump-autoload
```

### Recréer la base de données
```bash
mysql -u root -p
DROP DATABASE memoriaeventia;
CREATE DATABASE memoriaeventia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE memoriaeventia;
SOURCE database_production.sql;
```

---

## 📞 SUPPORT

**En cas de problème :**
1. Consulter les logs : `BackEnd/logs/error.log`
2. Vérifier la configuration : `.env` et `config.js`
3. Consulter cette checklist
4. Vérifier les permissions des fichiers

**Compte admin par défaut :**
- Email : `admin@memoriaeventia.com`
- Mot de passe : `@S76XVzqeAhFmEe`
- ⚠️ **CHANGER CE MOT DE PASSE** après la première connexion !

---

**Dernière mise à jour:** 21 Mai 2026  
**Créé par:** AI Assistant  
**Version:** 1.0.0
