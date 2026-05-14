// Gestion de l'authentification admin
import AuthManager from '../managers/AuthManager.js'

export const auth = {
  // Sauvegarder les données d'authentification
  saveAuthData(token, user, remember = false) {
    const storage = remember ? localStorage : sessionStorage
    storage.setItem('memoriaeventia_admin_token', token)
    storage.setItem('memoriaeventia_admin_user', JSON.stringify(user))
  },

  // Récupérer le token
  getToken() {
    return localStorage.getItem('memoriaeventia_admin_token') || sessionStorage.getItem('memoriaeventia_admin_token')
  },

  // Récupérer l'utilisateur
  getUser() {
    const userStr = localStorage.getItem('memoriaeventia_admin_user') || sessionStorage.getItem('memoriaeventia_admin_user')
    return userStr ? JSON.parse(userStr) : null
  },

  // Vérifier si l'utilisateur est authentifié
  isAuthenticated() {
    return this.getToken() !== null
  },

  // Vérifier si l'utilisateur est admin ou moderator
  isAdminOrModerator() {
    const user = this.getUser()
    if (!user) return false
    return user.role === 'admin' || user.role === 'moderator'
  },

  // Vérifier si l'utilisateur est admin
  isAdmin() {
    const user = this.getUser()
    if (!user) return false
    return user.role === 'admin'
  },

  // Déconnexion
  async logout() {
    const token = this.getToken()

    if (token) {
      await AuthManager.logout(token)
    }

    // Supprimer les données locales
    localStorage.removeItem('memoriaeventia_admin_token')
    localStorage.removeItem('memoriaeventia_admin_user')
    sessionStorage.removeItem('memoriaeventia_admin_token')
    sessionStorage.removeItem('memoriaeventia_admin_user')

    // Rediriger vers la page de login
    window.location.href = 'login.html'
  },

  // Vérifier le token avec le backend
  async checkToken() {
    const token = this.getToken()
    if (!token) return false

    const result = await AuthManager.checkToken(token)

    if (!result.success) {
      this.logout()
      return false
    }

    // Vérifier le rôle
    if (!this.isAdminOrModerator()) {
      this.logout()
      return false
      }

      return true
    } catch (error) {
      console.error('Erreur lors de la vérification du token:', error)
      return false
    }
  }
}
