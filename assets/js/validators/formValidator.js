// Validator utilitaire pour la gestion des formulaires

/**
 * Marque un champ de formulaire comme invalide et affiche le message d'erreur
 * 
 * @param {HTMLElement} input - L'élément input
 * @param {HTMLElement} errorDiv - L'élément d'affichage de l'erreur
 * @param {string} message - Le message d'erreur
 */
export function setFieldError(input, errorDiv, message) {
  if (!input) return
  
  input.classList.add('is-invalid')
  input.classList.remove('is-valid')
  
  if (errorDiv) {
    errorDiv.textContent = message
    errorDiv.style.display = 'block'
  }
}

/**
 * Marque un champ de formulaire comme valide et masque le message d'erreur
 * 
 * @param {HTMLElement} input - L'élément input
 * @param {HTMLElement} errorDiv - L'élément d'affichage de l'erreur
 */
export function setFieldValid(input, errorDiv) {
  if (!input) return
  
  input.classList.remove('is-invalid')
  input.classList.add('is-valid')
  
  if (errorDiv) {
    errorDiv.textContent = ''
    errorDiv.style.display = 'none'
  }
}

/**
 * Réinitialise l'état de validation d'un champ
 * 
 * @param {HTMLElement} input - L'élément input
 * @param {HTMLElement} errorDiv - L'élément d'affichage de l'erreur
 */
export function clearFieldValidation(input, errorDiv) {
  if (!input) return
  
  input.classList.remove('is-invalid', 'is-valid')
  
  if (errorDiv) {
    errorDiv.textContent = ''
    errorDiv.style.display = 'none'
  }
}

/**
 * Applique les erreurs de validation à un formulaire
 * 
 * @param {Object} errors - Objet contenant les erreurs {fieldName: errorMessage}
 * @param {Object} fields - Objet contenant les références aux champs {fieldName: {input, errorDiv}}
 */
export function applyFormErrors(errors, fields) {
  // Réinitialiser tous les champs
  Object.keys(fields).forEach(fieldName => {
    const { input, errorDiv } = fields[fieldName]
    clearFieldValidation(input, errorDiv)
  })
  
  // Appliquer les erreurs
  Object.keys(errors).forEach(fieldName => {
    if (fields[fieldName]) {
      const { input, errorDiv } = fields[fieldName]
      setFieldError(input, errorDiv, errors[fieldName])
    }
  })
}

/**
 * Valide un champ et applique visuellement le résultat
 * 
 * @param {HTMLElement} input - L'élément input
 * @param {HTMLElement} errorDiv - L'élément d'affichage de l'erreur
 * @param {Function} validationFn - Fonction de validation qui retourne {valid, error}
 * @param {*} value - La valeur à valider
 * @returns {boolean} - true si valide, false sinon
 */
export function validateField(input, errorDiv, validationFn, value) {
  const result = validationFn(value)
  
  if (result.valid) {
    setFieldValid(input, errorDiv)
    return true
  } else {
    setFieldError(input, errorDiv, result.error)
    return false
  }
}

/**
 * Attache des événements de validation en temps réel à un champ
 * 
 * @param {HTMLElement} input - L'élément input
 * @param {HTMLElement} errorDiv - L'élément d'affichage de l'erreur
 * @param {Function} validationFn - Fonction de validation
 * @param {Function} onValidChange - Callback appelé quand la validation change
 */
export function attachFieldValidation(input, errorDiv, validationFn, onValidChange = null) {
  if (!input) return
  
  const validate = () => {
    const isValid = validateField(input, errorDiv, validationFn, input.value)
    if (onValidChange) {
      onValidChange(isValid)
    }
  }
  
  input.addEventListener('input', validate)
  input.addEventListener('blur', validate)
}

/**
 * Récupère les valeurs d'un formulaire sous forme d'objet
 * 
 * @param {HTMLFormElement|Object} formOrFields - Le formulaire ou un objet de champs
 * @returns {Object} - Les valeurs du formulaire
 */
export function getFormValues(formOrFields) {
  if (formOrFields instanceof HTMLFormElement) {
    const formData = new FormData(formOrFields)
    const values = {}
    for (const [key, value] of formData.entries()) {
      values[key] = value
    }
    return values
  }
  
  // Si c'est un objet de champs
  const values = {}
  Object.keys(formOrFields).forEach(key => {
    const field = formOrFields[key]
    if (field && field.value !== undefined) {
      values[key] = field.value
    }
  })
  return values
}
