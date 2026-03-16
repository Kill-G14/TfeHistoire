// Point d'entrée principal de l'application SPA

// Imports
import { Router } from './router.js'
import { renderHeader } from './components/header.js'
import { renderFooter } from './components/footer.js'
import { renderLoginModal } from './components/loginModal.js'
import { appState } from './store/appState.js'
import { auth } from './utils/auth.js'

// Définition des routes
const routes = {
  '/': () => import('./views/home.js'),
  '/create-event': () => import('./views/createEvent.js'),
  '/profile': () => import('./views/profile.js'),
  '/map': () => import('./views/map.js')
}

// Instance du routeur
const router = new Router(routes, '#app')

// Export du routeur pour accès global
window.router = router

// Fonction init
async function init() {
  // Vérifier l'authentification
  const isLoggedIn = auth.isLoggedIn()
  const user = auth.getUser()
  
  if (isLoggedIn && user) {
    appState.set('user', user)
    appState.set('isAuthenticated', true)
  }

  // Rendre les composants persistants
  await renderHeader()
  await renderFooter()
  await renderLoginModal()

  // Initialiser le routeur
  router.init()

  // Écouter les changements d'état utilisateur
  appState.subscribe('user', handleUserChange)
}

// Gérer les changements d'utilisateur
function handleUserChange(user) {
  if (user) {
    appState.set('isAuthenticated', true)
  } else {
    appState.set('isAuthenticated', false)
  }
  // Mettre à jour le header
  renderHeader()
}

// Initialisation
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init)
} else {
  init()
}
