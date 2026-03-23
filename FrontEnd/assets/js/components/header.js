// Composant Header

import { auth } from '../utils/auth.js'
import { appState } from '../store/appState.js'

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

export async function renderHeader() {
  await loadTemplate('assets/components/header.html')

  const headerElement = document.getElementById('header')
  if (!headerElement) return

  const clone = templateObjects['headerTemplate'].cloneNode(true)
  headerElement.innerHTML = ''
  headerElement.appendChild(clone)

  // Marquer le lien actif selon l'URL
  updateActiveNav()

  // Rendre les actions du header
  renderHeaderActions()
}

function updateActiveNav() {
  const currentPath = window.location.pathname.replace(/^\/tfeHistoire\/FrontEnd/, '')
  const navHome = document.getElementById('navHome')
  const navMap = document.getElementById('navMap')

  if (navHome) navHome.classList.remove('active')
  if (navMap) navMap.classList.remove('active')

  if ((currentPath === '/' || currentPath === '') && navHome) {
    navHome.classList.add('active')
  } else if (currentPath === '/map' && navMap) {
    navMap.classList.add('active')
  }
}

function renderHeaderActions() {
  const headerActions = document.getElementById('headerActions')
  if (!headerActions) return

  headerActions.innerHTML = ''

  const isLoggedIn = appState.get('isAuthenticated')
  const templateKey = isLoggedIn ? 'headerActionsLoggedIn' : 'headerActionsLoggedOut'
  const clone = templateObjects[templateKey].cloneNode(true)

  headerActions.appendChild(clone)

  // Attacher les événements
  if (isLoggedIn) {
    const btnProfile = document.getElementById('btnProfile')
    const btnLogout = document.getElementById('btnLogout')

    if (btnProfile) {
      btnProfile.addEventListener('click', () => {
        window.router.navigate('/profile')
      })
    }

    if (btnLogout) {
      btnLogout.addEventListener('click', async () => {
        await auth.logout()
        appState.set('user', null)
        appState.set('isAuthenticated', false)
        window.router.navigate('/')
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
