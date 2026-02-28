// Script pour la page profil

import { renderHeader } from '../components/header.js'
import { renderLoginModal } from '../components/loginModal.js'
import { auth } from '../utils/auth.js'
import { helpers } from '../utils/helpers.js'

async function init() {
  await renderHeader()
  await renderLoginModal()
  
  // Vérifier si l'utilisateur est connecté
  if (!auth.isLoggedIn()) {
    helpers.showToast('Vous devez être connecté pour accéder à votre profil', 'error')
    setTimeout(() => {
      window.location.href = '../pages/index.html'
    }, 1500)
    return
  }

  displayUserInfo()
}

function displayUserInfo() {
  const user = auth.getUser()
  const userNameEl = document.getElementById('userName')
  const userEmailEl = document.getElementById('userEmail')

  if (userNameEl && user) {
    userNameEl.textContent = user.name || 'Utilisateur'
  }

  if (userEmailEl && user) {
    userEmailEl.textContent = user.email || ''
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init)
} else {
  init()
}
