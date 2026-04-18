# 🚀 INTÉGRATION STRIPE - GUIDE DE TEST

## ✅ CONFIGURATION TERMINÉE !

Vos clés API Stripe de test sont maintenant configurées et prêtes à l'emploi.

---

## 📋 ÉTAPE 1 : CRÉER LA TABLE `payments`

**Exécuter le script SQL :**

1. Ouvre phpMyAdmin : `http://localhost/phpmyadmin`
2. Sélectionne la base de données `eurofetes_db`
3. Va dans l'onglet **SQL**
4. Copie-colle le contenu du fichier `BackEnd/database_stripe.sql`
5. Clique sur **Exécuter**

✅ La table `payments` sera créée avec tous les champs nécessaires.

---

## 🧪 ÉTAPE 2 : TESTER LE PAIEMENT

### 1️⃣ Créer une commande

- Va sur l'application frontend
- Choisis un événement PAYANT
- Ajoute des billets au panier
- Crée une commande

### 2️⃣ Accéder au checkout

- Après la création de commande, clique sur **"Procéder au paiement"**
- Tu seras redirigé vers `/checkout`

### 3️⃣ Payer avec Stripe

- Clique sur **"Procéder au paiement"**
- Tu seras redirigé vers **Stripe Checkout** (page sécurisée Stripe)
- Utilise une **carte de test** :

**CARTES DE TEST STRIPE :**

| Numéro de carte       | Résultat                    |
| --------------------- | --------------------------- |
| `4242 4242 4242 4242` | ✅ Paiement réussi          |
| `4000 0000 0000 0002` | ❌ Carte déclinée           |
| `4000 0000 0000 9995` | ❌ Fonds insuffisants       |
| `4000 0025 0000 3155` | 🔐 Authentification requise |

**Date d'expiration :** N'importe quelle date **future** (ex: 12/28)
**CVC :** N'importe quel **3 chiffres** (ex: 123)
**Code postal :** N'importe quel **code valide** (ex: 1000)

### 4️⃣ Validation

- Après paiement réussi → Redirection vers `/payment/success` ✅
- Si annulation → Redirection vers `/payment/cancel` ⚠️

---

## 📊 ÉTAPE 3 : VÉRIFIER DANS STRIPE DASHBOARD

1. **Connecte-toi** : https://dashboard.stripe.com/test/payments
2. **Voir les paiements** : Tu verras tous les paiements de test
3. **Voir les détails** : Clique sur un paiement pour voir les infos complètes

---

## 🔔 WEBHOOKS (OPTIONNEL pour l'instant)

**Actuellement :** Les webhooks ne sont PAS configurés (normal en local).

**Conséquence :**

- ✅ Le paiement fonctionne
- ✅ Stripe enregistre la transaction
- ❌ La base de données NE SE MET PAS À JOUR automatiquement

**Pour activer les webhooks plus tard :**

### Option A : Stripe CLI (Recommandé)

```bash
# 1. Installer Stripe CLI : https://stripe.com/docs/stripe-cli
# 2. Se connecter
stripe login

# 3. Écouter les webhooks
stripe listen --forward-to http://localhost/tfeHistoire/BackEnd/Api/webhookStripeApi.php

# 4. Tu recevras un webhook secret (whsec_...)
# Copie-le dans config.php à la ligne 'webhook_secret'
```

### Option B : ngrok

```bash
# 1. Télécharger : https://ngrok.com/download
# 2. Lancer
ngrok http 80

# 3. Tu obtiendras une URL comme : https://abc123.ngrok.io
# 4. Dans Stripe Dashboard (https://dashboard.stripe.com/test/webhooks)
#    - Créer un endpoint
#    - URL : https://abc123.ngrok.io/tfeHistoire/BackEnd/Api/webhookStripeApi.php
#    - Événements : checkout.session.completed, payment_intent.succeeded,
#                   payment_intent.payment_failed, charge.refunded
#    - Copier le webhook secret dans config.php
```

---

## 🧾 FACTURES PDF

Les factures seront générées automatiquement dans :
`BackEnd/storage/tickets/`

**Format :** `invoice_000123_1234567890.pdf`

---

## 💰 REMBOURSEMENTS

Pour tester un remboursement (admin/organisateur) :

1. Va dans Stripe Dashboard : https://dashboard.stripe.com/test/payments
2. Clique sur un paiement
3. Clique sur **"Refund payment"**
4. Confirme

✅ Le webhook mettra à jour la commande automatiquement (si configuré)

---

## 📝 CHECKLIST DE TEST

- [ ] Table `payments` créée dans la BDD
- [ ] Créer une commande avec billets payants
- [ ] Accéder à `/checkout`
- [ ] Cliquer sur "Procéder au paiement"
- [ ] Redirection vers Stripe Checkout
- [ ] Payer avec `4242 4242 4242 4242`
- [ ] Redirection vers `/payment/success`
- [ ] Vérifier le paiement dans Stripe Dashboard

---

## 🆘 PROBLÈMES COURANTS

### Erreur "Stripe API key not set"

➡️ Vérifie que `config.php` contient bien tes clés

### Erreur "Order not found"

➡️ Vérifie que tu as créé une commande avant d'aller au checkout

### Redirection vers Stripe ne fonctionne pas

➡️ Vérifie la console JavaScript (F12) pour voir les erreurs

### Webhook ne fonctionne pas

➡️ C'est normal en local ! Utilise Stripe CLI ou ngrok

---

## 📞 AIDE

Si tu as un problème :

1. Vérifie la console JavaScript (F12)
2. Vérifie les logs PHP dans `BackEnd/logs/`
3. Vérifie Stripe Dashboard pour voir si le paiement est créé

---

## 🎉 FÉLICITATIONS !

Tu peux maintenant accepter des paiements avec Stripe ! 💳✨
