# MemoriaEventia - SPA

Application web Single Page Application (SPA) de réservation d'événements historiques européens. Version moderne en vanilla JavaScript avec routeur et Bootstrap 5.

## 🎯 Fonctionnalités

- **SPA Navigation** : Navigation fluide sans rechargement de page
- **Liste d'événements** : Affichage de tous les événements historiques d'Europe
- **Filtres avancés** : Recherche par nom, filtrage par pays et catégorie
- **Détails d'événement** : Vue détaillée avec réservation de tickets
- **Création d'événement** : Formulaire complet pour créer de nouveaux événements
- **Authentification** : Système de connexion/inscription
- **Profil utilisateur** : Gestion des réservations et événements créés
- **Carte interactive** : Visualisation géographique
- **State Management** : Gestion d'état centralisée avec appState

## 🛠️ Technologies utilisées

- **HTML5** : Structure (index.html unique)
- **CSS3** : Styles personnalisés + animations de transitions
- **JavaScript ES6+** : Modules, async/await, arrow functions, Router SPA
- **Bootstrap 5.3** : Framework CSS
- **Bootstrap Icons** : Icônes
- **History API** : Navigation SPA sans rechargement
- **LocalStorage** : Persistance des données

## 📁 Structure du projet (SPA)

```
index.html           # SEUL fichier HTML de l'application

assets/
  ├── css/
  │   └── custom.css       # Styles personnalisés + transitions SPA
  ├── js/
  │   ├── app.js               # Point d'entrée principal
  │   ├── router.js            # Routeur SPA (History API)
  │   ├── components/
  │   │   ├── navbar.js        # Navbar persistante
  │   │   ├── footer.js        # Footer persistant
  │   │   ├── eventCard.js     # Carte d'événement
  │   │   ├── loginModal.js    # Modal de connexion
  │   │   └── eventDetail.js   # Détail d'événement
  │   ├── views/
  │   │   ├── home.js          # Vue accueil
  │   │   ├── createEvent.js   # Vue création événement
  │   │   ├── profile.js       # Vue profil
  │   │   └── map.js           # Vue carte
  │   ├── managers/
  │   │   ├── EventManager.js  # Appels API événements
  │   │   ├── AuthManager.js   # Appels API auth
  │   │   └── ...
  │   ├── store/
  │   │   └── appState.js      # State management centralisé
  │   └── utils/
  │       ├── auth.js          # Gestion authentification
  │       ├── storage.js       # Gestion localStorage
  │       └── helpers.js       # Fonctions utilitaires
  └── templates/
      ├── navbar.html          # Template navbar
      ├── footer.html          # Template footer
      ├── eventCard.html       # Template carte événement
      ├── loginModal.html      # Template modal login
      ├── eventDetail.html     # Template détail événement
      └── views/
          ├── home.html        # Template vue accueil
          ├── createEvent.html # Template vue création
          ├── profile.html     # Template vue profil
          └── map.html         # Template vue carte
```

## 🚀 Installation et démarrage

### Prérequis

- Un serveur web local (WAMP, XAMPP, Live Server, etc.)
- Un navigateur web moderne compatible ES6+

### Installation

1. Cloner ou télécharger le projet
2. Placer le dossier dans le répertoire de votre serveur web
3. Ouvrir le projet avec votre serveur local

### Utilisation avec WAMP

```
c:\wamp64\www\tfeHistoire\FrontEnd\
```

Accéder à : `http://localhost/tfeHistoire/FrontEnd/`

### Utilisation avec Live Server (VS Code)

1. Installer l'extension "Live Server"
2. Ouvrir le dossier du projet
3. Clic droit sur `index.html` → "Open with Live Server"

## 📖 Guide d'utilisation

### Navigation SPA

- **URLs propres** : `/`, `/products`, `/product/123`
- **Pas de rechargement** : Navigation instantanée
- **Boutons précédent/suivant** : Fonctionnels (History API)
- **Transitions fluides** : Animations CSS entre les vues

### Routes disponibles

- `/` : Page d'accueil avec liste d'événements
- `/events` : Liste complète des événements
- `/event/:id` : Détails d'un événement spécifique
- `/create-event` : Création d'événement (authentification requise)
- `/profile` : Profil utilisateur (authentification requise)
- `/map` : Carte interactive des événements

### Fonctionnalités principales

#### Voir les événements

1. Accéder à la page d'accueil (`/`)
2. Utiliser les filtres pour affiner la recherche
3. Cliquer sur une carte pour voir les détails (navigation SPA)

#### Réserver un événement

