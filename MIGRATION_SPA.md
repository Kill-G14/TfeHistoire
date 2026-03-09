# Guide de Migration vers SPA

**Date :** 09/03/2026  
**Statut :** Documentation prête - Implémentation à venir

---

## 📖 Vue d'ensemble

Ce document décrit la migration de l'architecture **multi-pages** actuelle vers une architecture **SPA (Single Page Application)**.

### Pourquoi migrer vers SPA ?

✅ **Avantages :**

- Navigation instantanée sans rechargement
- Transitions fluides entre les vues
- Meilleure expérience utilisateur
- State management centralisé
- URLs propres sans `.html`
- Support boutons précédent/suivant du navigateur

⚠️ **Inconvénients :**

- Complexité accrue (routeur, state management)
- Courbe d'apprentissage pour développeur junior
- SEO nécessite des métadonnées dynamiques

---

## 🗂️ Changements de structure

### Avant (Multi-pages)

```
FrontEnd/
├── pages/
│   ├── index.html
│   ├── events.html
│   ├── profile.html
│   └── ...
└── assets/
    ├── js/
    │   ├── pages/           # Scripts par page
    │   ├── components/
    │   └── managers/
    └── components/          # Templates HTML
```

### Après (SPA)

```
FrontEnd/
├── index.html               # SEUL fichier HTML
└── assets/
    ├── js/
    │   ├── app.js          # Point d'entrée
    │   ├── router.js       # Routeur SPA
    │   ├── views/          # Ex-pages (vues)
    │   ├── components/     # Composants réutilisables
    │   ├── managers/       # Appels API
    │   ├── store/          # State management
    │   └── utils/          # Utilitaires
    └── templates/
        ├── navbar.html
        ├── footer.html
        └── views/          # Templates des vues
            ├── home.html
            └── ...
```

---

## 📋 Plan de migration étape par étape

### Phase 1 : Préparation (Fichiers core)

#### 1.1 Créer index.html unique

**Localisation :** `FrontEnd/index.html`

**Contenu :**

```html
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title id="pageTitle">EuroFêtes Historiques</title>
    <meta
      name="description"
      id="pageDescription"
      content="Découvrez les événements historiques d'Europe"
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
    <!-- Navbar persistante -->
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

#### 1.2 Créer router.js

**Localisation :** `FrontEnd/assets/js/router.js`

**Contenu :** Voir AGENTS.md section "Structure du fichier router.js"

**Fonctionnalités :**

- Classe `Router` exportée
- Gestion History API (`pushState`, `popstate`)
- Correspondance pattern/URL (support `/product/:id`)
- Lazy loading des vues
- Mise à jour métadonnées
- Page 404

#### 1.3 Créer app.js

**Localisation :** `FrontEnd/assets/js/app.js`

**Contenu :** Voir AGENTS.md section "Structure du fichier app.js"

**Rôle :**

- Point d'entrée unique
- Définition des routes
- Instanciation du routeur
- Chargement composants persistants (navbar, footer)
- Initialisation globale

#### 1.4 Créer store/appState.js

**Localisation :** `FrontEnd/assets/js/store/appState.js`

**Contenu :** Voir AGENTS.md section "Structure appState.js"

**Fonctionnalités :**

- Classe `AppState` singleton
- Méthodes : `get()`, `set()`, `subscribe()`, `notify()`
- État global : `user`, `cart`, `events`, `favorites`
- Pattern subscribe/notify pour réactivité

---

### Phase 2 : Migration des vues

#### 2.1 Renommer le dossier

```bash
# Renommer pages/ en views/
mv FrontEnd/assets/js/pages FrontEnd/assets/js/views
```

#### 2.2 Adapter chaque vue

**Avant (fichier page) :**

```javascript
// pages/home.js
import { renderNavbar } from "../components/navbar.js";

async function init() {
  await renderNavbar();
  await loadData();
  attachEventListeners();
}

