// Manager pour les billets : QR codes de chaque place et téléchargement du PDF
import { config } from "../config.js";

class TicketManager {
  constructor() {
    this.apiUrl = config.API_URL;
  }

  // Appel commun : les billets exigent toujours le token de l'utilisateur
  async call(action, token, reservationId) {
    if (!token) {
      return { response: null, message: "Non authentifié" };
    }

    try {
      const response = await fetch(`${this.apiUrl}/ticketApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action,
          token,
          reservation_id: reservationId,
        }),
      });

      return { response, message: null };
    } catch (error) {
      return { response: null, message: "Erreur de connexion au serveur" };
    }
  }

  // Billets d'une réservation (un par place, avec son QR code)
  async getTickets(reservationId, token) {
    const { response, message } = await this.call(
      "getTickets",
      token,
      reservationId,
    );

    if (!response) {
      return { success: false, message };
    }

    return await response.json();
  }

  // Vérification publique d'un billet (page ouverte en scannant le QR code)
  async verifyTicket(code) {
    try {
      const response = await fetch(`${this.apiUrl}/ticketApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ action: "verifyTicket", code }),
      });

      return await response.json();
    } catch (error) {
      return { success: false, message: "Erreur de connexion au serveur" };
    }
  }

  // PDF de la réservation : retourne le fichier prêt à être téléchargé
  async downloadPdf(reservationId, token) {
    const { response, message } = await this.call(
      "downloadPdf",
      token,
      reservationId,
    );

    if (!response) {
      return { success: false, message };
    }

    // En cas d'erreur, l'API répond en JSON avec un message lisible
    if (!response.ok) {
      const error = await response.json().catch(() => null);
      return {
        success: false,
        message: error?.message || "Impossible de télécharger le billet",
      };
    }

    return {
      success: true,
      blob: await response.blob(),
      filename: `billets-MEM-${String(reservationId).padStart(6, "0")}.pdf`,
    };
  }
}

export default new TicketManager();
