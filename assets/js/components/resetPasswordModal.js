import { helpers } from "../utils/helpers.js";
import { appState } from "../store/appState.js";
import { auth } from "../utils/auth.js";
import { loadTemplate } from "../utils/templateLoader.js";

const templateObjects = {};
let currentEmail = "";

/**
 * Initialiser la modal de réinitialisation
 */
export async function initResetPasswordModal() {
  Object.assign(templateObjects, await loadTemplate("assets/components/resetPasswordModal.html"));

  // Vérifier si le modal existe déjà
  let modalContainer = document.getElementById("resetPasswordModalContainer");
  if (!modalContainer) {
    modalContainer = document.createElement("div");
    modalContainer.id = "resetPasswordModalContainer";
    document.body.appendChild(modalContainer);
  }

  modalContainer.innerHTML = "";
  const clone = templateObjects["resetPasswordModalTemplate"].cloneNode(true);
  modalContainer.appendChild(clone);

  attachResetPasswordEvents();
}

/**
 * Ouvrir la modal de réinitialisation
 */
export function openResetPasswordModal(email) {
  currentEmail = email;

  const modal = document.getElementById("resetPasswordModal");
  if (!modal) return;

  // Réinitialiser le formulaire
  const form = document.getElementById("resetPasswordForm");
  if (form) form.reset();

  // Cacher l'alerte
  const alert = document.getElementById("resetPasswordAlert");
  if (alert) {
    alert.classList.add("d-none");
  }

  // Ouvrir la modal
  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();
}

/**
 * Attacher les événements
 */
function attachResetPasswordEvents() {
  const btnReset = document.getElementById("btnResetPassword");
  if (!btnReset) return;

  btnReset.addEventListener("click", handleResetPassword);

  // Validation du formulaire
  const form = document.getElementById("resetPasswordForm");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      handleResetPassword();
    });
  }

  // Valider que les mots de passe correspondent
  const confirmInput = document.getElementById("resetPasswordConfirm");
  if (confirmInput) {
    confirmInput.addEventListener("input", () => {
      const newPassword = document.getElementById("resetPasswordNew").value;
      const confirmPassword = confirmInput.value;

      if (confirmPassword && newPassword !== confirmPassword) {
        confirmInput.setCustomValidity(
          "Les mots de passe ne correspondent pas",
        );
      } else {
        confirmInput.setCustomValidity("");
      }
    });
  }

  // Formater le code (accepter uniquement des chiffres)
  const codeInput = document.getElementById("resetPasswordCode");
  if (codeInput) {
    codeInput.addEventListener("input", (e) => {
      e.target.value = e.target.value.replace(/\D/g, "").slice(0, 6);
    });
  }
}

/**
 * Réinitialiser le mot de passe
 */
async function handleResetPassword() {
  const form = document.getElementById("resetPasswordForm");
  const code = document.getElementById("resetPasswordCode").value.trim();
  const newPassword = document.getElementById("resetPasswordNew").value;
  const confirmPassword = document.getElementById("resetPasswordConfirm").value;
  const btnReset = document.getElementById("btnResetPassword");

  // Validation
  if (!form.checkValidity()) {
    form.classList.add("was-validated");
    return;
  }

  if (!code || code.length !== 6) {
    showAlert("Le code doit contenir 6 chiffres", "danger");
    return;
  }

  if (newPassword.length < 8) {
    showAlert("Le mot de passe doit contenir au moins 8 caractères", "danger");
    return;
  }

  if (newPassword !== confirmPassword) {
    showAlert("Les mots de passe ne correspondent pas", "danger");
    return;
  }

  // Désactiver le bouton
  btnReset.disabled = true;
  btnReset.innerHTML =
    '<i class="bi bi-hourglass-split me-2"></i>Réinitialisation...';

  try {
    const apiUrl =
      window.__APP_CONFIG__?.API_URL ||
      "https://memoriaeventia.com/BackEnd/Api";
    const response = await fetch(`${apiUrl}/authApi.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "resetPassword",
        email: currentEmail,
        code: code,
        newPassword: newPassword,
      }),
    });

    const data = await response.json();

    if (data.success) {
      showAlert(data.message, "success");

      // Attendre 2 secondes puis fermer la modal et déconnecter
      setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("resetPasswordModal"),
        );
        if (modal) {
          // Retirer le focus pour éviter les warnings aria-hidden
          if (document.activeElement) {
            document.activeElement.blur();
          }
          modal.hide();
        }

        // Déconnecter l'utilisateur
        auth.logout();
        appState.set("user", null);
        appState.set("isAuthenticated", false);

        // Rediriger vers la page d'accueil
        window.router.navigate("/");

        // Afficher un message de succès
        helpers.showToast(
          "Mot de passe réinitialisé ! Veuillez vous reconnecter.",
          "success",
        );
      }, 2000);
    } else {
      showAlert(data.message, "danger");
      btnReset.disabled = false;
      btnReset.innerHTML =
        '<i class="bi bi-check-circle-fill me-2"></i>Réinitialiser';
    }
  } catch (error) {
    showAlert("Erreur de connexion au serveur", "danger");
    btnReset.disabled = false;
    btnReset.innerHTML =
      '<i class="bi bi-check-circle-fill me-2"></i>Réinitialiser';
  }
}

/**
 * Afficher une alerte
 */
function showAlert(message, type) {
  const alert = document.getElementById("resetPasswordAlert");
  if (!alert) return;

  alert.className = `alert alert-${type}`;
  alert.textContent = message;
  alert.classList.remove("d-none");
}
