// Manager pour la gestion de l'authentification admin
const API_URL = 'http://localhost/tfeHistoire/BackEnd/Api'

export const AuthManager = {
  // Connexion admin/moderator
  async login(email, password) {
    try {
      const response = await fetch(`${API_URL}/authApi.php`, {
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

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la connexion:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  },

  // Déconnexion
  async logout(token) {
    if (!token) {
      return {
        success: false,
        message: 'Token manquant'
      }
    }

    try {
      const response = await fetch(`${API_URL}/authApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          action: 'logout',
          token: token
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  },

  // Vérifier le token avec le backend
  async checkToken(token) {
    if (!token) {
      return {
        success: false,
        message: 'Token manquant'
      }
    }

    try {
      const response = await fetch(`${API_URL}/authApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          action: 'getCurrentUser',
          token: token
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la vérification du token:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }
}
