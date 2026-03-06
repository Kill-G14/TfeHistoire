// Script pour la page de création d'événement

import { renderHeader } from '../components/header.js'
import { renderLoginModal } from '../components/loginModal.js'
import { auth } from '../utils/auth.js'
import { helpers } from '../utils/helpers.js'
import EventManager from '../managers/EventManager.js'

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
    image_url: document.getElementById('imageUrl')?.value || ''
  }

  // Désactiver le bouton de soumission
  const submitBtn = createEventForm.querySelector('button[type="submit"]')
  if (submitBtn) {
    submitBtn.disabled = true
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Création...'
  }

  // Appel API pour créer l'événement
  const token = auth.getToken()
  const result = await EventManager.create(eventData, token)

  if (result.success) {
    helpers.showToast('Événement créé avec succès !', 'success')
    setTimeout(() => {
      window.location.href = '../pages/index.html'
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

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init)
} else {
  init()
}
