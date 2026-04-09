// Validator pour les images - Validation frontend des fichiers images

/**
 * Vérifie les magic bytes (signature binaire) du fichier
 * pour s'assurer qu'il s'agit d'une vraie image du format attendu
 * 
 * @param {File} file - Le fichier à valider
 * @returns {Promise<{valid: boolean, type: string|null}>}
 */
export async function validateImageMagicBytes(file) {
  return new Promise((resolve) => {
    const reader = new FileReader()
    
    reader.onload = (e) => {
      const arr = new Uint8Array(e.target.result)
      
      // Vérifier les signatures binaires
      // JPEG: FF D8 FF
      if (arr[0] === 0xFF && arr[1] === 0xD8 && arr[2] === 0xFF) {
        resolve({ valid: true, type: 'JPEG' })
        return
      }
      
      // PNG: 89 50 4E 47 0D 0A 1A 0A
      if (arr[0] === 0x89 && arr[1] === 0x50 && arr[2] === 0x4E && arr[3] === 0x47 &&
          arr[4] === 0x0D && arr[5] === 0x0A && arr[6] === 0x1A && arr[7] === 0x0A) {
        resolve({ valid: true, type: 'PNG' })
        return
      }
      
      // WEBP: RIFF....WEBP (bytes 0-3: RIFF, bytes 8-11: WEBP)
      if (arr[0] === 0x52 && arr[1] === 0x49 && arr[2] === 0x46 && arr[3] === 0x46 &&
          arr[8] === 0x57 && arr[9] === 0x45 && arr[10] === 0x42 && arr[11] === 0x50) {
        resolve({ valid: true, type: 'WEBP' })
        return
      }
      
      // Si aucune signature ne correspond
      resolve({ valid: false, type: null })
    }
    
    reader.onerror = () => {
      resolve({ valid: false, type: null })
    }
    
    // Lire les 12 premiers octets (suffisant pour détecter les signatures)
    reader.readAsArrayBuffer(file.slice(0, 12))
  })
}

/**
 * Valide complètement un fichier image
 * Effectue toutes les vérifications nécessaires
 * 
 * @param {File} file - Le fichier à valider
 * @param {Object} options - Options de validation
 * @param {number} options.maxSize - Taille maximale en octets (défaut: 5MB)
 * @param {string[]} options.allowedExtensions - Extensions autorisées
 * @param {string[]} options.allowedMimeTypes - Types MIME autorisés
 * @returns {Promise<{valid: boolean, error: string|null}>}
 */
export async function validateImageFile(file, options = {}) {
  const {
    maxSize = 5 * 1024 * 1024, // 5 MB par défaut
    allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'],
    allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp']
  } = options

  // 1. Valider l'extension du fichier
  const fileExtension = file.name.split('.').pop().toLowerCase()
  
  if (!allowedExtensions.includes(fileExtension)) {
    return {
      valid: false,
      error: `Extension non autorisée. Utilisez ${allowedExtensions.join(', ').toUpperCase()} uniquement.`
    }
  }

  // 2. Valider le type MIME
  if (!allowedMimeTypes.includes(file.type)) {
    return {
      valid: false,
      error: `Type de fichier non autorisé. Utilisez ${allowedExtensions.join(', ').toUpperCase()} uniquement.`
    }
  }

  // 3. Valider la taille
  if (file.size > maxSize) {
    const maxSizeMB = (maxSize / (1024 * 1024)).toFixed(0)
    return {
      valid: false,
      error: `L'image est trop lourde. Maximum ${maxSizeMB} MB.`
    }
  }

  // 4. Vérifier les magic bytes (signature binaire du fichier)
  const magicBytesCheck = await validateImageMagicBytes(file)
  
  if (!magicBytesCheck.valid) {
    return {
      valid: false,
      error: 'Le fichier n\'est pas une image valide. Le contenu ne correspond pas à un format d\'image autorisé.'
    }
  }
  
  // 5. Vérifier la cohérence entre extension et contenu réel
  const extensionTypeMap = {
    'jpg': 'JPEG',
    'jpeg': 'JPEG',
    'png': 'PNG',
    'webp': 'WEBP'
  }
  
  const expectedType = extensionTypeMap[fileExtension]
  if (expectedType !== magicBytesCheck.type) {
    return {
      valid: false,
      error: `Le fichier semble être un ${magicBytesCheck.type} mais a une extension .${fileExtension}. Veuillez utiliser le bon format.`
    }
  }

  // Toutes les validations sont passées
  return {
    valid: true,
    error: null
  }
}

/**
 * Crée un aperçu (preview) d'une image
 * 
 * @param {File} file - Le fichier image
 * @returns {Promise<string>} - Data URL de l'image
 */
export async function createImagePreview(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    
    reader.onload = (event) => {
      resolve(event.target.result)
    }
    
    reader.onerror = () => {
      reject(new Error('Erreur lors de la lecture du fichier'))
    }
    
    reader.readAsDataURL(file)
  })
}
