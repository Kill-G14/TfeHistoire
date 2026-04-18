# GUIDE: TESTER STRIPE SANS WEBHOOK

## Pour tester Stripe SANS configurer les webhooks :

### 1. Utiliser cette URL temporaire pour les tests

Au lieu de configurer un webhook Stripe, tu peux :

- Créer une commande
- Cliquer sur "Payer"
- Être redirigé vers Stripe Checkout
- Payer avec une carte de test : `4242 4242 4242 4242` (n'importe quelle date future, n'importe quel CVC)
- Être redirigé vers la page de succès

### 2. Cartes de test Stripe

**Succès :**

- `4242 4242 4242 4242` - Paiement réussi

**Échec :**

- `4000 0000 0000 0002` - Carte déclinée
- `4000 0000 0000 9995` - Fonds insuffisants

**3D Secure (authentification) :**

- `4000 0025 0000 3155` - Authentification requise (succès)
- `4000 0082 6000 0000` - Authentification requise (échec)

**Date d'expiration :** N'importe quelle date FUTURE
**CVC :** N'importe quel 3 chiffres
**Code postal :** N'importe quel code valide

## Les webhooks serviront à :

- Mettre à jour automatiquement le statut de la commande
- Envoyer la facture par email
- Générer les billets automatiquement

## POUR ACTIVER LES WEBHOOKS PLUS TARD :

### Option A : Stripe CLI (recommandé)

```bash
stripe listen --forward-to http://localhost/tfeHistoire/BackEnd/Api/webhookStripeApi.php
```

### Option B : ngrok

```bash
ngrok http 80
# Puis configurer : https://VOTRE-URL.ngrok.io/tfeHistoire/BackEnd/Api/webhookStripeApi.php
```

### Option C : Déployer sur un serveur

Mettre l'application sur un serveur accessible depuis internet.
