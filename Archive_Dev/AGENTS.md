# STANDARDS FRONTEND & BACKEND

### TEST AGENT

Avant toute réponse, l'agent doit écrire :

AGENTS_OK

Code simple pas trop, voir pas d'abstraction ou de couches d'abstraction
Concidére que je suis un Junior Dev donc n'utilise pas de technique trop pousser a moin que je te le demande

---

# PARTIE 1 : STANDARD FRONTEND (SPA - Single Page Application)

## 1. STRUCTURE FRONTEND

### Arborescence des dossiers

```
index.html                         # SEUL fichier HTML de l'application

assets/
  ├── css/
  │   ├── custom.css
  │   └── input.css
  ├── js/
  │   ├── app.js                  # Point d'entrée principal + routeur
  │   ├── router.js               # Routeur SPA (History API)
  │   ├── components/
  │   │   ├── navbar.js
  │   │   ├── footer.js
  │   │   ├── productCard.js
  │   │   ├── loginModal.js
  │   │   ├── cartWidget.js
  │   │   └── newsletter.js
  │   ├── views/
  │   │   ├── home.js
  │   │   ├── products.js
  │   │   ├── productDetail.js
  │   │   ├── cart.js
  │   │   ├── checkout.js
  │   │   └── account.js
  │   ├── managers/
  │   │   ├── AuthManager.js
  │   │   ├── EventManager.js
  │   │   ├── FavoriteManager.js
  │   │   ├── OrderManager.js
  │   │   └── TicketManager.js
  │   ├── store/
  │   │   └── appState.js         # Gestion d'état centralisée
  │   └── utils/
  │       ├── auth.js
  │       ├── storage.js
  │       └── helpers.js
  ├── templates/
  │   ├── navbar.html
  │   ├── footer.html
  │   ├── productCard.html
  │   ├── loginModal.html
  │   ├── cartWidget.html
  │   └── views/
  │       ├── home.html
  │       ├── products.html
  │       ├── productDetail.html
  │       ├── cart.html
  │       └── checkout.html
  └── images/
      ├── logo.png
      └── ... autres images
```

### Guidelines

- Lire Guidelines.md pour avoir une cohérence visuelle et structurelle pour le projet frontend
- **SPA** : Un seul fichier HTML, toutes les pages sont des vues chargées dynamiquement

### Organisation du fichier HTML unique

- **Un seul** `index.html` à la racine
- Contient les éléments persistants (navbar, footer)
- Contient un conteneur principal `#app` pour l'injection des vues
- Scripts chargés une seule fois au démarrage
- Métadonnées mises à jour dynamiquement via JavaScript

### Organisation CSS

- Un seul fichier CSS custom : `assets/css/custom.css`
- shadcn/ui utilisé via CDN
- Bootstrap utilisé via CDN
- Bootstrap Icons via CDN
- Google Fonts via CDN
- CSS custom pour les surcharges et styles spécifiques

### Organisation JavaScript

- Modules ES6 avec `type="module"`
- **app.js** : Point d'entrée principal de l'application
- **router.js** : Routeur SPA avec History API
- Séparation en 5 dossiers :
  - `components/` : composants réutilisables (navbar, footer, cards)
  - `views/` : vues correspondant aux routes (home, products, cart)
  - `managers/` : logique des appels API vers le backend
  - `store/` : gestion d'état centralisée de l'application
  - `utils/` : fonctions utilitaires (formatage, storage, auth)
- Un fichier par fonctionnalité ou composant
- Exports nommés ou par défaut selon contexte

### Emplacement des assets

- CSS : `assets/css/`
- JS : `assets/js/`
- Templates HTML : `assets/templates/`
- Templates de vues : `assets/templates/views/`
- Images : `assets/images/`
- Chemins absolus depuis la racine : `/assets/`

## 2. CONVENTIONS DE NOMMAGE FRONTEND

### Fichier HTML

- **Un seul fichier** : `index.html` à la racine
- Templates de vues dans `assets/templates/views/` : `home.html`, `products.html`
- Templates de composants dans `assets/templates/` : `navbar.html`, `productCard.html`

### Fichiers CSS

- camelCase : `custom.css`, `input.css`

### Fichiers JS

- **app.js** : Point d'entrée principal
- **router.js** : Routeur SPA
- camelCase : `productCard.js`, `loginModal.js`
- Un fichier JS par composant dans `components/`
- Un fichier JS par vue dans `views/`

### Variables JavaScript

- camelCase : `cartCount`, `isAuth`, `productId`, `currentRoute`
- Constantes en UPPERCASE : `API_BASE_URL`, `ROUTES`

### Fonctions JavaScript

- camelCase : `renderNavbar()`, `loadProducts()`, `attachEventListeners()`
- Préfixes courants :
  - `render` : pour le rendu de composants/vues
  - `load` : pour le chargement de données
  - `loadTemplate` : pour le chargement de templates HTML
  - `attach` : pour les écouteurs d'événements
  - `navigate` : pour la navigation entre vues
  - `init` : pour l'initialisation
  - `mount` : pour le montage d'une vue
  - `unmount` : pour le démontage d'une vue
  - `get`, `set` : pour les getters/setters

### Classes JavaScript

- PascalCase : `Router`, `AppState`, `ApiClient`

### Classes CSS

- camelCase ou classes Bootstrap
- Exemples : `.containerProfile`, `.customBtn`

### IDs HTML

- camelCase : `#app`, `#navbar`, `#footer`, `#viewContainer`
- **#app** : Conteneur principal obligatoire pour les vues

### Dossiers

- Minuscules simples : `views/`, `components/`, `utils/`, `css/`, `images/`, `templates/`, `store/`

## 3. ARCHITECTURE FRONTEND

### Organisation HTML

- Doctype HTML5
- Structure sémantique avec balises HTML5
- **Un seul fichier** `index.html` à la racine
- Conteneur principal `#app` pour l'injection dynamique des vues
- Navbar et footer injectés une seule fois au démarrage
- Bootstrap pour la grille et composants UI
- Script module `app.js` en fin de `<body>`

