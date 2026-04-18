import UserManager from "../managers/UserManager.js";
import { storage, helpers } from "../utils/helpers.js";

let allUsers = [];

// Vérifier l'authentification
async function checkAuth() {
  const token = storage.getToken();
  if (!token) {
    window.location.href = "login.html";
    return;
  }
}

// Charger tous les utilisateurs
async function loadUsers() {
  const token = storage.getToken();
  const result = await UserManager.getAll(token);

  if (result.success) {
    allUsers = result.data;
    renderUsers();
  } else {
    helpers.showToast(result.message || "Erreur lors du chargement", "error");
  }
}

// Rendre les utilisateurs
function renderUsers() {
  const tbody = document.getElementById("usersList");

  if (allUsers.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="6" class="text-center">Aucun utilisateur</td></tr>';
    return;
  }

  tbody.innerHTML = allUsers
    .map(
      (user) => `
    <tr>
      <td>${user.id}</td>
      <td>${user.name}</td>
      <td>${user.email}</td>
      <td>
        ${user.is_admin ? '<span class="badge badge-danger"><i class="fas fa-shield-alt"></i> Admin</span> ' : ""}
        ${user.is_organizer ? '<span class="badge badge-primary"><i class="fas fa-calendar-plus"></i> Organisateur</span> ' : ""}
        ${user.is_moderator ? '<span class="badge badge-info"><i class="fas fa-user-shield"></i> Modérateur</span> ' : ""}
        ${!user.is_admin && !user.is_organizer && !user.is_moderator ? '<span class="badge badge-secondary">Utilisateur</span>' : ""}
      </td>
      <td>${helpers.formatDate(user.created_at)}</td>
      <td>
        <button class="btn btn-sm btn-primary" onclick="window.editUserRoles(${user.id})">
          <i class="fas fa-user-edit"></i> Modifier droits
        </button>
        <button class="btn btn-sm btn-danger" onclick="window.deleteUser(${user.id})">
          <i class="fas fa-trash"></i> Supprimer
        </button>
      </td>
    </tr>
  `,
    )
    .join("");
}

// Éditer les droits d'un utilisateur
window.editUserRoles = function (userId) {
  const user = allUsers.find((u) => u.id === userId);
  if (!user) return;

  // Remplir le modal
  document.getElementById("editUserId").value = user.id;
  document.getElementById("editUserName").textContent = user.name;
  document.getElementById("editUserEmail").textContent = user.email;

  document.getElementById("isAdmin").checked = user.is_admin;
  document.getElementById("isOrganizer").checked = user.is_organizer;
  document.getElementById("isModerator").checked = user.is_moderator;

  // Afficher le modal
  $("#editRolesModal").modal("show");
};

// Sauvegarder les droits
async function saveRoles() {
  const userId = parseInt(document.getElementById("editUserId").value);
  const roles = {
    isAdmin: document.getElementById("isAdmin").checked,
    isOrganizer: document.getElementById("isOrganizer").checked,
    isModerator: document.getElementById("isModerator").checked,
  };

  const token = storage.getToken();
  const result = await UserManager.updateRoles(userId, roles, token);

  if (result.success) {
    helpers.showToast("Droits mis à jour avec succès", "success");
    $("#editRolesModal").modal("hide");
    await loadUsers();
  } else {
    helpers.showToast(
      result.message || "Erreur lors de la mise à jour",
      "error",
    );
  }
}

// Supprimer un utilisateur
window.deleteUser = async function (userId) {
  if (!confirm("Êtes-vous sûr de vouloir supprimer cet utilisateur ?")) {
    return;
  }

  const token = storage.getToken();
  const result = await UserManager.delete(userId, token);

  if (result.success) {
    helpers.showToast("Utilisateur supprimé avec succès", "success");
    await loadUsers();
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
  await loadUsers();

  // Event listener pour la déconnexion
  document.getElementById("logoutBtn").addEventListener("click", (e) => {
    e.preventDefault();
    logout();
  });

  // Event listener pour sauvegarder les droits
  document.getElementById("saveRolesBtn").addEventListener("click", saveRoles);
}

// Lancer l'initialisation
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
