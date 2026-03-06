# Managers - Gestion des appels API (AdminOffice)

Ce dossier contient tous les managers qui gèrent la logique des appels API vers le backend pour l'interface d'administration.

## Architecture

Les **managers** sont des **classes ES6 exportées en tant qu'instances singleton** qui sont responsables de :

- Tous les appels `fetch()` vers le backend
- La gestion des erreurs réseau
- La structuration des données à envoyer
- Le retour de résultats standardisés
- Centralisation de l'URL API dans le constructeur

Les **pages** sont responsables de :

- L'affichage et les interactions UI (admin/moderator)
- La validation côté client
- L'utilisation des managers pour les appels API
- La gestion de l'état local de la page

## Pattern utilisé

Chaque manager est une **classe ES6** avec :

- Un **constructeur** qui initialise `this.apiUrl`
- Des **méthodes asynchrones** pour les appels API
- Un **export default** d'une instance singleton

```javascript
class AuthManager {
  constructor() {
    this.apiUrl = "http://localhost/tfeHistoire/BackEnd/Api";
  }

  async login(email, password) {
    // Logique d'appel API
  }
}

export default new AuthManager();
```

## Liste des Managers

### AuthManager.js

Gestion de l'authentification administrateur/moderator.

**Méthodes :**

- `login(email, password)` - Connexion admin/moderator
- `logout(token)` - Déconnexion
- `checkToken(token)` - Vérifier la validité du token

**Exemple d'utilisation :**

```javascript
import AuthManager from "../managers/AuthManager.js";

const result = await AuthManager.login(email, password);
if (result.success) {
  // Vérifier le rôle (admin/moderator)
  if (result.data.role === "admin" || result.data.role === "moderator") {
    // Accès autorisé
  }
}
```

## Conventions

### Retour des méthodes

Toutes les méthodes retournent un objet avec la structure suivante :

**Succès :**

```javascript
{
  success: true,
  data: { ... },
  message: 'Message optionnel'
}
```

**Erreur :**

```javascript
{
  success: false,
  message: "Message d'erreur"
}
```

### Authentification

Les méthodes nécessitant une authentification prennent le `token` en paramètre.

**Important :** Toujours vérifier si le token existe avant d'appeler une méthode authentifiée :

```javascript
const token = auth.getToken();
if (!token) {
  // Rediriger vers login
  return;
}
const result = await Manager.method(token);
```

## URL API

Chaque manager a son URL API définie dans le constructeur :

```javascript
class AuthManager {
  constructor() {
    this.apiUrl = "http://localhost/tfeHistoire/BackEnd/Api";
  }
}
```

Les endpoints correspondent aux fichiers API backend :

- `authApi.php`

## Bonnes pratiques

### Dans les pages

```javascript
// ✅ BON - Utiliser les managers (import default)
import AuthManager from "../managers/AuthManager.js";

async function handleLogin() {
  const result = await AuthManager.login(email, password);
  if (result.success) {
    // Vérifier le rôle avant d'autoriser l'accès
    if (result.data.role === "admin" || result.data.role === "moderator") {
      auth.saveToken(result.data.token);
      auth.saveUser(result.data);
      window.location.href = "/AdminOffice/pages/dashboard.html";
    } else {
      helpers.showToast("Accès non autorisé", "error");
    }
  } else {
    helpers.showToast(result.message, "error");
  }
}

// ❌ MAUVAIS - Faire des fetch directement
async function handleLogin() {
  const response = await fetch("...");
  // ...
}
```

### Gestion des erreurs

```javascript
// Toujours vérifier le succès de l'opération
const result = await Manager.method(data);

if (result.success) {
  // Succès - traiter les données
  console.log(result.data);
} else {
  // Erreur - afficher le message
  helpers.showToast(result.message, "error");
}
```

### Vérification du rôle

Pour l'AdminOffice, toujours vérifier que l'utilisateur a le bon rôle :

```javascript
const currentUser = auth.getCurrentUser();
if (
  !currentUser ||
  (currentUser.role !== "admin" && currentUser.role !== "moderator")
) {
  window.location.href = "/AdminOffice/pages/login.html";
}
```
