// Vue Calendar - Calendrier des événements

import EventManager from '../managers/EventManager.js'
import { helpers } from '../utils/helpers.js'
import { showEventDetail } from '../components/eventDetail.js'

// Métadonnées de la vue
export const meta = {
  title: 'Calendrier des Événements - MemoriaEventia',
  description: 'Découvrez tous les événements dans un calendrier interactif'
}

// Template HTML
const templateObjects = {}
let currentDate = new Date()
let allEvents = []
const sixMonthsAgo = new Date()
const oneYearAhead = new Date()

// Définir les limites de navigation
sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6)
oneYearAhead.setFullYear(oneYearAhead.getFullYear() + 1)

async function loadTemplate(path) {
  try {
    const response = await fetch(path, {
      cache: 'no-store',
      headers: {
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache'
      }
    })
    
    if (!response.ok) {
      throw new Error(`Erreur ${response.status}: ${response.statusText}`)
    }

    const htmlContent = await response.text()
    const parser = new DOMParser()
    const templateDoc = parser.parseFromString(htmlContent, 'text/html')
    const templates = templateDoc.querySelectorAll('template')

    if (templates.length === 0) {
      throw new Error('Aucun template trouvé dans le fichier')
    }

    Object.keys(templateObjects).forEach(key => delete templateObjects[key])

    templates.forEach((template) => {
      const templateId = template.id
      templateObjects[templateId] = template.content
    })
  } catch (error) {
    throw error
  }
}

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Réinitialiser la date au mois actuel
  currentDate = new Date()
  
  // Charger le template
  const timestamp = Date.now()
  await loadTemplate(`assets/templates/views/calendar.html?v=${timestamp}`)
  
  if (!templateObjects['calendarView']) {
    helpers.showToast('Erreur de chargement de la page', 'error')
    return
  }

  // Injecter le template
  const clone = templateObjects['calendarView'].cloneNode(true)
  container.innerHTML = ''
  container.appendChild(clone)

  // Charger les événements
  await loadEvents()
  
  // Afficher le calendrier
  renderCalendar()
  
  // Attacher les événements
  attachCalendarEvents()
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Nettoyage si nécessaire
}

// Charger les événements
async function loadEvents() {
  const result = await EventManager.getAll()
  
  if (result.success) {
    allEvents = result.data || []
  } else {
    helpers.showToast('Erreur de chargement des événements', 'error')
    allEvents = []
  }
}

// Rendre le calendrier
function renderCalendar() {
  const year = currentDate.getFullYear()
  const month = currentDate.getMonth()
  
  // Mettre à jour le titre
  const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                      'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
  const monthYearEl = document.getElementById('currentMonthYear')
  if (monthYearEl) {
    monthYearEl.textContent = `${monthNames[month]} ${year}`
  }
  
  // Calculer les jours du mois
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const daysInMonth = lastDay.getDate()
  
  // Calculer le jour de début (0 = Dimanche, besoin de convertir à 1 = Lundi)
  let startDay = firstDay.getDay()
  startDay = startDay === 0 ? 7 : startDay // Dimanche devient 7
  
  // Container des jours
  const calendarDays = document.getElementById('calendarDays')
  if (!calendarDays) return
  
  calendarDays.innerHTML = ''
  
  // Ajouter les jours vides du mois précédent
  for (let i = 1; i < startDay; i++) {
    const emptyDay = document.createElement('div')
    emptyDay.className = 'calendar-day empty'
    calendarDays.appendChild(emptyDay)
  }
  
  // Ajouter les jours du mois
  for (let day = 1; day <= daysInMonth; day++) {
    const dayDate = new Date(year, month, day)
    const dayEl = createDayElement(day, dayDate)
    calendarDays.appendChild(dayEl)
  }
  
  // Mettre à jour l'état des boutons de navigation
  updateNavigationButtons()
}

// Créer un élément jour du calendrier
function createDayElement(day, date) {
  const dayEl = document.createElement('div')
  dayEl.className = 'calendar-day'
  
  // Vérifier si c'est aujourd'hui
  const today = new Date()
  const isToday = date.toDateString() === today.toDateString()
  if (isToday) {
    dayEl.classList.add('today')
  }
  
  // Numéro du jour
  const dayNumber = document.createElement('div')
  dayNumber.className = 'day-number'
  dayNumber.textContent = day
  dayEl.appendChild(dayNumber)
  
  // Trouver les événements pour ce jour
  const eventsOnThisDay = getEventsForDate(date)
  
  if (eventsOnThisDay.length > 0) {
    dayEl.classList.add('has-events')
    
    // Container pour les événements
    const eventsContainer = document.createElement('div')
    eventsContainer.className = 'day-events'
    
    eventsOnThisDay.forEach(event => {
      const eventBadge = document.createElement('div')
      eventBadge.className = 'event-badge'
      eventBadge.textContent = event.title
      eventBadge.style.cursor = 'pointer'
      eventBadge.dataset.eventId = event.id
      
      // Cliquer sur l'événement pour voir les détails
      eventBadge.addEventListener('click', (e) => {
        e.stopPropagation()
        showEventDetail(helpers.transformEvents([event])[0])
      })
      
      eventsContainer.appendChild(eventBadge)
    })
    
    dayEl.appendChild(eventsContainer)
  }
  
  return dayEl
}

// Récupérer les événements pour une date donnée
function getEventsForDate(date) {
  const dateStr = date.toISOString().split('T')[0] // Format YYYY-MM-DD
  
  return allEvents.filter(event => {
    if (!event.date) return false
    const eventDate = new Date(event.date)
    const eventDateStr = eventDate.toISOString().split('T')[0]
    return eventDateStr === dateStr
  })
}

// Attacher les événements de navigation
function attachCalendarEvents() {
  const prevBtn = document.getElementById('prevMonth')
  const nextBtn = document.getElementById('nextMonth')
  
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      currentDate.setMonth(currentDate.getMonth() - 1)
      renderCalendar()
    })
  }
  
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      currentDate.setMonth(currentDate.getMonth() + 1)
      renderCalendar()
    })
  }
}

// Mettre à jour l'état des boutons de navigation
function updateNavigationButtons() {
  const prevBtn = document.getElementById('prevMonth')
  const nextBtn = document.getElementById('nextMonth')
  
  if (!prevBtn || !nextBtn) return
  
  // Vérifier si on peut aller en arrière (6 mois max)
  const testPrevDate = new Date(currentDate)
  testPrevDate.setMonth(testPrevDate.getMonth() - 1)
  
  if (testPrevDate < sixMonthsAgo) {
    prevBtn.disabled = true
    prevBtn.classList.add('disabled')
  } else {
    prevBtn.disabled = false
    prevBtn.classList.remove('disabled')
  }
  
  // Vérifier si on peut aller en avant (1 an max)
  const testNextDate = new Date(currentDate)
  testNextDate.setMonth(testNextDate.getMonth() + 1)
  
  if (testNextDate > oneYearAhead) {
    nextBtn.disabled = true
    nextBtn.classList.add('disabled')
  } else {
    nextBtn.disabled = false
    nextBtn.classList.remove('disabled')
  }
}

// Export par défaut
export default { mount, unmount, meta }
