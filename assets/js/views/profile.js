// Vue Profile - Profil utilisateur

import { auth } from "../utils/auth.js";
import { helpers } from "../utils/helpers.js";
import { appState } from "../store/appState.js";
import FavoriteManager from "../managers/FavoriteManager.js";
import EventManager from "../managers/EventManager.js";
import OrderManager from "../managers/OrderManager.js";
import StripeConnectManager from "../managers/StripeConnectManager.js";
import { showEventDetail } from "../components/eventDetail.js";
import { validateChangePasswordForm } from "../validators/authValidator.js";
import {
  setFieldError,
  clearFieldValidation,
} from "../validators/formValidator.js";

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
  try {
    // Forcer le rechargement sans cache
    const response = await fetch(path, {
      cache: "no-store",
      headers: {
        "Cache-Control": "no-cache",
        Pragma: "no-cache",
      },
    });

    if (!response.ok) {
      throw new Error(`Erreur ${response.status}: ${response.statusText}`);
    }

    const htmlContent = await response.text();

    const parser = new DOMParser();
    const templateDoc = parser.parseFromString(htmlContent, "text/html");
    const templates = templateDoc.querySelectorAll("template");

    if (templates.length === 0) {
      throw new Error("Aucun template trouvé dans le fichier");
    }

    // Vider l'objet templateObjects avant de le remplir
    Object.keys(templateObjects).forEach((key) => delete templateObjects[key]);

    templates.forEach((template) => {
      const templateId = template.id;
      templateObjects[templateId] = template.content;
    });
  } catch (error) {
    throw error;
  }
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
      window.router.navigate("/");
    }, 1500);
    return;
  }

  // Charger le template avec timestamp pour éviter le cache
  const timestamp = Date.now();
  await loadTemplate(`assets/templates/views/profile.html?v=${timestamp}`);

  // Vérifier que le template est chargé
  if (!templateObjects["profileView"]) {
    helpers.showToast("Erreur de chargement de la page", "error");
    return;
  }

  // Injecter le template
  const clone = templateObjects["profileView"].cloneNode(true);
  container.innerHTML = "";
  container.appendChild(clone);

  // Attendre que le DOM soit réellement mis à jour
  await new Promise((resolve) => setTimeout(resolve, 50));

  // Afficher les informations utilisateur
  displayUserInfo();

  // Attacher les événements (le DOM est maintenant prêt)
  attachProfileEvents();

  // Charger et afficher les données utilisateur
  await Promise.all([loadFavorites(), loadCreatedEvents(), loadReservations()]);

  // Charger et afficher le statut Stripe Connect
  await loadStripeConnectStatus();

  // Vérifier si retour depuis Stripe
  checkStripeReturnStatus();

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
  // Formulaire de changement de mot de passe
  const changePasswordForm = document.getElementById("changePasswordForm");
  if (changePasswordForm) {
    changePasswordForm.addEventListener("submit", handleChangePassword);
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

// Gérer le changement de mot de passe
async function handleChangePassword(e) {
  e.preventDefault();

  const currentPassword = document.getElementById("currentPassword").value;
  const newPassword = document.getElementById("newPassword").value;
  const confirmPassword = document.getElementById("confirmPassword").value;

  // Références aux éléments
  const currentPasswordInput = document.getElementById("currentPassword");
  const newPasswordInput = document.getElementById("newPassword");
  const confirmPasswordInput = document.getElementById("confirmPassword");
  const currentPasswordError = document.getElementById("currentPasswordError");
  const newPasswordError = document.getElementById("newPasswordError");
  const confirmPasswordError = document.getElementById("confirmPasswordError");

  // Réinitialiser les erreurs
  clearFieldValidation(currentPasswordInput, currentPasswordError);
  clearFieldValidation(newPasswordInput, newPasswordError);
  clearFieldValidation(confirmPasswordInput, confirmPasswordError);

  // Validation avec le validator
  const validation = validateChangePasswordForm({
    currentPassword,
    newPassword,
    confirmPassword,
  });

  if (!validation.valid) {
    // Afficher les erreurs
    if (validation.errors.currentPassword) {
      setFieldError(
        currentPasswordInput,
        currentPasswordError,
        validation.errors.currentPassword,
      );
    }
    if (validation.errors.newPassword) {
      setFieldError(
        newPasswordInput,
        newPasswordError,
        validation.errors.newPassword,
      );
    }
    if (validation.errors.confirmPassword) {
      setFieldError(
        confirmPasswordInput,
        confirmPasswordError,
        validation.errors.confirmPassword,
      );
    }
    return;
  }

  // Appel API
  const token = auth.getToken();
  if (!token) {
    helpers.showToast("Vous devez être connecté", "error");
    return;
  }

  // Import dynamique de AuthManager
  const { default: AuthManager } = await import("../managers/AuthManager.js");
  const result = await AuthManager.changePassword(
    token,
    currentPassword,
    newPassword,
  );

  if (result.success) {
    helpers.showToast("Mot de passe modifié avec succès", "success");

    // Fermer la modal
    const modal = bootstrap.Modal.getInstance(
      document.getElementById("changePasswordModal"),
    );
    if (modal) modal.hide();

    // Réinitialiser le formulaire
    document.getElementById("changePasswordForm").reset();
  } else {
    if (result.message.includes("actuel")) {
      setFieldError(currentPasswordInput, currentPasswordError, result.message);
    } else {
      helpers.showToast(result.message, "error");
    }
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

  // Récupérer les commandes de l'utilisateur
  const result = await OrderManager.getByUser(token);

  if (result.success && result.data && result.data.length > 0) {
    userReservations = result.data;
    displayReservations(userReservations);

    // Mettre à jour le compteur
    const statReservations = document.getElementById("statReservations");
    if (statReservations) {
      statReservations.textContent = userReservations.length;
    }
  } else {
    // Aucune réservation
    reservationsContainer.innerHTML = `
      <div class="empty-state-small">
        <i class="bi bi-ticket-perforated text-muted fs-1 mb-2"></i>
        <p class="text-muted mb-3">Vous n'avez pas encore de réservations</p>
        <a href="/" class="btn btn-primary btn-sm">
          <i class="bi bi-calendar-event"></i> Découvrir des événements
        </a>
      </div>
    `;
  }
}

// Afficher les réservations
function displayReservations(reservations) {
  const reservationsContainer = document.getElementById("userReservations");
  if (!reservationsContainer) return;

  reservationsContainer.innerHTML = `
    <div class="d-flex flex-column gap-3">
      ${reservations
        .map((order) => {
          // Déterminer le statut de la commande
          let statusBadge = "";
          let statusClass = "";

          if (order.is_paid) {
            statusBadge = '<span class="badge bg-success">Payé</span>';
            statusClass = "border-success";
          } else if (order.is_cancelled) {
            statusBadge = '<span class="badge bg-danger">Annulé</span>';
            statusClass = "border-danger";
          } else if (order.is_failed) {
            statusBadge = '<span class="badge bg-warning">Échec</span>';
            statusClass = "border-warning";
          } else if (order.is_pending) {
            statusBadge = '<span class="badge bg-info">En attente</span>';
            statusClass = "border-info";
          }

          // Formater le prix
          const priceDisplay =
            order.total_price <= 0
              ? "Gratuit"
              : `${helpers.formatPrice(order.total_price)}`;

          // Formater la date de commande
          const orderDate = helpers.formatDate(order.created_at);

          return `
        <div class="card ${statusClass}" style="border-width: 2px;">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h6 class="card-title mb-1">Commande #${order.id}</h6>
                <small class="text-muted">
                  <i class="bi bi-calendar3"></i> ${orderDate}
                </small>
              </div>
              ${statusBadge}
            </div>
            
            ${
              order.items && order.items.length > 0
                ? `
              <div class="mb-3">
                ${order.items
                  .map(
                    (item) => `
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                      <div class="fw-bold">${item.event_title}</div>
                      <small class="text-muted">${item.ticket_name} × ${item.quantity}</small>
                    </div>
                    <span class="text-primary fw-bold">
                      ${item.unit_price <= 0 ? "Gratuit" : helpers.formatPrice(item.unit_price * item.quantity)}
                    </span>
                  </div>
                `,
                  )
                  .join("")}
              </div>
              
              <div class="d-flex justify-content-between align-items-center border-top pt-3">
                <span class="fw-bold">Total</span>
                <span class="fs-5 fw-bold text-primary">${priceDisplay}</span>
              </div>
              
              ${
                order.is_paid
                  ? `
                <div class="d-grid gap-2 mt-3">
                  <button class="btn btn-primary btn-sm" 
                          onclick="downloadTickets(${order.id})"
                          id="download-tickets-${order.id}">
                    <i class="bi bi-download"></i> Télécharger les billets PDF
                  </button>
                </div>
              `
                  : ""
              }
            `
                : `
              <p class="text-muted small mb-0">Aucun détail disponible</p>
            `
            }
          </div>
        </div>
        `;
        })
        .join("")}
    </div>
  `;
}

// Fonction globale pour télécharger les billets
window.downloadTickets = async function (orderId) {
  const btnElement = document.getElementById(`download-tickets-${orderId}`);
  if (btnElement.disabled) return;

  const token = auth.getToken();
  if (!token) {
    helpers.showToast("Vous devez être connecté", "error");
    return;
  }

  // Désactiver le bouton pendant le traitement
  btnElement.disabled = true;
  btnElement.innerHTML =
    '<span class="spinner-border spinner-border-sm"></span> Téléchargement...';

  try {
    // TODO: Implémenter l'API de téléchargement des tickets
    // Pour l'instant, juste afficher un message
    helpers.showToast("Téléchargement des billets en cours...", "info");

    // Simuler un téléchargement
    setTimeout(() => {
      helpers.showToast("Billets téléchargés avec succès", "success");
      btnElement.disabled = false;
      btnElement.innerHTML =
        '<i class="bi bi-download"></i> Télécharger les billets PDF';
    }, 2000);
  } catch (error) {
    helpers.showToast("Erreur lors du téléchargement des billets", "error");
    btnElement.disabled = false;
    btnElement.innerHTML =
      '<i class="bi bi-download"></i> Télécharger les billets PDF';
  }
};

// ========================================
// FONCTIONS STRIPE CONNECT
// ========================================

// Charger et afficher le statut Stripe Connect
async function loadStripeConnectStatus() {
  const container = document.getElementById("stripeConnectSection");
  if (!container) return;

  const result = await StripeConnectManager.checkStripeAccount();

  if (!result.success) {
    container.innerHTML = "";
    return;
  }

  const status = result.data.status;
  const hasAccount = result.data.has_stripe_account;

  if (hasAccount && status === "connected") {
    // Compte connecté
    container.innerHTML = `
      <div class="alert alert-success d-flex align-items-center gap-3">
        <div>
          <i class="bi bi-check-circle-fill fs-3"></i>
        </div>
        <div class="flex-grow-1">
          <strong>Compte Stripe connecté</strong>
          <p class="mb-0 small">Vous pouvez créer des événements payants et recevoir les paiements directement.</p>
        </div>
        <button class="btn btn-outline-success btn-sm" id="btnManageStripe">
          <i class="bi bi-gear"></i> Gérer
        </button>
      </div>
    `;

    // Event listener pour gérer le compte
    document
      .getElementById("btnManageStripe")
      ?.addEventListener("click", handleManageStripe);
  } else if (status === "pending") {
    // Onboarding en cours
    container.innerHTML = `
      <div class="alert alert-warning d-flex align-items-center gap-3">
        <div>
          <i class="bi bi-hourglass-split fs-3"></i>
        </div>
        <div class="flex-grow-1">
          <strong>Configuration Stripe en cours</strong>
          <p class="mb-0 small">Finalisez votre compte Stripe pour recevoir les paiements.</p>
        </div>
        <button class="btn btn-outline-warning btn-sm" id="btnContinueStripe">
          <i class="bi bi-arrow-right"></i> Continuer
        </button>
      </div>
    `;

    // Event listener pour continuer l'onboarding
    document
      .getElementById("btnContinueStripe")
      ?.addEventListener("click", handleContinueStripeOnboarding);
  } else {
    // Pas de compte Stripe
    container.innerHTML = `
      <div class="alert alert-info d-flex align-items-center gap-3">
        <div>
          <i class="bi bi-stripe fs-3"></i>
        </div>
        <div class="flex-grow-1">
          <strong>Créez des événements payants</strong>
          <p class="mb-0 small">Connectez votre compte Stripe pour recevoir les paiements de vos billets.</p>
        </div>
        <button class="btn btn-primary btn-sm" id="btnConnectStripe">
          <i class="bi bi-plus-circle"></i> Connecter Stripe
        </button>
      </div>
    `;

    // Event listener pour connecter Stripe
    document
      .getElementById("btnConnectStripe")
      ?.addEventListener("click", handleConnectStripe);
  }
}

// Gérer la connexion Stripe
async function handleConnectStripe() {
  const btn = document.getElementById("btnConnectStripe");
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Connexion...';
  }

  const result = await StripeConnectManager.createStripeConnectAccount();

  if (result.success && result.data.onboarding_url) {
    helpers.showToast("Redirection vers Stripe...", "info");
    setTimeout(() => {
      window.location.href = result.data.onboarding_url;
    }, 500);
  } else {
    helpers.showToast(result.message || "Erreur lors de la connexion", "error");
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-plus-circle"></i> Connecter Stripe';
    }
  }
}

