# SYSTÈME DE SESSIONS - Documentation

## 📋 Vue d'ensemble

Le projet utilise un système de sessions simple et self-made pour gérer l'authentification des utilisateurs. **Plus de JWT, plus de Bearer !**

---

## 🔧 Architecture Backend

### 1. Structure de la base de données

**Table `sessions`** :

```sql
CREATE TABLE IF NOT EXISTS sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(16) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
)
```

- **token** : Chaîne de 16 caractères hexadécimaux (unique)
- **user_id** : ID de l'utilisateur associé
- **created_at** : Date/heure de création de la session

---

### 2. SessionRepository

**Fichier** : `BackEnd/Src/Repositories/SessionRepository.php`

**Méthodes** :

- `createSession(string $token, int $userId): bool` - Créer une session
- `getSessionByToken(string $token): ?array` - Récupérer une session
- `tokenExists(string $token): bool` - Vérifier l'existence d'un token
- `deleteSessionByToken(string $token): bool` - Supprimer une session
- `deleteUserSessions(int $userId): bool` - Supprimer toutes les sessions d'un utilisateur
- `getUserIdByToken(string $token): ?int` - Récupérer l'ID utilisateur depuis un token

---

### 3. SessionService

**Fichier** : `BackEnd/Src/Services/SessionService.php`

**Méthodes** :

- `createSession(int $userId): ?string` - Créer une session et générer un token (16 chars hex)
- `checkToken(string $token): bool` - Vérifier si un token est valide
- `getUserIdByToken(string $token): ?int` - Récupérer l'ID utilisateur depuis un token
- `deleteSessionByToken(string $token): bool` - Déconnexion (suppression du token)
- `deleteUserSessions(int $userId): bool` - Supprimer toutes les sessions d'un utilisateur

---

### 4. AuthService (modifié)

**Fichier** : `BackEnd/Src/Services/AuthService.php`

**Changements** :

- Injection de `SessionService` dans le constructeur
- `register()` : Crée une session automatiquement après l'inscription
- `login()` : Crée une session automatiquement après la connexion
- `checkToken(string $token): ?int` : Remplace `verifyToken()` - Vérifie le token et retourne l'ID utilisateur
- `logout(string $token): array` : Nouvelle méthode pour supprimer la session

---

### 5. Fichiers API

**Modifications dans tous les fichiers API** :

- ❌ **Retrait** : Header `Authorization: Bearer {token}`
- ✅ **Nouveau** : Token envoyé dans le **body JSON** des requêtes

#### auth.php

**Headers CORS** :

```php
header('Access-Control-Allow-Headers: Content-Type');
```

**Actions disponibles** :

- `register` - Inscription (retourne user + token)
- `login` - Connexion (retourne user + token)
- `getCurrentUser` - Récupérer l'utilisateur connecté (nécessite token dans le body)
- `logout` - Déconnexion (nécessite token dans le body)

**Exemple de requête** :

```json
{
  "action": "getCurrentUser",
  "token": "a1b2c3d4e5f6g7h8"
}
```

#### bookings.php

**Vérification d'authentification** :

```php
if (!isset($data['token']) || empty($data['token'])) {
  // Erreur : Non authentifié
}

$token = $data['token'];
$userId = $authService->checkToken($token);

if (!$userId) {
  // Erreur : Token invalide
}
```

**Actions disponibles** :

- `getMyBookings` - Mes réservations
- `create` - Créer une réservation
- `cancel` - Annuler une réservation

**Toutes les actions nécessitent le token dans le body.**

#### events.php

**Actions publiques (pas de token nécessaire)** :

- `getAll` - Tous les événements
- `getById` - Événement par ID
- `getByCountry` - Événements par pays
- `getByCategory` - Événements par catégorie
- `search` - Recherche d'événements

**Actions authentifiées (token requis dans le body)** :

- `create` - Créer un événement
- `update` - Modifier un événement
- `delete` - Supprimer un événement
- `getMyEvents` - Mes événements

---

## 🎨 Architecture Frontend

### 1. auth.js

**Fichier** : `FrontEnd/assets/js/utils/auth.js`

**Changements** :

- Les méthodes `login()` et `register()` sont maintenant **async** et font de vrais appels API
- Nouvelle méthode `logout()` **async** qui appelle l'API pour supprimer la session
- Stockage automatique du token et de l'utilisateur après connexion/inscription
- Suppression automatique du token et de l'utilisateur après déconnexion

**Méthodes** :

```javascript
auth.isLoggedIn(); // Vérifier si connecté
auth.getUser(); // Récupérer l'utilisateur
await auth.login(email, password); // Connexion
await auth.register(email, password, name); // Inscription
await auth.logout(); // Déconnexion
auth.getToken(); // Récupérer le token
```

**Exemple d'utilisation** :

```javascript
const result = await auth.login("user@example.com", "password");

if (result.success) {
  console.log("Connecté :", result.data.user);
  console.log("Token :", result.data.token);
} else {
  console.error("Erreur :", result.message);
}
```

---

### 2. helpers.js

**Fichier** : `FrontEnd/assets/js/utils/helpers.js`

**Nouvelles méthodes pour les appels API** :

#### `apiCall(endpoint, data)`

Appel API **sans authentification** :

