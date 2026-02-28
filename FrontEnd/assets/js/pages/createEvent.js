// Script pour la page de création d'événement

import { renderHeader } from '../components/header.js'
import { renderLoginModal } from '../components/loginModal.js'
import { auth } from '../utils/auth.js'
import { helpers } from '../utils/helpers.js'
import { storage } from '../utils/storage.js'

async function init() {
  await renderHeader()
  await renderLoginModal()
  
  // Vérifier si l'utilisateur est connecté
  if (!auth.isLoggedIn()) {
    helpers.showToast('Vous devez être connecté pour créer un événement', 'error')
    setTimeout(() => {
      window.location.href = '../pages/index.html'
    }, 1500)
    return
  }

  attachEventListeners()
}

function attachEventListeners() {
  const createEventForm = document.getElementById('createEventForm')
  const btnCancel = document.getElementById('btnCancel')

  if (createEventForm) {
    createEventForm.addEventListener('submit', handleSubmit)
  }

  if (btnCancel) {
    btnCancel.addEventListener('click', () => {
      window.location.href = '../pages/index.html'
    })
  }
}

function handleSubmit(e) {
  e.preventDefault()

  const formData = {
    id: Date.now().toString(),
    title: document.getElementById('title').value,
    description: document.getElementById('description').value,
    country: document.getElementById('country').value,
    city: document.getElementById('city').value,
    date: helpers.formatDate(document.getElementById('date').value),
    time: document.getElementById('time').value,
    price: parseFloat(document.getElementById('price').value),
    category: document.getElementById('category').value,
    availableTickets: parseInt(document.getElementById('availableTickets').value),
    image: document.getElementById('imageUrl').value || "https://images.unsplash.com/photo-1767128312636-de243003b0fe?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoaXN0b3JpY2FsJTIwZmVzdGl2YWwlMjBldXJvcGV8ZW58MXx8fHwxNzY3ODczMjc0fDA&ixlib=rb-4.1.0&q=80&w=1080"
  }

  // Récupérer les événements existants
  const events = storage.get('eurofetes_events') || []
  
  // Ajouter le nouvel événement
  events.push(formData)
  
  // Sauvegarder
  storage.set('eurofetes_events', events)

  helpers.showToast('Événement créé avec succès !', 'success')

  setTimeout(() => {
    window.location.href = '../pages/index.html'
  }, 1000)
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init)
} else {
  init()
}
