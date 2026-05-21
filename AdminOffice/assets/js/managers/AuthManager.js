// Manager pour la gestion de l'authentification admin
import { config } from "../../../../assets/js/config.js";

class AuthManager {
  constructor() {
    this.apiUrl = config.API_URL;
  }

  // Connexion admin/moderator
  async login(email, password) {
    try {
      const response = await fetch(`${this.apiUrl}/authApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "login",
          email: email,
          password: password,
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

  // Déconnexion
  async logout(token) {
    if (!token) {
      return {
        success: false,
        message: "Token manquant",
      };
    }

    try {
      const response = await fetch(`${this.apiUrl}/authApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          action: "logout",
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

  // Vérifier le token avec le backend
  async checkToken(token) {
    if (!token) {
      return {
        success: false,
        message: "Token manquant",
      };
    }

    try {
      const response = await fetch(`${this.apiUrl}/authApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          action: "getCurrentUser",
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

export default new AuthManager();
