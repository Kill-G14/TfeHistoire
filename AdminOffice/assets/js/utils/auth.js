// Gestion de l'authentification admin
export const auth = {
  // Sauvegarder les données d'authentification
  saveAuthData(token, user, remember = false) {
    const storage = remember ? localStorage : sessionStorage
    storage.setItem('admin_auth_token', token)
    storage.setItem('admin_user', JSON.stringify(user))
  },

  // Récupérer le token
  getToken() {
    return localStorage.getItem('admin_auth_token') || sessionStorage.getItem('admin_auth_token')
  },

  // Récupérer l'utilisateur
  getUser() {
    const userStr = localStorage.getItem('admin_user') || sessionStorage.getItem('admin_user')
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
      try {
        await fetch('../../BackEnd/Api/auth.php', {
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
      } catch (error) {
        console.error('Erreur lors de la déconnexion:', error)
      }
    }

    // Supprimer les données locales
    localStorage.removeItem('admin_auth_token')
    localStorage.removeItem('admin_user')
    sessionStorage.removeItem('admin_auth_token')
    sessionStorage.removeItem('admin_user')

    // Rediriger vers la page de login
    window.location.href = 'login.html'
  },

  // Vérifier le token avec le backend
  async checkToken() {
    const token = this.getToken()
    if (!token) return false

    try {
      const response = await fetch('../../BackEnd/Api/auth.php', {
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

      const result = await response.json()

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
