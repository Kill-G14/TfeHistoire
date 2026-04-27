import EventManager from "../managers/EventManager.js";
import { storage, helpers } from "../utils/helpers.js";

let allEvents = [];

// Vérifier l'authentification
async function checkAuth() {
  const token = storage.getToken();
  if (!token) {
    window.location.href = "login.html";
    return;
  }
}

// Charger tous les événements
async function loadEvents() {
  const token = storage.getToken();

  // Charger tous les événements
  const allResult = await EventManager.getAll(token);
  if (allResult.success) {
    allEvents = allResult.data;
    renderAllEvents();
    renderApprovedEvents();
  }

  // Charger les événements en attente
  const pendingResult = await EventManager.getPending(token);
  if (pendingResult.success) {
    renderPendingEvents(pendingResult.data);
    document.getElementById("pendingCount").textContent =
      pendingResult.data.length;
  }
}

// Rendre les événements en attente
function renderPendingEvents(events) {
  const tbody = document.getElementById("pendingEventsList");

  if (events.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="6" class="text-center">Aucun événement en attente</td></tr>';
    return;
  }

  tbody.innerHTML = events
    .map(
      (event) => `
    <tr>
      <td>${event.id}</td>
      <td>${event.title}</td>
      <td>${helpers.formatDate(event.date)}</td>
      <td>${event.city}</td>
      <td><span class="badge badge-info">${event.category}</span></td>
      <td>
        <button class="btn btn-sm btn-success" onclick="window.approveEvent(${event.id})">
          <i class="fas fa-check"></i> Approuver
        </button>
        <button class="btn btn-sm btn-warning" onclick="window.rejectEvent(${event.id})">
          <i class="fas fa-times"></i> Rejeter
        </button>
        <button class="btn btn-sm btn-danger" onclick="window.deleteEvent(${event.id})">
          <i class="fas fa-trash"></i> Supprimer
        </button>
      </td>
    </tr>
  `,
    )
    .join("");
}

// Rendre les événements approuvés
function renderApprovedEvents() {
  const approvedEvents = allEvents.filter((e) => e.is_approved);
  const tbody = document.getElementById("approvedEventsList");

  if (approvedEvents.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="6" class="text-center">Aucun événement approuvé</td></tr>';
    return;
  }

  tbody.innerHTML = approvedEvents
    .map(
      (event) => `
    <tr>
      <td>${event.id}</td>
      <td>${event.title}</td>
      <td>${helpers.formatDate(event.date)}</td>
      <td>${event.city}</td>
      <td><span class="badge badge-info">${event.category}</span></td>
      <td>
        <button class="btn btn-sm btn-danger" onclick="window.deleteEvent(${event.id})">
          <i class="fas fa-trash"></i> Supprimer
        </button>
      </td>
    </tr>
  `,
    )
    .join("");
}

// Rendre tous les événements
function renderAllEvents() {
  const tbody = document.getElementById("allEventsList");

  if (allEvents.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="6" class="text-center">Aucun événement</td></tr>';
    return;
  }

  tbody.innerHTML = allEvents
    .map(
      (event) => `
    <tr>
      <td>${event.id}</td>
      <td>${event.title}</td>
      <td>${helpers.formatDate(event.date)}</td>
      <td>${event.city}</td>
      <td>
        ${
          event.is_pending
            ? '<span class="badge badge-warning">En attente</span>'
            : event.is_approved
              ? '<span class="badge badge-success">Approuvé</span>'
              : event.is_rejected
                ? '<span class="badge badge-danger">Rejeté</span>'
                : ""
        }
      </td>
      <td>
        ${
          event.is_pending
            ? `
          <button class="btn btn-sm btn-success" onclick="window.approveEvent(${event.id})">
            <i class="fas fa-check"></i> Approuver
          </button>
          <button class="btn btn-sm btn-warning" onclick="window.rejectEvent(${event.id})">
            <i class="fas fa-times"></i> Rejeter
          </button>
        `
            : ""
        }
        <button class="btn btn-sm btn-danger" onclick="window.deleteEvent(${event.id})">
          <i class="fas fa-trash"></i> Supprimer
        </button>
      </td>
    </tr>
  `,
    )
    .join("");
}

// Approuver un événement
window.approveEvent = async function (eventId) {
  const confirmed = await helpers.showConfirm(
    "Approuver l'événement",
    "Êtes-vous sûr de vouloir approuver cet événement ? Il sera visible publiquement.",
    "Approuver",
    "Annuler",
    "warning",
  );

  if (!confirmed) return;

  const token = storage.getToken();
  const result = await EventManager.approve(eventId, token);

  if (result.success) {
    helpers.showToast("Événement approuvé avec succès", "success");
    await loadEvents();
  } else {
    helpers.showToast(
      result.message || "Erreur lors de l'approbation",
      "error",
    );
  }
};

// Rejeter un événement
window.rejectEvent = async function (eventId) {
  const confirmed = await helpers.showConfirm(
    "Rejeter l'événement",
    "Êtes-vous sûr de vouloir rejeter cet événement ? Cette action est définitive.",
    "Rejeter",
    "Annuler",
    "warning",
  );

  if (!confirmed) return;

  const token = storage.getToken();
  const result = await EventManager.reject(eventId, token);

  if (result.success) {
    helpers.showToast("Événement rejeté avec succès", "success");
    await loadEvents();
  } else {
    helpers.showToast(result.message || "Erreur lors du rejet", "error");
  }
};

// Supprimer un événement
window.deleteEvent = async function (eventId) {
  const confirmed = await helpers.showConfirm(
    "Supprimer l'événement",
    "⚠️ Attention ! Êtes-vous sûr de vouloir supprimer définitivement cet événement ? Cette action est irréversible.",
    "Supprimer",
    "Annuler",
    "danger",
  );

  if (!confirmed) return;

  const token = storage.getToken();
  const result = await EventManager.adminDelete(eventId, token);

  if (result.success) {
    helpers.showToast("Événement supprimé avec succès", "success");
    await loadEvents();
  } else {
    helpers.showToast(
      result.message || "Erreur lors de la suppression",
      "error",
    );
  }
};

// Déconnexion
function logout() {
  storage.removeToken();
  window.location.href = "login.html";
}

// Initialisation
async function init() {
  await checkAuth();
  await loadEvents();

  // Event listener pour la déconnexion
  document.getElementById("logoutBtn").addEventListener("click", (e) => {
    e.preventDefault();
    logout();
  });
}

// Lancer l'initialisation
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
