# STANDARD FRONTEND

## 1. STRUCTURE FRONTEND

### Arborescence des dossiers

```
pages/
  ├── index.html
  ├── products.html
  ├── cart.html
  ├── register.html
  ├── blog.html
  ├── login.html
  └── ect....

assets/
  ├── css/
  │   ├── custom.css
  │   └── input.css
  ├── js/
  │   ├── components/
  │   │   ├── navbar.js
  │   │   ├── footer.js
  │   │   ├── productCard.js
  │   │   ├── loginModal.js
  │   │   ├── cartWidget.js
  │   │   └── newsletter.js
  │   ├── pages/
  │   │   ├── home.js
  │   │   ├── products.js
  │   │   ├── productDetail.js
  │   │   ├── cart.js
  │   │   ├── checkout.js
  │   │   └── account.js
  │   └── utils/
  │       ├── auth.js
  │       ├── storage.js
  │       └── helpers.js
  ├── components/
  │   ├── navbar.html
  │   ├── footer.html
  │   ├── productCard.html
  │   ├── loginModal.html
  │   └── cartWidget.html
  └── images/
      ├── logo.png
      └── ... autres images
```

### Organisation des fichiers HTML

- Un fichier HTML par page dans le dossier `pages/`
- Templates de composants dans `assets/components/`
- Templates HTML natifs avec balise `<template>`
- Nommage en camelCase
- Structure sémantique et claire

### Organisation CSS

- Un seul fichier CSS custom : `assets/css/custom.css`
- shadcn/ui utilisé via CDN
- Bootstrap utilisé via CDN
- Bootstrap Icons via CDN
- Google Fonts via CDN
- CSS custom pour les surcharges et styles spécifiques

### Organisation JavaScript

- Modules ES6 avec `type="module"`
- Séparation en 3 dossiers :
  - `components/` : composants réutilisables qui chargent templates HTML
  - `pages/` : scripts spécifiques à chaque page
  - `utils/` : fonctions utilitaires
- Un fichier par fonctionnalité ou composant
- Exports nommés ou par défaut selon contexte

### Emplacement des assets

- CSS : `assets/css/`
- JS : `assets/js/`
- Templates HTML : `assets/components/`
- Images : `assets/images/`
- Chemins relatifs depuis les pages : `../assets/`

## 2. CONVENTIONS DE NOMMAGE FRONTEND

### Fichiers HTML

- camelCase : `index.html`, `products.html`
- Un nom par fonctionnalité : `checkout.html`, `favorites.html`
- Templates dans `assets/components/` : `navbar.html`, `productCard.html`

### Fichiers CSS

- camelCase : `custom.css`, `input.css`

### Fichiers JS

- camelCase : `productCard.js`, `loginModal.js`
- Un fichier JS par composant dans `components/`

### Variables JavaScript

- camelCase : `cartCount`, `isAuth`, `productId`, `templateObjects`
- Constantes en UPPERCASE : `API_BASE_URL`

### Fonctions JavaScript

- camelCase : `renderNavbar()`, `loadProducts()`, `attachEventListeners()`
- Préfixes courants :
  - `render` : pour le rendu de composants
  - `load` : pour le chargement de données
  - `loadTemplate` : pour le chargement de templates HTML
  - `attach` : pour les écouteurs d'événements
  - `create` : pour la création d'éléments
  - `get`, `set` : pour les getters/setters

### Classes JavaScript

- PascalCase : `ApiClient`, `ProductApi`

### Classes CSS

- camelCase ou classes Bootstrap
- Exemples : `.containerProfile`, `.customBtn`

### IDs HTML

- camelCase : `#navbar`, `#productList`, `#modalContainer`

### Dossiers

- Minuscules simples : `pages/`, `components/`, `utils/`, `css/`, `images/`

## 3. ARCHITECTURE FRONTEND

### Organisation HTML

- Doctype HTML5
- Structure sémantique avec balises HTML5
- Sections identifiées par `id` pour injection dynamique
- Bootstrap pour la grille et composants UI
- Scripts module en fin de `<body>`

Exemple :

```html
<!doctype html>
<html lang="fr">
  <head>
    <!-- meta, title, fonts, CSS -->
  </head>
  <body>
    <nav id="navbar"></nav>
    <main>
      <!-- contenu -->
    </main>
    <footer id="footer"></footer>
    <script type="module" src="../assets/js/pages/home.js"></script>
  </body>
</html>
```

### Organisation CSS

- Bootstrap via CDN
- Un fichier `custom.css` pour :
  - Surcharges Bootstrap
  - Classes utilitaires personnalisées
  - Styles spécifiques au projet
