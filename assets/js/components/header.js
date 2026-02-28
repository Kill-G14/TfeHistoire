// Composant Header

import { auth } from '../utils/auth.js'

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

export async function renderHeader(currentView = 'home') {
  await loadTemplate('../assets/components/header.html')

  const headerElement = document.getElementById('header')
  if (!headerElement) return

  const clone = templateObjects['headerTemplate'].cloneNode(true)
  headerElement.innerHTML = ''
  headerElement.appendChild(clone)

  // Marquer le lien actif
  updateActiveNav(currentView)

  // Rendre les actions du header
  renderHeaderActions()
}

function updateActiveNav(currentView) {
  const navHome = document.getElementById('navHome')
  const navMap = document.getElementById('navMap')

  if (navHome) navHome.classList.remove('active')
  if (navMap) navMap.classList.remove('active')

  if (currentView === 'home' && navHome) {
    navHome.classList.add('active')
  } else if (currentView === 'map' && navMap) {
    navMap.classList.add('active')
  }
}

function renderHeaderActions() {
  const headerActions = document.getElementById('headerActions')
  if (!headerActions) return

  headerActions.innerHTML = ''

  const isLoggedIn = auth.isLoggedIn()
  const templateKey = isLoggedIn ? 'headerActionsLoggedIn' : 'headerActionsLoggedOut'
  const clone = templateObjects[templateKey].cloneNode(true)

  headerActions.appendChild(clone)

  // Attacher les événements
  if (isLoggedIn) {
    const btnCreateEvent = document.getElementById('btnCreateEvent')
    const btnProfile = document.getElementById('btnProfile')
    const btnLogout = document.getElementById('btnLogout')

    if (btnCreateEvent) {
      btnCreateEvent.addEventListener('click', () => {
        window.location.href = '../pages/createEvent.html'
      })
    }

    if (btnProfile) {
      btnProfile.addEventListener('click', () => {
        window.location.href = '../pages/profile.html'
      })
    }

    if (btnLogout) {
      btnLogout.addEventListener('click', () => {
        auth.logout()
        window.location.reload()
      })
    }
  } else {
    const btnLogin = document.getElementById('btnLogin')
    if (btnLogin) {
      btnLogin.addEventListener('click', () => {
        // Déclencher l'ouverture du modal de connexion
        const event = new CustomEvent('openLoginModal')
        window.dispatchEvent(event)
      })
    }
  }
}
