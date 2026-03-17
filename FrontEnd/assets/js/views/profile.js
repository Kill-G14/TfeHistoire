// Vue Profile - Profil utilisateur

import { auth } from '../utils/auth.js'
import { helpers } from '../utils/helpers.js'
import { appState } from '../store/appState.js'

// Métadonnées de la vue
export const meta = {
  title: 'Mon Profil - MemoriaEventia',
  description: 'Gérez votre profil et consultez vos réservations'
}

// Template HTML
const templateObjects = {}

async function loadTemplate(path) {
  try {
    // Forcer le rechargement sans cache
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
    console.log('Template HTML chargé:', htmlContent.substring(0, 200)) // Debug
    
    const parser = new DOMParser()
    const templateDoc = parser.parseFromString(htmlContent, 'text/html')
    const templates = templateDoc.querySelectorAll('template')

    if (templates.length === 0) {
      throw new Error('Aucun template trouvé dans le fichier')
    }

    // Vider l'objet templateObjects avant de le remplir
    Object.keys(templateObjects).forEach(key => delete templateObjects[key])

    templates.forEach((template) => {
      const templateId = template.id
      console.log('Template trouvé:', templateId) // Debug
      templateObjects[templateId] = template.content
    })
  } catch (error) {
    console.error('Erreur lors du chargement du template:', error)
    throw error
  }
}

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Vérifier si l'utilisateur est connecté
  if (!appState.get('isAuthenticated')) {
    helpers.showToast('Vous devez être connecté pour accéder à votre profil', 'error')
    setTimeout(() => {
      window.router.navigate('/')
    }, 1500)
    return
  }

  // Charger le template avec timestamp pour éviter le cache
  const timestamp = Date.now()
  await loadTemplate(`assets/templates/views/profile.html?v=${timestamp}`)
  
  // Vérifier que le template est chargé
  if (!templateObjects['profileView']) {
    console.error('Template profileView non trouvé')
    helpers.showToast('Erreur de chargement de la page', 'error')
    return
  }

  // Injecter le template
  const clone = templateObjects['profileView'].cloneNode(true)
  container.innerHTML = ''
  container.appendChild(clone)

  // Afficher les informations utilisateur
  displayUserInfo()
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Pas de nettoyage nécessaire pour cette vue simple
}

// Afficher les informations utilisateur
function displayUserInfo() {
  const user = appState.get('user')
  const userNameEl = document.getElementById('userName')
  const userEmailEl = document.getElementById('userEmail')

  if (userNameEl && user) {
    userNameEl.textContent = user.name || 'Utilisateur'
  }

  if (userEmailEl && user) {
    userEmailEl.textContent = user.email || ''
  }
}

// Export par défaut
export default { mount, unmount, meta }