// Continuer l'onboarding Stripe
async function handleContinueStripeOnboarding() {
  const btn = document.getElementById("btnContinueStripe");
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Chargement...';
  }

  const result = await StripeConnectManager.createStripeConnectAccount();

  if (result.success && result.data.onboarding_url) {
    helpers.showToast("Redirection vers Stripe...", "info");
    setTimeout(() => {
      window.location.href = result.data.onboarding_url;
    }, 500);
  } else {
    helpers.showToast(result.message || "Erreur lors du chargement", "error");
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-arrow-right"></i> Continuer';
    }
  }
}

// Gérer le compte Stripe (ouvrir dashboard)
async function handleManageStripe() {
  const btn = document.getElementById("btnManageStripe");
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Chargement...';
  }

  const result = await StripeConnectManager.getDashboardLink();

  if (result.success && result.data.url) {
    window.open(result.data.url, "_blank");
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-gear"></i> Gérer';
    }
  } else {
    helpers.showToast(
      result.message || "Erreur lors de l'ouverture du dashboard",
      "error",
    );
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-gear"></i> Gérer';
    }
  }
}

// Vérifier si retour depuis Stripe et afficher message
function checkStripeReturnStatus() {
  const urlParams = new URLSearchParams(window.location.search);
  const stripeStatus = urlParams.get("stripe");

  if (stripeStatus === "success") {
    // Vérifier la complétion du compte
    verifyStripeAccountCompletion();

    // Nettoyer l'URL
    window.history.replaceState({}, "", window.location.pathname);
  } else if (stripeStatus === "refresh") {
    helpers.showToast(
      "Configuration Stripe non terminée. Vous pouvez la reprendre plus tard.",
      "info",
    );

    // Nettoyer l'URL
    window.history.replaceState({}, "", window.location.pathname);
  }
}

// Vérifier la complétion du compte Stripe
async function verifyStripeAccountCompletion() {
  const result = await StripeConnectManager.verifyAccountCompletion();

  if (result.success && result.data.is_complete) {
    helpers.showToast("Compte Stripe connecté avec succès ! 🎉", "success");

    // Recharger le statut Stripe
    await loadStripeConnectStatus();
  } else {
    helpers.showToast(
      "Configuration Stripe en cours. Finalisez-la pour activer les paiements.",
      "warning",
    );

    // Recharger le statut Stripe
    await loadStripeConnectStatus();
  }
}

// Export par défaut
export default { mount, unmount, meta };
