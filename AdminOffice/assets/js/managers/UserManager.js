// Manager pour la gestion des utilisateurs côté admin
class UserManager {
  constructor() {
    this.apiUrl = 'http://localhost/tfeHistoire/BackEnd/Api/adminApi.php'
  }

  // Récupérer tous les utilisateurs
  async getAll(token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          resource: 'users',
          action: 'getAll',
          token: token
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors du chargement des utilisateurs:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Mettre à jour les droits d'un utilisateur
  async updateRoles(userId, roles, token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          resource: 'users',
          action: 'updateRoles',
          id: userId,
          is_admin: roles.isAdmin,
          is_organizer: roles.isOrganizer,
          is_moderator: roles.isModerator,
          token: token
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la mise à jour des droits:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Supprimer un utilisateur
  async delete(userId, token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          resource: 'users',
          action: 'delete',
          id: userId,
          token: token
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la suppression de l\'utilisateur:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }
}

// Export d'une instance singleton
export default new UserManager()
