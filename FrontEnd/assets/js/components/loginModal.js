// Composant Login Modal

import { auth } from '../utils/auth.js'
import { helpers } from '../utils/helpers.js'
import { appState } from '../store/appState.js'

const templateObjects = {}
let modalInstance = null

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

export async function renderLoginModal() {
  await loadTemplate('./assets/components/loginModal.html')

  // Vérifier si le modal existe déjà
  let modalContainer = document.getElementById('loginModalContainer')
  if (!modalContainer) {
    modalContainer = document.createElement('div')
    modalContainer.id = 'loginModalContainer'
    document.body.appendChild(modalContainer)
  }

  modalContainer.innerHTML = ''
  const clone = templateObjects['loginModalTemplate'].cloneNode(true)
  modalContainer.appendChild(clone)

  // Attacher les événements
  attachLoginEvents()
  attachRegisterEvents()

  // Créer l'instance du modal Bootstrap
  const modalElement = document.getElementById('loginModal')
  modalInstance = new bootstrap.Modal(modalElement)

  // Écouter l'événement d'ouverture personnalisé
  window.addEventListener('openLoginModal', openLoginModal)
}

function attachLoginEvents() {
  const loginForm = document.getElementById('loginForm')
  if (!loginForm) return

  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault()

    const email = document.getElementById('loginEmail').value
    const password = document.getElementById('loginPassword').value

    const result = await auth.login(email, password)

    if (result.success) {
      closeLoginModal()
      helpers.showToast('Connexion réussie !', 'success')
      
      // Mettre à jour l'état global
      appState.set('user', result.data.user)
      appState.set('isAuthenticated', true)
    } else {
      helpers.showToast('Erreur de connexion', 'error')
    }
  })
}

function attachRegisterEvents() {
  const registerForm = document.getElementById('registerForm')
  if (!registerForm) return

  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault()

    const name = document.getElementById('registerName').value
    const email = document.getElementById('registerEmail').value
    const password = document.getElementById('registerPassword').value

    const result = await auth.register(email, password, name)

    if (result.success) {
      closeLoginModal()
      helpers.showToast(`Bienvenue ${name} !`, 'success')
      
      // Mettre à jour l'état global
      appState.set('user', result.data.user)
      appState.set('isAuthenticated', true)
    } else {
      helpers.showToast('Erreur d\'inscription', 'error')
    }
  })
}

export function openLoginModal() {
  if (modalInstance) {
    modalInstance.show()
  }
}

export function closeLoginModal() {
  if (modalInstance) {
    modalInstance.hide()
  }
}
