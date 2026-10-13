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

// Réglages de l'image envoyée au serveur
const UPLOAD_MAX_SIDE = 1200 // px, côté le plus long
const UPLOAD_TARGET_BYTES = 1.5 * 1024 * 1024 // reste sous upload_max_filesize (2 MB par défaut)

/**
 * Réduit une image dans le navigateur avant l'envoi : l'utilisateur peut choisir
 * une photo de 12 MB, le fichier envoyé pèsera quelques centaines de Ko.
 * La sortie est toujours un JPEG (les métadonnées d'origine sont retirées).
 *
 * @param {File} file - Image d'origine (déjà validée)
 * @returns {Promise<{file: File|null, error: string|null}>}
 */
export async function resizeImageForUpload(file) {
  let bitmap
  try {
    bitmap = await createImageBitmap(file)
  } catch (error) {
    return { file: null, error: 'Impossible de lire cette image.' }
  }

  let scale = Math.min(1, UPLOAD_MAX_SIDE / Math.max(bitmap.width, bitmap.height))
  const canvas = document.createElement('canvas')
  const context = canvas.getContext('2d')

  const toBlob = (quality) =>
    new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality))

  // On réduit d'abord la qualité, puis les dimensions, jusqu'à passer sous la taille cible
  for (let attempt = 0; attempt < 4; attempt++) {
    canvas.width = Math.max(1, Math.round(bitmap.width * scale))
    canvas.height = Math.max(1, Math.round(bitmap.height * scale))

    // Fond blanc : une image PNG transparente deviendrait noire en JPEG
    context.fillStyle = '#ffffff'
    context.fillRect(0, 0, canvas.width, canvas.height)
    context.drawImage(bitmap, 0, 0, canvas.width, canvas.height)

    for (const quality of [0.8, 0.7, 0.6, 0.5]) {
      const blob = await toBlob(quality)
      if (blob && blob.size <= UPLOAD_TARGET_BYTES) {
        bitmap.close()
        const baseName = file.name.replace(/\.[^.]+$/, '')
        return {
          file: new File([blob], `${baseName}.jpg`, { type: 'image/jpeg' }),
          error: null
        }
      }
    }

    scale *= 0.75
  }

  bitmap.close()
  return { file: null, error: "Impossible de réduire cette image à une taille acceptable." }
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
