// Imports
import AuthManager from '../managers/AuthManager.js'
import { storage } from '../utils/helpers.js'

// Fonction init
async function init() {
  // Vérifier si l'utilisateur est déjà connecté
  const token = storage.getToken()
  if (token) {
    window.location.href = 'dashboard.html'
    return
  }

  attachEventListeners()
}

// Gestion des événements
function attachEventListeners() {
  const loginForm = document.getElementById('loginForm')
  if (!loginForm) return

  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault()
    await handleLogin()
  })
}

// Gestion de la connexion
async function handleLogin() {
  const email = document.getElementById('email').value.trim()
  const password = document.getElementById('password').value
  const remember = document.getElementById('remember').checked
  const errorMessage = document.getElementById('errorMessage')
  const submitBtn = loginForm.querySelector('button[type="submit"]')

  // Reset message d'erreur
  errorMessage.classList.add('d-none')
  errorMessage.textContent = ''

  // Validation basique
  if (!email || !password) {
    showError('Veuillez remplir tous les champs')
    return
  }

  // Désactiver le bouton pendant le traitement
  submitBtn.disabled = true
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion...'

  const result = await AuthManager.login(email, password)

  if (result.success) {
    // Vérifier le rôle
    const user = result.data.user || result.data
    if (!user.is_admin && !user.is_moderator) {
      showError('Accès refusé. Seuls les administrateurs et modérateurs peuvent se connecter.')
      submitBtn.disabled = false
      submitBtn.innerHTML = 'Connexion'
      return
    }

    // Sauvegarder le token
    storage.setToken(result.data.token)

    // Rediriger vers le dashboard
    window.location.href = 'dashboard.html'
  } else {
    showError(result.message || 'Email ou mot de passe incorrect')
    submitBtn.disabled = false
    submitBtn.innerHTML = 'Connexion'
  }
}

// Afficher un message d'erreur
function showError(message) {
  const errorMessage = document.getElementById('errorMessage')
  errorMessage.textContent = message
  errorMessage.classList.remove('d-none')
}

// Initialisation
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init)
} else {
  init()
}