document.addEventListener("DOMContentLoaded", init);
```

**Après (fichier view) :**

```javascript
// views/home.js
import EventManager from "../managers/EventManager.js";
import { appState } from "../store/appState.js";

// Métadonnées
export const meta = {
  title: "Accueil - EuroFêtes",
  description: "Découvrez les événements historiques",
};

// Variables locales
let unsubscribe = null;

// Montage de la vue
export async function mount(container, params) {
  // 1. Injecter template
  container.innerHTML = `<div>...</div>`;

  // 2. Charger données
  await loadEvents();

  // 3. Attacher événements
  attachEventListeners();

  // 4. S'abonner au state
  unsubscribe = appState.subscribe("events", updateEvents);
}

// Démontage de la vue
export async function unmount() {
  // Nettoyer
  if (unsubscribe) unsubscribe();
}

// Fonctions privées
async function loadEvents() {
  /* ... */
}
function attachEventListeners() {
  /* ... */
}
function updateEvents(events) {
  /* ... */
}

// Export par défaut
export default { mount, unmount, meta };
```

#### 2.3 Vues à migrer

- ✅ `home.js` → Vue d'accueil avec liste d'événements
- ✅ `events.js` → Liste complète des événements (créer si n'existe pas)
- ✅ `eventDetail.js` → Détails d'un événement (créer)
- ✅ `createEvent.js` → Formulaire création événement
- ✅ `profile.js` → Profil utilisateur
- ✅ `myOrders.js` → Mes commandes (créer)
- ✅ `myTickets.js` → Mes billets (créer)
- ✅ `myFavorites.js` → Mes favoris (créer)
- ✅ `map.js` → Carte interactive

---

### Phase 3 : Adapter les composants

#### 3.1 Composants persistants

**navbar.js et footer.js** doivent être chargés **une seule fois** dans `app.js`.

**Modifications :**

```javascript
// components/navbar.js
export async function renderNavbar() {
  // Charger template
  await loadTemplate("/assets/templates/navbar.html");

  // Injecter
  const element = document.getElementById("navbar");
  const clone = templateObjects["navbarTemplate"].cloneNode(true);
  element.appendChild(clone);

  // Attacher événements
  attachNavbarEvents();

  // S'abonner au state (user connecté/déconnecté)
  appState.subscribe("user", updateNavbar);
}

function attachNavbarEvents() {
  // Intercepter clics pour navigation SPA
  navbar.addEventListener("click", (e) => {
    if (e.target.matches("[data-link]")) {
      e.preventDefault();
      const href = e.target.getAttribute("href");
      // Utiliser routeur ou event custom
    }
  });
}
```

#### 3.2 Mise à jour des liens

**Tous les liens internes** doivent avoir `data-link` :

```html
<!-- Navbar -->
<a href="/" data-link>Accueil</a>
<a href="/events" data-link>Événements</a>
<a href="/profile" data-link>Profil</a>
```

---

### Phase 4 : Configuration des routes

**Dans app.js :**

```javascript
const routes = {
  "/": () => import("./views/home.js"),
  "/events": () => import("./views/events.js"),
  "/event/:id": () => import("./views/eventDetail.js"),
  "/create-event": () => import("./views/createEvent.js"),
  "/profile": () => import("./views/profile.js"),
  "/my-orders": () => import("./views/myOrders.js"),
  "/my-tickets": () => import("./views/myTickets.js"),
  "/my-favorites": () => import("./views/myFavorites.js"),
  "/map": () => import("./views/map.js"),
};
```

---

### Phase 5 : Transitions CSS

**Ajouter dans custom.css :**

```css
/* Transitions SPA */
#app {
  transition: opacity 0.3s ease-in-out;
  min-height: 70vh;
}

#app.loading {
  opacity: 0.5;
  pointer-events: none;
}

#app.loaded {
  opacity: 1;
}

/* Animations optionnelles */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

