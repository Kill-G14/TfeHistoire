// Gestion de l'authentification

import { storage } from './storage.js'

const AUTH_KEY = 'eurofetes_user'
const TOKEN_KEY = 'eurofetes_token'

export const auth = {
  // Vérifier si l'utilisateur est connecté
  isLoggedIn() {
    return !!storage.get(TOKEN_KEY)
  },

  // Récupérer l'utilisateur connecté
  getUser() {
    return storage.get(AUTH_KEY)
  },

  // Connexion (mock - en production, faire un appel API)
  login(email, password) {
    // Simulation d'authentification
    const user = {
      id: Date.now(),
      email: email,
      name: email.split('@')[0]
    }

    const token = 'mock_token_' + Date.now()

    storage.set(AUTH_KEY, user)
    storage.set(TOKEN_KEY, token)

    return { success: true, user }
  },

  // Inscription (mock - en production, faire un appel API)
  register(email, password, name) {
    // Simulation d'inscription
    const user = {
      id: Date.now(),
      email: email,
      name: name
    }

    const token = 'mock_token_' + Date.now()

    storage.set(AUTH_KEY, user)
    storage.set(TOKEN_KEY, token)

    return { success: true, user }
  },

  // Déconnexion
  logout() {
    storage.remove(AUTH_KEY)
    storage.remove(TOKEN_KEY)
    return { success: true }
  },

  // Récupérer le token
  getToken() {
    return storage.get(TOKEN_KEY)
  }
}
