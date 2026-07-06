import ContactMessageManager from "../managers/ContactMessageManager.js";
import { storage, helpers } from "../utils/helpers.js";

let allMessages = [];

// Vérifier l'authentification
async function checkAuth() {
  const token = storage.getToken();
  if (!token) {
    window.location.href = "login.html";
    return;
  }
}

// Charger tous les messages de contact
async function loadMessages() {
  const token = storage.getToken();
  const result = await ContactMessageManager.getAll(token);

  if (result.success) {
    allMessages = result.data;
    renderMessages();
  } else {
    helpers.showToast(result.message || "Erreur lors du chargement", "error");
  }
}

// Rendre les messages
function renderMessages() {
  const tbody = document.getElementById("messagesList");

  if (allMessages.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="6" class="text-center">Aucun message</td></tr>';
    return;
  }

  tbody.innerHTML = allMessages
    .map(
      (message) => `
    <tr>
      <td>${message.name}</td>
      <td>${message.email}</td>
      <td>${message.subject}</td>
      <td>${helpers.formatDateTime(message.created_at)}</td>
      <td>
        ${
          message.is_treated
            ? '<span class="badge badge-success"><i class="fas fa-check"></i> Traité</span>'
            : '<span class="badge badge-warning"><i class="fas fa-clock"></i> Non traité</span>'
        }
      </td>
      <td>
        <button class="btn btn-sm btn-info" onclick="window.viewMessageDetail(${message.id})">
          <i class="fas fa-eye"></i> Voir détail
        </button>
      </td>
    </tr>
  `,
    )
    .join("");
}

// Afficher le détail d'un message
window.viewMessageDetail = function (messageId) {
  const message = allMessages.find((m) => m.id === messageId);
  if (!message) return;

  document.getElementById("detailMessageId").value = message.id;
  document.getElementById("detailName").textContent = message.name;
  document.getElementById("detailEmail").textContent = message.email;
  document.getElementById("detailSubject").textContent = message.subject;
  document.getElementById("detailDate").textContent = helpers.formatDateTime(
    message.created_at,
  );
  document.getElementById("detailMessage").textContent = message.message;

  const treatedBtn = document.getElementById("markAsTreatedBtn");
  treatedBtn.disabled = message.is_treated;
  treatedBtn.innerHTML = message.is_treated
    ? '<i class="fas fa-check"></i> Déjà traité'
    : '<i class="fas fa-check"></i> Marquer comme traité';

  $("#messageDetailModal").modal("show");
};

// Marquer le message affiché comme traité
async function markAsTreated() {
  const messageId = parseInt(document.getElementById("detailMessageId").value);

  const token = storage.getToken();
  const result = await ContactMessageManager.markAsTreated(messageId, token);

  if (result.success) {
    helpers.showToast("Message marqué comme traité", "success");
    $("#messageDetailModal").modal("hide");
    await loadMessages();
  } else {
    helpers.showToast(
      result.message || "Erreur lors de la mise à jour",
      "error",
    );
  }
}

// Déconnexion
function logout() {
  storage.removeToken();
  window.location.href = "login.html";
}

// Initialisation
async function init() {
  await checkAuth();
  await loadMessages();

  // Event listener pour la déconnexion
  document.getElementById("logoutBtn").addEventListener("click", (e) => {
    e.preventDefault();
    logout();
  });

  // Event listener pour marquer comme traité
  document
    .getElementById("markAsTreatedBtn")
    .addEventListener("click", markAsTreated);
}

// Lancer l'initialisation
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
