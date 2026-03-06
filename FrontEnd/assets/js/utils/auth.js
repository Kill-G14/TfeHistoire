// Gestion de l'authentification

import { storage } from './storage.js'
import AuthManager from '../managers/AuthManager.js'

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

  // Connexion avec appel API
  async login(email, password) {
    const result = await AuthManager.login(email, password)

    if (result.success) {
      // Stocker l'utilisateur et le token
      storage.set(AUTH_KEY, result.data.user)
      storage.set(TOKEN_KEY, result.data.token)
    }

    return result
  },

  // Inscription avec appel API
  async register(email, password, name) {
    const result = await AuthManager.register(email, password, name)

    if (result.success) {
      // Stocker l'utilisateur et le token
      storage.set(AUTH_KEY, result.data.user)
      storage.set(TOKEN_KEY, result.data.token)
    }

    return result
  },

  // Déconnexion avec appel API
  async logout() {
    const token = storage.get(TOKEN_KEY)

    if (token) {
      await AuthManager.logout(token)
    }

    // Supprimer les données locales dans tous les cas
    storage.remove(AUTH_KEY)
    storage.remove(TOKEN_KEY)
    
    return { success: true }
  },

  // Récupérer le token
  getToken() {
    return storage.get(TOKEN_KEY)
  }
}
