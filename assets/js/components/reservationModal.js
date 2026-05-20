// Composant Modal de confirmation de réservation

import ReservationManager from "../managers/ReservationManager.js";
import { helpers } from "../utils/helpers.js";
import { auth } from "../utils/auth.js";
import { appState } from "../store/appState.js";

// Objet pour stocker les templates
const templateObjects = {};

// Variable pour stocker l'événement en cours de réservation
let currentEvent = null;

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

// Fonction pour afficher la modal de réservation
export async function showReservationModal(event) {
  // Vérifier si l'utilisateur est connecté
  if (!appState.get("isAuthenticated")) {
    helpers.showToast("Vous devez être connecté pour réserver", "error");
    // Ouvrir la modal de connexion
    const loginModalElement = document.getElementById("loginModal");
    if (loginModalElement) {
      const loginModal = new bootstrap.Modal(loginModalElement);
      loginModal.show();
    }
    return;
  }

  // Charger le template si ce n'est pas déjà fait
  if (!templateObjects["reservationModalTemplate"]) {
    await loadTemplate("assets/components/reservationModal.html");
  }

  // Vérifier si la modal existe déjà dans le DOM
  let modalElement = document.getElementById("reservationModal");

  if (!modalElement) {
    // Créer la modal
    const clone = templateObjects["reservationModalTemplate"].cloneNode(true);
    document.body.appendChild(clone);
    modalElement = document.getElementById("reservationModal");
  }

  // Stocker l'événement
  currentEvent = event;

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
      <h6 class="fw-bold mb-2">${event.title}</h6>
      <p class="mb-1">
        <i class="bi bi-calendar-event"></i> ${formattedDate}
      </p>
      <p class="mb-1">
        <i class="bi bi-clock"></i> ${event.time}
      </p>
      <p class="mb-1">
        <i class="bi bi-geo-alt"></i> ${event.city}, ${event.country}
      </p>
      <p class="mb-0 text-success fw-bold">
        <i class="bi bi-gift"></i> Gratuit
      </p>
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
  confirmBtn.disabled = true;
  confirmBtn.innerHTML =
    '<span class="spinner-border spinner-border-sm me-2"></span>Réservation...';

  const token = auth.getToken();

  // Créer la réservation
  const result = await ReservationManager.create(currentEvent.id, 1, token);

  if (result.success) {
    helpers.showToast(result.message, "success");

    // Fermer la modal
    const modalElement = document.getElementById("reservationModal");
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) {
      modal.hide();
    }

    // Réinitialiser l'événement
    currentEvent = null;

    // Rediriger vers le profil ou recharger les données
    setTimeout(() => {
      // Événement personnalisé pour notifier que la réservation a été créée
      window.dispatchEvent(new CustomEvent("reservationCreated"));
    }, 500);
  } else {
    helpers.showToast(result.message, "error");

    // Réactiver le bouton
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = '<i class="bi bi-check-circle"></i> Oui, réserver';
  }
}

// Export des fonctions
export { loadTemplate };
