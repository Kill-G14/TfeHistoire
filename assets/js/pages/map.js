// Script pour la page carte

import { renderHeader } from '../components/header.js'
import { renderLoginModal } from '../components/loginModal.js'

async function init() {
  await renderHeader('map')
  await renderLoginModal()
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init)
} else {
  init()
}
