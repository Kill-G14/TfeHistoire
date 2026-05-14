# 💳 STRIPE - Guide Complet

## 📋 Vue d'ensemble

MemoriaEventia utilise **Stripe Connect** pour permettre aux créateurs d'événements de recevoir les paiements directement sur leur compte bancaire.

---

## 🚀 Fonctionnalités implémentées

### ✅ Backend

- **StripeConnectService.php** - Gestion des comptes Stripe Connect
- **stripeConnectApi.php** - API endpoints pour le frontend
- **UserRepository.php** - Méthodes Stripe ajoutées (3 nouvelles)
- **database.sql** - Tables Stripe intégrées (users, events, creator_earnings, stripe_connect_log)
- **config.php** - URLs de retour configurées

### ✅ Frontend

- **StripeConnectManager.js** - Manager API Stripe
- **createEvent.js** - Vérification Stripe + modal d'onboarding
- **profile.js** - Affichage statut Stripe + gestion compte
- **profile.html** - Section Stripe Connect

### ✅ Base de données

#### Table `users` - Colonnes Stripe ajoutées

```sql
stripe_account_id VARCHAR(255) NULL
stripe_account_status ENUM('not_connected', 'pending', 'connected', 'rejected')
stripe_onboarding_completed BOOLEAN DEFAULT FALSE
stripe_connected_at DATETIME NULL
```

#### Table `creator_earnings`

Suivi des gains des créateurs :

```sql
CREATE TABLE creator_earnings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    stripe_account_id VARCHAR(255) NOT NULL,
    gross_amount DECIMAL(10, 2) NOT NULL,
    platform_fee DECIMAL(10, 2) NOT NULL,
    stripe_fee DECIMAL(10, 2) NOT NULL,
    net_amount DECIMAL(10, 2) NOT NULL,
    transfer_id VARCHAR(255) NULL,
    transfer_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    transferred_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Table `stripe_connect_log`

Historique des connexions Stripe :

```sql
CREATE TABLE stripe_connect_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    stripe_account_id VARCHAR(255) NULL,
    status VARCHAR(50) NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 📊 Flux utilisateur

### Événement GRATUIT

```
1. Créateur coche "Événement gratuit"
2. Formulaire envoyé
3. Admin valide
4. Événement publié
✅ Pas besoin de Stripe
```

### Événement PAYANT

```
1. Créateur entre prix + quantité tickets
2. Formulaire validé côté client
3. ⭐ Vérification compte Stripe
   - SI compte connecté → Envoi formulaire
   - SINON → Modal "Connecter Stripe" → Stop
4. Créateur connecte son compte Stripe
5. Retour sur le site
6. Événement créé et envoyé en validation
7. Admin valide
8. Événement publié
9. Visiteurs achètent billets
10. 💰 Paiements reçus automatiquement
```

---

## 🧪 Tests et Données de test

### IBAN de test (onboarding Stripe)

```
France : FR14 2004 1010 0505 0001 3M02 606
Belgique : BE68539007547034
Allemagne : DE89370400440532013000
```

### Cartes bancaires de test

#### Succès

```
Numéro : 4242 4242 4242 4242
Date : N'importe quelle date future
CVC : N'importe quel 3 chiffres
```

#### Échec

```
Carte déclinée : 4000 0000 0000 0002
Fonds insuffisants : 4000 0000 0000 9995
```

#### 3D Secure (authentification)

```
Succès : 4000 0025 0000 3155
Échec : 4000 0082 6000 0000
```

---

## 🎯 Scénarios de test

### Test 1 : Créer événement GRATUIT

1. Se connecter
2. Aller sur "Créer un événement"
3. Cocher "Événement gratuit"
4. Remplir le formulaire
5. ✅ **Résultat attendu** : Formulaire envoyé normalement (pas de vérification Stripe)

### Test 2 : Créer événement PAYANT sans Stripe

