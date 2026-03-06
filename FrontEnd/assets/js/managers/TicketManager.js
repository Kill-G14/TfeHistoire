// Manager pour la gestion des tickets
class TicketManager {
  constructor() {
    this.apiUrl = 'http://localhost/tfeHistoire/BackEnd/Api'
  }

  // Récupérer les tickets d'un événement
  async getByEvent(eventId) {
    try {
      const response = await fetch(`${this.apiUrl}/ticketsApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'getByEvent',
          event_id: eventId
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors du chargement des tickets:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Récupérer un ticket par ID
  async getById(ticketId) {
    try {
      const response = await fetch(`${this.apiUrl}/ticketsApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'getById',
          id: ticketId
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors du chargement du ticket:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Créer un ticket (admin)
  async create(ticketData, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/ticketsApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'create',
          token: token,
          ...ticketData
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la création du ticket:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Mettre à jour un ticket (admin)
  async update(ticketId, ticketData, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/ticketsApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'update',
          token: token,
          id: ticketId,
          ...ticketData
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la mise à jour du ticket:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Supprimer un ticket (admin)
  async delete(ticketId, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/ticketsApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'delete',
          token: token,
          id: ticketId
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la suppression du ticket:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Récupérer les tickets achetés par un utilisateur
  async getPurchasedByUser(token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/ticketsGeneratedApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'getByUser',
          token: token
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors du chargement des tickets achetés:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Scanner un ticket
  async scan(ticketCode, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/scanTicketApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'scan',
          token: token,
          ticket_code: ticketCode
        })
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors du scan du ticket:', error)
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }
}

export default new TicketManager()