```javascript
const result = await helpers.apiCall("events.php", {
  action: "getAll",
});
```

#### `apiCallAuth(endpoint, data)`

Appel API **avec authentification** (ajoute automatiquement le token) :

```javascript
const result = await helpers.apiCallAuth("events.php", {
  action: "create",
  title: "Mon événement",
  description: "...",
  // ...
});
// Le token est ajouté automatiquement !
```

---

## 🔄 Flux d'authentification

### 1. Connexion

```
Frontend                    Backend
   |                           |
   |--- POST auth.php -------->|
   |    { action: 'login',     |
   |      email: '...',         |
   |      password: '...' }     |
   |                           |
   |                    [Vérification]
   |                    [Génération token 16 chars]
   |                    [Insertion en BDD]
   |                           |
   |<-- { success: true, ------|
   |      data: {               |
   |        user: {...},        |
   |        token: '...'        |
   |      }}                    |
   |                           |
[Stockage localStorage]        |
```

### 2. Requête authentifiée

```
Frontend                    Backend
   |                           |
   |--- POST events.php ------>|
   |    { action: 'create',    |
   |      token: '...',         |
   |      title: '...' }        |
   |                           |
   |                    [Vérification token en BDD]
   |                    [Récupération user_id]
   |                    [Traitement requête]
   |                           |
   |<-- { success: true } -----|
```

### 3. Déconnexion

```
Frontend                    Backend
   |                           |
   |--- POST auth.php -------->|
   |    { action: 'logout',    |
   |      token: '...' }        |
   |                           |
   |                    [Suppression token en BDD]
   |                           |
   |<-- { success: true } -----|
   |                           |
[Suppression localStorage]     |
```

---

## 📝 Exemples d'utilisation

### Backend - Vérification d'authentification

```php
// Récupérer le token depuis le body
if (!isset($data['token']) || empty($data['token'])) {
  echo json_encode([
    'success' => false,
    'message' => 'Non authentifié'
  ]);
  exit;
}

$token = $data['token'];
$userId = $authService->checkToken($token);

if (!$userId) {
  echo json_encode([
    'success' => false,
    'message' => 'Token invalide'
  ]);
  exit;
}

// Token valide, continuer le traitement
```

### Frontend - Appel API authentifié

**Méthode 1 : Avec helpers.apiCallAuth()**

```javascript
const result = await helpers.apiCallAuth("bookings.php", {
  action: "create",
  event_id: 123,
  tickets_count: 2,
});
```

**Méthode 2 : Manuel**

```javascript
const token = auth.getToken();

const response = await fetch(
  "http://localhost/tfeHistoire/BackEnd/Api/bookings.php",
  {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "create",
      token: token,
      event_id: 123,
      tickets_count: 2,
    }),
  },
);

const result = await response.json();
```

---

## ⚠️ Points importants

1. **Tokens de 16 caractères** : Générés avec `bin2hex(random_bytes(8))`
2. **Pas de Bearer** : Le token est envoyé directement dans le body JSON
3. **Pas de header Authorization** : Headers CORS simplifiés
4. **Stockage en BDD** : Tous les tokens sont en base de données
5. **Validation réelle** : Chaque token est vérifié en base à chaque requête
6. **Suppression à la déconnexion** : Les tokens sont supprimés de la BDD

---

## 🚀 Migration depuis JWT/Bearer

### Changements à faire dans le code existant

**Frontend** :

```javascript
// ❌ AVANT (Bearer)
headers: {
  'Authorization': 'Bearer ' + token
}

// ✅ APRÈS (Token dans le body)
body: JSON.stringify({
  action: 'monAction',
  token: token,
  // ... autres données
})
```

**Backend** :

```php
// ❌ AVANT
$headers = getallheaders();
$token = $headers['Authorization'] ?? null;
$token = str_replace('Bearer ', '', $token);
$userId = $authService->verifyToken($token);

// ✅ APRÈS
$token = $data['token'] ?? null;
$userId = $authService->checkToken($token);
```

---

## 🔐 Sécurité

- ✅ Tokens aléatoires de 16 caractères
- ✅ Tokens uniques en base de données
- ✅ Vérification à chaque requête
- ✅ Suppression à la déconnexion
- ✅ Suppression en cascade si l'utilisateur est supprimé
- ⚠️ **À ajouter** : Expiration des tokens (TODO)
- ⚠️ **À ajouter** : Renouvellement automatique des tokens (TODO)

---

## 📦 Fichiers modifiés/créés

### Backend

- ✅ `database.sql` - Ajout table `sessions`
- ✅ `Src/Repositories/SessionRepository.php` - Nouveau
- ✅ `Src/Services/SessionService.php` - Nouveau
- ✅ `Src/Services/AuthService.php` - Modifié
- ✅ `Api/auth.php` - Modifié
- ✅ `Api/bookings.php` - Modifié
- ✅ `Api/events.php` - Modifié

### Frontend

- ✅ `assets/js/utils/auth.js` - Modifié (appels API réels)
- ✅ `assets/js/utils/helpers.js` - Modifié (méthodes API ajoutées)

---

## 🎯 Résultat

Un système d'authentification **simple, clair et fonctionnel** sans JWT ni Bearer, parfait pour un junior dev ! 🚀
