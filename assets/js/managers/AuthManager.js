// Manager pour la gestion de l'authentification
class AuthManager {
  constructor() {
    this.apiUrl = "https://memoriaeventia.com/BackEnd/Api";
  }

  // Connexion
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

  // Inscription
  async register(email, password, name) {
    try {
      const response = await fetch(`${this.apiUrl}/authApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "register",
          email: email,
          password: password,
          name: name,
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

  // Récupérer l'utilisateur actuel
  async getCurrentUser(token) {
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

  // Changer de mot de passe
  async changePassword(token, currentPassword, newPassword) {
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
        },
        body: JSON.stringify({
          action: "changePassword",
          token: token,
          currentPassword: currentPassword,
          newPassword: newPassword,
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

  // Demande de réinitialisation de mot de passe
  async requestPasswordReset(email) {
    try {
      const response = await fetch(`${this.apiUrl}/authApi.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "requestPasswordReset",
          email: email,
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
