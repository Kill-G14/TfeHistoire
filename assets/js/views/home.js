// Vue Home - Page d'accueil avec liste d'événements

import {
  renderEventCards,
  renderEventCardsSkeleton,
} from "../components/eventCard.js";
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
let sortMode = "date-asc";

// Trie une copie de la liste (la liste d'origine du state n'est jamais modifiée)
function sortEvents(events) {
  const sorted = [...events];

  if (sortMode === "city-asc") {
    sorted.sort((a, b) => (a.city || "").localeCompare(b.city || "", "fr"));
  } else {
    // Les dates sont au format AAAA-MM-JJ : la comparaison de texte suffit
    sorted.sort((a, b) => String(a.date).localeCompare(String(b.date)));
    if (sortMode === "date-desc") sorted.reverse();
  }

  return sorted;
}

// Remet tous les filtres à zéro (état, champs affichés, liste)
function resetFilters() {
  filters.reset();

  const searchInput = document.getElementById("searchInput");
  const countrySelect = document.getElementById("countrySelect");
  const categorySelect = document.getElementById("categorySelect");
  if (searchInput) searchInput.value = "";
  if (countrySelect) countrySelect.value = "all";
  if (categorySelect) categorySelect.value = "all";

  applyFilters();
}

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

  // Les filtres mémorisés d'une visite précédente ne correspondent plus aux champs vides
  filters.reset();
  sortMode = "date-asc";

  // S'abonner aux changements d'état AVANT de charger
  unsubscribe = appState.subscribe("events", handleEventsChange);

  // Skeleton affiché tant que les événements ne sont pas arrivés (remplacé par displayEvents)
  renderEventCardsSkeleton("eventsList");

  // Charger les données (déclenche automatiquement l'affichage via l'abonnement)
  await loadEvents();

  // Populer les filtres
  filters.populateAllFilters(allEvents);

  // Attacher les événements de filtrage
  filters.attachFilterListeners({}, applyFilters);

  // Tri
  const sortSelect = document.getElementById("sortSelect");
  if (sortSelect) {
    sortSelect.addEventListener("change", (e) => {
      sortMode = e.target.value;
      displayEvents();
    });
  }

  // Bouton hero : défilement vers la liste (le router intercepte les liens, on évite de le laisser faire)
  const heroDiscover = document.getElementById("heroDiscover");
  if (heroDiscover) {
    heroDiscover.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      document
        .getElementById("eventsSection")
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  }
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
    // Pas de changement d'état en cas d'erreur : on remplace le skeleton nous-mêmes
    await displayEvents();
  }
}

// Afficher les événements
async function displayEvents() {
  const sortedEvents = sortEvents(filteredEvents);

  const resultsCount = document.getElementById("resultsCount");
  if (resultsCount) {
    const total = sortedEvents.length;
    resultsCount.textContent = `${total} événement${total > 1 ? "s" : ""}`;
  }

  // Le bouton de réinitialisation n'a de sens que si des filtres sont actifs
  const onReset = filters.hasActiveFilters() ? resetFilters : null;
  await renderEventCards(
    sortedEvents,
    "eventsList",
    handleEventClick,
    onReset,
  );
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
  // Les filtres actifs restent appliqués quand la liste est rafraîchie
  allEvents = events;
  filteredEvents = filters.filterEvents(allEvents);
  displayEvents();
}

// Export par défaut
export default { mount, unmount, meta };
