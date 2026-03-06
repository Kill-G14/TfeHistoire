# Managers - Gestion des appels API

Ce dossier contient tous les managers qui gèrent la logique des appels API vers le backend.

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

Gestion de l'authentification utilisateur.

**Méthodes :**

- `login(email, password)` - Connexion utilisateur
- `register(email, password, name)` - Inscription utilisateur
- `logout(token)` - Déconnexion utilisateur
- `getCurrentUser(token)` - Récupérer l'utilisateur actuel

**Exemple d'utilisation :**

```javascript
import { AuthManager } from "../managers/AuthManager.js";

const result = await AuthManager.login(email, password);
if (result.success) {
  // Traiter la connexion réussie
}
```

### EventManager.js

Gestion des événements.

**Méthodes :**

- `getAll()` - Récupérer tous les événements
- `getById(eventId)` - Récupérer un événement par ID
- `create(eventData, token)` - Créer un événement (authentifié)
- `update(eventId, eventData, token)` - Mettre à jour un événement (authentifié)
- `delete(eventId, token)` - Supprimer un événement (authentifié)

**Exemple d'utilisation :**

```javascript
import { EventManager } from "../managers/EventManager.js";

// Sans authentification
const result = await EventManager.getAll();

// Avec authentification
const token = auth.getToken();
const result = await EventManager.create(eventData, token);
```

### FavoriteManager.js

Gestion des favoris utilisateur.

**Méthodes :**

- `getByUser(token)` - Récupérer les favoris d'un utilisateur
- `add(eventId, token)` - Ajouter aux favoris
- `remove(eventId, token)` - Retirer des favoris
- `isFavorite(eventId, token)` - Vérifier si un événement est favori

**Exemple d'utilisation :**

```javascript
import { FavoriteManager } from "../managers/FavoriteManager.js";

const token = auth.getToken();
const result = await FavoriteManager.add(eventId, token);
if (result.success) {
  // Favori ajouté
}
```

### OrderManager.js

Gestion des commandes.

**Méthodes :**

- `getByUser(token)` - Récupérer les commandes d'un utilisateur
- `getById(orderId, token)` - Récupérer une commande par ID
- `create(orderData, token)` - Créer une commande
- `cancel(orderId, token)` - Annuler une commande

**Exemple d'utilisation :**

```javascript
import { OrderManager } from "../managers/OrderManager.js";

const token = auth.getToken();
const result = await OrderManager.create(orderData, token);
```

### TicketManager.js

Gestion des tickets (billetterie).

**Méthodes :**

- `getByEvent(eventId)` - Récupérer les tickets d'un événement
- `getById(ticketId)` - Récupérer un ticket par ID
- `create(ticketData, token)` - Créer un ticket (admin)
- `update(ticketId, ticketData, token)` - Mettre à jour un ticket (admin)
- `delete(ticketId, token)` - Supprimer un ticket (admin)
- `getPurchasedByUser(token)` - Récupérer les tickets achetés par un utilisateur
- `scan(ticketCode, token)` - Scanner un ticket

**Exemple d'utilisation :**

```javascript
import { TicketManager } from "../managers/TicketManager.js";

// Sans authentification
const result = await TicketManager.getByEvent(eventId);

// Avec authentification
const token = auth.getToken();
const result = await TicketManager.getPurchasedByUser(token);
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

Les méthodes nécessitant une authentification prennent le `token` en dernier paramètre.

**Important :** Toujours vérifier si le token existe avant d'appeler une méthode authentifiée :

```javascript
const token = auth.getToken();
if (!token) {
  // Rediriger vers login ou afficher erreur
  return;
}
const result = await Manager.method(data, token);
```

## URL API

Tous les managers pointent vers :

```javascript
const API_URL = "http://localhost/tfeHistoire/BackEnd/Api";
```

Les endpoints correspondent aux fichiers API backend :

- `authApi.php`
- `eventsApi.php`
- `favoritesApi.php`
- `ordersApi.php`
- `ticketsApi.php`
- `ticketsGeneratedApi.php`
- `scanTicketApi.php`

## Bonnes pratiques

### Dans les pages

```javascript
// ✅ BON - Utiliser les managers
import { EventManager } from "../managers/EventManager.js";

async function loadEvents() {
  const result = await EventManager.getAll();
  if (result.success) {
    displayEvents(result.data);
  } else {
    helpers.showToast(result.message, "error");
  }
}

// ❌ MAUVAIS - Faire des fetch directement
async function loadEvents() {
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

### Composants réutilisables

Si un composant a besoin de données API, passez-les en paramètre plutôt que de faire l'appel dans le composant :

```javascript
// ✅ BON - Dans la page
const result = await EventManager.getAll();
if (result.success) {
  renderEventCards(result.data);
}

// ❌ MAUVAIS - Dans le composant
async function renderEventCards() {
  const result = await EventManager.getAll(); // Ne pas faire ça
  // ...
}
```
