// Composant Login Modal

import { auth } from "../utils/auth.js";
import { helpers } from "../utils/helpers.js";
import { appState } from "../store/appState.js";
import {
  validateEmail,
  validatePassword,
  validateName,
} from "../validators/authValidator.js";
import {
  setFieldError,
  setFieldValid,
  validateField,
} from "../validators/formValidator.js";
import { openForgotPasswordModal } from "./forgotPasswordModal.js";
import { loadTemplate } from "../utils/templateLoader.js";

const templateObjects = {};
let modalInstance = null;

// Clé sessionStorage : page à rouvrir après connexion (survit à un rechargement complet, ex. lien d'e-mail)
const REDIRECT_KEY = "memoriaeventia_redirect_after_login";

// Action à rejouer après connexion (réserver, ajouter un favori...)
let pendingAction = null;

// Ouvre la connexion puis rejoue l'action demandée : l'utilisateur n'a pas à recliquer
export function requireLogin(onSuccess) {
  pendingAction = onSuccess;
  openLoginModal();
}

// Mémorise la page demandée pour y revenir après connexion
export function saveRedirectAfterLogin(path) {
  try {
    sessionStorage.setItem(REDIRECT_KEY, path);
  } catch (error) {
    // sessionStorage indisponible : on ignore, l'utilisateur reste sur l'accueil
  }
}

function takeRedirectAfterLogin() {
  try {
    const path = sessionStorage.getItem(REDIRECT_KEY);
    sessionStorage.removeItem(REDIRECT_KEY);
    return path;
  } catch (error) {
    return null;
  }
}

// Affiche à la place d'une page protégée un message avec un bouton de connexion
export function renderLoginRequiredPage(container, message) {
  saveRedirectAfterLogin(window.location.pathname + window.location.search);

  container.innerHTML = "";

  const wrapper = document.createElement("div");
  wrapper.className = "container py-5 text-center";

  const icon = document.createElement("i");
  icon.className = "bi bi-lock fs-1 mb-3 d-block text-primary";

  const text = document.createElement("p");
  text.className = "fs-5 mb-4";
  text.textContent = message;

  const button = document.createElement("button");
  button.type = "button";
  button.className = "btn btn-primary";
  button.textContent = "Se connecter";
  button.addEventListener("click", () => {
    // Le modal fermé sans connexion a effacé la page demandée : on la mémorise à nouveau
    saveRedirectAfterLogin(window.location.pathname + window.location.search);
    openLoginModal();
  });

  wrapper.append(icon, text, button);
  container.appendChild(wrapper);

  openLoginModal();
}

// Après une connexion réussie : ferme le modal puis redirige ou rejoue l'action en attente
function continueAfterLogin() {
  const action = pendingAction;
  pendingAction = null;
  const redirectPath = takeRedirectAfterLogin();

  helpers.closeModalThen(modalInstance, () => {
    if (redirectPath) {
      window.router.navigate(redirectPath);
    } else if (action) {
      action();
    }
  });
}

export async function renderLoginModal() {
  Object.assign(
    templateObjects,
    await loadTemplate("assets/components/loginModal.html"),
  );

  // Vérifier si le modal existe déjà
  let modalContainer = document.getElementById("loginModalContainer");
  if (!modalContainer) {
    modalContainer = document.createElement("div");
    modalContainer.id = "loginModalContainer";
    document.body.appendChild(modalContainer);
  }

  modalContainer.innerHTML = "";
  const clone = templateObjects["loginModalTemplate"].cloneNode(true);
  modalContainer.appendChild(clone);

  // Attacher les événements
  attachLoginEvents();
  attachRegisterEvents();
  attachValidationEvents();
  attachForgotPasswordEvents();

  // Créer l'instance du modal Bootstrap
  const modalElement = document.getElementById("loginModal");
  modalInstance = new bootstrap.Modal(modalElement);

  // Modal fermé sans connexion : on oublie l'action et la page demandées
  modalElement.addEventListener("hidden.bs.modal", () => {
    if (!appState.get("isAuthenticated")) {
      pendingAction = null;
      takeRedirectAfterLogin();
    }
  });

  // Écouter l'événement d'ouverture personnalisé
  window.addEventListener("openLoginModal", openLoginModal);

  // Écouter les changements d'onglets pour réinitialiser la validation
  attachTabSwitchEvents();
}

