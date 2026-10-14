// Vue de retour de paiement Stripe : /payment/success et /payment/cancel

import { auth } from "../utils/auth.js";
import { appState } from "../store/appState.js";
import PaymentManager from "../managers/PaymentManager.js";
import { renderLoginRequiredPage } from "../components/loginModal.js";

// Métadonnées de la vue
export const meta = {
  title: "Paiement - MemoriaEventia",
  description: "Résultat de votre paiement",
  noindex: true,
};

// Nombre de vérifications auprès du serveur avant d'abandonner l'attente
const MAX_CHECKS = 8;
const CHECK_INTERVAL_MS = 2000;

let pollTimer = null;
let isViewActive = false;

// Affiche une carte de résultat (tout est construit avec textContent)
function renderResult(container, { icon, color, title, message, actions = [], loading = false }) {
  container.innerHTML = "";

  const wrapper = document.createElement("div");
  wrapper.className = "container py-5 text-center";
  wrapper.style.maxWidth = "640px";

  if (loading) {
    const spinner = document.createElement("div");
    spinner.className = "spinner-border text-primary mb-4";
    spinner.setAttribute("role", "status");
    wrapper.appendChild(spinner);
  } else {
    const iconElement = document.createElement("i");
    iconElement.className = `bi ${icon} fs-1 mb-3 d-block text-${color}`;
    iconElement.style.fontSize = "4rem";
    wrapper.appendChild(iconElement);
  }

  const heading = document.createElement("h1");
  heading.className = "page-title mb-3";
  heading.textContent = title;

  const text = document.createElement("p");
  text.className = "mb-4";
  text.textContent = message;

  wrapper.append(heading, text);

  const actionsRow = document.createElement("div");
  actionsRow.className = "d-flex flex-wrap justify-content-center gap-3";
  actions.forEach((action) => {
    const link = document.createElement("a");
    link.href = action.href;
    link.setAttribute("data-link", "");
    link.className = `btn ${action.primary ? "btn-primary" : "btn-outline-secondary"}`;
    link.textContent = action.label;
    actionsRow.appendChild(link);
  });
  wrapper.appendChild(actionsRow);

  container.appendChild(wrapper);
}

async function showCancel(container, reservationId, token) {
  renderResult(container, {
    loading: true,
    title: "Annulation en cours",
    message: "Nous libérons les places que vous aviez retenues…",
  });

  // Les places sont libérées tout de suite plutôt qu'au bout de 30 minutes
  await PaymentManager.abandonPayment(reservationId, token);
  if (!isViewActive) return;

  renderResult(container, {
    icon: "bi-x-circle",
    color: "warning",
    title: "Paiement annulé",
    message:
      "Aucun montant n'a été débité. Les places que vous aviez retenues ont été libérées.",
    actions: [{ label: "Retour aux événements", href: "./", primary: true }],
  });
}

async function showSuccess(container, reservationId, token) {
  renderResult(container, {
    loading: true,
    title: "Vérification du paiement",
    message: "Nous confirmons votre paiement auprès de Stripe…",
  });

  let attempts = 0;

  // Le serveur interroge Stripe directement : le navigateur ne décide jamais du résultat
  const check = async () => {
    if (!isViewActive) return;
    attempts++;

    const result = await PaymentManager.confirmReturn(reservationId, token);
    if (!isViewActive) return;

    const status = result.data?.payment_status;

    if (result.success && status === "paid") {
      renderResult(container, {
        icon: "bi-check-circle",
        color: "success",
        title: "Paiement confirmé",
        message:
          "Votre réservation est confirmée. Un e-mail de confirmation vous a été envoyé.",
        actions: [
          {
            label: "Voir mes réservations",
            href: "/profile?section=reservations",
            primary: true,
          },
        ],
      });
      return;
    }

    if (attempts >= MAX_CHECKS) {
      renderResult(container, {
        icon: "bi-hourglass-split",
        color: "warning",
        title: "Paiement en cours de confirmation",
        message:
          "Stripe n'a pas encore confirmé votre paiement. Votre réservation apparaîtra dans votre profil dès qu'il sera validé.",
        actions: [
          {
            label: "Voir mes réservations",
            href: "/profile?section=reservations",
            primary: true,
          },
        ],
      });
      return;
    }

    pollTimer = setTimeout(check, CHECK_INTERVAL_MS);
  };

  await check();
}

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  isViewActive = true;

  if (!appState.get("isAuthenticated")) {
    renderLoginRequiredPage(
      container,
      "Connectez-vous pour afficher le résultat de votre paiement.",
    );
    return;
  }

  const reservationId = Number(
    new URLSearchParams(window.location.search).get("reservation"),
  );
  const token = auth.getToken();

  if (!reservationId) {
    renderResult(container, {
      icon: "bi-question-circle",
      color: "warning",
      title: "Réservation introuvable",
      message: "Aucune réservation n'est associée à cette page.",
      actions: [{ label: "Retour à l'accueil", href: "./", primary: true }],
    });
    return;
  }

  if (window.location.pathname.endsWith("/cancel")) {
    await showCancel(container, reservationId, token);
  } else {
    await showSuccess(container, reservationId, token);
  }
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  isViewActive = false;
  if (pollTimer) {
    clearTimeout(pollTimer);
    pollTimer = null;
  }
}

// Export par défaut
export default { mount, unmount, meta };
