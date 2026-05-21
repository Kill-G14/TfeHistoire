// Vue Profile - Profil utilisateur

import { auth } from "../utils/auth.js";
import { helpers } from "../utils/helpers.js";
import { appState } from "../store/appState.js";
import FavoriteManager from "../managers/FavoriteManager.js";
import EventManager from "../managers/EventManager.js";
import ReservationManager from "../managers/ReservationManager.js";
import { showEventDetail } from "../components/eventDetail.js";
import {
  initCancelReservationModal,
  openCancelReservationModal,
} from "../components/cancelReservationModal.js";
import { initForgotPasswordModal } from "../components/forgotPasswordModal.js";
import {
  initResetPasswordModal,
  openResetPasswordModal,
} from "../components/resetPasswordModal.js";

// Métadonnées de la vue
export const meta = {
  title: "Mon Profil - MemoriaEventia",
  description: "Gérez votre profil et consultez vos réservations",
};

// Template HTML
const templateObjects = {};

// Stocker les événements favoris et créés pour les détails
let favoriteEvents = [];
let createdEvents = [];
let userReservations = [];

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

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Vérifier si l'utilisateur est connecté
  if (!appState.get("isAuthenticated")) {
    helpers.showToast(
      "Vous devez être connecté pour accéder à votre profil",
      "error",
    );
    setTimeout(() => {
      window.router.navigate("./");
    }, 1500);
    return;
  }

  // Charger le template
  await loadTemplate("assets/templates/views/profile.html");

  // Injecter le template
  const clone = templateObjects["profileView"].cloneNode(true);
  container.innerHTML = "";
  container.appendChild(clone);

  // Initialiser les modals
  await initCancelReservationModal();
  await initForgotPasswordModal();
  await initResetPasswordModal();

  // Afficher les informations utilisateur
  displayUserInfo();

  // Attacher les événements (le DOM est maintenant prêt)
  attachProfileEvents();

  // Charger et afficher les données utilisateur
  await Promise.all([loadFavorites(), loadCreatedEvents(), loadReservations()]);

  // Écouter les changements de favoris
  appState.subscribe("favorites", loadFavorites);

  // Rendre les fonctions globales pour les onclick
  window.openModifyEventModal = openModifyEventModal;
  window.openDeleteEventModal = openDeleteEventModal;
}