- Organisation du CSS :
  - Styles généraux en premier
  - Surcharges Bootstrap
  - Classes utilitaires
  - Composants spécifiques

### Organisation JS

#### Séparation composants/pages

- **Components** : composants réutilisables qui chargent des templates HTML
- **Pages** : scripts dédiés à une page spécifique
- **Utils** : fonctions utilitaires transversales
- **Templates HTML** : fichiers `.html` dans `assets/components/` avec balises `<template>`

#### Structure d'un fichier page

```javascript
// Imports
import { renderNavbar } from "../components/navbar.js";
import { loadProducts } from "../utils/helpers.js";

// Fonction init
async function init() {
  await renderNavbar();
  await loadData();
  attachEventListeners();
}

// Fonctions de chargement de données
async function loadData() {
  const response = await fetch("api-url.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ action: "getData" }),
  });
  const result = await response.json();
}

// Gestion des événements
function attachEventListeners() {}

// Initialisation
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
```

#### Structure d'un composant

```javascript
// Imports
import { auth } from "../utils/auth.js";

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
export async function renderComponent() {
  await loadTemplate("../components/component.html");

  const element = document.getElementById("component");
  if (!element) return;

  const clone = templateObjects["componentTemplate"].cloneNode(true);
  element.appendChild(clone);
}
```

#### Structure d'un template HTML

```html
<template id="productCard">
  <div class="card">
    <img src="" alt="Product" class="productImage" />
    <h3 class="productName"></h3>
    <p class="productPrice"></p>
    <button class="btn btn-primary addToCart">Ajouter</button>
  </div>
</template>
```

#### Structure Utils

```javascript
export const utilName = {
  method1() {},
  method2() {},
};
```

### Scripts par page

- Un fichier JS principal par page dans `pages/`
- Import des composants nécessaires
- Bootstrap JS via CDN chargé avant le script custom
- Ordre : Bootstrap Bundle → Script page module

### Organisation du DOM

- Sections identifiées par ID pour injection : `#navbar`, `#footer`, `#productList`
- IDs pour éléments interactifs
- Classes Bootstrap pour styles
- `dataset` pour stocker des données : `data-product-id`

## 4. RÈGLES DE CODE FRONTEND

### HTML

#### Structure

- Doctype HTML5 : `<!doctype html>`
- Lang : `<html lang="fr">`
- Viewport responsive : `<meta name="viewport" content="width=device-width, initial-scale=1.0">`

#### Indentation

- 2 espaces
- Balises auto-fermantes avec `/>`

#### Organisation

- HEAD :
  - Meta charset et viewport
  - Title
  - Preconnect fonts
  - CDN CSS (Bootstrap, Icons, Fonts)
  - CSS custom
- BODY :
  - Navbar injectée via JS
  - Contenu principal
  - Footer injecté via JS
  - Scripts en fin de body
  - Scripts avec `type="module"`

### CSS

#### Organisation

- Styles généraux (body, fonts)
- Surcharges Bootstrap (btn, colors)
- Classes utilitaires personnalisées
- Styles de composants
- Animations et transitions

#### Structure

- Sélecteurs simples
- Classes Bootstrap surchargées avec `!important` si nécessaire
- Classes utilitaires préfixées ou descriptives

#### Séparation des fichiers

- Un seul fichier custom

### JavaScript

#### Style d'écriture

- ES6+ : async/await, arrow functions, template literals
- Modules ES6 : import/export
- camelCase pour variables et fonctions
- Pas de point-virgule obligatoire (dépend du style)
- Template literals pour HTML : `` `<div></div>` ``
- Indentation de 2 espaces

#### Organisation du code

- Imports en haut
- Déclarations de fonctions
- Fonction `init()` comme point d'entrée
- Initialisation en bas avec `DOMContentLoaded`
- Déclarations d'objets templates en haut

#### Manipulation du DOM

- `document.getElementById()` pour sélection
- `element.innerHTML` ou `appendChild()` pour injection
- `document.addEventListener()` pour les événements globaux
- Event delegation pour les événements dynamiques
- `e.target.closest()` pour remonter au parent
- `dataset` pour stocker des données : `data-product-id`
- `cloneNode(true)` pour templates

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

#### Appels API

- `fetch()` natif
- Méthode POST
- Headers JSON :
  ```javascript
  headers: {
      'Content-Type': 'application/json'
  }
  ```
- Body stringifié : `JSON.stringify(data)`
- `await response.json()` pour récupération
- Pas de couche d'abstraction

#### Gestion asynchrone