Exemple :

```html
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title id="pageTitle">Mon Application</title>
    <meta
      name="description"
      id="pageDescription"
      content="Description par défaut"
    />
    <!-- Bootstrap CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <!-- Bootstrap Icons -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
      rel="stylesheet"
    />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/custom.css" />
  </head>
  <body>
    <!-- Navbar persistant -->
    <nav id="navbar"></nav>

    <!-- Conteneur principal pour les vues -->
    <main id="app"></main>

    <!-- Footer persistant -->
    <footer id="footer"></footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Application principale -->
    <script type="module" src="/assets/js/app.js"></script>
  </body>
</html>
```

### Organisation CSS

- Bootstrap via CDN
- Un fichier `custom.css` pour :
  - Surcharges Bootstrap
  - Classes utilitaires personnalisées
  - Styles spécifiques au projet
  - Animations de transitions entre vues
- Organisation du CSS :
  - Styles généraux en premier
  - Surcharges Bootstrap
  - Classes utilitaires
  - Composants spécifiques
  - Animations et transitions

### Organisation JS

#### Séparation app/router/components/views/managers

- **app.js** : Point d'entrée principal, initialisation de l'application
- **router.js** : Routeur SPA avec History API
- **Components** : composants réutilisables (navbar, footer, cards)
- **Views** : vues correspondant aux routes (home, products, cart)
- **Managers** : logique des appels API vers le backend
- **Store** : gestion d'état centralisée (appState.js)
- **Utils** : fonctions utilitaires transversales

#### Structure du fichier app.js (Point d'entrée)

```javascript
// Imports
import { Router } from "./router.js";
import { renderNavbar } from "./components/navbar.js";
import { renderFooter } from "./components/footer.js";
import { appState } from "./store/appState.js";
import { auth } from "./utils/auth.js";

// Définition des routes
const routes = {
  "/": () => import("./views/home.js"),
  "/products": () => import("./views/products.js"),
  "/product/:id": () => import("./views/productDetail.js"),
  "/cart": () => import("./views/cart.js"),
  "/checkout": () => import("./views/checkout.js"),
  "/account": () => import("./views/account.js"),
};

// Instance du routeur
const router = new Router(routes, "#app");

// Fonction init
async function init() {
  // Vérifier l'authentification
  await auth.checkAuth();

  // Rendre les composants persistants
  await renderNavbar();
  await renderFooter();

  // Initialiser le routeur
  router.init();

  // Écouter les changements d'état
  appState.subscribe("user", updateNavbar);
}

// Initialisation
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
```

#### Structure du fichier router.js

```javascript
// Routeur SPA avec History API
export class Router {
  constructor(routes, appSelector) {
    this.routes = routes;
    this.appElement = document.querySelector(appSelector);
    this.currentView = null;
    this.params = {};
  }

  init() {
    // Écouter les changements d'URL
    window.addEventListener("popstate", () => this.handleRoute());

    // Intercepter les clics sur les liens
    document.addEventListener("click", (e) => {
      if (e.target.matches("[data-link]")) {
        e.preventDefault();
        this.navigate(e.target.getAttribute("href"));
      }
    });

    // Charger la route initiale
    this.handleRoute();
  }

  async navigate(url) {
    history.pushState(null, null, url);
    await this.handleRoute();
  }

  async handleRoute() {
    const path = window.location.pathname;

    // Trouver la route correspondante
    const route = this.matchRoute(path);

    if (route) {
      // Démonter la vue précédente
      if (this.currentView && this.currentView.unmount) {
        await this.currentView.unmount();
      }

      // Charger et monter la nouvelle vue
      const viewModule = await route.handler();
      this.currentView = viewModule.default || viewModule;

      // Mettre à jour les métadonnées
      this.updateMetadata(this.currentView.meta);

      // Monter la vue
      await this.currentView.mount(this.appElement, this.params);
    } else {
      // Route 404
      this.show404();
    }
  }

  matchRoute(path) {
    for (const [pattern, handler] of Object.entries(this.routes)) {
      const match = this.match(pattern, path);
      if (match) {
        this.params = match.params;
        return { handler, params: match.params };
      }
    }
    return null;
  }

  match(pattern, path) {
    // Convertir le pattern en regex (ex: /product/:id -> /product/([^/]+))
    const paramNames = [];
    const regexPattern = pattern
      .replace(/:[^/]+/g, (match) => {
        paramNames.push(match.slice(1));
        return "([^/]+)";
      })
      .replace(/\//g, "\\/");

    const regex = new RegExp(`^${regexPattern}$`);
    const matches = path.match(regex);

    if (matches) {
      const params = {};
      paramNames.forEach((name, index) => {
        params[name] = matches[index + 1];
      });
      return { params };
    }

    return null;
  }

  updateMetadata(meta = {}) {
    document.title = meta.title || "Mon Application";

    const description = document.getElementById("pageDescription");
    if (description) {
      description.content = meta.description || "Description par défaut";
    }
  }

  show404() {
    this.appElement.innerHTML = `
      <div class="container text-center py-5">
        <h1>404</h1>
        <p>Page non trouvée</p>
        <a href="/" data-link class="btn btn-primary">Retour à l'accueil</a>
      </div>
    `;
  }
}
```

#### Structure d'un fichier view

```javascript
// Imports
import EventManager from "../managers/EventManager.js";
import { helpers } from "../utils/helpers.js";
import { appState } from "../store/appState.js";

// Métadonnées de la vue
export const meta = {
  title: "Accueil - Mon Site",
  description: "Description de la page d'accueil",
};

// Template HTML (peut aussi être chargé depuis assets/templates/views/)
const template = `
  <div class="container">
    <h1>Page d'accueil</h1>
    <div id="eventsList"></div>
  </div>
