// Vue Profile - Profil utilisateur

import { auth } from '../utils/auth.js'
import { helpers } from '../utils/helpers.js'
import { appState } from '../store/appState.js'
import FavoriteManager from '../managers/FavoriteManager.js'
import { showEventDetail } from '../components/eventDetail.js'

// Métadonnées de la vue
export const meta = {
  title: 'Mon Profil - MemoriaEventia',
  description: 'Gérez votre profil et consultez vos réservations'
}

// Template HTML
const templateObjects = {}

// Stocker les événements favoris pour les détails
let favoriteEvents = []

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
      templateObjects[templateId] = template.content
    })
  } catch (error) {
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
    helpers.showToast('Erreur de chargement de la page', 'error')
    return
  }

  // Injecter le template
  const clone = templateObjects['profileView'].cloneNode(true)
  container.innerHTML = ''
  container.appendChild(clone)
  
  // Attendre que le DOM soit réellement mis à jour
  await new Promise(resolve => setTimeout(resolve, 50))

  // Afficher les informations utilisateur
  displayUserInfo()
  
  // Attacher les événements (le DOM est maintenant prêt)
  attachProfileEvents()
  
  // Charger et afficher les favoris (après avoir attaché les événements)
  await loadFavorites()
  
  // Écouter les changements de favoris
  appState.subscribe('favorites', loadFavorites)
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Pas de nettoyage nécessaire pour cette vue simple
}

// Attacher les événements de la page profil
function attachProfileEvents() {
  // Formulaire de changement de mot de passe
  const changePasswordForm = document.getElementById('changePasswordForm')
  if (changePasswordForm) {
    changePasswordForm.addEventListener('submit', handleChangePassword)
  }
  
  // Navigation entre sections via les stat cards
  initSectionNavigation()
}

// Initialiser la navigation entre sections
function initSectionNavigation() {
  const statButtons = document.querySelectorAll('.stat-card-btn')
  
  if (statButtons.length === 0) return
  
  statButtons.forEach((button) => {
    const section = button.getAttribute('data-section')
    
    button.addEventListener('click', function(e) {
      e.preventDefault()
      e.stopPropagation()
      
      // Retirer la classe active de tous les boutons
      statButtons.forEach(btn => btn.classList.remove('active'))
      
      // Ajouter active au bouton cliqué
      button.classList.add('active')
      
      // Cacher toutes les sections
      document.querySelectorAll('.profile-section').forEach(sec => {
        sec.style.display = 'none'
      })
      
      // Afficher la section ciblée
      const targetElement = document.getElementById(`section-${section}`)
      if (targetElement) {
        targetElement.style.display = 'block'
      }
    })
  })
}

// Gérer le changement de mot de passe
async function handleChangePassword(e) {
  e.preventDefault()

  const currentPassword = document.getElementById('currentPassword').value
  const newPassword = document.getElementById('newPassword').value
  const confirmPassword = document.getElementById('confirmPassword').value

  // Réinitialiser les erreurs
  document.getElementById('currentPasswordError').textContent = ''
  document.getElementById('newPasswordError').textContent = ''
  document.getElementById('confirmPasswordError').textContent = ''
  document.getElementById('currentPassword').classList.remove('is-invalid')
  document.getElementById('newPassword').classList.remove('is-invalid')
  document.getElementById('confirmPassword').classList.remove('is-invalid')

  // Validation
  let hasError = false

  if (!currentPassword) {
    document.getElementById('currentPasswordError').textContent = 'Le mot de passe actuel est requis'
    document.getElementById('currentPassword').classList.add('is-invalid')
    hasError = true
  }

  if (!newPassword || newPassword.length < 6) {
    document.getElementById('newPasswordError').textContent = 'Le nouveau mot de passe doit contenir au moins 6 caractères'
    document.getElementById('newPassword').classList.add('is-invalid')
    hasError = true
  }

  if (newPassword !== confirmPassword) {
    document.getElementById('confirmPasswordError').textContent = 'Les mots de passe ne correspondent pas'
    document.getElementById('confirmPassword').classList.add('is-invalid')
    hasError = true
  }

  if (hasError) return

  // Appel API
  const token = auth.getToken()
  if (!token) {
    helpers.showToast('Vous devez être connecté', 'error')
    return
  }

  // Import dynamique de AuthManager
  const { default: AuthManager } = await import('../managers/AuthManager.js')
  const result = await AuthManager.changePassword(token, currentPassword, newPassword)

  if (result.success) {
    helpers.showToast('Mot de passe modifié avec succès', 'success')
    
    // Fermer la modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'))
    if (modal) modal.hide()
    
    // Réinitialiser le formulaire
    document.getElementById('changePasswordForm').reset()
  } else {
    if (result.message.includes('actuel')) {
      document.getElementById('currentPasswordError').textContent = result.message
      document.getElementById('currentPassword').classList.add('is-invalid')
    } else {
      helpers.showToast(result.message, 'error')
    }
  }
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
  
  // Mettre à jour le compteur de favoris
  const favorites = appState.get('favorites') || []
  const statFavorites = document.getElementById('statFavorites')
  if (statFavorites) {
    statFavorites.textContent = favorites.length
  }
}