1. Cliquer sur "Voir détails" d'un événement
2. Ajuster la quantité de tickets
3. Cliquer sur "Réserver maintenant"
4. Se connecter si nécessaire

#### Créer un événement

1. Se connecter avec le bouton "Connexion"
2. Cliquer sur "Créer un événement"
3. Remplir le formulaire
4. Cliquer sur "Publier l'événement"

#### S'authentifier

1. Cliquer sur "Connexion"
2. Choisir "Connexion" ou "Inscription"
3. Remplir les champs
4. La navbar se met à jour automatiquement (state management)

## 🎨 Personnalisation

### Couleurs

Modifier les variables CSS dans `assets/css/custom.css` :

```css
:root {
  --color-primary: #1a3a52;
  --color-accent: #c9a961;
  --color-background: #f8f9fa;
}
```

### Transitions SPA

Modifier les animations de transitions dans `assets/css/custom.css` :

```css
#app {
  transition: opacity 0.3s ease-in-out;
}

#app.loading {
  opacity: 0.5;
  pointer-events: none;
}
```

### Événements

Les événements sont stockés dans le store centralisé `appState` et en localStorage.

## 🔧 Architecture technique (SPA)

### app.js (Point d'entrée)

- Définition des routes
- Instanciation du routeur
- Chargement des composants persistants (navbar, footer)
- Initialisation de l'application

### router.js (Routeur SPA)

- Gestion de l'historique (History API)
- Correspondance URL ↔ Vue
- Lazy loading des vues
- Support des paramètres dynamiques (`:id`)
- Mise à jour des métadonnées (title, description)

### Views (Vues)

Chaque vue suit le pattern :

- Export `meta` : métadonnées (title, description)
- Export `mount(container, params)` : montage de la vue
- Export `unmount()` : démontage et nettoyage
- Chargement des données via Managers
- Gestion locale des event listeners

### Components (Composants)

Composants réutilisables :

- **Persistants** : navbar, footer (chargés une seule fois)
- **Dynamiques** : cards, modals (rechargés à la demande)
- Chargement de templates HTML
- Gestion d'événements
- Abonnement au state

### State Management (Store)

- **appState.js** : Store centralisé
- Pattern subscribe/notify
- État global : user, cart, events, favorites
- Synchronisation automatique des composants
- Persistance dans localStorage

### Managers

- Centralisation des appels API
- Retour standardisé : `{ success, message, data }`
- Gestion des erreurs
- Un manager par domaine métier

### Utilitaires

- **auth.js** : Gestion de l'authentification
- **storage.js** : Abstraction du localStorage
- **helpers.js** : Fonctions utilitaires (formatage, filtres, toasts)

## 📱 Responsive Design

L'application est entièrement responsive grâce à Bootstrap :

- Mobile first
- Breakpoints adaptés
- Grille flexible
- Navigation mobile adaptée

## 🌐 Compatibilité navigateurs

- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

Nécessite un navigateur compatible :

- ES6+ Modules
- History API
- Async/await
- DOMParser

## 📝 Conventions de code

- **Nommage** : camelCase pour variables/fonctions, PascalCase pour classes
- **Indentation** : 2 espaces
- **Modules** : ES6 import/export + import() dynamique
- **Async** : async/await (pas de .then())
- **DOM** : querySelector/getElementById
- **Navigation** : History API + data-link
- **URLs** : Sans .html (`/products`, `/product/123`)

## 🔄 Cycle de vie d'une vue

1. **Navigation** : Utilisateur clique sur un lien avec `data-link`
2. **Unmount** : La vue précédente est démontée (nettoyage)
3. **Lazy load** : La nouvelle vue est chargée via `import()`
4. **Mount** : La nouvelle vue est montée (template + données + événements)
5. **Update** : Mise à jour des métadonnées (title, description)

## 🤝 Contribution

Respecter les conventions définies dans `AGENTS.md` (architecture SPA) pour toute modification.

## 🆕 Changements SPA vs Multi-pages

- ✅ Un seul fichier HTML au lieu de plusieurs
- ✅ Routeur JavaScript pour la navigation
- ✅ Lazy loading des vues
- ✅ State management centralisé
- ✅ Transitions fluides sans rechargement
- ✅ URLs propres sans .html
- ✅ Support boutons précédent/suivant
- ✅ Métadonnées dynamiques

## 📄 Licence

Projet éducatif - Libre d'utilisation

## 🎓 Crédits

Images : Unsplash
Icons : Bootstrap Icons
Framework : Bootstrap 5.3
