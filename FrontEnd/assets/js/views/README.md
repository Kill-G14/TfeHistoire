# Views - Vues SPA

Ce dossier contient toutes les vues de l'application SPA. Chaque vue correspond à une route définie dans `app.js`.

## Structure d'une vue

Chaque vue doit exporter :

- **meta** : Objet contenant `title` et `description`
- **mount(container, params)** : Fonction appelée lors du chargement de la vue
- **unmount()** : Fonction appelée avant de quitter la vue
- **default** : Export par défaut avec `{ mount, unmount, meta }`

## Exemple

```javascript
// views/home.js

export const meta = {
  title: "Accueil - EuroFêtes",
  description: "Description de la page",
};

export async function mount(container, params) {
  // 1. Charger le template
  // 2. Injecter dans le conteneur
  // 3. Charger les données
  // 4. Attacher les event listeners
  // 5. S'abonner au state
}

export async function unmount() {
  // 1. Nettoyer les event listeners
  // 2. Désabonner du state
  // 3. Annuler les requêtes
}

export default { mount, unmount, meta };
```

## Vues disponibles

- **home.js** : Page d'accueil avec liste d'événements
- **createEvent.js** : Formulaire de création d'événement
- **profile.js** : Profil utilisateur
- **map.js** : Carte interactive

## Bonnes pratiques

1. Toujours nettoyer les event listeners dans `unmount()`
2. Désabonner des changements d'état dans `unmount()`
3. Utiliser `appState` pour l'état global
4. Utiliser les Managers pour les appels API
5. Vérifier l'authentification avec `appState.get('isAuthenticated')`