#app > * {
  animation: fadeIn 0.4s ease-out;
}
```

---

### Phase 6 : Nettoyage

#### 6.1 Supprimer les anciens fichiers HTML

```bash
# Supprimer le dossier pages/ (une fois migration terminée)
rm -rf FrontEnd/pages/
```

#### 6.2 Déplacer templates

```bash
# Créer dossier templates/views si nécessaire
mkdir -p FrontEnd/assets/templates/views

# Déplacer les templates HTML (si existants)
# Ou créer nouveaux templates pour chaque vue
```

---

## 🧪 Tests à effectuer après migration

### Checklist de validation

- [ ] **Navigation** : Tous les liens fonctionnent sans rechargement
- [ ] **Boutons navigateur** : Précédent/Suivant fonctionnels
- [ ] **URLs** : Propres et sans `.html`
- [ ] **Métadonnées** : Title et description changent par route
- [ ] **State** : Persistance entre les vues
- [ ] **Authentification** : Navbar se met à jour automatiquement
- [ ] **Lazy loading** : Vues chargées à la demande
- [ ] **404** : Page d'erreur affichée pour routes inexistantes
- [ ] **Nettoyage** : Pas de fuites mémoire (unmount())
- [ ] **Responsive** : Tout fonctionne sur mobile
- [ ] **Transitions** : Animations fluides

### Tests de navigation

1. Ouvrir `/` → Vérifier accueil
2. Cliquer sur "Événements" → URL change à `/events`
3. Cliquer sur un événement → URL change à `/event/123`
4. Bouton précédent → Retour à `/events`
5. Refresh page → Reste sur la même route
6. Taper URL directement → Route chargée correctement

---

## 📚 Ressources et documentation

### Documentation mise à jour

- ✅ **AGENTS.md** : Section Frontend complètement réécrite pour SPA
- ✅ **FrontEnd/README.md** : Adapté pour SPA
- ✅ **PROJECT_STATUS.md** : Section migration ajoutée

### Fichiers de référence

- Voir `AGENTS.md` sections :
  - "Structure du fichier app.js"
  - "Structure du fichier router.js"
  - "Structure d'un fichier view"
  - "Structure appState.js"

---

## ⚠️ Points d'attention

### Pour développeur Junior

1. **Complexité accrue** : Le code est plus abstrait (routeur, state)
2. **Debugging** : Plus difficile de tracer les erreurs
3. **Cycle de vie** : Bien comprendre `mount()` et `unmount()`
4. **State management** : Penser en termes d'état global
5. **Event listeners** : Toujours nettoyer dans `unmount()`

### Pièges à éviter

❌ **Ne jamais** :

- Oublier `data-link` sur les liens internes
- Oublier `unmount()` dans une vue
- Faire des appels `fetch()` directement dans les vues
- Charger navbar/footer à chaque vue
- Utiliser `.html` dans les URLs
- Recharger la page lors de la navigation

✅ **Toujours** :

- Utiliser le routeur pour la navigation
- Nettoyer dans `unmount()`
- Passer par les Managers pour les API
- Désabonner du state dans `unmount()`
- Tester boutons précédent/suivant

---

## 🚀 Ordre d'implémentation recommandé

1. ✅ **Étape 1** : Créer fichiers core (app.js, router.js, appState.js)
2. ✅ **Étape 2** : Créer index.html unique
3. ✅ **Étape 3** : Migrer navbar et footer (persistants)
4. ✅ **Étape 4** : Migrer une première vue simple (home.js)
5. ✅ **Étape 5** : Tester navigation sur cette vue
6. ✅ **Étape 6** : Migrer les autres vues une par une
7. ✅ **Étape 7** : Ajouter transitions CSS
8. ✅ **Étape 8** : Tests complets
9. ✅ **Étape 9** : Nettoyage (supprimer anciens fichiers)

---

## 📞 Support

En cas de blocage, se référer à :

- `AGENTS.md` : Standards et exemples de code
- `FrontEnd/README.md` : Documentation SPA
- Backend reste inchangé (pas de modification nécessaire)

---

**Dernière mise à jour :** 09/03/2026
