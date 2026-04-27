// Validator pour l'authentification - Validation des données utilisateur

/**
 * Valide une adresse email
 * 
 * @param {string} email - L'email à valider
 * @returns {{valid: boolean, error: string|null}}
 */
export function validateEmail(email) {
  const trimmedEmail = email.trim()
  
  if (trimmedEmail === '') {
    return {
      valid: false,
      error: 'L\'email est requis'
    }
  }
  
  // Regex email simple mais efficace
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  
  if (!emailRegex.test(trimmedEmail)) {
    return {
      valid: false,
      error: 'L\'email n\'est pas valide'
    }
  }
  
  return {
    valid: true,
    error: null
  }
}

/**
 * Valide un mot de passe
 * 
 * @param {string} password - Le mot de passe à valider
 * @param {Object} options - Options de validation
 * @param {number} options.minLength - Longueur minimale (défaut: 6)
 * @param {boolean} options.required - Si le champ est requis (défaut: true)
 * @returns {{valid: boolean, error: string|null}}
 */
export function validatePassword(password, options = {}) {
  const {
    minLength = 6,
    required = true
  } = options
  
  if (password === '' && required) {
    return {
      valid: false,
      error: 'Le mot de passe est requis'
    }
  }
  
  if (password === '' && !required) {
    return {
      valid: true,
      error: null
    }
  }
  
  if (password.length < minLength) {
    return {
      valid: false,
      error: `Le mot de passe doit contenir au moins ${minLength} caractères`
    }
  }
  
  return {
    valid: true,
    error: null
  }
}

/**
 * Valide un nom d'utilisateur
 * 
 * @param {string} name - Le nom à valider
 * @param {Object} options - Options de validation
 * @param {number} options.minLength - Longueur minimale (défaut: 2)
 * @returns {{valid: boolean, error: string|null}}
 */
export function validateName(name, options = {}) {
  const {
    minLength = 2
  } = options
  
  const trimmedName = name.trim()
  
  if (trimmedName === '') {
    return {
      valid: false,
      error: 'Le nom est requis'
    }
  }
  
  if (trimmedName.length < minLength) {
    return {
      valid: false,
      error: `Le nom doit contenir au moins ${minLength} caractères`
    }
  }
  
  return {
    valid: true,
    error: null
  }
}

/**
 * Valide que deux mots de passe correspondent
 * 
 * @param {string} password - Le mot de passe
 * @param {string} confirmPassword - La confirmation du mot de passe
 * @returns {{valid: boolean, error: string|null}}
 */
export function validatePasswordMatch(password, confirmPassword) {
  if (password !== confirmPassword) {
    return {
      valid: false,
      error: 'Les mots de passe ne correspondent pas'
    }
  }
  
  return {
    valid: true,
    error: null
  }
}

/**
 * Valide un formulaire de connexion
 * 
 * @param {Object} data - Les données du formulaire
 * @param {string} data.email - L'email
 * @param {string} data.password - Le mot de passe
 * @returns {{valid: boolean, errors: Object}}
 */
export function validateLoginForm(data) {
  const errors = {}
  
  const emailValidation = validateEmail(data.email)
  if (!emailValidation.valid) {
    errors.email = emailValidation.error
  }
  
  const passwordValidation = validatePassword(data.password)
  if (!passwordValidation.valid) {
    errors.password = passwordValidation.error
  }
  
  return {
    valid: Object.keys(errors).length === 0,
    errors
  }
}

/**
 * Valide un formulaire d'inscription
 * 
 * @param {Object} data - Les données du formulaire
 * @param {string} data.name - Le nom
 * @param {string} data.email - L'email
 * @param {string} data.password - Le mot de passe
 * @returns {{valid: boolean, errors: Object}}
 */
export function validateRegisterForm(data) {
  const errors = {}
  
  const nameValidation = validateName(data.name)
  if (!nameValidation.valid) {
    errors.name = nameValidation.error
  }
  
  const emailValidation = validateEmail(data.email)
  if (!emailValidation.valid) {
    errors.email = emailValidation.error
  }
  
  const passwordValidation = validatePassword(data.password)
  if (!passwordValidation.valid) {
    errors.password = passwordValidation.error
  }
  
  return {
    valid: Object.keys(errors).length === 0,
    errors
  }
}

/**
 * Valide un formulaire de changement de mot de passe
 * 
 * @param {Object} data - Les données du formulaire
 * @param {string} data.currentPassword - Le mot de passe actuel
 * @param {string} data.newPassword - Le nouveau mot de passe
 * @param {string} data.confirmPassword - La confirmation du nouveau mot de passe
 * @returns {{valid: boolean, errors: Object}}
 */
export function validateChangePasswordForm(data) {
  const errors = {}
  
  const currentPasswordValidation = validatePassword(data.currentPassword)
  if (!currentPasswordValidation.valid) {
    errors.currentPassword = currentPasswordValidation.error
  }
  
  const newPasswordValidation = validatePassword(data.newPassword)
  if (!newPasswordValidation.valid) {
    errors.newPassword = newPasswordValidation.error
  }
  
  const passwordMatchValidation = validatePasswordMatch(data.newPassword, data.confirmPassword)
  if (!passwordMatchValidation.valid) {
    errors.confirmPassword = passwordMatchValidation.error
  }
  
  return {
    valid: Object.keys(errors).length === 0,
    errors
  }
}
