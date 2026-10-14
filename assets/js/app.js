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
import EventManager from "./managers/EventManager.js";

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
  "/contact": () => import("./views/contact.js"),
  // Retour de la page de paiement Stripe (la vue choisit le message selon l'URL)
  "/payment/success": () => import("./views/payment.js"),
  "/payment/cancel": () => import("./views/payment.js"),
  "/ticket/verify": () => import("./views/ticketVerify.js"),
  "/event/:id": () => import("./views/eventPage.js"),
};

// Instance du routeur
const router = new Router(routes, "#app");

// Export du routeur pour accès global
window.router = router;

// Fonction init
async function init() {
  // Vérifier l'authentification (valide le token auprès du serveur)
  if (auth.isLoggedIn()) {
    const session = await auth.validateSession();

    if (session.valid) {
      appState.set("user", session.user);
      appState.set("isAuthenticated", true);
    }
  }

  // Les composants persistants (header, footer, modals) et les favoris ne dépendent pas
  // les uns des autres : ils se chargent en parallèle plutôt qu'à la file.
  // Les favoris restent attendus avant le routeur : les cartes les lisent à l'affichage.
  const favoritesLoading = appState.get("isAuthenticated")
    ? loadUserFavorites()
    : Promise.resolve();

  await Promise.all([
    renderHeader(),
    renderFooter(),
    loadMobileNav(),
    renderLoginModal(),
    initReservationModal(),
    favoritesLoading,
  ]);

  // Initialiser le routeur
  router.init();

  // Écouter les changements d'état utilisateur
  appState.subscribe("user", handleUserChange);

  // Rafraîchir automatiquement les événements (approbation/rejet/suppression par un admin)
  startEventsPolling();
}

// Charger les favoris de l'utilisateur connecté (sans bloquer le démarrage)
async function loadUserFavorites() {
  const favoritesResult = await FavoriteManager.getByUser(auth.getToken());
  if (favoritesResult.success) {
    appState.set("favorites", favoritesResult.data || []);
  }
}

// Sonder périodiquement les événements pour refléter les actions de modération sans reload
function startEventsPolling() {
  // Dernière empreinte connue : la liste complète n'est rechargée que si elle a changé
  let lastFingerprint = null;

  setInterval(async () => {
    if (document.hidden) return; // ne pas solliciter le serveur si l'onglet n'est pas actif

    const fingerprintResult = await EventManager.getFingerprint();
    if (!fingerprintResult.success) return;

    // Rien n'a changé depuis la dernière sonde : on évite de recharger toute la liste
    const fingerprint = fingerprintResult.data.fingerprint;
    if (fingerprint === lastFingerprint) return;
    lastFingerprint = fingerprint;

    const result = await EventManager.getAll();
    if (result.success) {
      const newData = JSON.stringify(result.data);
      if (newData !== JSON.stringify(appState.get("events"))) {
        appState.set("events", result.data);
      }
    }
  }, 45000);
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