function attachLoginEvents() {
  const loginForm = document.getElementById("loginForm");
  if (!loginForm) return;

  loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const submitButton = loginForm.querySelector('button[type="submit"]');
    // Déjà en cours d'envoi : on ignore le double clic
    if (submitButton.disabled) return;

    const email = document.getElementById("loginEmail").value;
    const password = document.getElementById("loginPassword").value;

    helpers.setButtonLoading(submitButton, "Connexion...");
    const result = await auth.login(email, password);
    helpers.resetButton(submitButton);

    if (result.success) {
      helpers.showToast("Connexion réussie !", "success");

      // Mettre à jour l'état global
      appState.set("user", result.data.user);
      appState.set("isAuthenticated", true);

      continueAfterLogin();
    } else {
      helpers.showToast(result.message || "Erreur de connexion", "error");
    }
  });
}

function attachRegisterEvents() {
  const registerForm = document.getElementById("registerForm");
  if (!registerForm) return;

  registerForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const name = document.getElementById("registerName").value;
    const email = document.getElementById("registerEmail").value;
    const password = document.getElementById("registerPassword").value;

    const submitButton = document.getElementById("registerSubmitBtn");
    // Déjà en cours d'envoi : on ignore le double clic
    if (submitButton.dataset.originalHtml !== undefined) return;

    helpers.setButtonLoading(submitButton, "Inscription...");
    const result = await auth.register(email, password, name, true);
    helpers.resetButton(submitButton);

    if (result.success) {
      helpers.showToast(`Bienvenue ${name} !`, "success");

      // Mettre à jour l'état global
      appState.set("user", result.data.user);
      appState.set("isAuthenticated", true);

      continueAfterLogin();
    } else {
      helpers.showToast(result.message || "Erreur d'inscription", "error");
    }
  });
}

function attachForgotPasswordEvents() {
  const forgotPasswordLink = document.getElementById("forgotPasswordLink");
  if (!forgotPasswordLink) return;

  forgotPasswordLink.addEventListener("click", (e) => {
    e.preventDefault();

    // Retirer le focus pour éviter les warnings aria-hidden
    if (document.activeElement) {
      document.activeElement.blur();
    }

    // Fermer la modal de login puis ouvrir celle du mot de passe oublié
    helpers.closeModalThen(modalInstance, openForgotPasswordModal);
  });
}

export function openLoginModal() {
  if (modalInstance) {
    modalInstance.show();
  }
}

export function closeLoginModal() {
  if (modalInstance) {
    // Retirer le focus pour éviter les warnings aria-hidden
    if (document.activeElement) {
      document.activeElement.blur();
    }
    modalInstance.hide();
  }
}

// ============================================
// VALIDATION EN TEMPS RÉEL
// ============================================