// Ouvrir la modal de modification d'événement
function openModifyEventModal(eventId, currentDate, currentTime) {
  const modalHtml = `
    <div class="modal fade" id="modifyEventModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Modifier la date et l'heure</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info">
              <i class="bi bi-info-circle"></i>
              Vous ne pouvez modifier que la date et l'heure de l'événement. La modification devra être validée par un administrateur.
            </div>
            <form id="modifyEventForm">
              <input type="hidden" id="modifyEventId" value="${eventId}">
              <div class="mb-3">
                <label for="modifyDate" class="form-label">Nouvelle date</label>
                <input type="date" class="form-control" id="modifyDate" value="${currentDate}" required>
                <div class="invalid-feedback"></div>
              </div>
              <div class="mb-3">
                <label for="modifyTime" class="form-label">Nouvelle heure</label>
                <input type="time" class="form-control" id="modifyTime" value="${currentTime}" required>
                <div class="invalid-feedback"></div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="button" class="btn btn-primary" id="btnConfirmModify">Envoyer la demande</button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Supprimer l'ancienne modal si elle existe
  const existingModal = document.getElementById("modifyEventModal");
  if (existingModal) {
    existingModal.remove();
  }

  // Ajouter la modal au DOM
  document.body.insertAdjacentHTML("beforeend", modalHtml);

  // Afficher la modal
  const modalEl = document.getElementById("modifyEventModal");
  const modal = new bootstrap.Modal(modalEl);
  modal.show();

  // Event listener pour le bouton de confirmation
  document
    .getElementById("btnConfirmModify")
    .addEventListener("click", async () => {
      await handleModifyEvent(modal);
    });
}

// Gérer la modification d'événement
async function handleModifyEvent(modal) {
  const eventId = document.getElementById("modifyEventId").value;
  const newDate = document.getElementById("modifyDate").value;
  const newTime = document.getElementById("modifyTime").value;

  // Validation basique
  if (!newDate || !newTime) {
    helpers.showToast("Veuillez remplir tous les champs", "error");
    return;
  }

  // Vérifier que la date est dans le futur
  const selectedDate = new Date(newDate);
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (selectedDate < today) {
    helpers.showToast("La date doit être dans le futur", "error");
    return;
  }

  const token = auth.getToken();
  const result = await EventManager.requestModification(
    eventId,
    newDate,
    newTime,
    token,
  );

  if (result.success) {
    helpers.showToast("Demande de modification envoyée avec succès", "success");
    modal.hide();
    // Recharger les événements
    await loadCreatedEvents();
  } else {
    helpers.showToast(result.message || "Erreur lors de la demande", "error");
  }
}

// Ouvrir la modal de suppression d'événement
function openDeleteEventModal(eventId, eventTitle) {
  const modalHtml = `
    <div class="modal fade" id="deleteEventModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Demander la suppression de l'événement</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
              <i class="bi bi-exclamation-triangle"></i>
              <strong>Attention !</strong> La suppression devra être validée par un administrateur.
            </div>
            <p><strong>Événement :</strong> ${eventTitle}</p>
            <p class="text-muted small">
              Veuillez expliquer la raison de l'annulation. Ce message sera envoyé par email à tous les participants ayant réservé ou acheté des billets.
            </p>
            <form id="deleteEventForm">
              <input type="hidden" id="deleteEventId" value="${eventId}">
              <div class="mb-3">
                <label for="deletionMessage" class="form-label">Message d'excuse et d'explication <span class="text-danger">*</span></label>
                <textarea 
                  class="form-control" 
                  id="deletionMessage" 
                  rows="4" 
                  required
                  placeholder="Exemple : Nous sommes sincèrement désolés mais en raison de circonstances imprévues, nous devons annuler cet événement..."></textarea>
                <div class="form-text">Minimum 20 caractères</div>
                <div class="invalid-feedback"></div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="button" class="btn btn-danger" id="btnConfirmDelete">Demander la suppression</button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Supprimer l'ancienne modal si elle existe
  const existingModal = document.getElementById("deleteEventModal");
  if (existingModal) {
    existingModal.remove();
  }

  // Ajouter la modal au DOM
  document.body.insertAdjacentHTML("beforeend", modalHtml);

  // Afficher la modal
  const modalEl = document.getElementById("deleteEventModal");
  const modal = new bootstrap.Modal(modalEl);
  modal.show();

  // Event listener pour le bouton de confirmation
  document
    .getElementById("btnConfirmDelete")
    .addEventListener("click", async () => {
      await handleDeleteEvent(modal);
    });
}

// Gérer la suppression d'événement
async function handleDeleteEvent(modal) {
  const eventId = document.getElementById("deleteEventId").value;
  const deletionMessage = document
    .getElementById("deletionMessage")
    .value.trim();

  // Validation
  if (!deletionMessage || deletionMessage.length < 20) {
    helpers.showToast(
      "Le message doit contenir au moins 20 caractères",
      "error",
    );
    return;
  }

  const token = auth.getToken();
  const result = await EventManager.requestDeletion(
    eventId,
    deletionMessage,
    token,
  );

  if (result.success) {
    helpers.showToast("Demande de suppression envoyée avec succès", "success");
    modal.hide();
    // Recharger les événements
    await loadCreatedEvents();
  } else {
    helpers.showToast(result.message || "Erreur lors de la demande", "error");
  }
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Pas de nettoyage nécessaire pour cette vue simple
  // Nettoyer les fonctions globales
  delete window.openModifyEventModal;
  delete window.openDeleteEventModal;
}

// Attacher les événements de la page profil
function attachProfileEvents() {
  // Bouton de confirmation de réinitialisation du mot de passe
  const btnConfirmPasswordReset = document.getElementById(
    "btnConfirmPasswordReset",
  );
  if (btnConfirmPasswordReset) {
    btnConfirmPasswordReset.addEventListener(
      "click",
      handleConfirmPasswordReset,
    );
  }

  // Navigation entre sections via les stat cards
  initSectionNavigation();
}

// Initialiser la navigation entre sections
function initSectionNavigation() {
  const statButtons = document.querySelectorAll(".stat-card-btn");

  if (statButtons.length === 0) return;

  statButtons.forEach((button) => {
    const section = button.getAttribute("data-section");

    button.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      // Retirer la classe active de tous les boutons
      statButtons.forEach((btn) => btn.classList.remove("active"));

      // Ajouter active au bouton cliqué
      button.classList.add("active");

      // Cacher toutes les sections
      document.querySelectorAll(".profile-section").forEach((sec) => {
        sec.style.display = "none";
      });

      // Afficher la section ciblée
      const targetElement = document.getElementById(`section-${section}`);
      if (targetElement) {
        targetElement.style.display = "block";
      }
    });
  });
}

