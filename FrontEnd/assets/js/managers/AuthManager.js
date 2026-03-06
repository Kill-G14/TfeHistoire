// Manager pour la gestion de l'authentification
class AuthManager {
  constructor() {
    this.apiUrl = 'http://localhost/tfeHistoire/BackEnd/Api'
  }

  // Connexion
  async login(email, password) {
    try {
      const response = await fetch(`${this.apiUrl}/authApi.php`, {
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
  }

  // Inscription
  async register(email, password, name) {
    try {
      const response = await fetch(`${this.apiUrl}/authApi.php`, {
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

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de l\'inscription:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Déconnexion
  async logout(token) {
    if (!token) {
      return {
        success: false,
        message: 'Token manquant'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/authApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
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
  }

  // Récupérer l'utilisateur actuel
  async getCurrentUser(token) {
    if (!token) {
      return {
        success: false,
        message: 'Token manquant'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/authApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'getCurrentUser',
          token: token
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la récupération de l\'utilisateur:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }
}

export default new AuthManager()
