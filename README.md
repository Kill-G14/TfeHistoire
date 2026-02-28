# EuroFêtes Historiques

Application web de réservation d'événements historiques européens. Version refactorisée en vanilla JavaScript avec Bootstrap 5.

## 🎯 Fonctionnalités

- **Liste d'événements** : Affichage de tous les événements historiques d'Europe
- **Filtres avancés** : Recherche par nom, filtrage par pays et catégorie
- **Détails d'événement** : Vue détaillée avec réservation de tickets
- **Création d'événement** : Formulaire complet pour créer de nouveaux événements
- **Authentification** : Système de connexion/inscription (mock)
- **Profil utilisateur** : Gestion des réservations et événements créés
- **Carte interactive** : Visualisation géographique (placeholder)

## 🛠️ Technologies utilisées

- **HTML5** : Structure sémantique
- **CSS3** : Styles personnalisés
- **JavaScript ES6+** : Modules, async/await, arrow functions
- **Bootstrap 5.3** : Framework CSS
- **Bootstrap Icons** : Icônes
- **LocalStorage** : Persistance des données

## 📁 Structure du projet

```
pages/
  ├── index.html           # Page d'accueil avec liste d'événements
  ├── createEvent.html     # Formulaire de création d'événement
  ├── profile.html         # Page profil utilisateur
  └── map.html             # Carte interactive

assets/
  ├── css/
  │   └── custom.css       # Styles personnalisés
  ├── js/
  │   ├── components/
  │   │   ├── header.js           # Header avec navigation
  │   │   ├── eventCard.js        # Carte d'événement
  │   │   ├── loginModal.js       # Modal de connexion
  │   │   └── eventDetail.js      # Détail d'événement
  │   ├── pages/
  │   │   ├── home.js             # Script page d'accueil
  │   │   ├── createEvent.js      # Script création d'événement
  │   │   ├── profile.js          # Script profil
  │   │   └── map.js              # Script carte
  │   └── utils/
  │       ├── auth.js             # Gestion authentification
  │       ├── storage.js          # Gestion localStorage
  │       └── helpers.js          # Fonctions utilitaires
  └── components/
      ├── header.html             # Template header
      ├── eventCard.html          # Template carte événement
      ├── loginModal.html         # Template modal login
      └── eventDetail.html        # Template détail événement
```

## 🚀 Installation et démarrage

### Prérequis

- Un serveur web local (WAMP, XAMPP, Live Server, etc.)
- Un navigateur web moderne

### Installation

1. Cloner ou télécharger le projet
2. Placer le dossier dans le répertoire de votre serveur web
3. Ouvrir le projet avec votre serveur local

### Utilisation avec WAMP

```
c:\wamp64\www\Event Booking Website\
```

Accéder à : `http://localhost/Event Booking Website/pages/index.html`

### Utilisation avec Live Server (VS Code)

1. Installer l'extension "Live Server"
2. Ouvrir le dossier du projet
3. Clic droit sur `pages/index.html` → "Open with Live Server"

## 📖 Guide d'utilisation

### Navigation

- **Événements** : Page d'accueil avec tous les événements
- **Carte** : Carte interactive (à venir)
- **Créer un événement** : Accessible uniquement connecté
- **Profil** : Accessible uniquement connecté

### Fonctionnalités principales

#### Voir les événements

1. Accéder à la page d'accueil
2. Utiliser les filtres pour affiner la recherche
3. Cliquer sur une carte pour voir les détails

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
3. Remplir les champs (mock - accepte n'importe quelles valeurs)

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

### Événements

Les événements sont stockés dans le localStorage. Pour réinitialiser :

```javascript
localStorage.removeItem("eurofetes_events");
```

Les événements par défaut sont définis dans `assets/js/pages/home.js`.

## 🔧 Architecture technique

### Composants

Chaque composant suit le pattern :

- Un fichier HTML avec `<template>` pour la structure
- Un fichier JS qui charge le template et gère la logique
- Export d'une fonction `render{ComponentName}()`

### Utilitaires

- **auth.js** : Gestion de l'authentification (mock)
- **storage.js** : Abstraction du localStorage
- **helpers.js** : Fonctions utilitaires (formatage, filtres, toasts)

### Pages

Chaque page suit le pattern :

- Import des composants nécessaires
- Fonction `init()` comme point d'entrée
- Chargement avec `DOMContentLoaded`

## 📱 Responsive Design

L'application est entièrement responsive grâce à Bootstrap :

- Mobile first
- Breakpoints adaptés
- Grille flexible

## 🌐 Compatibilité navigateurs

- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

## 📝 Conventions de code

- **Nommage** : camelCase pour variables/fonctions, PascalCase pour classes
- **Indentation** : 2 espaces
- **Modules** : ES6 import/export
- **Async** : async/await (pas de .then())
- **DOM** : querySelector/getElementById

## 🤝 Contribution

Respecter les conventions définies dans `AGENTS.md` pour toute modification.

## 📄 Licence

Projet éducatif - Libre d'utilisation

## 🎓 Crédits

Images : Unsplash
Icons : Bootstrap Icons
Framework : Bootstrap 5.3