// Gérer la confirmation de réinitialisation du mot de passe
async function handleConfirmPasswordReset() {
  const user = auth.getUser();
  if (!user || !user.email) {
    helpers.showToast("Erreur : utilisateur non connecté", "error");
    return;
  }

  const btnConfirm = document.getElementById("btnConfirmPasswordReset");
  if (!btnConfirm) return;

  // Désactiver le bouton
  btnConfirm.disabled = true;
  btnConfirm.innerHTML =
    '<i class="bi bi-hourglass-split me-2"></i>Envoi en cours...';

  try {
    // Envoyer la demande de réinitialisation
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
        email: user.email,
      }),
    });

    const data = await response.json();

    if (data.success) {
      // Fermer la modal de confirmation
      const modal = bootstrap.Modal.getInstance(
        document.getElementById("changePasswordModal"),
      );
      if (modal) modal.hide();

      // Attendre un peu puis ouvrir la modal de réinitialisation
      setTimeout(() => {
        openResetPasswordModal(user.email);
        helpers.showToast("Un code a été envoyé à votre email", "success");
      }, 300);
    } else {
      helpers.showToast(data.message, "error");
      btnConfirm.disabled = false;
      btnConfirm.innerHTML = '<i class="bi bi-check-circle me-2"></i>Oui';
    }
  } catch (error) {
    helpers.showToast("Erreur de connexion au serveur", "error");
    btnConfirm.disabled = false;
    btnConfirm.innerHTML = '<i class="bi bi-check-circle me-2"></i>Oui';
  }
}

// Afficher les informations utilisateur
function displayUserInfo() {
  const user = appState.get("user");
  const userNameEl = document.getElementById("userName");
  const userEmailEl = document.getElementById("userEmail");

  if (userNameEl && user) {
    userNameEl.textContent = user.name || "Utilisateur";
  }

  if (userEmailEl && user) {
    userEmailEl.textContent = user.email || "";
  }

  // Mettre à jour le compteur de favoris
  const favorites = appState.get("favorites") || [];
  const statFavorites = document.getElementById("statFavorites");
  if (statFavorites) {
    statFavorites.textContent = favorites.length;
  }
}

// Charger les événements créés par l'utilisateur
async function loadCreatedEvents() {
  const token = auth.getToken();
  if (!token) return;

  const eventsContainer = document.getElementById("userEvents");
  if (!eventsContainer) return;

  // Afficher un loader
  eventsContainer.innerHTML = `
    <div class="text-center py-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
    </div>
  `;

  // Récupérer les événements créés
  const result = await EventManager.getMyEvents(token);

  if (result.success && result.data && result.data.length > 0) {
    createdEvents = result.data;
    displayCreatedEvents(createdEvents);

    // Mettre à jour le compteur
    const statEvents = document.getElementById("statEvents");
    if (statEvents) {
      statEvents.textContent = createdEvents.length;
    }
  } else {
    // Aucun événement créé
    eventsContainer.innerHTML = `
      <div class="empty-state-small">
        <i class="bi bi-calendar-x text-muted fs-1 mb-2"></i>
        <p class="text-muted mb-3">Vous n'avez pas encore créé d'événements</p>
      </div>
    `;
  }
}

