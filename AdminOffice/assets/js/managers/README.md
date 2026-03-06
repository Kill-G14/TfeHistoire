# Managers - AdminOffice

Ce dossier contient les managers qui gèrent la logique des appels API vers le backend pour l'interface d'administration.

## Architecture

Les **managers** sont responsables de :

- Tous les appels `fetch()` vers le backend
- La gestion des erreurs réseau
- La structuration des données à envoyer
- Le retour de résultats standardisés

Les **pages** sont responsables de :

- L'affichage et les interactions UI
- La validation côté client
- L'utilisation des managers pour les appels API
- La gestion de l'état local de la page

## Liste des Managers

### AuthManager.js

Gestion de l'authentification pour les administrateurs et modérateurs.

**Méthodes :**

- `login(email, password)` - Connexion admin/moderator
- `logout(token)` - Déconnexion
- `checkToken(token)` - Vérifier la validité du token

**Exemple d'utilisation :**

```javascript
import { AuthManager } from "../managers/AuthManager.js";

const result = await AuthManager.login(email, password);
if (result.success) {
  // Vérifier le rôle avant de sauvegarder
  if (result.data.role === "admin" || result.data.role === "moderator") {
    auth.saveAuthData(result.data.token, result.data.user, remember);
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
  message: 'Message d\'erreur'
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
const result = await AuthManager.logout(token);
```

## URL API

Tous les managers pointent vers :

```javascript
const API_URL = "http://localhost/tfeHistoire/BackEnd/Api";
```

Les endpoints correspondent aux fichiers API backend :

- `authApi.php`
- `eventsApi.php`
- `ticketsApi.php`
- etc.

## Bonnes pratiques

### Dans les pages

```javascript
// ✅ BON - Utiliser les managers
import { AuthManager } from '../managers/AuthManager.js'

async function handleLogin() {
  const result = await AuthManager.login(email, password)
  if (result.success) {
    // Traiter la connexion
  } else {
    showError(result.message)
  }
}

// ❌ MAUVAIS - Faire des fetch directement
async function handleLogin() {
  const response = await fetch('../../BackEnd/Api/authApi.php', {...})
  // ...
}
```

### Gestion des erreurs

```javascript
// Toujours vérifier le succès de l'opération
const result = await AuthManager.login(email, password);

if (result.success) {
  // Succès - traiter les données
  console.log(result.data);
} else {
  // Erreur - afficher le message
  showError(result.message);
}
```

### Vérification des rôles

Pour l'administration, toujours vérifier le rôle après authentification :

```javascript
const result = await AuthManager.login(email, password);

if (result.success) {
  const role = result.data.role;

  // Vérifier si admin ou moderator
  if (role === "admin" || role === "moderator") {
    // OK - autoriser l'accès
    auth.saveAuthData(result.data.token, result.data.user, remember);
  } else {
    // Refuser l'accès
    showError("Accès refusé. Administrateurs et modérateurs uniquement.");
  }
}
```
