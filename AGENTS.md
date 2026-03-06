# STANDARDS FRONTEND & BACKEND

### TEST AGENT

Avant toute réponse, l'agent doit écrire :

AGENTS_OK

Code simple pas trop, voir pas d'abstraction ou de couches d'abstraction
Concidére que je suis un Junior Dev donc n'utilise pas de technique trop pousser a moin que je te le demande

---

# PARTIE 1 : STANDARD FRONTEND

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
  │   ├── managers/
  │   │   ├── AuthManager.js
  │   │   ├── EventManager.js
  │   │   ├── FavoriteManager.js
  │   │   ├── OrderManager.js
  │   │   └── TicketManager.js
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

### Guideslines

- Lire Guidelines.md pour avoir une cohérence visuelle et structurelle pour le porjet frontend

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
- Séparation en 4 dossiers :
  - `components/` : composants réutilisables qui chargent templates HTML
  - `pages/` : scripts spécifiques à chaque page (interactions UI uniquement)
  - `managers/` : logique des appels API vers le backend
  - `utils/` : fonctions utilitaires (formatage, storage, auth)
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

#### Séparation composants/pages/managers

- **Components** : composants réutilisables qui chargent des templates HTML
- **Pages** : scripts dédiés à une page spécifique (interactions UI uniquement)
- **Managers** : logique des appels API vers le backend
- **Utils** : fonctions utilitaires transversales
- **Templates HTML** : fichiers `.html` dans `assets/components/` avec balises `<template>`

#### Structure d'un fichier page

```javascript
// Imports
import { renderNavbar } from "../components/navbar.js";
import EventManager from "../managers/EventManager.js";
import { helpers } from "../utils/helpers.js";

// Fonction init
async function init() {
  await renderNavbar();
  await loadData();
  attachEventListeners();
}

// Fonctions de chargement de données
async function loadData() {
  const result = await EventManager.getAll();
  if (result.success) {
    displayData(result.data);
  } else {
    helpers.showToast(result.message, "error");
  }
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

- Tous les appels `fetch()` doivent être dans les **Managers**
- Les pages ne font **jamais** de `fetch()` directement
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

Utilisation dans une page :

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

- **Managers** : tous les appels `fetch()` sont centralisés dans les managers
- **Pages** : utilisent les managers, ne font jamais de `fetch()` directement
- Un manager par domaine (Auth, Event, Favorite, Order, Ticket)
- Méthodes avec paramètres clairs
- Retour standardisé : `{ success: boolean, message: string, data?: any }`
- Gestion des erreurs dans les managers
- `async/await` obligatoire

Exemple :

```javascript
// Dans la page
import EventManager from "../managers/EventManager.js";

const result = await EventManager.getAll();
if (result.success) {
  // Traiter les données
}
```

### Responsive

- Bootstrap grid system
- Classes responsive Bootstrap : `d-none d-md-block`
- Mobile-first

## 6. RÈGLES À IMPOSER À L'AGENT FRONTEND

### Structure obligatoire

- Toujours respecter l'arborescence `assets/js/{components,pages,managers,utils}` et `assets/components/`
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
- Les pages utilisent les managers, jamais fetch directement
- Import dans les pages : `import ManagerName from '../managers/ManagerName.js'`

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
- Utiliser des classes ES6 pour les composants (fonctions simples uniquement)
- Utiliser des objets littéraux pour les managers (classes ES6 obligatoires)
- Faire des appels `fetch()` dans les pages (utiliser les managers)

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
