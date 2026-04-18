// Manager pour la gestion des paiements Stripe
class CheckoutManager {
  constructor() {
    this.apiUrl = "http://localhost/tfeHistoire/BackEnd/Api";
  }

  /**
   * Récupérer la clé publique Stripe
   */
  async getPublishableKey() {
    try {
      const response = await fetch(`${this.apiUrl}/stripeApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "getPublishableKey",
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur lors de la récupération de la clé Stripe:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Créer une session de checkout Stripe
   */
  async createCheckoutSession(token, orderId, orderItems) {
    try {
      const response = await fetch(`${this.apiUrl}/stripeApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "createCheckoutSession",
          token: token,
          order_id: orderId,
          order_items: orderItems,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error(
        "Erreur lors de la création de la session de checkout:",
        error,
      );
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Récupérer le statut d'un paiement
   */
  async getPaymentStatus(token, paymentId) {
    try {
      const response = await fetch(`${this.apiUrl}/stripeApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "getPaymentStatus",
          token: token,
          payment_id: paymentId,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error(
        "Erreur lors de la récupération du statut de paiement:",
        error,
      );
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Récupérer tous les paiements d'une commande
   */
  async getPaymentsByOrder(token, orderId) {
    try {
      const response = await fetch(`${this.apiUrl}/stripeApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "getPaymentsByOrder",
          token: token,
          order_id: orderId,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur lors de la récupération des paiements:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Demander un remboursement (admin/organisateur uniquement)
   */
  async requestRefund(token, paymentId, amount = null) {
    try {
      const body = {
        action: "requestRefund",
        token: token,
        payment_id: paymentId,
      };

      if (amount !== null) {
        body.amount = amount;
      }

      const response = await fetch(`${this.apiUrl}/stripeApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(body),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur lors de la demande de remboursement:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }
}

// Export d'une instance singleton
export default new CheckoutManager();
