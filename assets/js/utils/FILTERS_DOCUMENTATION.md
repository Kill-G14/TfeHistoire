# Documentation du Module Filters

## Description

Le module `filters.js` est un module JavaScript dédié exclusivement à la gestion des filtres pour les événements. Il centralise toute la logique de filtrage, la gestion de l'état des filtres, et les interactions avec les éléments du DOM.

## Emplacement

`assets/js/utils/filters.js`

## Utilisation

### Import du module

```javascript
import { filters } from "../utils/filters.js";
```

## Fonctionnalités

### 1. Gestion de l'état des filtres

Le module maintient un état interne avec tous les filtres actifs :

```javascript
filters.state = {
  searchTerm: "", // Terme de recherche
  country: "all", // Pays sélectionné
  category: "all", // Catégorie sélectionnée
  dateFrom: null, // Date de début
  dateTo: null, // Date de fin
  priceMin: null, // Prix minimum
  priceMax: null, // Prix maximum
};
```

### 2. Méthodes principales

#### `filterEvents(events)`

Filtre un tableau d'événements selon les critères actuels de `filters.state`.

```javascript
const filteredEvents = filters.filterEvents(allEvents);
```

#### `filterEventsCustom(events, searchTerm, country, category)`

Filtre avec des paramètres personnalisés sans modifier l'état.

```javascript
const filtered = filters.filterEventsCustom(
  events,
  "concert",
  "France",
  "Musique",
);
```

#### `updateFilter(filterName, value)`

Met à jour un filtre spécifique.

```javascript
filters.updateFilter("country", "France");
filters.updateFilter("searchTerm", "concert");
```

#### `updateFilters(filtersObject)`

Met à jour plusieurs filtres en une seule fois.

```javascript
filters.updateFilters({
  country: "France",
  category: "Festival",
  priceMax: 50,
});
```

#### `reset()`

Réinitialise tous les filtres à leur valeur par défaut.

```javascript
filters.reset();
```

### 3. Gestion des options de filtres

#### `getUniqueCountries(events)`

Retourne la liste triée des pays uniques.

```javascript
const countries = filters.getUniqueCountries(allEvents);
// ['Allemagne', 'Espagne', 'France', 'Italie', ...]
```

#### `getUniqueCategories(events)`

Retourne la liste triée des catégories uniques.

```javascript
const categories = filters.getUniqueCategories(allEvents);
// ['Carnaval', 'Festival', 'Reconstitution', ...]
```

#### `getUniqueCities(events)`

Retourne la liste triée des villes uniques.

```javascript
const cities = filters.getUniqueCities(allEvents);
```

### 4. Population automatique des filtres

#### `populateSelect(selectId, options, defaultText)`

Remplit un élément `<select>` avec des options.

```javascript
filters.populateSelect("countrySelect", ["France", "Italie"], "Tous les pays");
```

#### `populateAllFilters(events, config)`

Remplit automatiquement tous les filtres à partir des événements.

```javascript
filters.populateAllFilters(allEvents, {
  countrySelectId: "countrySelect",
  categorySelectId: "categorySelect",
  countryText: "Tous les pays",
  categoryText: "Toutes les catégories",
});
```

### 5. Gestion des événements DOM

#### `attachFilterListeners(config, onFilterChange)`

Attache automatiquement les event listeners aux éléments de filtres.

```javascript
filters.attachFilterListeners(
  {
    searchInputId: "searchInput",
    countrySelectId: "countrySelect",
    categorySelectId: "categorySelect",
  },
  () => {
    // Callback appelé à chaque changement de filtre
    const filtered = filters.filterEvents(allEvents);
    displayEvents(filtered);
  },
);
```

Configuration par défaut :

```javascript
{
  searchInputId: 'searchInput',
  countrySelectId: 'countrySelect',
  categorySelectId: 'categorySelect',
  priceMinId: 'priceMin',
  priceMaxId: 'priceMax',
  dateFromId: 'dateFrom',
  dateToId: 'dateTo'
}
```

### 6. Utilitaires

#### `getActiveFiltersCount()`

Retourne le nombre de filtres actifs.

```javascript
const count = filters.getActiveFiltersCount();
// 3
```

#### `hasActiveFilters()`

Vérifie si des filtres sont actifs.

```javascript
if (filters.hasActiveFilters()) {
  console.log("Des filtres sont appliqués");
}
```

#### `getCurrentFilters()`

Retourne une copie de l'état actuel des filtres.

```javascript
const currentState = filters.getCurrentFilters();
```

#### `applyFilters(savedFilters)`

Applique des filtres sauvegardés.

```javascript
filters.applyFilters({
  country: "France",
  category: "Festival",
});
```

## Exemple complet d'utilisation

```javascript
import { filters } from "../utils/filters.js";

let allEvents = [];
let filteredEvents = [];

async function init() {
  // Charger les événements
  allEvents = await loadEvents();

  // Populer les filtres
  filters.populateAllFilters(allEvents);

  // Attacher les listeners avec callback
  filters.attachFilterListeners({}, applyFilters);

  // Afficher les événements
  displayEvents();
}

function applyFilters() {
  // Filtrer selon l'état actuel
  filteredEvents = filters.filterEvents(allEvents);
  displayEvents();
}

function displayEvents() {
  // Afficher les événements filtrés
  renderEventCards(filteredEvents);
}

// Réinitialiser les filtres
document.getElementById("resetBtn").addEventListener("click", () => {
  filters.reset();
  applyFilters();
});

init();
```

## Avantages du module

1. **Centralisation** : Toute la logique de filtrage est au même endroit
2. **Réutilisabilité** : Peut être utilisé sur n'importe quelle page
3. **Maintenabilité** : Facile à modifier et étendre
4. **État centralisé** : Un seul objet state pour tous les filtres
5. **Simplicité** : API claire et intuitive
6. **Extensibilité** : Facile d'ajouter de nouveaux types de filtres

## Extension future

Pour ajouter un nouveau filtre :

1. Ajouter la propriété dans `state`
2. Ajouter la logique de filtrage dans `filterEvents()`
3. Ajouter l'élément HTML correspondant
4. Ajouter le listener dans `attachFilterListeners()`

Exemple pour ajouter un filtre par nombre de places :

```javascript
// Dans state
availableTicketsMin: null;

// Dans filterEvents()
const matchesTickets =
  !this.state.availableTicketsMin ||
  event.availableTickets >= this.state.availableTicketsMin;

// Dans attachFilterListeners()
const ticketsMin = document.getElementById("ticketsMin");
if (ticketsMin) {
  ticketsMin.addEventListener("input", (e) => {
    this.updateFilter("availableTicketsMin", e.target.value);
    callback();
  });
}
```
