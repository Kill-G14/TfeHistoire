# 📧 CONFIGURATION SENDGRID - Guide d'installation

Ce guide explique comment configurer SendGrid pour l'envoi d'emails dans MemoriaEventia.

---

## 🎯 Pourquoi SendGrid ?

SendGrid est un service professionnel d'envoi d'emails qui offre :

- ✅ **Délivrabilité élevée** : Moins de risques d'emails marqués comme spam
- ✅ **Statistiques** : Suivi des emails envoyés, ouverts, cliqués
- ✅ **Gratuit** : 100 emails/jour gratuitement (suffisant pour débuter)
- ✅ **Fiable** : Infrastructure professionnelle

---

## 📋 Étapes d'installation

### 1. Créer un compte SendGrid

1. Aller sur : https://signup.sendgrid.com/
2. Créer un compte gratuit (Free tier : 100 emails/jour)
3. Vérifier votre email

---

### 2. Obtenir une clé API SendGrid

1. Se connecter sur : https://app.sendgrid.com/
2. Aller dans **Settings** → **API Keys** (menu gauche)
3. Cliquer sur **Create API Key**
4. Nommer la clé : `MemoriaEventia Production` (ou `Development`)
5. **Permissions** : Sélectionner **Full Access** (ou minimum **Mail Send**)
6. Cliquer sur **Create & View**
7. **COPIER LA CLÉ** immédiatement (elle ne sera plus affichée !)

**Format de la clé :**

```
SG.xxxxxxxxxxxxxxxxxxx.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

### 3. Vérifier un email d'expédition (Sender Authentication)

SendGrid exige que vous **vérifiiez** l'email depuis lequel vous envoyez.

#### Option A : Single Sender Verification (Rapide)

1. Aller dans **Settings** → **Sender Authentication**
2. Cliquer sur **Get Started** dans "Single Sender Verification"
3. Remplir le formulaire :
   - **From Name** : `MemoriaEventia`
   - **From Email Address** : `noreply@memoriaeventia.com` (ou votre email réel)
   - **Reply To** : Votre email de support
   - **Company Address** : Votre adresse
4. Cliquer sur **Create**
5. **Vérifier votre email** : Vous recevrez un email de confirmation, cliquez sur le lien

⚠️ **Important** : Vous devez avoir accès à cet email pour le vérifier !

#### Option B : Domain Authentication (Recommandé pour production)

Pour utiliser un domaine personnalisé (`@memoriaeventia.com`) :

1. Aller dans **Settings** → **Sender Authentication**
2. Cliquer sur **Authenticate Your Domain**
3. Suivre les instructions pour ajouter des enregistrements DNS

⚠️ **Nécessite un nom de domaine** et l'accès aux paramètres DNS.

---

### 4. Configurer le fichier `.env`

Ouvrir `BackEnd/.env` et modifier :

```bash
# SENDGRID - ENVOI D'EMAILS
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxxx.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
SENDGRID_FROM_EMAIL=noreply@memoriaeventia.com
SENDGRID_FROM_NAME=MemoriaEventia
SENDGRID_ENABLED=true
```

**Explications :**

- `SENDGRID_API_KEY` : La clé API copiée depuis SendGrid
- `SENDGRID_FROM_EMAIL` : L'email vérifié dans SendGrid
- `SENDGRID_FROM_NAME` : Le nom affiché comme expéditeur
- `SENDGRID_ENABLED` :
  - `true` : Emails réellement envoyés
  - `false` : Mode simulation (logs uniquement, pas d'envoi)

---

### 5. Installer les dépendances PHP

Ouvrir un terminal dans `BackEnd/` et exécuter :

```bash
composer install
```

Cela va installer la bibliothèque SendGrid PHP (`sendgrid/sendgrid`).

---

### 6. Tester l'envoi d'emails

Pour tester, il suffit de créer une réservation via l'application :

1. Se connecter à l'application frontend
2. Réserver un événement
3. Vérifier que vous recevez un email de confirmation

**Logs** : Les emails envoyés sont loggés dans `BackEnd/logs/`

---

## 🧪 Mode développement vs Production

### En développement (local)

```bash
SENDGRID_ENABLED=false
```

- Les emails ne sont **pas réellement envoyés**
- Les actions sont loggées dans les logs
- Pas de consommation du quota SendGrid

### En production

```bash
SENDGRID_ENABLED=true
```

- Les emails sont **réellement envoyés**
- Vérifie bien d'avoir configuré la clé API et l'email vérifié

---

## 📊 Quotas SendGrid

### Plan gratuit (Free tier)

- **100 emails/jour**
- Suffisant pour tester et démarrer

### Plans payants

- **Essentials** : 40 000 emails/mois à partir de $15/mois
- **Pro** : 100 000 emails/mois à partir de $60/mois

---

## 📧 Types d'emails envoyés

Le système envoie automatiquement des emails pour :

1. **Réservation confirmée** → Utilisateur
2. **Réservation annulée** → Utilisateur
3. **Événement modifié** → Utilisateurs ayant réservé
4. **Événement annulé** → Utilisateurs ayant réservé
5. **Nouvelle demande de modification** → Administrateurs
6. **Nouvelle demande de suppression** → Administrateurs

---

## 🔍 Vérifier les emails envoyés

Sur le dashboard SendGrid :

1. Aller sur : https://app.sendgrid.com/
2. Cliquer sur **Activity**
3. Voir tous les emails envoyés, leur statut, et les statistiques

---

## ⚠️ Problèmes courants

### "Unauthorized" (401)

- Vérifier que la clé API est correcte dans `.env`
- Vérifier que les permissions de la clé incluent **Mail Send**

### "The from email does not contain a valid address" (400)

- L'email expéditeur n'est **pas vérifié** dans SendGrid
- Aller dans **Settings** → **Sender Authentication** et vérifier l'email

### Les emails n'arrivent pas

- Vérifier les **spams** du destinataire
- Vérifier le **Activity Feed** sur SendGrid pour voir le statut
- Vérifier que `SENDGRID_ENABLED=true` dans `.env`

### "Missing or invalid API key"

- La clé API n'est pas définie dans `.env`
- Vérifier que le fichier `.env` existe et est bien lu

---

## 🔐 Sécurité

### ⚠️ NE JAMAIS COMMITER LA CLÉ API

Le fichier `.env` est dans `.gitignore` par défaut.

**Vérifier** :

```bash
# Dans .gitignore
BackEnd/.env
```

### 🔄 Rotation des clés

Il est recommandé de :

- Créer une nouvelle clé API tous les 6 mois
- Supprimer l'ancienne clé après migration

---

## 📚 Documentation SendGrid

- Documentation officielle : https://docs.sendgrid.com/
- API PHP : https://github.com/sendgrid/sendgrid-php
- Tableau de bord : https://app.sendgrid.com/

---

## ✅ Checklist de configuration

- [ ] Créer un compte SendGrid
- [ ] Obtenir une clé API
- [ ] Vérifier un email d'expédition (Single Sender)
- [ ] Configurer le fichier `.env` avec la clé API
- [ ] Exécuter `composer install` dans `BackEnd/`
- [ ] Tester en créant une réservation
- [ ] Vérifier la réception de l'email
- [ ] Vérifier les logs dans `BackEnd/logs/`

---

**Date de création** : 20 Mai 2026
