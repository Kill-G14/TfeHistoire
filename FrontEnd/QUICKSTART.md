# 🚀 Quick Start - Application SPA

Guide de démarrage et référence rapide pour l'application SPA.

---

## Démarrage

**URL :**

```
http://localhost/tfeHistoire/FrontEnd/
```

**Routes disponibles :**

- `/` - Page d'accueil (événements)
- `/create-event` - Créer un événement (🔒 auth requise)
- `/profile` - Profil utilisateur (🔒 auth requise)
- `/map` - Carte interactive

---

## Architecture SPA

### Structure clé

```
FrontEnd/
├── index.html              # Point d'entrée unique
├── .htaccess              # Routing Apache
├── assets/js/
│   ├── app.js             # Initialisation + routes
│   ├── router.js          # Navigation SPA (History API)
│   ├── views/             # Vues (mount/unmount)
│   ├── store/appState.js  # State management
│   ├── components/        # Composants réutilisables
│   ├── managers/          # Appels API
│   └── utils/             # Utilitaires
└── assets/templates/
    └── views/             # Templates HTML des vues
```

### Comment ça marche

**Navigation SPA :**

```html
<!-- Liens avec data-link -->
<a href="/profile" data-link>Mon profil</a>
```

**State Management :**

```javascript
// Mise à jour
appState.set("user", userData);

// Écoute des changements
appState.subscribe("user", (user) => {
  console.log("User changed:", user);
});
```

**Cycle de vie des vues :**

```javascript
export async function mount(container, params) {
  // Initialisation, chargement, événements
}

export async function unmount() {
  // Nettoyage, désabonnements
}
```

---

## Tests rapides

### ✅ Navigation

1. Cliquer sur les liens du header
2. Vérifier : **pas de rechargement de page**
3. Utiliser les boutons Précédent/Suivant du navigateur

### ✅ Authentification

1. Cliquer sur "Connexion"
2. Se connecter ou s'inscrire
3. Vérifier : **header mis à jour sans rechargement**

### ✅ Routes protégées

1. Taper `/create-event` dans l'URL (sans être connecté)
2. Vérifier : **redirection vers `/` avec message d'erreur**

### ✅ Console

- Ouvrir F12 → Console
- Naviguer dans l'app
- Vérifier : **aucune erreur JS**

---

## Dépannage

### ❌ Routes ne fonctionnent pas (404)

**Cause :** `.htaccess` absent ou `mod_rewrite` désactivé

**Solution :**

1. Vérifier `.htaccess` dans `FrontEnd/`
2. WAMP : Icône → Apache → Apache modules → Cocher `rewrite_module`
3. Redémarrer Apache

### ❌ Assets ne se chargent pas

**Cause :** Chemins incorrects ou cache

**Solution :**

1. Vérifier chemins : `./assets/...` dans `index.html`
2. Vider cache : `Ctrl + Shift + R`

### ❌ Authentification ne persiste pas

**Cause :** localStorage bloqué

**Solution :**

1. F12 → Application → Local Storage
2. Vérifier présence de `eurofetes_token` et `eurofetes_user`
3. Vérifier que `appState` est initialisé dans `app.js`

### ❌ Header ne se met pas à jour

**Cause :** Abonnement appState manquant

**Solution :**

1. Vérifier `app.js` : `appState.subscribe('user', handleUserChange)`
2. Console F12 pour les erreurs

---

## Créer une nouvelle vue

### 1. Créer le fichier JS

**`assets/js/views/maVue.js` :**

```javascript
export const meta = {
  title: "Ma Vue - MemoriaEventia",
  description: "Description de ma vue",
};

export async function mount(container, params) {
  container.innerHTML = `<div class="container"><h1>Ma Vue</h1></div>`;
  // Charger données, attacher événements
}

export async function unmount() {
  // Nettoyer
}

export default { mount, unmount, meta };
```

### 2. Ajouter la route

**Dans `app.js` :**

```javascript
const routes = {
  "/": () => import("./views/home.js"),
  "/ma-vue": () => import("./views/maVue.js"), // Ajouter ici
};
```

### 3. Ajouter un lien

**Dans le header ou ailleurs :**

```html
<a href="/ma-vue" data-link>Ma Vue</a>
```

---

## Bonnes pratiques

### ✅ À faire

- Utiliser `data-link` pour tous les liens internes
- Toujours nettoyer dans `unmount()`
- Centraliser les appels API dans les Managers
- Utiliser `appState` pour l'état global
- Typer tous les paramètres (voir `AGENTS.md`)

### ❌ À éviter

- Faire des appels `fetch()` directement dans les vues
- Oublier de désabonner du `appState` dans `unmount()`
- Utiliser `.html` dans les URLs
- Recharger la page (`window.location.reload()`)
- Créer des variables globales

---

## Commandes utiles

**Vérifier les erreurs :**

```bash
# Console F12 → Network → Filtrer XHR
```

**Vider le cache localStorage :**

```javascript
// Dans la console
localStorage.clear();
location.reload();
```

**Inspecter l'état global :**

```javascript
// Dans la console
console.log(window.appState.state);
```

---

## Documentation complète

- **`AGENTS.md`** - Standards frontend/backend complets
- **`Guidelines.md`** - Guidelines de design
- **`assets/js/views/README.md`** - Guide détaillé des vues

---

**Bon développement ! 🎉**