- `async/await` pour toutes les opérations asynchrones
- `try/catch` pour la gestion d'erreurs
- Retour de résultats via objets : `{ success: true, data: ... }`

## 5. BONNES PRATIQUES FRONTEND

### Séparation CSS / JS / HTML

- HTML : structure et contenu statique uniquement
- CSS : styles et présentation
- JS : logique, interactions, injection dynamique
- Pas de styles inline dans le HTML
- Pas de HTML statique répété (utiliser des composants JS)

### Organisation des pages

- Une page = un fichier HTML + un fichier JS dans `pages/`
- Les composants sont injectés dynamiquement
- Navigation via liens relatifs : `/pages/products.html`
- Paramètres d'URL : `product-detail.html?id=123`

### Gestion des scripts

- Un script module par page
- Import des composants nécessaires
- Bootstrap JS via CDN chargé avant le script custom
- Ordre : Bootstrap Bundle → Script page

### Organisation des composants

- Templates HTML dans `assets/components/`
- Chargement dynamique via `fetch()` + `DOMParser()`
- Un fichier JS par composant dans `assets/js/components/`
- Export d'une fonction `render{ComponentName}()`
- Clone avec `cloneNode(true)` avant manipulation
- Vérification de l'existence de l'élément avant injection
- Composants autonomes et réutilisables

### Gestion de l'état

- localStorage pour persistance : tokens, user, favorites
- Module `storage.js` pour abstraction localStorage
- Module `auth.js` pour gestion authentification
- Pas de state management complexe

### Appels API

- `fetch()` natif direct dans les pages/composants
- Méthode POST avec JSON
- Headers `Content-Type: application/json`
- Body avec `JSON.stringify()`
- Pas de couche d'abstraction API
- `async/await` obligatoire

### Responsive

- Bootstrap grid system
- Classes responsive Bootstrap : `d-none d-md-block`
- Mobile-first

## 6. RÈGLES À IMPOSER À L'AGENT FRONTEND

### Structure obligatoire

- Toujours respecter l'arborescence `assets/js/{components,pages,utils}` et `assets/components/`
- Un fichier HTML = un fichier JS dans `pages/`
- Tous les HTML dans le dossier `pages/`
- Templates HTML dans `assets/components/`

### Modules ES6

- Toujours utiliser `import/export`
- Script avec `type="module"`
- Pas de scripts globaux multiples

### Bootstrap

- Toujours utiliser Bootstrap 5.3+
- CDN pour Bootstrap CSS et JS
- Bootstrap Icons pour les icônes
- Ne jamais réinventer ce que Bootstrap propose

### Nommage

- Fichiers HTML : camelCase
- Fichiers JS : camelCase
- Variables/fonctions : camelCase
- Classes : PascalCase
- Constantes : UPPERCASE

### Organisation du code

- Imports en haut
- Point d'entrée : fonction `init()`
- Initialisation avec `DOMContentLoaded`
- Toujours vérifier l'existence des éléments DOM avant manipulation

### Composants

- Un composant = un fichier HTML template + un fichier JS
- Templates HTML natifs avec `<template id="...">`
- Chargement via `fetch()` + `DOMParser()`
- Clone avec `cloneNode(true)`
- Export d'une fonction `render{Name}()`
- Toujours retourner si l'élément d'injection n'existe pas

### API

- `fetch()` natif direct
- Pas de couche d'abstraction
- Méthode POST avec JSON
- Headers et body à chaque appel

### Utilitaires

- Fonctions helpers dans `utils/helpers.js`
- Auth dans `utils/auth.js`
- Storage dans `utils/storage.js`
- Export const avec méthodes

### Indentation

- 2 espaces pour HTML, CSS, JS
- Pas de tabs

### CSS

- Un seul fichier `custom.css`
- Surcharges Bootstrap en premier
- Classes utilitaires ensuite
- Styles de composants en dernier

### Gestion des événements

- Event delegation pour éléments dynamiques
- `e.preventDefault()` et `e.stopPropagation()` si nécessaire
- `dataset` pour passer des données

### Async/Await

- Toujours utiliser async/await
- Pas de `.then()/.catch()`
- try/catch pour gestion d'erreurs

### Chemins

- Relatifs depuis `pages/` : `../assets/`
- Absolus pour navigation : `/pages/products.html`

### Ne jamais

- Mélanger HTML/CSS/JS dans un même fichier
- Utiliser jQuery
- Utiliser des scripts inline
- Créer des variables globales
- Dupliquer du code (créer un composant ou une fonction)
- Utiliser des classes ES6 pour les composants (fonctions simples)
- Créer une couche d'abstraction API