1. Se connecter
2. Aller sur "Créer un événement"
3. NE PAS cocher "Événement gratuit"
4. Entrer prix (ex: 25€)
5. Cliquer "Créer l'événement"
6. ✅ **Résultat attendu** : Modal "Connecter votre compte Stripe"
7. Cliquer "Connecter mon compte Stripe"
8. ✅ **Résultat attendu** : Redirection vers stripe.com

### Test 3 : Onboarding Stripe (mode test)

1. Sur stripe.com, remplir le formulaire
2. Utiliser un IBAN de test (voir ci-dessus)
3. Entrer n'importe quelles infos (mode test)
4. Finaliser
5. ✅ **Résultat attendu** : Retour automatique sur page profil
6. ✅ **Résultat attendu** : Message "Compte Stripe connecté avec succès ! 🎉"

### Test 4 : Créer événement PAYANT avec Stripe

1. Maintenant que Stripe est connecté
2. Aller sur "Créer un événement"
3. NE PAS cocher gratuit, mettre un prix
4. Cliquer "Créer l'événement"
5. ✅ **Résultat attendu** : Formulaire envoyé directement (pas de modal)

### Test 5 : Page Profil - Section Stripe

1. Aller sur page Profil
2. ✅ **Résultat attendu** : Badge vert "Compte Stripe connecté"
3. Cliquer "Gérer mon compte"
4. ✅ **Résultat attendu** : Ouverture du dashboard Stripe (nouvel onglet)

### Test 6 : Achat de billet

1. Visiteur sélectionne un événement payant
2. Clique "Réserver"
3. Remplit formulaire de réservation
4. Clique "Payer"
5. ✅ **Résultat attendu** : Redirection vers Stripe Checkout
6. Entre carte de test : `4242 4242 4242 4242`
7. Valide le paiement
8. ✅ **Résultat attendu** : Retour sur page de succès
9. ✅ **Résultat attendu** : Billet généré avec QR code

---

## 🔧 Configuration

### Fichier config.php

```php
'stripe' => [
    'secret_key' => getenv('STRIPE_SECRET_KEY'),
    'publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY'),
    'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET'),
    'refresh_url' => 'http://localhost/tfeHistoire/#/profile?stripe=refresh',
    'return_url' => 'http://localhost/tfeHistoire/#/profile?stripe=success',
],
```

### Fichier .env (à créer)

```env
# Stripe (MODE TEST)
STRIPE_SECRET_KEY=sk_test_VOTRE_CLE_SECRETE
STRIPE_PUBLISHABLE_KEY=pk_test_VOTRE_CLE_PUBLIQUE
STRIPE_WEBHOOK_SECRET=whsec_VOTRE_SECRET_WEBHOOK
```

⚠️ **Important** : Le fichier `.env` ne doit JAMAIS être commité sur Git !

---

## 🌐 Webhooks Stripe (optionnel pour développement)

Les webhooks servent à :

- Mettre à jour automatiquement le statut de la commande
- Envoyer la facture par email
- Générer les billets automatiquement

### Sans webhooks (développement)

Vous pouvez tester sans configurer les webhooks :

1. Le paiement fonctionne
2. Le statut de la commande sera mis à jour manuellement
3. Les billets peuvent être générés après validation

### Avec webhooks (production)

#### Option 1 : Stripe CLI (recommandé pour développement)

```bash
# Installer Stripe CLI
# https://stripe.com/docs/stripe-cli

# Écouter les événements
stripe listen --forward-to http://localhost/tfeHistoire/BackEnd/Api/webhookStripeApi.php

# Tester un événement
stripe trigger payment_intent.succeeded
```

#### Option 2 : ngrok (tunnel public)

```bash
# Installer ngrok
# https://ngrok.com/

# Créer un tunnel
ngrok http 80

# Configurer dans Stripe Dashboard
# URL : https://VOTRE-URL.ngrok.io/tfeHistoire/BackEnd/Api/webhookStripeApi.php
```

