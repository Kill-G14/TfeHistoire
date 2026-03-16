// Vue CreateEvent - Création d'événement

import { auth } from '../utils/auth.js'
import { helpers } from '../utils/helpers.js'
import { appState } from '../store/appState.js'
import EventManager from '../managers/EventManager.js'

// Métadonnées de la vue
export const meta = {
  title: 'Créer un événement - MemoriaEventia',
  description: 'Créez votre événement historique et partagez-le avec toute l\'Europe'
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

// Variables locales
let createEventForm = null

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Vérifier si l'utilisateur est connecté
  if (!appState.get('isAuthenticated')) {
    helpers.showToast('Vous devez être connecté pour créer un événement', 'error')
    setTimeout(() => {
      window.router.navigate('/')
    }, 1500)
    return
  }

  // Charger le template
  await loadTemplate('./assets/templates/views/createEvent.html')
  
  // Injecter le template
  const clone = templateObjects['createEventView'].cloneNode(true)
  container.innerHTML = ''
  container.appendChild(clone)

  // Attacher les événements
  attachEventListeners()
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Nettoyer les event listeners
  if (createEventForm) {
    createEventForm.removeEventListener('submit', handleSubmit)
  }
  
  const btnCancel = document.getElementById('btnCancel')
  if (btnCancel) {
    btnCancel.removeEventListener('click', handleCancel)
  }
}

// Attacher les event listeners
function attachEventListeners() {
  createEventForm = document.getElementById('createEventForm')
  const btnCancel = document.getElementById('btnCancel')

  if (createEventForm) {
    createEventForm.addEventListener('submit', handleSubmit)
  }

  if (btnCancel) {
    btnCancel.addEventListener('click', handleCancel)
  }
}

// Gérer l'annulation
function handleCancel() {
  window.router.navigate('/')
}

// Gérer la soumission du formulaire
async function handleSubmit(e) {
  e.preventDefault()

  const eventData = {
    title: document.getElementById('title').value,
    description: document.getElementById('description').value,
    country: document.getElementById('country').value,
    city: document.getElementById('city').value,
    postal_code: document.getElementById('postalCode')?.value || '',
    address: document.getElementById('address')?.value || '',
    date: document.getElementById('date').value,
    time: document.getElementById('time').value,
    category: document.getElementById('category').value,
    is_free: document.getElementById('isFree')?.checked || false,
    image_event: document.getElementById('imageEvent')?.value || ''
  }

  // Désactiver le bouton de soumission
  const submitBtn = createEventForm.querySelector('button[type="submit"]')
  if (submitBtn) {
    submitBtn.disabled = true
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Création...'
  }

  // Appel API pour créer l'événement
  const token = auth.getToken ? auth.getToken() : null
  const result = await EventManager.create(eventData, token)

  if (result.success) {
    helpers.showToast('Événement créé avec succès !', 'success')
    setTimeout(() => {
      window.router.navigate('/')
    }, 1000)
  } else {
    helpers.showToast(result.message || 'Erreur lors de la création de l\'événement', 'error')
    
    // Réactiver le bouton
    if (submitBtn) {
      submitBtn.disabled = false
      submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Créer l\'événement'
    }
  }
}

// Export par défaut
export default { mount, unmount, meta }
