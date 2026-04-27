// Composant Event Detail

import { auth } from "../utils/auth.js";
import { helpers } from "../utils/helpers.js";

const templateObjects = {};
let currentEvent = null;
let quantity = 1;

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

export async function showEventDetail(event) {
  await loadTemplate("assets/components/eventDetail.html");

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
  if (detailTickets) detailTickets.textContent = event.availableTickets;
  if (detailPrice) detailPrice.textContent = event.price;
  if (quantityEl) quantityEl.textContent = quantity;
  if (totalPrice)
    totalPrice.textContent = helpers.formatPrice(event.priceValue * quantity);
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
      if (currentEvent && quantity < currentEvent.availableTickets) {
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
  if (totalPrice && currentEvent) {
    totalPrice.textContent = helpers.formatPrice(
      currentEvent.priceValue * quantity,
    );
  }
}

function handleReservation() {
  if (!auth.isLoggedIn()) {
    closeEventDetail();
    const event = new CustomEvent("openLoginModal");
    window.dispatchEvent(event);
    helpers.showToast("Veuillez vous connecter pour réserver", "error");
    return;
  }

  if (currentEvent) {
    helpers.showToast(
      `${quantity} ticket(s) réservé(s) pour ${currentEvent.title} !`,
      "success",
    );
    closeEventDetail();
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
