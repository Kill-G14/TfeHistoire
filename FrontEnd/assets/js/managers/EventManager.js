// Manager pour la gestion des événements
class EventManager {
  constructor() {
    this.apiUrl = 'http://localhost/tfeHistoire/BackEnd/Api'
  }

  // Récupérer tous les événements
  async getAll() {
    try {
      const response = await fetch(`${this.apiUrl}/eventsApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'getAll'
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors du chargement des événements:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Récupérer un événement par ID
  async getById(eventId) {
    try {
      const response = await fetch(`${this.apiUrl}/eventsApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'getById',
          id: eventId
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors du chargement de l\'événement:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Créer un événement (nécessite authentification)
  async create(eventData, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/eventsApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'create',
          token: token,
          ...eventData
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la création de l\'événement:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Mettre à jour un événement (nécessite authentification)
  async update(eventId, eventData, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/eventsApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'update',
          token: token,
          id: eventId,
          ...eventData
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la mise à jour de l\'événement:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Supprimer un événement (nécessite authentification)
  async delete(eventId, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/eventsApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'delete',
          token: token,
          id: eventId
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la suppression de l\'événement:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }
}

export default new EventManager()
