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
  attachValidationEvents()
  attachForgotPasswordEvents()

  // Créer l'instance du modal Bootstrap
  const modalElement = document.getElementById('loginModal')
  modalInstance = new bootstrap.Modal(modalElement)

  // Écouter l'événement d'ouverture personnalisé
  window.addEventListener('openLoginModal', openLoginModal)

  // Écouter les changements d'onglets pour réinitialiser la validation
  attachTabSwitchEvents()
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

function attachForgotPasswordEvents() {
  const forgotPasswordForm = document.getElementById('forgotPasswordForm')
  if (!forgotPasswordForm) return

  forgotPasswordForm.addEventListener('submit', async (e) => {
    e.preventDefault()

    const email = document.getElementById('forgotEmail').value
    const emailError = document.getElementById('forgotEmailError')
    const emailInput = document.getElementById('forgotEmail')

    // Réinitialiser les erreurs
    emailError.textContent = ''
    emailInput.classList.remove('is-invalid')

    // Validation
    if (!email || !isValidEmail(email)) {
      emailError.textContent = 'Veuillez entrer une adresse email valide'
      emailInput.classList.add('is-invalid')
      return
    }

    // Import dynamique de AuthManager
    const { default: AuthManager } = await import('../managers/AuthManager.js')
    const result = await AuthManager.requestPasswordReset(email)

    if (result.success) {
      helpers.showToast(result.message, 'success')
      
      // Fermer la modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'))
      if (modal) modal.hide()
      
      // Réinitialiser le formulaire
      forgotPasswordForm.reset()
    } else {
      helpers.showToast(result.message, 'error')
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

// ============================================
// VALIDATION EN TEMPS RÉEL
// ============================================

function attachValidationEvents() {
  // Champs d'inscription
  const registerName = document.getElementById('registerName')
  const registerEmail = document.getElementById('registerEmail')
  const registerPassword = document.getElementById('registerPassword')

  // Champs de connexion
  const loginEmail = document.getElementById('loginEmail')
  const loginPassword = document.getElementById('loginPassword')

  // Validation inscription
  if (registerName) {
    registerName.addEventListener('input', () => {
      validateRegisterName()
      checkRegisterFormValidity()
    })
    registerName.addEventListener('blur', validateRegisterName)
  }

  if (registerEmail) {
    registerEmail.addEventListener('input', () => {
      validateRegisterEmail()
      checkRegisterFormValidity()
    })
    registerEmail.addEventListener('blur', validateRegisterEmail)
  }

  if (registerPassword) {
    registerPassword.addEventListener('input', () => {
      validateRegisterPassword()
      checkRegisterFormValidity()
    })
    registerPassword.addEventListener('blur', validateRegisterPassword)
  }

  // Validation connexion
  if (loginEmail) {
    loginEmail.addEventListener('input', validateLoginEmail)
    loginEmail.addEventListener('blur', validateLoginEmail)
  }

  if (loginPassword) {
    loginPassword.addEventListener('input', validateLoginPassword)
    loginPassword.addEventListener('blur', validateLoginPassword)
  }
}

// Validation du nom (inscription)
function validateRegisterName() {
  const nameInput = document.getElementById('registerName')
  const errorDiv = document.getElementById('registerNameError')
  const value = nameInput.value.trim()

  if (value === '') {
    setFieldError(nameInput, errorDiv, 'Le nom est requis')
    return false
  } else if (value.length < 2) {
    setFieldError(nameInput, errorDiv, 'Le nom doit contenir au moins 2 caractères')
    return false
  } else {
    setFieldValid(nameInput, errorDiv)
    return true
  }
}

// Validation de l'email (inscription)
function validateRegisterEmail() {
  const emailInput = document.getElementById('registerEmail')
  const errorDiv = document.getElementById('registerEmailError')
  const value = emailInput.value.trim()

  if (value === '') {
    setFieldError(emailInput, errorDiv, 'L\'email est requis')
    return false
  } else if (!isValidEmail(value)) {
    setFieldError(emailInput, errorDiv, 'L\'email n\'est pas valide')
    return false
  } else {
    setFieldValid(emailInput, errorDiv)
    return true
  }
}

// Validation du mot de passe (inscription)
function validateRegisterPassword() {
  const passwordInput = document.getElementById('registerPassword')
  const errorDiv = document.getElementById('registerPasswordError')
  const value = passwordInput.value

  if (value === '') {
    setFieldError(passwordInput, errorDiv, 'Le mot de passe est requis')
    return false
  } else if (value.length < 6) {
    setFieldError(passwordInput, errorDiv, 'Le mot de passe doit contenir au moins 6 caractères')
    return false
  } else {
    setFieldValid(passwordInput, errorDiv)
    return true
  }
}

// Validation de l'email (connexion)
function validateLoginEmail() {
  const emailInput = document.getElementById('loginEmail')
  const errorDiv = document.getElementById('loginEmailError')
  const value = emailInput.value.trim()

  if (value === '') {
    setFieldError(emailInput, errorDiv, 'L\'email est requis')
    return false
  } else if (!isValidEmail(value)) {
    setFieldError(emailInput, errorDiv, 'L\'email n\'est pas valide')
    return false
  } else {
    setFieldValid(emailInput, errorDiv)
    return true
  }
}

// Validation du mot de passe (connexion)
function validateLoginPassword() {
  const passwordInput = document.getElementById('loginPassword')
  const errorDiv = document.getElementById('loginPasswordError')
  const value = passwordInput.value

  if (value === '') {
    setFieldError(passwordInput, errorDiv, 'Le mot de passe est requis')
    return false
  } else {
    setFieldValid(passwordInput, errorDiv)
    return true
  }
}

// Vérifier la validité du formulaire d'inscription
function checkRegisterFormValidity() {
  const isNameValid = validateRegisterName()
  const isEmailValid = validateRegisterEmail()
  const isPasswordValid = validateRegisterPassword()

  const submitBtn = document.getElementById('registerSubmitBtn')
  if (submitBtn) {
    submitBtn.disabled = !(isNameValid && isEmailValid && isPasswordValid)
  }
}

// Utilitaires de validation
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

function setFieldError(input, errorDiv, message) {
  input.classList.add('is-invalid')
  input.classList.remove('is-valid')
  errorDiv.textContent = message
  errorDiv.style.display = 'block'
}

function setFieldValid(input, errorDiv) {
  input.classList.remove('is-invalid')
  input.classList.add('is-valid')
  errorDiv.textContent = ''
  errorDiv.style.display = 'none'
}

// Réinitialiser les formulaires lors du changement d'onglet
function attachTabSwitchEvents() {
  const loginTab = document.getElementById('login-tab')
  const registerTab = document.getElementById('register-tab')

  if (loginTab) {
    loginTab.addEventListener('click', () => {
      resetForm('loginForm')
    })
  }

  if (registerTab) {
    registerTab.addEventListener('click', () => {
      resetForm('registerForm')
    })
  }
}

function resetForm(formId) {
  const form = document.getElementById(formId)
  if (!form) return

  // Réinitialiser le formulaire
  form.reset()

  // Retirer toutes les classes de validation
  const inputs = form.querySelectorAll('.form-control')
  inputs.forEach(input => {
    input.classList.remove('is-invalid', 'is-valid')
  })

  // Cacher tous les messages d'erreur
  const errorDivs = form.querySelectorAll('.invalid-feedback')
  errorDivs.forEach(div => {
    div.textContent = ''
    div.style.display = 'none'
  })

  // Réactiver le bouton d'inscription si nécessaire
  if (formId === 'registerForm') {
    const submitBtn = document.getElementById('registerSubmitBtn')
    if (submitBtn) {
      submitBtn.disabled = true
    }
  }
}
