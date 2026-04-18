// Manager pour la gestion des événements côté admin
class EventManager {
  constructor() {
    this.apiUrl = "http://localhost/tfeHistoire/BackEnd/Api/adminApi.php";
  }

  // Récupérer tous les événements
  async getAll(token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "events",
          action: "getAll",
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur lors du chargement des événements:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  // Récupérer les événements en attente (admin)
  async getPending(token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "events",
          action: "getPending",
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error(
        "Erreur lors du chargement des événements en attente:",
        error,
      );
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  // Approuver un événement (admin)
  async approve(eventId, token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "events",
          action: "approve",
          id: eventId,
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur lors de l'approbation de l'événement:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  // Rejeter un événement (admin)
  async reject(eventId, token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "events",
          action: "reject",
          id: eventId,
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur lors du rejet de l'événement:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  // Supprimer un événement (admin)
  async adminDelete(eventId, token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "events",
          action: "delete",
          id: eventId,
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur lors de la suppression de l'événement:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }
}

// Export d'une instance singleton
export default new EventManager();
