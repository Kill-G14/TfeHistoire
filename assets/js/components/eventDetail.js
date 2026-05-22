// Composant Event Detail

import { auth } from "../utils/auth.js";
import { helpers } from "../utils/helpers.js";
import { appState } from "../store/appState.js";
import { showReservationModal } from "./reservationModal.js";
import { loadTemplate } from "../utils/templateLoader.js";

const templateObjects = {};
let currentEvent = null;
let quantity = 1;

export async function showEventDetail(event) {
  Object.assign(templateObjects, await loadTemplate("assets/components/eventDetail.html"));

  currentEvent = event;
  quantity = 1;

  // Vérifier si le conteneur existe déjà
  let detailContainer = document.getElementById("eventDetailContainer");
  if (!detailContainer) {
    detailContainer = document.createElement("div");
    detailContainer.id = "eventDetailContainer";
    document.body.appendChild(detailContainer);
  }

  detailContainer.innerHTML = "";
  const clone = templateObjects["eventDetailTemplate"].cloneNode(true);
  detailContainer.appendChild(clone);

  // Remplir les données
  fillEventDetails(event);

  // Attacher les événements
  attachDetailEvents();

  // Appliquer la logique conditionnelle pour le bouton Réserver
  applyReserveButtonLogic(event);

  // Empêcher le scroll du body
  document.body.style.overflow = "hidden";
}

function fillEventDetails(event) {
  const detailImage = document.getElementById("detailImage");
  const detailCategory = document.getElementById("detailCategory");
  const detailTitle = document.getElementById("detailTitle");
  const detailDescription = document.getElementById("detailDescription");
  const detailLocation = document.getElementById("detailLocation");
  const detailDate = document.getElementById("detailDate");
  const detailTime = document.getElementById("detailTime");
  const detailTickets = document.getElementById("detailTickets");
  const detailPrice = document.getElementById("detailPrice");
  const quantityEl = document.getElementById("quantity");
  const totalPrice = document.getElementById("totalPrice");

  if (detailImage) {
    detailImage.src = event.image;
    detailImage.alt = event.title;
  }
  if (detailCategory) detailCategory.textContent = event.category;
  if (detailTitle) detailTitle.textContent = event.title;
  if (detailDescription) detailDescription.textContent = event.description;
  if (detailLocation)
    detailLocation.textContent = `${event.city}, ${event.country}`;
  if (detailDate) detailDate.textContent = event.date;
  if (detailTime) detailTime.textContent = event.time;

  // Afficher les tickets disponibles
  if (detailTickets) {
    if (event.ticket_quantity > 0) {
      detailTickets.textContent = event.ticket_quantity;
    } else {
      detailTickets.textContent = "Illimité";
    }
  }

  // Tous les événements sont gratuits
  if (detailPrice) {
    detailPrice.textContent = "0.00";
  }

  if (quantityEl) quantityEl.textContent = quantity;

  // Le total est toujours 0 (gratuit)
  if (totalPrice) {
    totalPrice.textContent = "0.00";
  }
}

function attachDetailEvents() {
  const closeBtn = document.getElementById("closeDetailBtn");
  const overlay = document.getElementById("eventDetailOverlay");
  const decreaseBtn = document.getElementById("decreaseQty");
  const increaseBtn = document.getElementById("increaseQty");
  const reserveBtn = document.getElementById("reserveBtn");

  if (closeBtn) {
    closeBtn.addEventListener("click", closeEventDetail);
  }

  if (overlay) {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        closeEventDetail();
      }
    });
  }

  if (decreaseBtn) {
    decreaseBtn.addEventListener("click", () => {
      if (quantity > 1) {
        quantity--;
        updateQuantityDisplay();
      }
    });
  }

  if (increaseBtn) {
    increaseBtn.addEventListener("click", () => {
      if (
        currentEvent &&
        currentEvent.ticket_quantity > 0 &&
        quantity < currentEvent.ticket_quantity
      ) {
        quantity++;
        updateQuantityDisplay();
      } else if (currentEvent && currentEvent.ticket_quantity === 0) {
        // Pas de limite de tickets
        quantity++;
        updateQuantityDisplay();
      }
    });
  }

  if (reserveBtn) {
    reserveBtn.addEventListener("click", handleReservation);
  }
}

function updateQuantityDisplay() {
  const quantityEl = document.getElementById("quantity");
  const totalPrice = document.getElementById("totalPrice");

  if (quantityEl) quantityEl.textContent = quantity;
  // Tous les événements sont gratuits, total toujours à 0
  if (totalPrice) {
    totalPrice.textContent = "0.00";
  }
}

async function handleReservation() {
  // Vérifier si l'utilisateur est connecté
  const isAuthenticated = appState.get("isAuthenticated");

  if (!isAuthenticated) {
    // Afficher la modale de connexion
    helpers.showToast(
      "Vous devez être connecté pour réserver un événement",
      "warning",
    );
    closeEventDetail();
    const loginModal = document.getElementById("loginModal");
    if (loginModal) {
      const modal = new bootstrap.Modal(loginModal);
      modal.show();
    }
    return;
  }

  // Fermer la modal de détails
  closeEventDetail();

  // Afficher la modal de confirmation de réservation
  await showReservationModal(currentEvent);
}

/**
 * Tous les événements sont gratuits → Section réservation toujours visible
 */
function applyReserveButtonLogic(event) {
  const reservationSection = document.getElementById("reservationSection");

  // Tous les événements sont gratuits, toujours afficher la section
  if (reservationSection) {
    reservationSection.style.display = "block";
  }
}

export function closeEventDetail() {
  const detailContainer = document.getElementById("eventDetailContainer");
  if (detailContainer) {
    detailContainer.innerHTML = "";
  }

  // Réactiver le scroll du body
  document.body.style.overflow = "";
}
