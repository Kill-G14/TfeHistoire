# 🔗 IMPLÉMENTATION STRIPE CONNECT - GUIDE COMPLET

## 📋 FLUX UTILISATEUR

### Pour un événement GRATUIT

```
1. Utilisateur crée événement → Coche "Gratuit"
2. Formulaire envoyé
3. Admin valide
4. Événement publié
✅ PAS BESOIN DE STRIPE
```

### Pour un événement PAYANT

```
1. Utilisateur crée événement → Entre prix + quantité
2. Formulaire validé côté client
3. ⭐ NOUVEAU : Vérification compte Stripe
   - SI stripe_account_id existe ET stripe_onboarding_completed = true
     → Formulaire envoyé normalement
   - SINON
     → Modale : "Connectez votre compte Stripe pour recevoir les paiements"
     → Bouton "Connecter mon compte Stripe"
4. Admin valide l'événement
5. Événement publié
6. Utilisateurs achètent des billets
7. 💰 Argent transféré automatiquement au créateur (avec commission)
```

---

## 🗂️ ÉTAPE 1 : BASE DE DONNÉES

### ✅ Tables déjà intégrées dans database.sql

Les tables Stripe Connect sont **déjà incluses** dans `database.sql` :

- Colonnes Stripe dans `users`
- Colonnes Stripe dans `events`
- Table `creator_earnings`
- Table `stripe_connect_log`

**Si vous recréez votre base de données** :

```bash
# Dans phpMyAdmin ou ligne de commande
mysql -u root -p < database.sql
# Tout sera créé automatiquement, y compris Stripe Connect
```

**Si votre base existe déjà**, vous devez ajouter manuellement les colonnes :

```sql
-- Ajouter dans la table users
ALTER TABLE users
ADD COLUMN stripe_account_id VARCHAR(255) NULL,
ADD COLUMN stripe_account_status ENUM('not_connected', 'pending', 'connected', 'rejected') DEFAULT 'not_connected',
ADD COLUMN stripe_onboarding_completed BOOLEAN DEFAULT FALSE,
ADD COLUMN stripe_connected_at DATETIME NULL;

-- Ajouter dans la table events
ALTER TABLE events
ADD COLUMN requires_stripe_account BOOLEAN DEFAULT FALSE,
ADD COLUMN stripe_account_verified BOOLEAN DEFAULT FALSE;

-- Créer les nouvelles tables (voir database.sql pour le code complet)
```

### Nouvelles colonnes dans `users`

- `stripe_account_id` : ID du compte Stripe Connect
- `stripe_account_status` : not_connected | pending | connected | rejected
- `stripe_onboarding_completed` : true/false
- `stripe_connected_at` : Date de connexion

### Nouvelle table `creator_earnings`

- Suivi de tous les gains des créateurs
- Montants brut/net/commission/frais
- Statut du transfert

---

## 🎨 ÉTAPE 2 : FRONTEND - Modifications

### 2.1 - Vue de création d'événement (`views/createEvent.js`)

**AJOUT : Vérification Stripe avant soumission**

```javascript
// À AJOUTER dans la fonction handleSubmit (AVANT l'envoi au backend)

async function handleSubmit(e) {
  e.preventDefault();

  // ... validation existante ...

  // ⭐ NOUVEAU : Si événement payant, vérifier Stripe
  if (!formData.is_free) {
    const hasStripe = await checkUserStripeAccount();

    if (!hasStripe) {
      // Afficher modale pour connecter Stripe
      showStripeConnectModal();
      return; // Arrêter la soumission
    }
  }

  // ... envoi normal au backend ...
}

// Vérifier si l'utilisateur a un compte Stripe
async function checkUserStripeAccount() {
  const result = await AuthManager.checkStripeAccount();
  return result.success && result.data.has_stripe_account;
}

// Afficher modale de connexion Stripe
function showStripeConnectModal() {
  const modal = `
        <div class="modal fade show d-block" id="stripeConnectModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Connecter votre compte Stripe</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <i class="bi bi-credit-card text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h6>Pourquoi connecter Stripe ?</h6>
                        <p>Pour créer un événement payant, vous devez connecter votre compte Stripe afin de recevoir les paiements directement.</p>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Sécurisé et rapide</strong><br>
                            Le processus prend environ 2 minutes.
                        </div>
                        
                        <ul class="list-unstyled">
                            <li>✅ Recevez vos paiements automatiquement</li>
                            <li>✅ Gestion sécurisée par Stripe</li>
                            <li>✅ Frais de 2,9% + 0,25€ par transaction</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Annuler
                        </button>
                        <button type="button" class="btn btn-primary" id="btnConnectStripe">
                            <i class="bi bi-stripe"></i> Connecter mon compte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    `;

  document.body.insertAdjacentHTML("beforeend", modal);

  // Event listener pour le bouton
  document
    .getElementById("btnConnectStripe")
    .addEventListener("click", initiateStripeConnect);
}

// Lancer le processus Stripe Connect
async function initiateStripeConnect() {
  try {
    const result = await AuthManager.createStripeConnectAccount();

    if (result.success && result.data.onboarding_url) {
      // Rediriger vers Stripe pour finaliser
      window.location.href = result.data.onboarding_url;
    } else {
      helpers.showToast(
        result.message || "Erreur lors de la connexion",
        "error",
      );
    }
  } catch (error) {
    helpers.showToast("Erreur de connexion à Stripe", "error");
  }
}
```

