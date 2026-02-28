// Gestion de l'authentification

import { storage } from './storage.js'

const AUTH_KEY = 'eurofetes_user'
const TOKEN_KEY = 'eurofetes_token'
const API_URL = 'http://localhost/tfeHistoire/BackEnd/Api'

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
    try {
      const response = await fetch(`${API_URL}/auth.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'login',
          email: email,
          password: password
        })
      })

      const result = await response.json()

      if (result.success) {
        // Stocker l'utilisateur et le token
        storage.set(AUTH_KEY, result.data.user)
        storage.set(TOKEN_KEY, result.data.token)
      }

      return result
    } catch (error) {
      console.error('Erreur lors de la connexion:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  },

  // Inscription avec appel API
  async register(email, password, name) {
    try {
      const response = await fetch(`${API_URL}/auth.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'register',
          email: email,
          password: password,
          name: name
        })
      })

      const result = await response.json()

      if (result.success) {
        // Stocker l'utilisateur et le token
        storage.set(AUTH_KEY, result.data.user)
        storage.set(TOKEN_KEY, result.data.token)
      }

      return result
    } catch (error) {
      console.error('Erreur lors de l\'inscription:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  },

  // Déconnexion avec appel API
  async logout() {
    const token = storage.get(TOKEN_KEY)

    if (token) {
      try {
        await fetch(`${API_URL}/auth.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'logout',
            token: token
          })
        })
      } catch (error) {
        console.error('Erreur lors de la déconnexion:', error)
      }
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
