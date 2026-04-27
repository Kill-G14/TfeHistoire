// Manager pour la gestion des commandes
class OrderManager {
  constructor() {
    this.apiUrl = 'http://localhost/tfeHistoire/BackEnd/Api'
  }

  // Récupérer les commandes d'un utilisateur
  async getByUser(token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/ordersApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'getMyOrders',
          token: token
        })
      })

      return await response.json()
    } catch (error) {
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Récupérer une commande par ID
  async getById(orderId, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/ordersApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'getOrderById',
          token: token,
          id: orderId
        })
      })

      return await response.json()
    } catch (error) {
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Créer une commande
  async create(orderData, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/ordersApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'create',
          token: token,
          ...orderData
        })
      })

      return await response.json()
    } catch (error) {
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }

  // Annuler une commande
  async cancel(orderId, token) {
    if (!token) {
      return {
        success: false,
        message: 'Non authentifié'
      }
    }

    try {
      const response = await fetch(`${this.apiUrl}/ordersApi.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'cancel',
          token: token,
          id: orderId
        })
      })

      return await response.json()
    } catch (error) {
      return {
        success: false,
        message: 'Erreur de connexion au serveur'
      }
    }
  }
}

export default new OrderManager()
