// Manager pour l'envoi des messages de contact
import { config } from "../config.js";

class ContactManager {
  constructor() {
    this.apiUrl = config.API_URL;
  }

  // Envoyer un message de contact
  async send(data) {
    try {
      const response = await fetch(`${this.apiUrl}/contactApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "send",
          name: data.name,
          email: data.email,
          subject: data.subject,
          message: data.message,
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

export default new ContactManager();