// Charger les favoris
async function loadFavorites() {
  const token = auth.getToken()
  if (!token) return

  const favoritesContainer = document.getElementById('userFavorites')
  if (!favoritesContainer) return

  // Afficher un loader
  favoritesContainer.innerHTML = `
    <div class="text-center py-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
    </div>
  `

  // Récupérer les favoris avec détails
  const result = await FavoriteManager.getByUserWithDetails(token)

  if (result.success && result.data && result.data.length > 0) {
    // Transformer les événements pour avoir le bon format d'image
    const transformedEvents = helpers.transformEvents(result.data)
    favoriteEvents = transformedEvents // Stocker pour les détails
    displayFavorites(transformedEvents)
  } else {
    // Aucun favori
    favoritesContainer.innerHTML = `
      <div class="text-center py-5">
        <i class="bi bi-heart text-muted fs-1 mb-2"></i>
        <p class="text-muted mb-0">Aucun événement en favoris</p>
      </div>
    `
  }
}

// Afficher les favoris
function displayFavorites(favorites) {
  const favoritesContainer = document.getElementById('userFavorites')
  if (!favoritesContainer) return

  favoritesContainer.innerHTML = `
    <div class="d-flex flex-wrap gap-3">
      ${favorites.map(event => {
        // Gérer le prix
        const priceDisplay = event.is_free ? 'Gratuit' : 'Voir billets'
        
        return `
        <div class="favorite-card-wrapper" id="favorite-card-${event.id}">
          <div class="card h-100 shadow-sm hover-card" 
               onclick="viewEventDetails(${event.id})" 
               style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
            <div class="card-body">
              <h5 class="card-title text-primary mb-3">${event.title}</h5>
              
              <div class="mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-geo-alt text-muted"></i>
                  <span>${event.city}, ${event.country}</span>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-calendar3 text-muted"></i>
                  <span>${helpers.formatDate(event.date)}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-tag text-muted"></i>
                  <span class="fw-bold text-primary">${priceDisplay}</span>
                </div>
              </div>
              
              <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" 
                        onclick="event.stopPropagation(); viewEventDetails(${event.id})">
                  <i class="bi bi-eye"></i> Voir détails
                </button>
                <button class="btn btn-outline-danger btn-sm" 
                        onclick="event.stopPropagation(); removeFavoriteFromProfile(${event.id}, this)"
                        id="remove-fav-${event.id}"
                        title="Retirer des favoris">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        `
      }).join('')}
    </div>
  `
}

// Voir les détails d'un événement (fonction globale pour onclick)
window.viewEventDetails = function(eventId) {
  const event = favoriteEvents.find(e => e.id === eventId)
  if (event) {
    showEventDetail(event)
  } else {
    helpers.showToast('Événement non trouvé', 'error')
  }
}

// Fonction globale pour retirer un favori depuis le profil
window.removeFavoriteFromProfile = async function(eventId, btnElement) {
  if (btnElement.disabled) return

  const token = auth.getToken()
  
  // Désactiver le bouton pendant le traitement
  btnElement.disabled = true
  btnElement.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Suppression...'

  const result = await FavoriteManager.remove(eventId, token)

  if (result.success) {
    helpers.showToast('Retiré des favoris', 'success')
    
    // Mettre à jour le state
    const userFavorites = appState.get('favorites') || []
    const updatedFavorites = userFavorites.filter(fav => fav.event_id != eventId)
    appState.set('favorites', updatedFavorites)
    
    // Animation de suppression de la carte
    const card = document.getElementById(`favorite-card-${eventId}`)
    if (card) {
      card.style.transition = 'opacity 0.3s, transform 0.3s'
      card.style.opacity = '0'
      card.style.transform = 'scale(0.8)'
      
      setTimeout(() => {
        // Recharger l'affichage après l'animation
        loadFavorites()
      }, 300)
    } else {
      // Si la carte n'existe pas, juste recharger
      loadFavorites()
    }
  } else {
    helpers.showToast(result.message || 'Erreur', 'error')
    btnElement.disabled = false
    btnElement.innerHTML = '<i class="bi bi-trash"></i> Retirer des favoris'
  }
}

// Export par défaut
export default { mount, unmount, meta }
