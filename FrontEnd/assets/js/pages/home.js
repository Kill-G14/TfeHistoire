// Script pour la page d'accueil

import { renderHeader } from '../components/header.js'
import { renderLoginModal } from '../components/loginModal.js'
import { renderEventCards } from '../components/eventCard.js'
import { showEventDetail } from '../components/eventDetail.js'
import { helpers } from '../utils/helpers.js'
import { filters } from '../utils/filters.js'

let allEvents = []
let filteredEvents = []

async function init() {
  await renderHeader('home')
  await renderLoginModal()
  
  await loadEvents()
  filters.populateAllFilters(allEvents)
  await displayEvents()
  filters.attachFilterListeners({}, applyFilters)
}

async function loadEvents() {
  // Charger les événements depuis le backend
  const result = await helpers.apiCall('events.php', { action: 'getAll' })
  
  if (result.success) {
    allEvents = result.data
    filteredEvents = allEvents
  } else {
    helpers.showToast('Erreur lors du chargement des événements', 'error')
    allEvents = []
    filteredEvents = []
  }
}

async function displayEvents() {
  await renderEventCards(filteredEvents, 'eventsList', handleEventClick)
}

function handleEventClick(event) {
  showEventDetail(event)
}

function applyFilters() {
  filteredEvents = filters.filterEvents(allEvents)
  displayEvents()
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init)
} else {
  init()
}