// Afficher les événements créés
function displayCreatedEvents(events) {
  const eventsContainer = document.getElementById("userEvents");
  if (!eventsContainer) return;

  eventsContainer.innerHTML = `
    <div class="d-flex flex-wrap gap-3">
      ${events
        .map((event) => {
          // Déterminer le statut de l'événement
          let statusBadge = "";
          if (event.deletion_requested) {
            statusBadge =
              '<span class="badge bg-dark">En attente de suppression</span>';
          } else if (event.has_pending_modification) {
            statusBadge =
              '<span class="badge bg-info">Modification en attente</span>';
          } else if (event.is_pending) {
            statusBadge = '<span class="badge bg-warning">En attente</span>';
          } else if (event.is_approved) {
            statusBadge = '<span class="badge bg-success">Approuvé</span>';
          } else if (event.is_rejected) {
            statusBadge = '<span class="badge bg-danger">Rejeté</span>';
          }

          const priceDisplay = event.is_free ? "Gratuit" : "Voir billets";

          // Boutons d'action selon le statut
          let actionButtons = `
            <button class="btn btn-primary btn-sm flex-grow-1" 
                    onclick="event.stopPropagation(); viewCreatedEventDetails(${event.id})">
              <i class="bi bi-eye"></i> Voir détails
            </button>
          `;

          // Si l'événement est approuvé et n'a pas de modification/suppression en attente
          if (
            event.is_approved &&
            !event.has_pending_modification &&
            !event.deletion_requested
          ) {
            actionButtons = `
              <button class="btn btn-primary btn-sm" 
                      onclick="event.stopPropagation(); viewCreatedEventDetails(${event.id})">
                <i class="bi bi-eye"></i> Détails
              </button>
              <button class="btn btn-warning btn-sm" 
                      onclick="event.stopPropagation(); openModifyEventModal(${event.id}, '${event.date}', '${event.time}')">
                <i class="bi bi-pencil"></i> Modifier
              </button>
              <button class="btn btn-danger btn-sm" 
                      onclick="event.stopPropagation(); openDeleteEventModal(${event.id}, '${event.title.replace(/'/g, "\\'")}')">
                <i class="bi bi-trash"></i> Supprimer
              </button>
            `;
          }

          return `
        <div class="favorite-card-wrapper" id="created-event-${event.id}">
          <div class="card h-100 shadow-sm hover-card" 
               onclick="viewCreatedEventDetails(${event.id})" 
               style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <h5 class="card-title text-primary mb-0">${event.title}</h5>
                ${statusBadge}
              </div>
              
              <div class="mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-geo-alt text-muted"></i>
                  <span class="small">${event.city}, ${event.country}</span>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-calendar3 text-muted"></i>
                  <span class="small">${helpers.formatDate(event.date)}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-tag text-muted"></i>
                  <span class="fw-bold text-primary small">${priceDisplay}</span>
                </div>
              </div>
              
              <div class="d-flex gap-2">
                ${actionButtons}
              </div>
            </div>
          </div>
        </div>
        `;
        })
        .join("")}
    </div>
  `;
}

// Voir les détails d'un événement créé (fonction globale pour onclick)
window.viewCreatedEventDetails = function (eventId) {
  const event = createdEvents.find((e) => e.id === eventId);
  if (event) {
    showEventDetail(event);
  } else {
    helpers.showToast("Événement non trouvé", "error");
  }
};

// Charger les favoris
async function loadFavorites() {
  const token = auth.getToken();
  if (!token) return;

  const favoritesContainer = document.getElementById("userFavorites");
  if (!favoritesContainer) return;

  // Afficher un loader
  favoritesContainer.innerHTML = `
    <div class="text-center py-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
    </div>
  `;

  // Récupérer les favoris avec détails
  const result = await FavoriteManager.getByUserWithDetails(token);

  if (result.success && result.data && result.data.length > 0) {
    // Transformer les événements pour avoir le bon format d'image
    const transformedEvents = helpers.transformEvents(result.data);
    favoriteEvents = transformedEvents; // Stocker pour les détails
    displayFavorites(transformedEvents);
  } else {
    // Aucun favori
    favoritesContainer.innerHTML = `
      <div class="text-center py-5">
        <i class="bi bi-heart text-muted fs-1 mb-2"></i>
        <p class="text-muted mb-0">Aucun événement en favoris</p>
      </div>
    `;
  }
}

// Afficher les favoris
function displayFavorites(favorites) {
  const favoritesContainer = document.getElementById("userFavorites");
  if (!favoritesContainer) return;

  favoritesContainer.innerHTML = `
    <div class="d-flex flex-wrap gap-3">
      ${favorites
        .map((event) => {
          // Gérer le prix
          const priceDisplay = event.is_free ? "Gratuit" : "Voir billets";

          return `
        <div class="favorite-card-wrapper" id="favorite-card-${event.id}">
          <div class="card h-100 shadow-sm hover-card" 
               onclick="viewEventDetails(${event.id})" 
               style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
            <div class="card-body">
              <h5 class="card-title text-primary mb-3">${event.title}</h5>
              
              <div class="mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-geo-alt text-muted"></i>
                  <span>${event.city}, ${event.country}</span>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-calendar3 text-muted"></i>
                  <span>${helpers.formatDate(event.date)}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-tag text-muted"></i>
                  <span class="fw-bold text-primary">${priceDisplay}</span>
                </div>
              </div>
              
              <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" 
                        onclick="event.stopPropagation(); viewEventDetails(${event.id})">
                  <i class="bi bi-eye"></i> Voir détails
                </button>
                <button class="btn btn-outline-danger btn-sm" 
                        onclick="event.stopPropagation(); removeFavoriteFromProfile(${event.id}, this)"
                        id="remove-fav-${event.id}"
                        title="Retirer des favoris">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        `;
        })
        .join("")}
    </div>
  `;
}

