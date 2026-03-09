// Manager pour la gestion des favoris
class FavoriteManager {
  constructor() {
    this.apiUrl = 'http://localhost/tfeHistoire/BackEnd/Api'
  }

  // Récupérer les favoris d'un utilisateur
  async getByUser(token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/favoritesApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'getMyFavorites',
          token: token
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors du chargement des favoris:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Ajouter un événement aux favoris
  async add(eventId, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/favoritesApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'add',
          token: token,
          event_id: eventId
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de l\'ajout aux favoris:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Retirer un événement des favoris
  async remove(eventId, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/favoritesApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'remove',
          token: token,
          event_id: eventId
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors du retrait des favoris:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Vérifier si un événement est dans les favoris
  async isFavorite(eventId, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/favoritesApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'isFavorite',
          token: token,
          event_id: eventId
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la vérification des favoris:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }
}

export default new FavoriteManager()
