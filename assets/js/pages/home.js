// Script pour la page d'accueil

import { renderHeader } from '../components/header.js'
import { renderLoginModal } from '../components/loginModal.js'
import { renderEventCards } from '../components/eventCard.js'
import { showEventDetail } from '../components/eventDetail.js'
import { storage } from '../utils/storage.js'
import { filters } from '../utils/filters.js'

// Données mockées pour les événements historiques européens
const initialEvents = [
  {
    id: "1",
    title: "Carnaval de Venise",
    description: "Le célèbre carnaval vénitien avec ses masques élaborés et costumes somptueux. Une tradition datant du Moyen Âge qui transforme Venise en un théâtre vivant.",
    country: "Italie",
    city: "Venise",
    date: "15/02/2026",
    time: "10:00",
    price: 45,
    image: "https://images.unsplash.com/photo-1709717146395-6d368ebc8231?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx2ZW5pY2UlMjBjYXJuaXZhbCUyMG1hc2tzfGVufDF8fHx8MTc2Nzg3MzI3NHww&ixlib=rb-4.1.0&q=80&w=1080",
    category: "Carnaval",
    availableTickets: 500
  },
  {
    id: "2",
    title: "Oktoberfest",
    description: "La plus grande fête de la bière au monde à Munich. Une célébration bavaroise traditionnelle avec musique, danse et gastronomie depuis 1810.",
    country: "Allemagne",
    city: "Munich",
    date: "20/09/2026",
    time: "09:00",
    price: 30,
    image: "https://images.unsplash.com/photo-1669778631871-7bb6d5411c4b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxva3RvYmVyZmVzdCUyMG11bmljaHxlbnwxfHx8fDE3Njc4NzMyNzV8MA&ixlib=rb-4.1.0&q=80&w=1080",
    category: "Fête Traditionnelle",
    availableTickets: 1000
  },
  {
    id: "3",
    title: "Festival Médiéval de Carcassonne",
    description: "Plongez dans l'histoire médiévale avec des tournois de chevaliers, des marchés d'artisans et des spectacles d'époque dans la cité fortifiée.",
    country: "France",
    city: "Carcassonne",
    date: "05/07/2026",
    time: "14:00",
    price: 25,
    image: "https://images.unsplash.com/photo-1660892367133-82d376bce4fe?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxtZWRpZXZhbCUyMGZlc3RpdmFsJTIwY2FzdGxlfGVufDF8fHx8MTc2Nzg3MzI3Nnww&ixlib=rb-4.1.0&q=80&w=1080",
    category: "Festival Médiéval",
    availableTickets: 300
  },
  {
    id: "4",
    title: "San Fermín - Course des Taureaux",
    description: "La célèbre fête de Pampelune avec sa course de taureaux traditionnelle, une tradition controversée mais historique depuis 1591.",
    country: "Espagne",
    city: "Pampelune",
    date: "07/07/2026",
    time: "08:00",
    price: 35,
    image: "https://images.unsplash.com/photo-1527728180910-ce0511918c1f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxydW5uaW5nJTIwYnVsbHMlMjBwYW1wbG9uYXxlbnwxfHx8fDE3Njc4NzMyNzZ8MA&ixlib=rb-4.1.0&q=80&w=1080",
    category: "Fête Traditionnelle",
    availableTickets: 200
  },
  {
    id: "5",
    title: "Edinburgh Military Tattoo",
    description: "Un spectacle militaire impressionnant au château d'Édimbourg avec des fanfares, des cornemuses et des performances internationales.",
    country: "Royaume-Uni",
    city: "Édimbourg",
    date: "01/08/2026",
    time: "21:00",
    price: 50,
    image: "https://images.unsplash.com/photo-1619429303894-4b40ee7810ba?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxlZGluYnVyZ2glMjBjYXN0bGUlMjBmZXN0aXZhbHxlbnwxfHx8fDE3Njc4NzMyNzZ8MA&ixlib=rb-4.1.0&q=80&w=1080",
    category: "Reconstitution Historique",
    availableTickets: 800
  },
  {
    id: "6",
    title: "Fête de la Renaissance",
    description: "Célébration historique européenne avec costumes d'époque, danses Renaissance et reconstitutions historiques authentiques.",
    country: "France",
    city: "Lyon",
    date: "12/06/2026",
    time: "11:00",
    price: 20,
    image: "https://images.unsplash.com/photo-1767128312636-de243003b0fe?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoaXN0b3JpY2FsJTIwZmVzdGl2YWwlMjBldXJvcGV8ZW58MXx8fHwxNzY3ODczMjc0fDA&ixlib=rb-4.1.0&q=80&w=1080",
    category: "Festival Médiéval",
    availableTickets: 400
  }
]

let allEvents = []
let filteredEvents = []

async function init() {
  await renderHeader('home')
  await renderLoginModal()
  
  loadEvents()
  filters.populateAllFilters(allEvents)
  await displayEvents()
  filters.attachFilterListeners({}, applyFilters)
}

function loadEvents() {
  // Récupérer les événements du localStorage ou utiliser les données initiales
  const storedEvents = storage.get('eurofetes_events')
  allEvents = storedEvents || initialEvents
  
  // Sauvegarder dans le localStorage si pas déjà fait
  if (!storedEvents) {
    storage.set('eurofetes_events', allEvents)
  }

  filteredEvents = allEvents
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
