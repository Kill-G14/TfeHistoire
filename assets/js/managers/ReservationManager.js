// Manager pour la gestion des réservations
class ReservationManager {
  constructor() {
    this.apiUrl = "http://localhost/tfeHistoire/BackEnd/Api";
  }

  /**
   * Créer une réservation
   */
  async create(eventId, quantity = 1, token) {
    if (!token) {
      return {
        success: false,
        message: "Non authentifié",
      };
    }

    try {
      const response = await fetch(`${this.apiUrl}/reservationsApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "create",
          token: token,
          event_id: eventId,
          quantity: quantity,
        }),
      });

      return await response.json();
    } catch (error) {
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Récupérer les réservations de l'utilisateur
   */
  async getMyReservations(token) {
    if (!token) {
      return {
        success: false,
        message: "Non authentifié",
      };
    }

    try {
      const response = await fetch(`${this.apiUrl}/reservationsApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "getMyReservations",
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Annuler une réservation
   */
  async cancel(reservationId, token) {
    if (!token) {
      return {
        success: false,
        message: "Non authentifié",
      };
    }

    try {
      const response = await fetch(`${this.apiUrl}/reservationsApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "cancel",
          token: token,
          reservation_id: reservationId,
        }),
      });

      return await response.json();
    } catch (error) {
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Récupérer le nombre de places disponibles
   */
  async getAvailableTickets(eventId) {
    try {
      const response = await fetch(`${this.apiUrl}/reservationsApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "getAvailableTickets",
          event_id: eventId,
        }),
      });

      return await response.json();
    } catch (error) {
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Vérifier si l'utilisateur a déjà réservé
   */
  async checkReservation(eventId, token) {
    if (!token) {
      return {
        success: false,
        message: "Non authentifié",
      };
    }

    try {
      const response = await fetch(`${this.apiUrl}/reservationsApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "checkReservation",
          token: token,
          event_id: eventId,
        }),
      });

      return await response.json();
    } catch (error) {
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }
}

// Export d'une instance singleton
export default new ReservationManager();
