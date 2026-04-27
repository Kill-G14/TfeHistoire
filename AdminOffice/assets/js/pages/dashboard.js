import EventManager from "../managers/EventManager.js";
import UserManager from "../managers/UserManager.js";
import { storage, helpers } from "../utils/helpers.js";

// Vérifier l'authentification
async function checkAuth() {
  const token = storage.getToken();
  if (!token) {
    window.location.href = "login.html";
    return;
  }
}

// Charger les statistiques
async function loadStats() {
  const token = storage.getToken();

  // Charger tous les événements
  const eventsResult = await EventManager.getAll(token);
  if (eventsResult.success) {
    const events = eventsResult.data;
    const totalEvents = events.length;
    const pendingEvents = events.filter((e) => e.is_pending).length;
    const approvedEvents = events.filter((e) => e.is_approved).length;

    document.getElementById("totalEvents").textContent = totalEvents;
    document.getElementById("pendingEvents").textContent = pendingEvents;
    document.getElementById("approvedEvents").textContent = approvedEvents;
    document.getElementById("pendingEventsCount").textContent = pendingEvents;
  }

  // Charger les utilisateurs
  const usersResult = await UserManager.getAll(token);
  if (usersResult.success) {
    const totalUsers = usersResult.data.length;
    document.getElementById("totalUsers").textContent = totalUsers;
  }
}

// Déconnexion
function logout() {
  storage.removeToken();
  window.location.href = "login.html";
}

// Gérer les clics sur les boîtes statistiques
function attachBoxListeners() {
  const clickableBoxes = document.querySelectorAll(".clickable-box");
  clickableBoxes.forEach((box) => {
    box.addEventListener("click", function () {
      const link = this.getAttribute("data-link");
      if (link) {
        window.location.href = link;
      }
    });
  });
}

// Initialisation
async function init() {
  await checkAuth();
  await loadStats();
  attachBoxListeners();

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
