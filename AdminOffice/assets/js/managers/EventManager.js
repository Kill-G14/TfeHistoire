// Manager pour la gestion des événements côté admin
import { config } from "../../../../assets/js/config.js";

class EventManager {
  constructor() {
    this.apiUrl = `${config.API_URL}/adminApi.php`;
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
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  // Récupérer les modifications en attente
  async getPendingModifications(token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "events",
          action: "getPendingModifications",
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

  // Approuver une modification
  async approveModification(modificationId, token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "events",
          action: "approveModification",
          modification_id: modificationId,
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

  // Rejeter une modification
  async rejectModification(modificationId, reason, token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "events",
          action: "rejectModification",
          modification_id: modificationId,
          reason: reason,
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

  // Récupérer les suppressions en attente
  async getPendingDeletions(token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "events",
          action: "getPendingDeletions",
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

  // Approuver une suppression
  async approveDeletion(eventId, adminMessage, token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "events",
          action: "approveDeletion",
          event_id: eventId,
          admin_message: adminMessage || null,
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

  // Rejeter une suppression
  async rejectDeletion(eventId, reason, token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "events",
          action: "rejectDeletion",
          event_id: eventId,
          reason: reason,
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
}

// Export d'une instance singleton
export default new EventManager();