### 2.2 - Vue Profil (`views/profile.js`)

**AJOUT : Section Stripe Connect dans le profil**

```javascript
// À AJOUTER dans le template HTML du profil

const stripeSection =
  user.stripe_account_status === "connected"
    ? `
    <div class="card mt-4">
        <div class="card-header bg-success text-white">
            <i class="bi bi-check-circle"></i> Compte Stripe connecté
        </div>
        <div class="card-body">
            <p class="mb-2">
                <strong>Statut :</strong> 
                <span class="badge bg-success">Connecté</span>
            </p>
            <p class="mb-2">
                <strong>Connecté le :</strong> ${helpers.formatDate(user.stripe_connected_at)}
            </p>
            <p class="text-muted small">
                Vous pouvez maintenant créer des événements payants et recevoir les paiements directement.
            </p>
            <button class="btn btn-sm btn-outline-secondary" id="btnManageStripe">
                <i class="bi bi-gear"></i> Gérer mon compte Stripe
            </button>
        </div>
    </div>
`
    : `
    <div class="card mt-4">
        <div class="card-header">
            <i class="bi bi-stripe"></i> Compte Stripe
        </div>
        <div class="card-body">
            <p>Connectez votre compte Stripe pour créer des événements payants et recevoir les paiements.</p>
            <button class="btn btn-primary" id="btnConnectStripeProfile">
                <i class="bi bi-plus-circle"></i> Connecter mon compte Stripe
            </button>
        </div>
    </div>
`;

// Ajouter dans le HTML du profil
```

---

## ⚙️ ÉTAPE 3 : BACKEND - Nouvelles APIs

### 3.1 - Créer `StripeConnectService.php`

```php
<?php
// BackEnd/Src/Services/StripeConnectService.php

namespace App\Services;

use App\Repositories\UserRepository;

class StripeConnectService {
    private UserRepository $userRepository;
    private string $secretKey;
    private string $clientId;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;

        $config = require __DIR__ . '/../../config.php';
        $this->secretKey = $config['stripe']['secret_key'];
        $this->clientId = $config['stripe']['connect_client_id'] ?? '';

        \Stripe\Stripe::setApiKey($this->secretKey);
    }

    /**
     * Créer un compte Stripe Connect pour un utilisateur
     */
    public function createConnectAccount(int $userId, string $email): array {
        try {
            // Créer un compte Stripe Express (recommandé pour marketplaces)
            $account = \Stripe\Account::create([
                'type' => 'express',
                'email' => $email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);

            // Créer le lien d'onboarding
            $accountLink = \Stripe\AccountLink::create([
                'account' => $account->id,
                'refresh_url' => 'http://localhost/tfeHistoire/#/profile?stripe=refresh',
                'return_url' => 'http://localhost/tfeHistoire/#/profile?stripe=success',
                'type' => 'account_onboarding',
            ]);

            // Enregistrer dans la BDD
            $this->userRepository->updateStripeAccount(
                $userId,
                $account->id,
                'pending',
                false
            );

            return [
                'success' => true,
                'message' => 'Compte Stripe créé',
                'data' => [
                    'account_id' => $account->id,
                    'onboarding_url' => $accountLink->url
                ]
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Erreur Stripe : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier si le compte Stripe est complet
     */
    public function checkAccountStatus(string $stripeAccountId): array {
        try {
            $account = \Stripe\Account::retrieve($stripeAccountId);

            $isComplete = $account->charges_enabled && $account->payouts_enabled;

            return [
                'success' => true,
                'data' => [
                    'is_complete' => $isComplete,
                    'charges_enabled' => $account->charges_enabled,
                    'payouts_enabled' => $account->payouts_enabled,
                ]
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification : ' . $e->getMessage()
            ];
        }
    }
}
```