`;

// Variables locales de la vue
let events = [];
let unsubscribe = null;

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Injecter le template
  container.innerHTML = template;

  // Charger les données
  await loadEvents();

  // Attacher les événements
  attachEventListeners();

  // S'abonner aux changements d'état
  unsubscribe = appState.subscribe("events", renderEvents);
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Nettoyer les event listeners
  // Annuler les abonnements
  if (unsubscribe) {
    unsubscribe();
  }

  // Annuler les requêtes en cours
  // Libérer les ressources
}

// Charger les événements
async function loadEvents() {
  const result = await EventManager.getAll();

  if (result.success) {
    events = result.data;
    appState.set("events", events);
    renderEvents(events);
  } else {
    helpers.showToast(result.message, "error");
  }
}

// Rendre les événements
function renderEvents(events) {
  const container = document.getElementById("eventsList");
  if (!container) return;

  container.innerHTML = events
    .map(
      (event) => `
    <div class="card">
      <h3>${event.name}</h3>
      <p>${event.description}</p>
    </div>
  `,
    )
    .join("");
}

// Attacher les event listeners
function attachEventListeners() {
  // Event delegation
  const container = document.getElementById("eventsList");
  if (!container) return;

  container.addEventListener("click", (e) => {
    if (e.target.matches(".btn-details")) {
      const id = e.target.dataset.id;
      // Navigation
      window.dispatchEvent(
        new CustomEvent("navigate", { detail: `/product/${id}` }),
      );
    }
  });
}

// Export par défaut
export default { mount, unmount, meta };
```

#### Structure d'un manager

```javascript
// Manager pour la gestion des événements
class EventManager {
  constructor() {
    this.apiUrl = "http://localhost/tfeHistoire/BackEnd/Api";
  }

  // Récupérer tous les événements
  async getAll() {
    try {
      const response = await fetch(`${this.apiUrl}/eventsApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "getAll",
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur lors du chargement des événements:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }
}

// Export d'une instance singleton
export default new EventManager();
```

#### Structure d'un composant

```javascript
// Imports
import { auth } from "../utils/auth.js";
import { appState } from "../store/appState.js";

// Objet pour stocker les templates
const templateObjects = {};

// Chargement du template HTML
async function loadTemplate(path) {
  const response = await fetch(path);
  const htmlContent = await response.text();
  const parser = new DOMParser();
  const templateDoc = parser.parseFromString(htmlContent, "text/html");
  const templates = templateDoc.querySelectorAll("template");

  templates.forEach((template) => {
    const templateId = template.id;
    templateObjects[templateId] = template.content;
  });
}

// Export de fonction de rendu
export async function renderNavbar() {
  await loadTemplate("/assets/templates/navbar.html");

  const element = document.getElementById("navbar");
  if (!element) return;

  const clone = templateObjects["navbarTemplate"].cloneNode(true);
  element.appendChild(clone);

  // Attacher les événements
  attachNavbarEvents();

  // S'abonner aux changements d'état utilisateur
  appState.subscribe("user", updateNavbar);
}

function attachNavbarEvents() {
  // Event listeners pour la navbar
  const navbar = document.getElementById("navbar");
  if (!navbar) return;

  // Intercepter les liens pour la navigation SPA
  navbar.addEventListener("click", (e) => {
    if (e.target.matches("[data-link]")) {
      e.preventDefault();
      const href = e.target.getAttribute("href");
      window.dispatchEvent(new CustomEvent("navigate", { detail: href }));
    }
  });
}

function updateNavbar(user) {
  // Mettre à jour l'affichage de la navbar selon l'utilisateur
  const loginBtn = document.getElementById("loginBtn");
  const userMenu = document.getElementById("userMenu");

  if (user) {
    loginBtn?.classList.add("d-none");
    userMenu?.classList.remove("d-none");
  } else {
    loginBtn?.classList.remove("d-none");
    userMenu?.classList.add("d-none");
  }
}
```

#### Structure d'un template HTML

```html
<template id="navbarTemplate">
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a href="/" data-link class="navbar-brand">Mon Site</a>
      <ul class="navbar-nav">
        <li class="nav-item">
          <a href="/" data-link class="nav-link">Accueil</a>
        </li>
        <li class="nav-item">
          <a href="/products" data-link class="nav-link">Produits</a>
        </li>
      </ul>
    </div>
  </nav>
</template>
```

#### Structure appState.js (Store)

```javascript
// Gestion d'état centralisée
class AppState {
  constructor() {
    this.state = {
      user: null,
      cart: [],
      events: [],
      favorites: [],
    };
    this.subscribers = {};
  }

  // Obtenir une valeur
  get(key) {
    return this.state[key];
  }

  // Définir une valeur
  set(key, value) {
    this.state[key] = value;
    this.notify(key, value);
  }

  // S'abonner aux changements
  subscribe(key, callback) {
    if (!this.subscribers[key]) {
      this.subscribers[key] = [];
    }
    this.subscribers[key].push(callback);

    // Retourner une fonction de désabonnement
    return () => {
      this.subscribers[key] = this.subscribers[key].filter(
        (cb) => cb !== callback,
      );
    };
  }

  // Notifier les abonnés
  notify(key, value) {
    if (this.subscribers[key]) {
      this.subscribers[key].forEach((callback) => callback(value));
    }
  }

  // Réinitialiser l'état
  reset() {
    this.state = {
      user: null,
      cart: [],
      events: [],
      favorites: [],
    };
    Object.keys(this.subscribers).forEach((key) => {
      this.notify(key, this.state[key]);
    });
  }
}

