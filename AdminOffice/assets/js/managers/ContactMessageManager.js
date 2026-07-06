// Manager pour la gestion des messages de contact côté admin
import { config } from "../../../../assets/js/config.js";

class ContactMessageManager {
  constructor() {
    this.apiUrl = `${config.API_URL}/adminApi.php`;
  }

  // Récupérer tous les messages de contact
  async getAll(token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "contact_messages",
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

  // Marquer un message comme traité
  async markAsTreated(messageId, token) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          resource: "contact_messages",
          action: "markAsTreated",
          id: messageId,
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
export default new ContactMessageManager();
