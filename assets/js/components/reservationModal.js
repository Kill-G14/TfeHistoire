// Composant Modal de confirmation de réservation

import ReservationManager from "../managers/ReservationManager.js";
import { helpers } from "../utils/helpers.js";
import { auth } from "../utils/auth.js";
import { appState } from "../store/appState.js";
import { loadTemplate } from "../utils/templateLoader.js";
import { requireLogin } from "./loginModal.js";

// Objet pour stocker les templates
const templateObjects = {};

// Variable pour stocker l'événement en cours de réservation
let currentEvent = null;
let currentQuantity = 1;

/**
 * Précharger le template de la modal de réservation
 * (appelé au démarrage de l'application)
 */
export async function initReservationModal() {
  Object.assign(
    templateObjects,
    await loadTemplate("assets/components/reservationModal.html"),
  );
}

// Fonction pour afficher la modal de réservation
export async function showReservationModal(event, quantity = 1) {
  // Vérifier si l'utilisateur est connecté
  if (!appState.get("isAuthenticated")) {
    helpers.showToast("Connectez-vous pour réserver", "warning");
    // Connexion puis réouverture automatique de la réservation
    requireLogin(() => showReservationModal(event, quantity));
    return;
  }

  // Charger le template si ce n'est pas déjà fait
  if (!templateObjects["reservationModalTemplate"]) {
    Object.assign(
      templateObjects,
      await loadTemplate("assets/components/reservationModal.html"),
    );
  }

  // Vérifier si la modal existe déjà dans le DOM
  let modalElement = document.getElementById("reservationModal");

  if (!modalElement) {
    // Créer la modal
    const clone = templateObjects["reservationModalTemplate"].cloneNode(true);
    document.body.appendChild(clone);
    modalElement = document.getElementById("reservationModal");
  }

  // Stocker l'événement et la quantité sélectionnée
  currentEvent = event;
  currentQuantity = quantity;

  // Afficher les informations de l'événement
  const eventInfo = document.getElementById("eventInfoReservation");
  if (eventInfo) {
    const eventDate = new Date(event.date);
    const formattedDate = eventDate.toLocaleDateString("fr-FR", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    eventInfo.innerHTML = `
      <div class="card-body">
        <h5 class="card-title text-primary mb-3">
          <i class="bi bi-star-fill me-2"></i>${event.title}
        </h5>
        <div class="d-flex flex-column gap-2">
          <div class="d-flex align-items-center">
            <i class="bi bi-calendar3 text-muted me-2" style="width: 20px;"></i>
            <span class="text-muted">${formattedDate}</span>
          </div>
          <div class="d-flex align-items-center">
            <i class="bi bi-clock text-muted me-2" style="width: 20px;"></i>
            <span class="text-muted">${helpers.formatTime(event.time)}</span>
          </div>
          <div class="d-flex align-items-center">
            <i class="bi bi-geo-alt-fill text-muted me-2" style="width: 20px;"></i>
            <span class="text-muted">${event.city}, ${event.country}</span>
          </div>
          <div class="d-flex align-items-center mt-2">
            <i class="bi bi-tag-fill text-success me-2" style="width: 20px;"></i>
            <span class="badge bg-success">Événement gratuit</span>
          </div>
          <div class="d-flex align-items-center">
            <i class="bi bi-ticket-perforated-fill text-muted me-2" style="width: 20px;"></i>
            <span class="text-muted">${quantity} place${quantity > 1 ? "s" : ""}</span>
          </div>
        </div>
      </div>
    `;
  }

  // Attacher l'événement de confirmation
  attachReservationEvents();

  // Afficher la modal
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
}

// Attacher les événements à la modal
function attachReservationEvents() {
  const confirmBtn = document.getElementById("confirmReservationBtn");

  if (confirmBtn) {
    // Supprimer les anciens listeners
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

    // Ajouter le nouveau listener
    newConfirmBtn.addEventListener("click", handleConfirmReservation);
  }
}

// Gérer la confirmation de réservation
async function handleConfirmReservation() {
  if (!currentEvent) return;

  const confirmBtn = document.getElementById("confirmReservationBtn");
  if (!confirmBtn) return;

  // Désactiver le bouton pendant le traitement
  helpers.setButtonLoading(confirmBtn, "Réservation...");

  const token = auth.getToken();

  // Créer la réservation
  const result = await ReservationManager.create(
    currentEvent.id,
    currentQuantity,
    token,
  );

  if (result.success) {
    helpers.showToast(result.message, "success");

    // Réactiver le bouton
    helpers.resetButton(confirmBtn);

    // Réinitialiser l'événement
    currentEvent = null;
    currentQuantity = 1;

    // Retirer le focus pour éviter les warnings aria-hidden
    if (document.activeElement) {
      document.activeElement.blur();
    }

    // Fermer la modal puis rediriger vers l'onglet réservations du profil
    const modalElement = document.getElementById("reservationModal");
    const modal = bootstrap.Modal.getInstance(modalElement);
    const goToReservations = () =>
      window.router.navigate("/profile?section=reservations");

    if (modal) {
      helpers.closeModalThen(modal, goToReservations);
    } else {
      goToReservations();
    }
  } else {
    helpers.showToast(result.message, "error");

    // Réactiver le bouton
    helpers.resetButton(confirmBtn);
  }
}

// Export des fonctions
export { loadTemplate };