// Voir les détails d'un événement (fonction globale pour onclick)
window.viewEventDetails = function (eventId) {
  const event = favoriteEvents.find((e) => e.id === eventId);
  if (event) {
    showEventDetail(event);
  } else {
    helpers.showToast("Événement non trouvé", "error");
  }
};

// Fonction globale pour retirer un favori depuis le profil
window.removeFavoriteFromProfile = async function (eventId, btnElement) {
  if (btnElement.disabled) return;

  const token = auth.getToken();

  // Désactiver le bouton pendant le traitement
  btnElement.disabled = true;
  btnElement.innerHTML =
    '<span class="spinner-border spinner-border-sm"></span> Suppression...';

  const result = await FavoriteManager.remove(eventId, token);

  if (result.success) {
    helpers.showToast("Retiré des favoris", "success");

    // Mettre à jour le state
    const userFavorites = appState.get("favorites") || [];
    const updatedFavorites = userFavorites.filter(
      (fav) => fav.event_id != eventId,
    );
    appState.set("favorites", updatedFavorites);

    // Animation de suppression de la carte
    const card = document.getElementById(`favorite-card-${eventId}`);
    if (card) {
      card.style.transition = "opacity 0.3s, transform 0.3s";
      card.style.opacity = "0";
      card.style.transform = "scale(0.8)";

      setTimeout(() => {
        // Recharger l'affichage après l'animation
        loadFavorites();
      }, 300);
    } else {
      // Si la carte n'existe pas, juste recharger
      loadFavorites();
    }
  } else {
    helpers.showToast(result.message || "Erreur", "error");
    btnElement.disabled = false;
    btnElement.innerHTML = '<i class="bi bi-trash"></i> Retirer des favoris';
  }
};

// Charger les réservations de l'utilisateur
async function loadReservations() {
  const token = auth.getToken();
  if (!token) return;

  const reservationsContainer = document.getElementById("userReservations");
  if (!reservationsContainer) return;

  // Afficher un loader
  reservationsContainer.innerHTML = `
    <div class="text-center py-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
    </div>
  `;

  // Récupérer les réservations de l'utilisateur
  const result = await ReservationManager.getMyReservations(token);

  if (result.success && result.data && result.data.length > 0) {
    // Filtrer pour ne garder que les réservations confirmées (exclure les annulées)
    userReservations = result.data.filter(
      (reservation) => reservation.status === "confirmed",
    );

    if (userReservations.length > 0) {
      displayReservations(userReservations);

      // Mettre à jour le compteur
      const statReservations = document.getElementById("statReservations");
      if (statReservations) {
        statReservations.textContent = userReservations.length;
      }
    } else {
      // Aucune réservation confirmée
      reservationsContainer.innerHTML = `
        <div class="empty-state-small">
          <i class="bi bi-ticket-perforated text-muted fs-1 mb-2"></i>
          <p class="text-muted mb-3">Vous n'avez pas de réservations actives</p>
          <a href="/" data-link class="btn btn-primary btn-sm">
            <i class="bi bi-calendar-event"></i> Découvrir des événements
          </a>
        </div>
      `;

      // Mettre à jour le compteur à 0
      const statReservations = document.getElementById("statReservations");
      if (statReservations) {
        statReservations.textContent = "0";
      }
    }
  } else {
    // Aucune réservation
    reservationsContainer.innerHTML = `
      <div class="empty-state-small">
        <i class="bi bi-ticket-perforated text-muted fs-1 mb-2"></i>
        <p class="text-muted mb-3">Vous n'avez pas encore de réservations</p>
        <a href="/" data-link class="btn btn-primary btn-sm">
          <i class="bi bi-calendar-event"></i> Découvrir des événements
        </a>
      </div>
    `;

    // Mettre à jour le compteur à 0
    const statReservations = document.getElementById("statReservations");
    if (statReservations) {
      statReservations.textContent = "0";
    }
  }
}

