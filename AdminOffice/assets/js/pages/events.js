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
    const count = pendingResult.data.length;
    document.getElementById("pendingCount").textContent = count;
    document.getElementById("pendingCountSidebar").textContent = count;
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

// Charger les modifications en attente
async function loadModifications() {
  const token = storage.getToken();
  const result = await EventManager.getPendingModifications(token);

  if (result.success) {
    renderModifications(result.data);
    const count = result.data.length;
    document.getElementById("modificationsCount").textContent = count;
    document.getElementById("modificationsCountSidebar").textContent = count;
  } else {
    helpers.showToast(
      result.message || "Erreur lors du chargement des modifications",
      "error",
    );
  }
}

// Rendre les modifications en attente
function renderModifications(modifications) {
  const tbody = document.getElementById("modificationsList");

  if (modifications.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="9" class="text-center">Aucune modification en attente</td></tr>';
    return;
  }

  tbody.innerHTML = modifications
    .map(
      (mod) => `
    <tr>
      <td>${mod.id}</td>
      <td><strong>${mod.event_title}</strong></td>
      <td>${mod.creator_firstname} ${mod.creator_lastname}</td>
      <td>${helpers.formatDate(mod.old_date)}</td>
      <td>${helpers.formatTime(mod.old_time)}</td>
      <td class="bg-light"><strong>${helpers.formatDate(mod.new_date)}</strong></td>
      <td class="bg-light"><strong>${helpers.formatTime(mod.new_time)}</strong></td>
      <td>${helpers.formatDate(mod.created_at)}</td>
      <td>
        <button class="btn btn-sm btn-success" onclick="window.approveModification(${mod.id})">
          <i class="fas fa-check"></i> Approuver
        </button>
        <button class="btn btn-sm btn-danger" onclick="window.rejectModification(${mod.id})">
          <i class="fas fa-times"></i> Rejeter
        </button>
      </td>
    </tr>
  `,
    )
    .join("");
}

// Approuver une modification
window.approveModification = async function (modificationId) {
  const confirmed = await helpers.showConfirm(
    "Approuver la modification",
    "Êtes-vous sûr de vouloir approuver cette modification ? La date et l'heure de l'événement seront mises à jour et tous les acheteurs de billets recevront un email.",
    "Approuver",
    "Annuler",
    "warning",
  );

  if (!confirmed) return;

  const token = storage.getToken();
  const result = await EventManager.approveModification(modificationId, token);

  if (result.success) {
    helpers.showToast("Modification approuvée avec succès", "success");
    await loadModifications();
    await loadEvents();
  } else {
    helpers.showToast(
      result.message || "Erreur lors de l'approbation",
      "error",
    );
  }
};

// Rejeter une modification
window.rejectModification = async function (modificationId) {
  const reason = await helpers.showPrompt(
    "Rejeter la modification",
    "Veuillez indiquer la raison du rejet :",
    "Entrez la raison...",
  );

  if (!reason) return;

  const token = storage.getToken();
  const result = await EventManager.rejectModification(
    modificationId,
    reason,
    token,
  );

  if (result.success) {
    helpers.showToast("Modification rejetée avec succès", "success");
    await loadModifications();
    await loadEvents();
  } else {
    helpers.showToast(result.message || "Erreur lors du rejet", "error");
  }
};

// Charger les suppressions en attente
async function loadDeletions() {
  const token = storage.getToken();
  const result = await EventManager.getPendingDeletions(token);

  if (result.success) {
    renderDeletions(result.data);
    const count = result.data.length;
    document.getElementById("deletionsCount").textContent = count;
    document.getElementById("deletionsCountSidebar").textContent = count;
  } else {
    helpers.showToast(
      result.message || "Erreur lors du chargement des suppressions",
      "error",
    );
  }
}

