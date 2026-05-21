// Modal de confirmation d'annulation de réservation
import { helpers } from "../utils/helpers.js";

// Objet pour stocker le template
const templateObjects = {};

// Variables de la modal
let modalElement = null;
let modalInstance = null;
let currentReservationId = null;
let currentEventInfo = null;
let onConfirmCallback = null;

// Chargement du template HTML
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

// Initialisation de la modal
export async function initCancelReservationModal() {
  await loadTemplate("assets/components/cancelReservationModal.html");

  // Vérifier si la modal existe déjà
  if (document.getElementById("cancelReservationModal")) {
    return;
  }

  // Créer et injecter la modal
  const clone =
    templateObjects["cancelReservationModalTemplate"].cloneNode(true);
  document.body.appendChild(clone);

  modalElement = document.getElementById("cancelReservationModal");
  modalInstance = new bootstrap.Modal(modalElement);

  // Attacher les événements
  attachEventListeners();
}

// Attacher les event listeners
function attachEventListeners() {
  const confirmBtn = document.getElementById("confirmCancelReservation");

  if (confirmBtn) {
    confirmBtn.addEventListener("click", handleConfirmCancel);
  }

  // Réinitialiser lors de la fermeture
  modalElement.addEventListener("hidden.bs.modal", () => {
    currentReservationId = null;
    currentEventInfo = null;
    onConfirmCallback = null;
  });
}

// Ouvrir la modal avec les informations de la réservation
export function openCancelReservationModal(
  reservationId,
  eventInfo,
  onConfirm,
) {
  currentReservationId = reservationId;
  currentEventInfo = eventInfo;
  onConfirmCallback = onConfirm;

  // Afficher les informations de l'événement
  displayEventInfo(eventInfo);

  // Ouvrir la modal
  modalInstance.show();
}

// Afficher les informations de l'événement
function displayEventInfo(eventInfo) {
  const container = document.getElementById("eventInfoCancelReservation");
  if (!container) return;

  const eventDate = new Date(eventInfo.date);
  const formattedDate = helpers.formatDate(eventDate);

  container.innerHTML = `
    <div class="card-body">
      <h6 class="card-title mb-2">
        <i class="bi bi-calendar-event text-primary me-2"></i>
        ${eventInfo.name}
      </h6>
      <p class="card-text small text-muted mb-2">
        <i class="bi bi-geo-alt me-2"></i>
        ${eventInfo.location || "Non spécifié"}
      </p>
      <p class="card-text small text-muted mb-0">
        <i class="bi bi-clock me-2"></i>
        ${formattedDate}
      </p>
    </div>
  `;
}

// Gérer la confirmation d'annulation
async function handleConfirmCancel() {
  const confirmBtn = document.getElementById("confirmCancelReservation");
  if (!confirmBtn || confirmBtn.disabled) return;

  if (!currentReservationId || !onConfirmCallback) {
    helpers.showToast("Erreur lors de l'annulation", "error");
    return;
  }

  // Désactiver le bouton pendant le traitement
  confirmBtn.disabled = true;
  confirmBtn.innerHTML =
    '<span class="spinner-border spinner-border-sm"></span> Annulation...';

  try {
    // Appeler le callback de confirmation
    await onConfirmCallback(currentReservationId);

    // Fermer la modal
    // Retirer le focus pour éviter les warnings aria-hidden
    if (document.activeElement) {
      document.activeElement.blur();
    }
    modalInstance.hide();
  } catch (error) {
    helpers.showToast("Erreur lors de l'annulation", "error");
  } finally {
    // Réactiver le bouton
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = '<i class="bi bi-x-circle"></i> Oui, annuler';
  }
}

// Export pour utilisation dans d'autres modules
export default {
  init: initCancelReservationModal,
  open: openCancelReservationModal,
};
