# 🚀 ACTIONS À FAIRE - SendGrid Integration

Voici les **actions que tu dois faire manuellement** pour que SendGrid fonctionne.

---

## ⚡ ACTIONS OBLIGATOIRES (5 minutes)

### 1️⃣ Installer les dépendances PHP

```bash
cd d:\wamp\www\TfeHistoire\BackEnd
composer install
```

**Résultat attendu :**

- La bibliothèque SendGrid est téléchargée
- Les erreurs dans `EmailService.php` disparaissent

---

### 2️⃣ Créer un compte SendGrid (GRATUIT)

🔗 https://signup.sendgrid.com/

- Plan gratuit : **100 emails/jour**
- Pas de carte bancaire requise

---

### 3️⃣ Obtenir ta clé API SendGrid

1. Se connecter : https://app.sendgrid.com/
2. **Settings** → **API Keys** (menu gauche)
3. **Create API Key**
4. Nom : `MemoriaEventia`
5. Permissions : **Full Access** (ou minimum **Mail Send**)
6. **Copier la clé** (format : `SG.xxxx.xxxx`)

⚠️ **La clé ne sera affichée qu'une seule fois !**

---

### 4️⃣ Vérifier ton email d'expédition

1. **Settings** → **Sender Authentication**
2. **Get Started** dans "Single Sender Verification"
3. Remplir :
   - From Name : `MemoriaEventia`
   - From Email : `noreply@memoriaeventia.com` (ou ton email perso)
   - Reply To : Ton email de support
4. **Créer**
5. **Vérifier l'email reçu** (cliquer sur le lien)

⚠️ **Tu dois avoir accès à cet email pour le vérifier !**

---

### 5️⃣ Configurer le fichier `.env`

Ouvrir `BackEnd/.env` et modifier :

```bash
# Coller ta clé API ici
SENDGRID_API_KEY=SG.ta_cle_api_ici

# Email vérifié dans SendGrid
SENDGRID_FROM_EMAIL=noreply@memoriaeventia.com

# Nom de l'expéditeur
SENDGRID_FROM_NAME=MemoriaEventia

# Activer l'envoi d'emails (true = réel, false = simulation)
SENDGRID_ENABLED=true
```

---

### 6️⃣ Tester l'envoi d'emails

1. Démarrer WAMP
2. Ouvrir l'application : `http://localhost/tfeHistoire/`
3. Se connecter
4. **Réserver un événement**
5. **Vérifier ton email** → Tu devrais recevoir un email de confirmation ✉️

---

## 🧪 MODE DÉVELOPPEMENT (optionnel)

Pour **tester sans envoyer de vrais emails** :

```bash
# Dans .env
SENDGRID_ENABLED=false
```

**Résultat :**

- Les emails sont simulés
- Actions loggées dans `BackEnd/logs/`
- Pas d'envoi réel
- Pas de consommation du quota

---

## 📚 DOCUMENTATION COMPLÈTE

Si tu veux plus de détails :

- **[SENDGRID_CONFIGURATION.md](SENDGRID_CONFIGURATION.md)** - Guide complet d'installation
- **[SENDGRID_INTEGRATION_SUMMARY.md](SENDGRID_INTEGRATION_SUMMARY.md)** - Résumé des modifications
- **[EMAILS_ARCHITECTURE.md](EMAILS_ARCHITECTURE.md)** - Documentation technique
- **[ERREURS_SENDGRID_AVANT_COMPOSER.md](ERREURS_SENDGRID_AVANT_COMPOSER.md)** - Pourquoi tu vois des erreurs

---

## ⚠️ ERREURS ACTUELLES (NORMAL)

Tu vois des erreurs dans `EmailService.php` :

```
Undefined type 'SendGrid'
```

✅ **C'est normal !** Elles disparaîtront après `composer install`.

---

## ✅ CHECKLIST

- [ ] `composer install` exécuté
- [ ] Compte SendGrid créé
- [ ] Clé API obtenue
- [ ] Email expéditeur vérifié
- [ ] `.env` configuré avec la clé API
- [ ] Test de réservation effectué
- [ ] Email de confirmation reçu

---

**Temps estimé : 5-10 minutes**

🎉 **C'est tout !** SendGrid est maintenant intégré.