// Rendre les suppressions en attente
function renderDeletions(deletions) {
  const tbody = document.getElementById("deletionsList");

  if (deletions.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="8" class="text-center">Aucune demande de suppression</td></tr>';
    return;
  }

  tbody.innerHTML = deletions
    .map(
      (del) => `
    <tr>
      <td>${del.id}</td>
      <td><strong>${del.title}</strong></td>
      <td>${del.creator_firstname} ${del.creator_lastname}</td>
      <td>${helpers.formatDate(del.date)}</td>
      <td>
        <button class="btn btn-sm btn-outline-info" onclick="window.showDeletionMessage(${del.id}, \`${del.deletion_message.replace(/`/g, "\\`")}\`)">
          <i class="fas fa-eye"></i> Voir message
        </button>
      </td>
      <td><span class="badge badge-warning">${del.tickets_sold || 0}</span></td>
      <td>${helpers.formatDate(del.deletion_requested_at)}</td>
      <td>
        <button class="btn btn-sm btn-success" onclick="window.approveDeletion(${del.id}, '${del.title.replace(/'/g, "\\'")}')">
          <i class="fas fa-check"></i> Approuver
        </button>
        <button class="btn btn-sm btn-danger" onclick="window.rejectDeletion(${del.id})">
          <i class="fas fa-times"></i> Rejeter
        </button>
      </td>
    </tr>
  `,
    )
    .join("");
}

// Afficher le message de suppression
window.showDeletionMessage = function (eventId, message) {
  helpers.showAlert(
    "Message d'excuse et d'explication",
    message,
    "info",
    "Fermer",
  );
};

// Approuver une suppression
window.approveDeletion = async function (eventId, eventTitle) {
  const adminMessage = await helpers.showPrompt(
    "Approuver la suppression",
    `Êtes-vous sûr de vouloir approuver la suppression de l'événement "${eventTitle}" ?<br><br>Vous pouvez ajouter un message supplémentaire qui sera envoyé aux participants (optionnel) :`,
    "Message administrateur (optionnel)...",
  );

  if (adminMessage === null) return; // Annulé

  const confirmed = await helpers.showConfirm(
    "Confirmation finale",
    "L'événement sera supprimé et tous les acheteurs de billets recevront un email. Confirmer ?",
    "Confirmer",
    "Annuler",
    "danger",
  );

  if (!confirmed) return;

  const token = storage.getToken();
  const result = await EventManager.approveDeletion(
    eventId,
    adminMessage,
    token,
  );

  if (result.success) {
    helpers.showToast("Suppression approuvée avec succès", "success");
    await loadDeletions();
    await loadEvents();
  } else {
    helpers.showToast(
      result.message || "Erreur lors de l'approbation",
      "error",
    );
  }
};

// Rejeter une suppression
window.rejectDeletion = async function (eventId) {
  const reason = await helpers.showPrompt(
    "Rejeter la suppression",
    "Veuillez indiquer la raison du rejet :",
    "Entrez la raison...",
  );

  if (!reason) return;

  const token = storage.getToken();
  const result = await EventManager.rejectDeletion(eventId, reason, token);

  if (result.success) {
    helpers.showToast("Suppression rejetée avec succès", "success");
    await loadDeletions();
    await loadEvents();
  } else {
    helpers.showToast(result.message || "Erreur lors du rejet", "error");
  }
};

// Initialisation
async function init() {
  await checkAuth();
  await loadEvents();
  await loadModifications();
  await loadDeletions();

  // Activer l'onglet en fonction du hash dans l'URL
  const hash = window.location.hash;
  if (hash) {
    const tabTrigger = document.querySelector(`a[href="${hash}"]`);
    if (tabTrigger) {
      // Utiliser jQuery pour activer l'onglet (Bootstrap 4 + jQuery)
      $(tabTrigger).tab("show");
    }
  }

  // Mettre à jour le hash dans l'URL quand on change d'onglet
  $('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
    window.location.hash = e.target.getAttribute("href");
  });

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
