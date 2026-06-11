// Point d'entrée principal de l'application SPA

// Imports
import { config } from "./config.js";
import { Router } from "./router.js";
import { renderHeader } from "./components/header.js";
import { renderFooter } from "./components/footer.js";
import { loadMobileNav } from "./components/mobileNav.js";
import { renderLoginModal } from "./components/loginModal.js";
import { initReservationModal } from "./components/reservationModal.js";
import { appState } from "./store/appState.js";
import { auth } from "./utils/auth.js";
import FavoriteManager from "./managers/FavoriteManager.js";

// Exposer la configuration globalement pour un accès facile
window.__APP_CONFIG__ = config;

// Définition des routes
const routes = {
  "/": () => import("./views/home.js"),
  "/calendar": () => import("./views/calendar.js"),
  "/create-event": () => import("./views/createEvent.js"),
  "/profile": () => import("./views/profile.js"),
  "/map": () => import("./views/map.js"),
  "/about": () => import("./views/about.js"),
  "/terms": () => import("./views/terms.js"),
  "/privacy": () => import("./views/privacy.js"),
  "/faq": () => import("./views/faq.js"),
};

// Instance du routeur
const router = new Router(routes, "#app");

// Export du routeur pour accès global
window.router = router;

// Fonction init
async function init() {
  // Vérifier l'authentification
  const isLoggedIn = auth.isLoggedIn();
  const user = auth.getUser();

  if (isLoggedIn && user) {
    appState.set("user", user);
    appState.set("isAuthenticated", true);

    // Charger les favoris de l'utilisateur
    const token = auth.getToken();
    const favoritesResult = await FavoriteManager.getByUser(token);
    if (favoritesResult.success) {
      appState.set("favorites", favoritesResult.data || []);
    }
  }

  // Rendre les composants persistants
  await renderHeader();
  await renderFooter();
  await loadMobileNav();
  await renderLoginModal();

  // Précharger la modal de réservation
  await initReservationModal();

  // Initialiser le routeur
  router.init();

  // Écouter les changements d'état utilisateur
  appState.subscribe("user", handleUserChange);
}

// Gérer les changements d'utilisateur
async function handleUserChange(user) {
  if (user) {
    appState.set("isAuthenticated", true);

    // Charger les favoris de l'utilisateur
    const token = auth.getToken();
    if (token) {
      const favoritesResult = await FavoriteManager.getByUser(token);
      if (favoritesResult.success) {
        appState.set("favorites", favoritesResult.data || []);
      }
    }
  } else {
    appState.set("isAuthenticated", false);
    appState.set("favorites", []);
  }
  // Mettre à jour le header
  renderHeader();
}

// Initialisation
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
