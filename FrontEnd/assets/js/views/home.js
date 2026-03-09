// Vue Home - Page d'accueil avec liste d'événements

import { renderEventCards } from '../components/eventCard.js'
import { showEventDetail } from '../components/eventDetail.js'
import { helpers } from '../utils/helpers.js'
import { filters } from '../utils/filters.js'
import { appState } from '../store/appState.js'
import EventManager from '../managers/EventManager.js'

// Métadonnées de la vue
export const meta = {
  title: 'EuroFêtes Historiques - Événements historiques d\'Europe',
  description: 'Découvrez et participez aux plus grandes célébrations historiques à travers l\'Europe'
}

// Template HTML
const templateObjects = {}

async function loadTemplate(path) {
  const response = await fetch(path)
  const htmlContent = await response.text()
  const parser = new DOMParser()
  const templateDoc = parser.parseFromString(htmlContent, 'text/html')
  const templates = templateDoc.querySelectorAll('template')

  templates.forEach((template) => {
    const templateId = template.id
    templateObjects[templateId] = template.content
  })
}

// Variables locales de la vue
let allEvents = []
let filteredEvents = []
let unsubscribe = null

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Charger le template
  await loadTemplate('./assets/templates/views/home.html')
  
  // Injecter le template
  const clone = templateObjects['homeView'].cloneNode(true)
  container.innerHTML = ''
  container.appendChild(clone)

  // Charger les données
  await loadEvents()
  
  // Populer les filtres
  filters.populateAllFilters(allEvents)
  
  // Afficher les événements
  await displayEvents()
  
  // Attacher les événements
  filters.attachFilterListeners({}, applyFilters)

  // S'abonner aux changements d'état
  unsubscribe = appState.subscribe('events', handleEventsChange)
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Désabonner des changements d'état
  if (unsubscribe) {
    unsubscribe()
  }
  
  // Nettoyer les variables
  allEvents = []
  filteredEvents = []
}

// Charger les événements
async function loadEvents() {
  const result = await EventManager.getAll()
  
  if (result.success) {
    allEvents = result.data
    filteredEvents = allEvents
    appState.set('events', allEvents)
  } else {
    helpers.showToast('Erreur lors du chargement des événements', 'error')
    allEvents = []
    filteredEvents = []
  }
}

// Afficher les événements
async function displayEvents() {
  await renderEventCards(filteredEvents, 'eventsList', handleEventClick)
}

// Gérer le clic sur un événement
function handleEventClick(event) {
  showEventDetail(event)
}

// Appliquer les filtres
function applyFilters() {
  filteredEvents = filters.filterEvents(allEvents)
  displayEvents()
}

// Gérer les changements d'événements
function handleEventsChange(events) {
  allEvents = events
  filteredEvents = events
  displayEvents()
}

// Export par défaut
export default { mount, unmount, meta }