// Afficher les réservations
function displayReservations(reservations) {
  const reservationsContainer = document.getElementById("userReservations");
  if (!reservationsContainer) return;

  reservationsContainer.innerHTML = `
    <div class="d-flex flex-column gap-3">
      ${reservations
        .map((reservation) => {
          // Déterminer le statut de la réservation
          let statusBadge = "";
          let statusClass = "";

          if (reservation.status === "confirmed") {
            statusBadge = '<span class="badge bg-success">Confirmée</span>';
            statusClass = "border-success";
          } else if (reservation.status === "cancelled") {
            statusBadge = '<span class="badge bg-danger">Annulée</span>';
            statusClass = "border-danger";
          }

          // Formater le prix
          const priceDisplay =
            reservation.event_is_free || reservation.event_ticket_price <= 0
              ? "Gratuit"
              : `${helpers.formatPrice(reservation.event_ticket_price * reservation.quantity)}`;

          // Formater la date de réservation
          const reservationDate = helpers.formatDate(reservation.created_at);

          // Formater la date de l'événement
          const eventDate = helpers.formatDate(reservation.event_date);

          return `
        <div class="card ${statusClass}" style="border-width: 2px;">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h6 class="card-title mb-1">${reservation.event_title}</h6>
                <small class="text-muted">
                  <i class="bi bi-calendar3"></i> Réservé le ${reservationDate}
                </small>
              </div>
              ${statusBadge}
            </div>
            
            <div class="mb-3">
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-calendar-event me-2 text-primary"></i>
                <span>${eventDate} à ${reservation.event_time}</span>
              </div>
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-geo-alt me-2 text-primary"></i>
                <span>${reservation.event_city} - ${reservation.event_address}</span>
              </div>
              <div class="d-flex align-items-center">
                <i class="bi bi-ticket-perforated me-2 text-primary"></i>
                <span>${reservation.quantity} place${reservation.quantity > 1 ? "s" : ""}</span>
              </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center border-top pt-3">
              <span class="fw-bold">Prix</span>
              <span class="fs-5 fw-bold text-primary">${priceDisplay}</span>
            </div>
            
            ${
              reservation.status === "confirmed"
                ? `
              <div class="d-grid gap-2 mt-3">
                <button class="btn btn-outline-danger btn-sm" 
                        onclick="cancelReservation(${reservation.id})"
                        id="cancel-reservation-${reservation.id}">
                  <i class="bi bi-x-circle"></i> Annuler la réservation
                </button>
              </div>
            `
                : ""
            }
          </div>
        </div>
        `;
        })
        .join("")}
    </div>
  `;
}

// Fonction globale pour annuler une réservation
window.cancelReservation = function (reservationId) {
  // Trouver les informations de la réservation
  const reservation = userReservations.find((r) => r.id === reservationId);

  if (!reservation) {
    helpers.showToast("Réservation introuvable", "error");
    return;
  }

  // Préparer les informations de l'événement pour la modal
  const eventInfo = {
    name: reservation.event_title,
    date: reservation.event_date,
    location: `${reservation.event_city} - ${reservation.event_address}`,
  };

  // Ouvrir la modal de confirmation avec un callback
  openCancelReservationModal(reservationId, eventInfo, async (resId) => {
    const btnElement = document.getElementById(`cancel-reservation-${resId}`);
    if (!btnElement || btnElement.disabled) return;

    const token = auth.getToken();
    if (!token) {
      helpers.showToast("Vous devez être connecté", "error");
      return;
    }

    // Désactiver le bouton pendant le traitement
    btnElement.disabled = true;
    btnElement.innerHTML =
      '<span class="spinner-border spinner-border-sm"></span> Annulation...';

    const result = await ReservationManager.cancel(resId, token);

    if (result.success) {
      helpers.showToast(result.message, "success");
      // Recharger les réservations
      await loadReservations();
    } else {
      helpers.showToast(result.message, "error");
      btnElement.disabled = false;
      btnElement.innerHTML =
        '<i class="bi bi-x-circle"></i> Annuler la réservation';
    }
  });
};

// Export par défaut
export default { mount, unmount, meta };
