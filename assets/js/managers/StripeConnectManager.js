// Manager pour la gestion de Stripe Connect
class StripeConnectManager {
  constructor() {
    this.apiUrl =
      "http://localhost/tfeHistoire/BackEnd/Api/stripeConnectApi.php";
  }

  /**
   * Vérifier si l'utilisateur a un compte Stripe connecté
   */
  async checkStripeAccount() {
    try {
      const token = localStorage.getItem("token");

      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "checkStripeAccount",
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Créer un compte Stripe Connect et obtenir l'URL d'onboarding
   */
  async createStripeConnectAccount() {
    try {
      const token = localStorage.getItem("token");

      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "createConnectAccount",
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Vérifier si l'onboarding Stripe est complété
   */
  async verifyAccountCompletion() {
    try {
      const token = localStorage.getItem("token");

      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "verifyAccountCompletion",
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }

  /**
   * Obtenir le lien vers le dashboard Stripe du créateur
   */
  async getDashboardLink() {
    try {
      const token = localStorage.getItem("token");

      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "getDashboardLink",
          token: token,
        }),
      });

      return await response.json();
    } catch (error) {
      console.error("Erreur:", error);
      return {
        success: false,
        message: "Erreur de connexion au serveur",
      };
    }
  }
}

export default new StripeConnectManager();
