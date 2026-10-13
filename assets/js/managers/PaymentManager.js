// Manager pour les paiements (Stripe Connect) : compte organisateur et paiement des réservations
import { config } from "../config.js";

class PaymentManager {
  constructor() {
    this.apiUrl = config.API_URL;
  }

  // Appel commun : toutes les actions de paiement exigent le token de l'utilisateur
  async call(action, token, data = {}) {
    if (!token) {
      return { success: false, message: "Non authentifié" };
    }

    try {
      const response = await fetch(`${this.apiUrl}/paymentApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ action, token, ...data }),
      });

      return await response.json();
    } catch (error) {
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  // URL d'inscription Stripe pour relier le compte de l'organisateur
  connectStripeAccount(token) {
    return this.call("connectStripeAccount", token);
  }

  // État du compte Stripe de l'organisateur (connecté ou non)
  getStripeAccountStatus(token) {
    return this.call("getStripeAccountStatus", token);
  }

  // URL de la page de paiement Stripe pour une réservation en attente
  createCheckoutSession(reservationId, token) {
    return this.call("createCheckoutSession", token, {
      reservation_id: reservationId,
    });
  }

  // Vérification du paiement au retour de Stripe
  confirmReturn(reservationId, token) {
    return this.call("confirmReturn", token, { reservation_id: reservationId });
  }

  // Abandon d'un paiement : libère les places retenues
  abandonPayment(reservationId, token) {
    return this.call("abandonPayment", token, {
      reservation_id: reservationId,
    });
  }
}

export default new PaymentManager();
