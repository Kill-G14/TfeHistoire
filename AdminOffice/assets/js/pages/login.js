// Imports
import { auth } from '../utils/auth.js'
import AuthManager from '../managers/AuthManager.js'

// Fonction init
async function init() {
  // Vérifier si l'utilisateur est déjà connecté
  if (auth.isAuthenticated()) {
    window.location.href = 'index.html'
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
    if (result.data.role !== 'admin' && result.data.role !== 'moderator') {
      showError('Accès refusé. Seuls les administrateurs et modérateurs peuvent se connecter.')
      submitBtn.disabled = false
      submitBtn.innerHTML = 'Connexion'
      return
    }

    // Sauvegarder les données d'authentification
    auth.saveAuthData(result.data.token, result.data.user, remember)

    // Rediriger vers le dashboard
    window.location.href = 'index.html'
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
