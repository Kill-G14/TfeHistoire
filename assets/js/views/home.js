// Vue Home - Page d'accueil avec liste d'événements

import { renderEventCards } from "../components/eventCard.js";
import { showEventDetail } from "../components/eventDetail.js";
import { helpers } from "../utils/helpers.js";
import { filters } from "../utils/filters.js";
import { appState } from "../store/appState.js";
import EventManager from "../managers/EventManager.js";
import { loadTemplate } from "../utils/templateLoader.js";

// Métadonnées de la vue
export const meta = {
  title: "MemoriaEventia - Événements historiques d'Europe",
  description:
    "Découvrez et participez aux plus grandes célébrations historiques à travers l'Europe",
};

// Template HTML
const templateObjects = {};

// Variables locales de la vue
let allEvents = [];
let filteredEvents = [];
let unsubscribe = null;

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Charger le template
  Object.assign(
    templateObjects,
    await loadTemplate("assets/templates/views/home.html"),
  );

  // Injecter le template
  const clone = templateObjects["homeView"].cloneNode(true);
  container.innerHTML = "";
  container.appendChild(clone);

  // S'abonner aux changements d'état AVANT de charger
  unsubscribe = appState.subscribe("events", handleEventsChange);

  // Charger les données (déclenche automatiquement l'affichage via l'abonnement)
  await loadEvents();

  // Populer les filtres
  filters.populateAllFilters(allEvents);

  // Attacher les événements de filtrage
  filters.attachFilterListeners({}, applyFilters);
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Désabonner des changements d'état
  if (unsubscribe) {
    unsubscribe();
  }

  // Nettoyer les variables
  allEvents = [];
  filteredEvents = [];
}

// Charger les événements
async function loadEvents() {
  const result = await EventManager.getAll();

  if (result.success) {
    allEvents = result.data;
    filteredEvents = allEvents;
    appState.set("events", allEvents);
  } else {
    helpers.showToast("Erreur lors du chargement des événements", "error");
    allEvents = [];
    filteredEvents = [];
  }
}

// Afficher les événements
async function displayEvents() {
  await renderEventCards(filteredEvents, "eventsList", handleEventClick);
}

// Gérer le clic sur un événement
function handleEventClick(event) {
  showEventDetail(event);
}

// Appliquer les filtres
function applyFilters() {
  filteredEvents = filters.filterEvents(allEvents);
  displayEvents();
}

// Gérer les changements d'événements
function handleEventsChange(events) {
  allEvents = events;
  filteredEvents = events;
  displayEvents();
}

// Export par défaut
export default { mount, unmount, meta };