// Export d'une instance singleton
export const appState = new AppState();
```

#### Structure Utils

```javascript
export const helpers = {
  formatDate(date) {
    // ...
  },
  showToast(message, type) {
    // ...
  },
};
```

### Scripts et chargement

- **Un seul script** : `app.js` chargé au démarrage
- Lazy loading des vues via `import()` dynamique
- Bootstrap JS via CDN chargé avant le script custom
- Ordre : Bootstrap Bundle → app.js

### Organisation du DOM

- **#app** : Conteneur principal pour l'injection des vues
- **#navbar** : Navbar persistante (chargée une seule fois)
- **#footer** : Footer persistant (chargé une seule fois)
- IDs pour éléments interactifs
- Classes Bootstrap pour styles
- `dataset` pour stocker des données : `data-product-id`, `data-link`

## 4. RÈGLES DE CODE FRONTEND

### HTML

#### Structure

- Doctype HTML5 : `<!doctype html>`
- Lang : `<html lang="fr">`
- Viewport responsive : `<meta name="viewport" content="width=device-width, initial-scale=1.0">`
- Métadonnées avec ID pour mise à jour dynamique : `<title id="pageTitle">`, `<meta id="pageDescription">`

#### Indentation

- 2 espaces
- Balises auto-fermantes avec `/>`

#### Organisation du fichier index.html

- HEAD :
  - Meta charset et viewport
  - Title et description avec IDs
  - Preconnect fonts
  - CDN CSS (Bootstrap, Icons, Fonts)
  - CSS custom
- BODY :
  - Navbar avec `id="navbar"` (injectée au démarrage)
  - Conteneur principal `<main id="app"></main>`
  - Footer avec `id="footer"` (injecté au démarrage)
  - Scripts CDN (Bootstrap Bundle)
  - Script principal `app.js` avec `type="module"`

### CSS

#### Organisation

- Styles généraux (body, fonts)
- Surcharges Bootstrap (btn, colors)
- Classes utilitaires personnalisées
- Styles de composants
- **Animations de transitions entre vues**
- Classes pour les états de chargement

#### Structure

- Sélecteurs simples
- Classes Bootstrap surchargées avec `!important` si nécessaire
- Classes utilitaires préfixées ou descriptives

#### Transitions SPA

```css
/* Exemple de transitions pour la SPA */
#app {
  transition: opacity 0.3s ease-in-out;
}

#app.loading {
  opacity: 0.5;
  pointer-events: none;
}
```

#### Séparation des fichiers

- Un seul fichier custom

### JavaScript

#### Style d'écriture

- ES6+ : async/await, arrow functions, template literals
- Modules ES6 : import/export
- Import dynamique pour lazy loading : `import()`
- camelCase pour variables et fonctions
- Pas de point-virgule obligatoire (dépend du style)
- Template literals pour HTML : `` `<div></div>` ``
- Indentation de 2 espaces

#### Organisation du code

##### app.js (Point d'entrée)

- Imports en haut
- Définition des routes
- Instanciation du routeur
- Fonction `init()` : initialisation de l'application
- Chargement unique des composants persistants
- Initialisation en bas avec `DOMContentLoaded`

##### router.js

- Classe `Router` exportée
- Méthode `init()` : initialisation et event listeners
- Méthode `navigate()` : navigation programmatique
- Méthode `handleRoute()` : gestion des changements de route
- Méthode `matchRoute()` : correspondance pattern/URL
- Méthode `updateMetadata()` : mise à jour title/description

##### views/

- Export `meta` : métadonnées de la vue (title, description)
- Export `mount()` : fonction appelée lors du chargement de la vue
- Export `unmount()` : fonction appelée avant de quitter la vue
- Fonctions privées pour la logique interne
- Export default d'un objet `{ mount, unmount, meta }`

##### components/

- Fonction `render{ComponentName}()` exportée
- Chargement du template HTML
- Injection dans le conteneur approprié
- Attachement des event listeners
- Abonnement aux changements d'état si nécessaire

##### store/appState.js

- Classe `AppState` avec state management
- Méthodes `get()` et `set()`
- Méthode `subscribe()` pour abonnements
- Méthode `notify()` pour notifier les abonnés
- Export d'une instance singleton

#### Manipulation du DOM

- `document.getElementById()` ou `document.querySelector()` pour sélection
- `element.innerHTML` ou `appendChild()` pour injection
- Event delegation systématique
- `e.target.closest()` pour remonter au parent
- `dataset` pour stocker des données : `data-product-id`, `data-link`
- `cloneNode(true)` pour templates
- **Nettoyage des event listeners** dans `unmount()`

#### Gestion des templates HTML

```javascript
const templateObjects = {};

async function loadTemplate(path) {
  const response = await fetch(path);
  const htmlContent = await response.text();
  const parser = new DOMParser();
  const templateDoc = parser.parseFromString(htmlContent, "text/html");
  const templates = templateDoc.querySelectorAll("template");

  templates.forEach((template) => {
    const templateId = template.id;
    templateObjects[templateId] = template.content;
  });
}

// Utilisation
const clone = templateObjects["cardProduct"].cloneNode(true);
container.appendChild(clone);
```

#### Navigation SPA

```javascript
// Navigation via routeur
import { router } from './router.js'
router.navigate('/products')

// Ou via événement personnalisé
window.dispatchEvent(new CustomEvent('navigate', { detail: '/products' }))