function attachValidationEvents() {
  // Champs d'inscription
  const registerName = document.getElementById("registerName");
  const registerEmail = document.getElementById("registerEmail");
  const registerPassword = document.getElementById("registerPassword");

  // Champs de connexion
  const loginEmail = document.getElementById("loginEmail");
  const loginPassword = document.getElementById("loginPassword");

  // Acceptation des conditions d'utilisation : débloque le bouton d'inscription
  const registerAcceptTerms = document.getElementById("registerAcceptTerms");
  if (registerAcceptTerms) {
    registerAcceptTerms.addEventListener("change", checkRegisterFormValidity);
  }

  // Validation inscription
  if (registerName) {
    registerName.addEventListener("input", () => {
      validateRegisterName();
      checkRegisterFormValidity();
    });
    registerName.addEventListener("blur", validateRegisterName);
  }

  if (registerEmail) {
    registerEmail.addEventListener("input", () => {
      validateRegisterEmail();
      checkRegisterFormValidity();
    });
    registerEmail.addEventListener("blur", validateRegisterEmail);
  }

  if (registerPassword) {
    registerPassword.addEventListener("input", () => {
      validateRegisterPassword();
      checkRegisterFormValidity();
    });
    registerPassword.addEventListener("blur", validateRegisterPassword);
  }

  // Validation connexion
  if (loginEmail) {
    loginEmail.addEventListener("input", validateLoginEmail);
    loginEmail.addEventListener("blur", validateLoginEmail);
  }

  if (loginPassword) {
    loginPassword.addEventListener("input", validateLoginPassword);
    loginPassword.addEventListener("blur", validateLoginPassword);
  }
}

// Validation du nom (inscription)
function validateRegisterName() {
  const nameInput = document.getElementById("registerName");
  const errorDiv = document.getElementById("registerNameError");
  return validateField(nameInput, errorDiv, validateName, nameInput.value);
}

// Validation de l'email (inscription)
function validateRegisterEmail() {
  const emailInput = document.getElementById("registerEmail");
  const errorDiv = document.getElementById("registerEmailError");
  return validateField(emailInput, errorDiv, validateEmail, emailInput.value);
}

// Validation du mot de passe (inscription)
function validateRegisterPassword() {
  const passwordInput = document.getElementById("registerPassword");
  const errorDiv = document.getElementById("registerPasswordError");
  return validateField(
    passwordInput,
    errorDiv,
    validatePassword,
    passwordInput.value,
  );
}

// Validation de l'email (connexion)
function validateLoginEmail() {
  const emailInput = document.getElementById("loginEmail");
  const errorDiv = document.getElementById("loginEmailError");
  return validateField(emailInput, errorDiv, validateEmail, emailInput.value);
}

// Validation du mot de passe (connexion)
function validateLoginPassword() {
  const passwordInput = document.getElementById("loginPassword");
  const errorDiv = document.getElementById("loginPasswordError");
  return validateField(
    passwordInput,
    errorDiv,
    validatePassword,
    passwordInput.value,
  );
}

// Vérifier la validité du formulaire d'inscription
function checkRegisterFormValidity() {
  const isNameValid = validateRegisterName();
  const isEmailValid = validateRegisterEmail();
  const isPasswordValid = validateRegisterPassword();
  const hasAcceptedTerms =
    document.getElementById("registerAcceptTerms")?.checked === true;

  const submitBtn = document.getElementById("registerSubmitBtn");
  if (submitBtn) {
    submitBtn.disabled = !(
      isNameValid &&
      isEmailValid &&
      isPasswordValid &&
      hasAcceptedTerms
    );
  }
}

// Réinitialiser les formulaires lors du changement d'onglet
function attachTabSwitchEvents() {
  const loginTab = document.getElementById("login-tab");
  const registerTab = document.getElementById("register-tab");

  if (loginTab) {
    loginTab.addEventListener("click", () => {
      resetForm("loginForm");
    });
  }

  if (registerTab) {
    registerTab.addEventListener("click", () => {
      resetForm("registerForm");
    });
  }
}

function resetForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return;

  // Réinitialiser le formulaire
  form.reset();

  // Retirer toutes les classes de validation
  const inputs = form.querySelectorAll(".form-control");
  inputs.forEach((input) => {
    input.classList.remove("is-invalid", "is-valid");
  });

  // Cacher tous les messages d'erreur
  const errorDivs = form.querySelectorAll(".invalid-feedback");
  errorDivs.forEach((div) => {
    div.textContent = "";
    div.style.display = "none";
  });

  // Réactiver le bouton d'inscription si nécessaire
  if (formId === "registerForm") {
    const submitBtn = document.getElementById("registerSubmitBtn");
    if (submitBtn) {
      submitBtn.disabled = true;
    }
  }
}