### 3.2 - Ajouter méthodes dans `UserRepository.php`

```php
// AJOUTER dans BackEnd/Src/Repositories/UserRepository.php

/**
 * Mettre à jour les informations Stripe Connect d'un utilisateur
 */
public function updateStripeAccount(
    int $userId,
    string $stripeAccountId,
    string $status,
    bool $onboardingCompleted
): bool {
    $query = "UPDATE users
              SET stripe_account_id = :stripe_account_id,
                  stripe_account_status = :status,
                  stripe_onboarding_completed = :onboarding_completed,
                  stripe_connected_at = IF(:onboarding_completed = 1, NOW(), stripe_connected_at)
              WHERE id = :id";

    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
    $stmt->bindParam(':stripe_account_id', $stripeAccountId);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':onboarding_completed', $onboardingCompleted, \PDO::PARAM_BOOL);

    return $stmt->execute();
}

/**
 * Récupérer le statut Stripe d'un utilisateur
 */
public function getStripeAccountStatus(int $userId): ?array {
    $query = "SELECT stripe_account_id, stripe_account_status,
              stripe_onboarding_completed, stripe_connected_at
              FROM users WHERE id = :id";

    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $result ?: null;
}
```

### 3.3 - Créer `stripeConnectApi.php`

```php
<?php
// BackEnd/Api/stripeConnectApi.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
use App\Validators\UserValidator;
use App\Services\AuthService;
use App\Services\SessionService;
use App\Services\StripeConnectService;

$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
$userValidator = new UserValidator();

$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$stripeConnectService = new StripeConnectService($userRepository);

$request = json_decode(file_get_contents("php://input"), true);

if (!$request || !isset($request['action'])) {
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

// Vérifier le token
if (!isset($request['token'])) {
    echo json_encode(['success' => false, 'message' => 'Token non fourni']);
    exit;
}

$userId = $authService->checkToken($request['token']);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Token invalide']);
    exit;
}

switch ($request['action']) {
    case 'checkStripeAccount':
        // Vérifier si l'utilisateur a un compte Stripe
        $stripeData = $userRepository->getStripeAccountStatus($userId);

        $hasAccount = $stripeData &&
                     !empty($stripeData['stripe_account_id']) &&
                     $stripeData['stripe_onboarding_completed'];

        $response = [
            'success' => true,
            'data' => [
                'has_stripe_account' => $hasAccount,
                'status' => $stripeData['stripe_account_status'] ?? 'not_connected',
                'stripe_account_id' => $stripeData['stripe_account_id'] ?? null
            ]
        ];
        break;

    case 'createConnectAccount':
        // Créer un compte Stripe Connect
        $user = $userRepository->getUserById($userId);

        if (!$user) {
            $response = ['success' => false, 'message' => 'Utilisateur introuvable'];
            break;
        }

        $response = $stripeConnectService->createConnectAccount($userId, $user->email);
        break;

    case 'verifyAccountCompletion':
        // Vérifier si l'onboarding est terminé
        $stripeData = $userRepository->getStripeAccountStatus($userId);

        if (!$stripeData || empty($stripeData['stripe_account_id'])) {
            $response = ['success' => false, 'message' => 'Aucun compte Stripe trouvé'];
            break;
        }

        $statusResult = $stripeConnectService->checkAccountStatus($stripeData['stripe_account_id']);

        if ($statusResult['success'] && $statusResult['data']['is_complete']) {
            // Mettre à jour le statut dans la BDD
            $userRepository->updateStripeAccount(
                $userId,
                $stripeData['stripe_account_id'],
                'connected',
                true
            );

            $response = [
                'success' => true,
                'message' => 'Compte Stripe vérifié et connecté',
                'data' => ['is_complete' => true]
            ];
        } else {
            $response = [
                'success' => true,
                'data' => ['is_complete' => false]
            ];
        }
        break;

    default:
        $response = ['success' => false, 'message' => 'Action non reconnue'];
        break;
}

echo json_encode($response);
```

---