// Liens HTML avec data-link
<a href="/products" data-link>Produits</a>
```

#### Appels API

- Tous les appels `fetch()` doivent être dans les **Managers**
- Les vues ne font **jamais** de `fetch()` directement
- Utilisation de managers pour centraliser la logique API
- Méthode POST
- Headers JSON :
  ```javascript
  headers: {
      'Content-Type': 'application/json'
  }
  ```
- Body stringifié : `JSON.stringify(data)`
- `await response.json()` pour récupération
- Retour standardisé : `{ success: boolean, message: string, data?: any }`

Exemple dans un Manager :

```javascript
export const EventManager = {
  async getAll() {
    try {
      const response = await fetch(`${API_URL}/eventsApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "getAll",
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
  },
};
```

Utilisation dans une vue :

```javascript
import EventManager from "../managers/EventManager.js";

async function loadEvents() {
  const result = await EventManager.getAll();

  if (result.success) {
    displayEvents(result.data);
  } else {
    helpers.showToast(result.message, "error");
  }
}
```

#### Gestion asynchrone

- `async/await` pour toutes les opérations asynchrones
- `try/catch` pour la gestion d'erreurs
- Retour de résultats via objets : `{ success: true, data: ... }`
- Gestion des requêtes en vol (annulation dans `unmount()`)

#### Gestion du state

- Utilisation du store `appState` pour l'état global
- Abonnement aux changements : `appState.subscribe('key', callback)`
- Désabonnement dans `unmount()` pour éviter les fuites mémoire
- LocalStorage pour la persistance (auth, settings)

## 5. BONNES PRATIQUES FRONTEND

### Séparation CSS / JS / HTML

- HTML : structure unique dans `index.html`
- CSS : styles et présentation
- JS : logique, routing, interactions, injection dynamique
- Pas de styles inline dans le HTML
- Pas de HTML statique répété (utiliser des composants JS)

### Organisation de l'application SPA

- Un seul fichier `index.html` à la racine
- Routeur pour gérer la navigation (router.js)
- Vues dans `assets/js/views/` (un fichier par route)
- Composants réutilisables dans `assets/js/components/`
- Templates HTML dans `assets/templates/` et `assets/templates/views/`
- Navigation sans rechargement de page
- URLs propres sans `.html` : `/products`, `/product/123`

### Gestion des scripts

- **Un seul script chargé** : `app.js` (point d'entrée)
- Lazy loading des vues avec `import()` dynamique
- Bootstrap JS via CDN chargé avant le script custom
- Ordre : Bootstrap Bundle → app.js

### Cycle de vie des vues

- **mount()** : Appelée lors du chargement de la vue
  - Injection du template
  - Chargement des données
  - Attachement des event listeners
  - Abonnement au state
- **unmount()** : Appelée avant de quitter la vue
  - Nettoyage des event listeners
  - Désabonnement du state
  - Annulation des requêtes en cours
  - Libération des ressources

### Organisation des composants

- Templates HTML dans `assets/templates/`
- Chargement dynamique via `fetch()` + `DOMParser()`
- Un fichier JS par composant dans `assets/js/components/`
- Export d'une fonction `render{ComponentName}()`
- Clone avec `cloneNode(true)` avant manipulation
- Vérification de l'existence de l'élément avant injection
- **Composants persistants** : navbar, footer (chargés une seule fois)
- **Composants dynamiques** : cards, modals (rechargés à la demande)

### Gestion de l'état (State Management)

- **appState.js** : Store centralisé pour l'état global
- Pattern subscribe/notify pour la réactivité
- Utilisation de `appState.set()` pour modifier l'état
- Abonnement avec `appState.subscribe('key', callback)`
- **Toujours désabonner** dans `unmount()` pour éviter les fuites mémoire
- localStorage pour persistance : tokens, user, favorites, settings
- Module `storage.js` pour abstraction localStorage
- Module `auth.js` pour gestion authentification

### Navigation SPA

- **History API** : `history.pushState()` pour changer l'URL
- **Interception des liens** : attribut `data-link` sur les balises `<a>`
- **Événement popstate** : écouter les boutons précédent/suivant du navigateur
- **Navigation programmatique** : `router.navigate('/path')`
- Pas de rechargement de page
- Transitions fluides entre vues

### Gestion des métadonnées

- Mise à jour dynamique du `<title>`
- Mise à jour de `<meta name="description">`
- Métadonnées définies dans chaque vue (objet `meta`)
- Mise à jour automatique par le routeur lors du changement de vue

### Appels API

- **Managers** : tous les appels `fetch()` sont centralisés dans les managers
- **Vues** : utilisent les managers, ne font jamais de `fetch()` directement
- Un manager par domaine (Auth, Event, Favorite, Reservation)
- Méthodes avec paramètres clairs
- Retour standardisé : `{ success: boolean, message: string, data?: any }`
- Gestion des erreurs dans les managers
- `async/await` obligatoire

Exemple :

```javascript
// Dans une vue
import EventManager from "../managers/EventManager.js";

async function loadEvents() {
  const result = await EventManager.getAll();
  if (result.success) {
    // Traiter les données
  }
}
```

### Performance et optimisation

- **Lazy loading** : chargement différé des vues avec `import()`
- **Code splitting** : séparation du code par route
- **Cache des templates** : ne pas recharger les mêmes templates
- **Event delegation** : utiliser la délégation pour les éléments dynamiques
- **Nettoyage mémoire** : toujours nettoyer dans `unmount()`
- **Transitions CSS** : utiliser CSS pour les animations, pas JS

### Responsive

- Bootstrap grid system
- Classes responsive Bootstrap : `d-none d-md-block`
- Mobile-first
- Navigation mobile adaptée (burger menu)

### SEO et accessibilité

- Métadonnées dynamiques (title, description)
- Balises sémantiques HTML5
- Attributs ARIA si nécessaire
- Support du bouton précédent/suivant du navigateur
- URLs propres et significatives

## 6. RÈGLES À IMPOSER À L'AGENT FRONTEND

### Structure obligatoire

- Toujours respecter l'arborescence `assets/js/{components,views,managers,store,utils}` et `assets/templates/`
- **Un seul fichier HTML** : `index.html` à la racine
- **Un fichier JS par vue** dans `views/`
- **Un fichier JS par composant** dans `components/`
- Templates HTML des vues dans `assets/templates/views/`
- Templates HTML des composants dans `assets/templates/`
- **Obligatoire : router.js** pour la gestion des routes
- **Obligatoire : app.js** comme point d'entrée unique

### Architecture SPA obligatoire

- **Un seul index.html** avec conteneur `#app`
- **Routeur JavaScript** avec History API
- **Navigation sans rechargement** : intercepter tous les liens avec `data-link`
- **Lazy loading** des vues via `import()` dynamique
- Composants persistants (navbar, footer) chargés une seule fois
- Vues montées/démontées dynamiquement

### Modules ES6

- Toujours utiliser `import/export`
- Script principal avec `type="module"`
- Import dynamique pour les vues : `() => import('./views/home.js')`
- Pas de scripts globaux multiples

### Bootstrap

- Toujours utiliser Bootstrap 5.3+
- CDN pour Bootstrap CSS et JS
- Bootstrap Icons pour les icônes
- Ne jamais réinventer ce que Bootstrap propose

### Nommage

- Fichier HTML : `index.html` (un seul)
- Fichiers JS : camelCase
- **router.js** et **app.js** : noms fixes obligatoires
- Variables/fonctions : camelCase
- Classes : PascalCase
- Constantes : UPPERCASE

### Organisation du code

#### app.js (Point d'entrée)

- Imports en haut
- Définition des routes (objet `routes`)
- Instanciation du routeur
- Fonction `init()` : initialisation globale
- Chargement des composants persistants
- Initialisation du routeur
- Lancement avec `DOMContentLoaded`

#### router.js (Routeur)

- Classe `Router` exportée
- Constructeur : `(routes, appSelector)`
- Méthode `init()` : écouter popstate et clics
- Méthode `navigate(url)` : navigation programmatique
- Méthode `handleRoute()` : montage/démontage des vues
- Méthode `matchRoute(path)` : correspondance pattern/URL
- Support des paramètres dynamiques : `/product/:id`

#### views/ (Vues)

- **Structure obligatoire** :
  - Export `meta` : `{ title, description }`
  - Export `mount(container, params)` : montage de la vue
  - Export `unmount()` : démontage et nettoyage
  - Export default : `{ mount, unmount, meta }`
- **mount()** doit :
  - Injecter le template dans le conteneur
  - Charger les données nécessaires
  - Attacher les event listeners
  - S'abonner au state si nécessaire
- **unmount()** doit :
  - Nettoyer les event listeners
  - Désabonner du state
  - Annuler les requêtes en cours

#### components/ (Composants)

- Un composant = un fichier HTML template + un fichier JS
- Templates HTML natifs avec `<template id="...">`
- Chargement via `fetch()` + `DOMParser()`
- Clone avec `cloneNode(true)`
- Export d'une fonction `render{Name}()`
- Toujours retourner si l'élément d'injection n'existe pas
- **Composants persistants** : ne pas recharger à chaque navigation

### State Management (Store)

- **Obligatoire** : `store/appState.js`
- Classe `AppState` avec pattern singleton
- Méthodes obligatoires :
  - `get(key)` : récupérer une valeur
  - `set(key, value)` : définir une valeur (+ notification)
  - `subscribe(key, callback)` : s'abonner aux changements
  - `notify(key, value)` : notifier les abonnés
- État global : `user`, `cart`, `events`, `favorites`, etc.
- **Toujours désabonner** dans `unmount()` des vues

### Navigation

- **Attribut data-link obligatoire** : `<a href="/products" data-link>`
- **Pas de .html dans les URLs** : `/products`, `/product/123`
- **History API** : utiliser `history.pushState()`
- **Écouter popstate** : supporter boutons précédent/suivant
- **Navigation programmatique** : `router.navigate('/path')`
- Intercepter tous les clics sur liens avec `data-link`

### API

- Tous les appels `fetch()` dans les **Managers**
- Un manager par domaine (Auth, Event, Favorite, etc.)
- Méthode POST avec JSON
- Headers et body à chaque appel
- Retour standardisé : `{ success: boolean, message: string, data?: any }`

### Managers

- Un fichier manager par domaine métier
- Nommage : `{Domain}Manager.js` (PascalCase)
- **Structure : Classe ES6** avec constructeur et méthodes
- **Export : default d'une instance singleton** (`export default new ManagerName()`)
- URL API dans le constructeur : `this.apiUrl`
- Gestion des erreurs dans try/catch
- Les vues utilisent les managers, jamais fetch directement
- Import dans les vues : `import ManagerName from '../managers/ManagerName.js'`

### Utilitaires

- Fonctions helpers dans `utils/helpers.js`
- Auth dans `utils/auth.js`
- Storage dans `utils/storage.js`
- Export const avec méthodes

### Métadonnées dynamiques

- Chaque vue doit exporter `meta` : `{ title, description }`
- Le routeur met à jour automatiquement le `<title>` et `<meta description>`
- IDs dans index.html : `<title id="pageTitle">`, `<meta id="pageDescription">`

### Indentation

- 2 espaces pour HTML, CSS, JS
- Pas de tabs

### CSS

- Un seul fichier `custom.css`
- Surcharges Bootstrap en premier
- Classes utilitaires ensuite
- Styles de composants en dernier
- **Animations de transitions entre vues**

### Gestion des événements

- **Event delegation obligatoire** pour éléments dynamiques
- `e.preventDefault()` pour les liens avec `data-link`
- `e.stopPropagation()` si nécessaire
- `dataset` pour passer des données
- **Toujours nettoyer** dans `unmount()`

### Async/Await

- Toujours utiliser async/await
- Pas de `.then()/.catch()`
- try/catch pour gestion d'erreurs

### Chemins

- **Absolus depuis la racine** : `/assets/`, `/assets/templates/`
- Pas de chemins relatifs : `../assets/` (interdit)
- Navigation : `/products`, `/product/123` (sans .html)

### Cycle de vie obligatoire

- **app.js** : lancé une seule fois au démarrage
- **Composants persistants** : navbar, footer chargés une seule fois
- **Vues** : montées/démontées à chaque changement de route
- **mount()** : initialiser la vue
- **unmount()** : nettoyer avant de quitter

### Ne jamais

- Créer plusieurs fichiers HTML (un seul : index.html)
- Utiliser jQuery
- Utiliser des scripts inline
- Créer des variables globales
- Dupliquer du code (créer un composant ou une fonction)
- Faire des appels `fetch()` dans les vues (utiliser les managers)
- Oublier de nettoyer dans `unmount()` (fuites mémoire)
- Utiliser `.html` dans les URLs de navigation
- Recharger la page lors de la navigation
- Oublier l'attribut `data-link` sur les liens internes
- Charger les composants persistants à chaque vue (navbar/footer)

---

# PARTIE 2 : STANDARD BACKEND

## 1. STRUCTURE BACKEND

### Arborescence des dossiers

```
ProjectRoot/
│
├── Api/                          # Points d'entrée HTTP
│   ├── auth.php
│   ├── products.php
│   ├── cart.php
│   ├── orders.php
│   └── ...
│
├── Src/
│   ├── Models/                   # Entités métier
│   │   ├── Product.php
│   │   ├── User.php
│   │   └── ModelsDTO/            # Data Transfer Objects
│   │       ├── ProductDTO.php
│   │       └── UserDTO.php
│   │
│   ├── Repositories/             # Accès base de données
│   │   ├── ProductRepository.php
│   │   └── UserRepository.php
│   │
│   ├── Services/                 # Logique métier
│   │   ├── ProductService.php
│   │   └── AuthService.php
│   │
│   ├── Validators/               # Validation des données
│   │   ├── ProductValidator.php
│   │   └── UserValidator.php
│   │
│   ├── Factories/                # Création d'objets complexes
│   │   ├── ProductFactory.php
│   │   └── OrderFactory.php
│   │
│   └── Utils/                    # Utilitaires transversaux
│       ├── Database.php
│       ├── Logger.php
│       └── Helpers.php
│
├── vendor/                       # Autoload Composer
├── composer.json                 # Configuration autoload PSR-4
└── .htaccess                     # Configuration serveur
```

### Organisation des fichiers PHP

- **Api/** : Un fichier = un endpoint
- **Src/** : Code organisé par responsabilité
- **vendor/** : Dépendances Composer

### Points d'entrée

- Chaque fichier dans `Api/` est un point d'entrée HTTP direct
- Pas de routeur central
- Un fichier API = une ressource (products, users, cart, etc.)

## 2. CONVENTIONS DE NOMMAGE BACKEND

### Fichiers PHP

- **Api** : `camelCase.php` (ex: `products.php`, `auth.php`, `blogArticles.php`)
- **Classes** : `PascalCase.php` (ex: `ProductService.php`, `UserRepository.php`)

### Classes

- **Models** : `PascalCase` (ex: `Product`, `User`, `Order`)
- **DTOs** : `PascalCaseDTO` (ex: `ProductDTO`, `UserDTO`)
- **Repositories** : `PascalCaseRepository` (ex: `ProductRepository`)
- **Services** : `PascalCaseService` (ex: `ProductService`)
- **Validators** : `PascalCaseValidator` (ex: `ProductValidator`)
- **Factories** : `PascalCaseFactory` (ex: `ProductFactory`)

### Méthodes

- **camelCase** pour toutes les méthodes
- **Préfixes courants** :
  - `get` : récupération (ex: `getProductById`, `getAllProducts`)
  - `create` : création (ex: `createProduct`)
  - `update` : mise à jour (ex: `updateProduct`)
  - `delete` : suppression (ex: `deleteProduct`)
  - `validate` : validation (ex: `validate`, `validateRegister`)

### Variables

- **camelCase** pour toutes les variables
- **snake_case** pour les propriétés correspondant aux colonnes SQL

### Dossiers

- **PascalCase** pour les dossiers de code (ex: `Models`, `Services`, `Repositories`)

### Bases de données

- **Tables** : `plural_lowercase` (ex: `products`, `users`)
- **Colonnes** : `snake_case` (ex: `category_id`, `created_at`)

## 3. ARCHITECTURE BACKEND

### Pattern utilisé

**API File → Validator → Service → Repository → Model**

### Rôle de chaque couche

#### API Files (`Api/`)

- Point d'entrée HTTP
- Headers CORS
- Autoload Composer
- Instanciation des dépendances
- Récupération du JSON d'entrée
- Authentification si nécessaire
- Routing par `switch/case` sur `action`
- Retour JSON

#### Validators (`Src/Validators/`)

- Validation des données entrantes
- Retour d'un tableau d'erreurs
- Pas de logique métier
- Pas d'accès base de données

#### Services (`Src/Services/`)

- Logique métier centralisée
- Manipulation des Models
- Transformation des données
- Conversion Model → DTO pour l'API
- Retour de tableaux structurés `['success' => bool, 'message' => string, 'data' => array]`

#### Repositories (`Src/Repositories/`)

- **SEUL** accès à la base de données
- Requêtes SQL préparées avec PDO
- Utilisation de `bindParam` pour tous les paramètres
- Retour d'objets Model ou tableaux
- Utilisation de `PDO::FETCH_CLASS` pour peupler les Models
- Pattern Singleton pour la connexion via `Database::getConnection()`

#### Models (`Src/Models/`)

- Représentation des entités de la base
- Propriétés publiques
- Noms de propriétés = noms de colonnes SQL
- Pas de constructeur (peuplement automatique par `PDO::FETCH_CLASS`)
- Pas de logique métier

#### DTOs (`Src/Models/ModelsDTO/`)

- Objets de transfert pour l'API
- Exclusion des données sensibles (ex: `password`)
- Constructeur prenant un Model en paramètre
- Méthode `toArray()` pour la conversion JSON

## 4. RÈGLES DE CODE BACKEND

### PHP

#### Organisation des classes

```php
<?php

namespace App\NamespaceName;

use ImportedClass;

class ClassName {
    // 1. Propriétés privées/protégées avec typage
    private Type $property;

    // 2. Constructeur avec injection de dépendances
    public function __construct(Dependency $dep) {
        $this->property = $dep;
    }

    // 3. Méthodes publiques
    public function publicMethod(): ReturnType {
        // ...
    }

    // 4. Méthodes privées/protégées
    private function privateMethod(): ReturnType {
        // ...
    }
}
```

#### Structure des méthodes Repository

```php
public function getEntityById(int $id): ?Entity {
    $query = "SELECT * FROM table_name WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $stmt->setFetchMode(\PDO::FETCH_CLASS, Entity::class);
    $entity = $stmt->fetch();
    return $entity ?: null;
}
```

#### Structure des Api Files

// Models
use App\Models\ModelName;

// repositories
use App\Repositories\repositoryName;

// Validator
use App\Validators\ValidatorName;

// services
use App\Services\ServiceName;

// Models
$modelName = new ModelName();

// repositories
$userRepository = new UserRepository();

// Validator
$validator = new ValidatorName();

// services
$service = new ServiceName($userRepository, $validator);

#### Typage

- **Typer tous les paramètres de méthodes**
- **Typer tous les retours de méthodes**
- **Typer toutes les propriétés de classe**
- Utiliser `?Type` pour les valeurs nullables
- Utiliser `array` pour les tableaux

#### Accès base de données

- Uniquement dans les Repositories
- Connexion via `Database::getConnection()`
- Requêtes préparées uniquement
- `bindParam` pour tous les paramètres
- `PDO::FETCH_CLASS` pour peupler les Models

#### Séparation des responsabilités

- **API** : orchestration
- **Validator** : validation
- **Service** : logique métier
- **Repository** : accès données
- **Model** : représentation données

### SQL

#### Organisation des requêtes

- Requêtes préparées uniquement
- Utilisation de paramètres nommés (`:param`)
- `bindParam` pour tous les paramètres
- Pas de concaténation de valeurs dans les requêtes

#### Requêtes SELECT

```php
$query = "SELECT * FROM table_name WHERE column = :value";
$stmt = $this->getPdo()->prepare($query);
$stmt->bindParam(':value', $value);
$stmt->execute();
```

#### Requêtes INSERT

```php
$query = "INSERT INTO table_name (col1, col2, created_at)
          VALUES (:col1, :col2, NOW())";
$stmt = $this->getPdo()->prepare($query);
$stmt->bindParam(':col1', $object->col1);
$stmt->bindParam(':col2', $object->col2);
$stmt->execute();
```

#### Requêtes UPDATE

```php
$query = "UPDATE table_name SET col1 = :col1, updated_at = NOW()
          WHERE id = :id";
$stmt = $this->getPdo()->prepare($query);
$stmt->bindParam(':id', $object->id);
$stmt->bindParam(':col1', $object->col1);
$stmt->execute();
```

## 5. BONNES PRATIQUES BACKEND

### Architecture

- Respecter la séparation Controller / Service / Repository
- Injection manuelle des dépendances dans les fichiers API
- Pattern Singleton pour la connexion base de données

### Validation

- Valider toutes les données entrantes avant traitement
- Validation dans Validators dédiés
- Retourner un tableau d'erreurs explicites

### Sécurité

- Hash des mots de passe avec `password_hash()`
- Requêtes préparées uniquement
- Vérification d'authentification avant actions sensibles
- Exclusion des données sensibles dans les DTOs

### Gestion des erreurs

- Retours structurés : `['success' => bool, 'message' => string]`
- Messages d'erreur clairs
- Vérification d'existence avant opérations

### Base de données

- Accès base uniquement dans Repository
- Pattern Singleton pour connexion PDO
- `PDO::FETCH_CLASS` pour peupler les Models
- Timestamps automatiques (`NOW()`)

### API

- Headers CORS systématiques
- Gestion des requêtes OPTIONS
- Autoload Composer en début de fichier
- Routing par `action` dans le JSON
- Retour JSON systématique

## 6. RÈGLES À IMPOSER À L'AGENT BACKEND

### Structure obligatoire

- Créer un dossier `Api/` pour tous les points d'entrée HTTP
- Créer un dossier `Src/` avec sous-dossiers : `Models/`, `Repositories/`, `Services/`, `Validators/`, `Utils/`
- Placer les DTOs dans `Src/Models/ModelsDTO/`
- Un fichier API = un endpoint

### Conventions de nommage strictes

- Fichiers API : `camelCase.php`
- Classes : `PascalCase.php`
- Méthodes et variables : `camelCase`
- Propriétés SQL : `snake_case`
- Suffixes obligatoires : `Repository`, `Service`, `Validator`, `DTO`, `Factory`

### Architecture imposée

- Flux obligatoire : API → Validator → Service → Repository → Model
- Pas de requête SQL en dehors des Repositories
- Pas de logique métier dans les API Files
- Pas de logique métier dans les Repositories
- Models sans logique, uniquement propriétés

### Code obligatoire

- Typage systématique : paramètres, retours, propriétés
- Namespace PSR-4 : `namespace App\FolderName;`
- Autoload Composer dans chaque fichier API
- Headers CORS dans chaque fichier API
- Gestion des OPTIONS
- Validation avant toute opération de création/modification

### Base de données obligatoire

- Requêtes préparées uniquement
- `bindParam` pour tous les paramètres
- `PDO::FETCH_CLASS` pour peupler les Models
- Pattern Singleton pour connexion via `Database::getConnection()`
- Méthode privée `getPdo()` dans chaque Repository

### Sécurité obligatoire

- Hash des mots de passe avec `password_hash()`
- Vérification avec `password_verify()`
- Exclusion des données sensibles dans les DTOs
- Vérification d'authentification pour actions sensibles

### Retours obligatoires

- Services : retourner `['success' => bool, 'message' => string, 'data' => mixed]`
- Repositories : retourner objets Model ou `null`
- Validators : retourner tableau d'erreurs vide ou avec erreurs
- API : retourner JSON systématiquement

### Interdictions

- Ne jamais faire de requête SQL en dehors des Repositories
- Ne jamais retourner de Model directement depuis un Service (utiliser DTO)
- Ne jamais concaténer de valeurs dans les requêtes SQL
- Ne jamais retourner de password dans les réponses API
- Ne jamais oublier les headers CORS
- Ne jamais oublier le typage
