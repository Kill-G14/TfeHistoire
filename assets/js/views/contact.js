// Vue Contact (formulaire de contact)

import { helpers } from "../utils/helpers.js";
import { loadHTMLTemplate } from "../utils/templateLoader.js";
import ContactManager from "../managers/ContactManager.js";
import {
  setFieldError,
  clearFieldValidation,
} from "../validators/formValidator.js";

// Métadonnées de la vue
export const meta = {
  title: "Contact - MemoriaEventia",
  description:
    "Contactez l'équipe MemoriaEventia pour toute question ou remarque.",
};

// Template HTML
let template = "";

// Variables locales
let contactForm = null;

// Mapping des clés d'erreur backend vers les IDs frontend
const errorFieldMapping = {
  name: "contactName",
  email: "contactEmail",
  subject: "contactSubject",
  message: "contactMessage",
};

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Charger le template
  template = await loadHTMLTemplate("assets/templates/views/contact.html");

  // Injecter le template
  container.innerHTML = template;

  // Attacher les événements
  attachEventListeners();

  // Scroll en haut de la page
  window.scrollTo(0, 0);
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  if (contactForm) {
    contactForm.removeEventListener("submit", handleSubmit);
  }
}

// Attacher les event listeners
function attachEventListeners() {
  contactForm = document.getElementById("contactForm");

  if (contactForm) {
    contactForm.addEventListener("submit", handleSubmit);
  }
}

// Réinitialiser toutes les validations du formulaire
function clearAllValidation() {
  Object.values(errorFieldMapping).forEach((fieldId) => {
    const input = document.getElementById(fieldId);
    const errorDiv = document.getElementById(`${fieldId}Error`);

    if (input && errorDiv) {
      clearFieldValidation(input, errorDiv);
    }
  });
}

// Afficher les erreurs de validation sur les champs
function displayContactErrors(errors) {
  clearAllValidation();

  if (!errors || Object.keys(errors).length === 0) {
    return;
  }

  Object.keys(errors).forEach((backendKey) => {
    const frontendKey = errorFieldMapping[backendKey];
    if (frontendKey) {
      const input = document.getElementById(frontendKey);
      const errorDiv = document.getElementById(`${frontendKey}Error`);

      if (input && errorDiv) {
        setFieldError(input, errorDiv, errors[backendKey]);
      }
    }
  });
}

// Gestion de la soumission du formulaire
async function handleSubmit(e) {
  e.preventDefault();

  clearAllValidation();

  const submitBtn = contactForm.querySelector('button[type="submit"]');
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Envoi en cours...';
  }

  const data = {
    name: document.getElementById("contactName").value,
    email: document.getElementById("contactEmail").value,
    subject: document.getElementById("contactSubject").value,
    message: document.getElementById("contactMessage").value,
  };

  const result = await ContactManager.send(data);

  if (!result.success) {
    if (result.errors) {
      displayContactErrors(result.errors);
    }

    helpers.showToast(
      result.message || "Erreur lors de l'envoi de votre message",
      "error",
    );

    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Envoyer le message';
    }
    return;
  }

  helpers.showToast(result.message || "Votre message a bien été envoyé", "success");
  contactForm.reset();

  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Envoyer le message';
  }
}

// Export par défaut
export default { mount, unmount, meta };