## 📱 ÉTAPE 4 : FRONTEND - Manager Stripe Connect

### Créer `StripeConnectManager.js`

```javascript
// assets/js/managers/StripeConnectManager.js

class StripeConnectManager {
  constructor() {
    this.apiUrl =
      "http://localhost/tfeHistoire/BackEnd/Api/stripeConnectApi.php";
  }

  /**
   * Vérifier si l'utilisateur a un compte Stripe
   */
  async checkStripeAccount() {
    try {
      const token = localStorage.getItem("token");

      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "checkStripeAccount",
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Créer un compte Stripe Connect
   */
  async createStripeConnectAccount() {
    try {
      const token = localStorage.getItem("token");

      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "createConnectAccount",
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Vérifier si l'onboarding est complété
   */
  async verifyAccountCompletion() {
    try {
      const token = localStorage.getItem("token");

      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "verifyAccountCompletion",
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }
}

export default new StripeConnectManager();
```

---

## 🔧 ÉTAPE 5 : CONFIGURATION

### Ajouter dans `config.php`

```php
// AJOUTER dans BackEnd/config.php

'stripe' => [
    'secret_key' => 'sk_test_...',
    'publishable_key' => 'pk_test_...',
    'webhook_secret' => 'whsec_...',
    'currency' => 'eur',
    'success_url' => 'http://localhost/tfeHistoire/#/payment/success',
    'cancel_url' => 'http://localhost/tfeHistoire/#/payment/cancel',

    // ⭐ NOUVEAU pour Stripe Connect
    'platform_fee_percent' => 10, // Commission de 10%
],
```

---

## 🎯 RÉSUMÉ DU FLUX

```
┌─────────────────────────────────────────────────────────┐
│ 1. Utilisateur crée événement PAYANT                    │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ 2. Frontend vérifie: A-t-il un compte Stripe ?         │
│    → AuthManager.checkStripeAccount()                   │
└─────────────────────────────────────────────────────────┘
                         ↓
                    ┌────┴────┐
                    │   OUI   │
                    └────┬────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ 3. Formulaire envoyé normalement                        │
│    → EventManager.createEvent()                         │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ 4. Admin valide l'événement                             │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ 5. Utilisateurs achètent des billets                    │
│    → Paiement via Stripe                                │
│    → Argent transféré automatiquement au créateur       │
└─────────────────────────────────────────────────────────┘

                    ┌────┴────┐
                    │   NON   │
                    └────┬────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ Modale : "Connectez votre compte Stripe"                │
│ Bouton : "Connecter mon compte"                         │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ AuthManager.createStripeConnectAccount()                │
│ → Redirige vers Stripe                                  │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ Utilisateur complète le formulaire Stripe               │
│ (Coordonnées bancaires, vérifications...)               │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ Retour sur le site → Vérification automatique           │
│ → AuthManager.verifyAccountCompletion()                 │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ ✅ Compte Stripe connecté !                             │
│ L'utilisateur peut maintenant créer l'événement         │
└─────────────────────────────────────────────────────────┘
```

---

## ✅ CHECKLIST D'IMPLÉMENTATION

### Base de données

- [ ] Exécuter `add_stripe_connect.sql`
- [ ] Vérifier que les colonnes ont été ajoutées

### Backend

- [ ] Créer `StripeConnectService.php`
- [ ] Ajouter méthodes dans `UserRepository.php`
- [ ] Créer `stripeConnectApi.php`
- [ ] Mettre à jour `config.php`

### Frontend

- [ ] Créer `StripeConnectManager.js`
- [ ] Modifier `views/createEvent.js` (vérification + modale)
- [ ] Modifier `views/profile.js` (section Stripe)
- [ ] Ajouter gestion du retour Stripe (`?stripe=success`)

### Tests

- [ ] Créer un événement gratuit → Doit fonctionner normalement
- [ ] Créer un événement payant sans Stripe → Afficher modale
- [ ] Connecter compte Stripe → Vérifier redirection
- [ ] Créer événement payant avec Stripe → Doit fonctionner

---

## 🚀 PROCHAINES ÉTAPES

Une fois tout implémenté, tu devras :

1. **Modifier `StripeService.php`** pour utiliser le `stripe_account_id` lors des paiements
2. **Créer un dashboard créateur** pour voir ses gains
3. **Tester avec des vraies transactions** (mode test Stripe)

**Veux-tu que je t'aide à implémenter une partie spécifique ?** 😊
