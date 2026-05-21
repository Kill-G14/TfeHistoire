import { helpers } from "../utils/helpers.js";
import { openResetPasswordModal } from "./resetPasswordModal.js";

const templateObjects = {};

async function loadTemplate(path) {
  const response = await fetch(path);
  const htmlContent = await response.text();
  const parser = new DOMParser();
  const templateDoc = parser.parseFromString(htmlContent, "text/html");
  const templates = templateDoc.querySelectorAll("template");

  templates.forEach((template) => {
    const templateId = template.id;
    templateObjects[templateId] = template.content;
  });
}

/**
 * Initialiser la modal de mot de passe oublié
 */
export async function initForgotPasswordModal() {
  await loadTemplate("assets/components/forgotPasswordModal.html");

  // Vérifier si le modal existe déjà
  let modalContainer = document.getElementById("forgotPasswordModalContainer");
  if (!modalContainer) {
    modalContainer = document.createElement("div");
    modalContainer.id = "forgotPasswordModalContainer";
    document.body.appendChild(modalContainer);
  }

  modalContainer.innerHTML = "";
  const clone = templateObjects["forgotPasswordModalTemplate"].cloneNode(true);
  modalContainer.appendChild(clone);

  attachForgotPasswordEvents();
}

/**
 * Ouvrir la modal de mot de passe oublié
 */
export function openForgotPasswordModal() {
  const modal = document.getElementById("forgotPasswordModal");
  if (!modal) return;

  // Réinitialiser le formulaire
  const form = document.getElementById("forgotPasswordForm");
  if (form) form.reset();

  // Cacher l'alerte
  const alert = document.getElementById("forgotPasswordAlert");
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
function attachForgotPasswordEvents() {
  const btnSend = document.getElementById("btnSendResetCode");
  if (!btnSend) return;

  btnSend.addEventListener("click", handleSendCode);

  // Validation du formulaire
  const form = document.getElementById("forgotPasswordForm");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      handleSendCode();
    });
  }
}

/**
 * Envoyer le code de réinitialisation
 */
async function handleSendCode() {
  const form = document.getElementById("forgotPasswordForm");
  const email = document.getElementById("forgotPasswordEmail").value.trim();
  const alert = document.getElementById("forgotPasswordAlert");
  const btnSend = document.getElementById("btnSendResetCode");

  // Validation
  if (!form.checkValidity()) {
    form.classList.add("was-validated");
    return;
  }

  if (!email) {
    showAlert("Veuillez entrer votre adresse email", "danger");
    return;
  }

  // Désactiver le bouton
  btnSend.disabled = true;
  btnSend.innerHTML =
    '<i class="bi bi-hourglass-split me-2"></i>Envoi en cours...';

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
        action: "requestPasswordReset",
        email: email,
      }),
    });

    const data = await response.json();

    if (data.success) {
      showAlert(data.message, "success");

      // Attendre 2 secondes puis ouvrir la modal de réinitialisation
      setTimeout(() => {
        // Fermer la modal actuelle
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("forgotPasswordModal"),
        );
        if (modal) {
          // Retirer le focus pour éviter les warnings aria-hidden
          if (document.activeElement) {
            document.activeElement.blur();
          }
          modal.hide();
        }

        // Ouvrir la modal de réinitialisation
        openResetPasswordModal(email);
      }, 2000);
    } else {
      showAlert(data.message, "danger");
      btnSend.disabled = false;
      btnSend.innerHTML = '<i class="bi bi-send-fill me-2"></i>Envoyer le code';
    }
  } catch (error) {
    showAlert("Erreur de connexion au serveur", "danger");
    btnSend.disabled = false;
    btnSend.innerHTML = '<i class="bi bi-send-fill me-2"></i>Envoyer le code';
  }
}

/**
 * Afficher une alerte
 */
function showAlert(message, type) {
  const alert = document.getElementById("forgotPasswordAlert");
  if (!alert) return;

  alert.className = `alert alert-${type}`;
  alert.textContent = message;
  alert.classList.remove("d-none");
}