#### Option 3 : Serveur de production

Déployer l'application sur un serveur accessible publiquement et configurer l'URL du webhook dans le dashboard Stripe.

### Événements Stripe écoutés

```
checkout.session.completed
payment_intent.succeeded
payment_intent.payment_failed
account.updated (Stripe Connect)
```

---

## 📡 API Endpoints

### POST `/BackEnd/Api/stripeConnectApi.php`

#### Action : `checkStripeAccount`

Vérifier le statut du compte Stripe de l'utilisateur.

**Request:**

```json
{
  "action": "checkStripeAccount"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "has_stripe_account": true,
    "stripe_account_id": "acct_xxx",
    "stripe_account_status": "connected",
    "stripe_onboarding_completed": true,
    "stripe_connected_at": "2026-05-10 14:32:00"
  }
}
```

#### Action : `createConnectAccount`

Créer un compte Stripe Connect et obtenir l'URL d'onboarding.

**Request:**

```json
{
  "action": "createConnectAccount"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Compte Stripe créé avec succès",
  "data": {
    "onboarding_url": "https://connect.stripe.com/setup/xxx"
  }
}
```

#### Action : `verifyAccountCompletion`

Vérifier si l'onboarding Stripe est terminé.

**Request:**

```json
{
  "action": "verifyAccountCompletion"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "is_complete": true
  }
}
```

#### Action : `getDashboardLink`

Obtenir le lien vers le dashboard Stripe Express.

**Request:**

```json
{
  "action": "getDashboardLink"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "dashboard_url": "https://connect.stripe.com/express/xxx"
  }
}
```

---

## 💰 Frais et Commissions

### Structure tarifaire

- **Stripe** : 2,9% + 0,25€ par transaction (standard EU)
- **Plateforme** : À définir (ex: 5% du montant brut)
- **Créateur** : Montant restant après frais

### Exemple de calcul

```
Prix du billet : 50,00€
Frais Stripe : 1,70€ (2,9% + 0,25€)
Montant brut : 48,30€
Commission plateforme (5%) : 2,42€
Montant net créateur : 45,88€
```

Ces calculs sont gérés automatiquement par le système.

---

## 🚨 Troubleshooting

### Erreur "Token non fourni"

→ Vérifier que vous êtes connecté (localStorage contient un token)

### Modal ne s'affiche pas

→ Ouvrir la console (F12) et chercher les erreurs  
→ Vérifier que `StripeConnectManager.js` est bien chargé

### Redirection Stripe ne fonctionne pas

→ Vérifier les URLs dans `config.php`  
→ Doit être : `http://localhost/tfeHistoire/#/profile?stripe=success`

### Badge ne s'affiche pas dans le profil

→ Ouvrir la console et vérifier les erreurs  
→ Tester l'API : `POST http://localhost/tfeHistoire/BackEnd/Api/stripeConnectApi.php`

### Paiement échoue en production

→ Vérifier que les clés API sont en mode production (pas `_test_`)  
→ Vérifier que le webhook est correctement configuré  
→ Vérifier les logs Stripe dans le dashboard

---

## 📚 Ressources Stripe

- **Dashboard** : https://dashboard.stripe.com
- **Documentation Stripe Connect** : https://stripe.com/docs/connect
- **Cartes de test** : https://stripe.com/docs/testing
- **Webhooks** : https://stripe.com/docs/webhooks
- **API Reference** : https://stripe.com/docs/api

---

## ✅ Checklist mise en production

- [ ] Créer compte Stripe en mode production
- [ ] Activer Stripe Connect dans les paramètres
- [ ] Remplacer les clés test par les clés production dans `.env`
- [ ] Configurer l'URL du webhook en production
- [ ] Tester le flux complet en mode production
- [ ] Vérifier que les transferts fonctionnent
- [ ] Configurer les emails de notification
- [ ] Activer le monitoring des paiements

---

✅ **Stripe Connect est opérationnel !** 🎉
