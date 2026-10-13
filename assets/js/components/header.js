// Composant Header

import { auth } from "../utils/auth.js";
import { appState } from "../store/appState.js";
import { loadTemplate } from "../utils/templateLoader.js";
import { config } from "../config.js";

const templateObjects = {};

export async function renderHeader() {
  Object.assign(
    templateObjects,
    await loadTemplate("assets/components/header.html"),
  );

  const headerElement = document.getElementById("header");
  if (!headerElement) return;

  const clone = templateObjects["headerTemplate"].cloneNode(true);
  headerElement.innerHTML = "";
  headerElement.appendChild(clone);

  // Marquer le lien actif selon l'URL
  updateActiveNav();

  // Rendre les actions du header
  renderHeaderActions();
}

export function updateActiveNav() {
  // On retire BASE_PATH pour comparer le même chemin en local (/tfeHistoire/) et en production (/)
  const basePath = config.BASE_PATH.replace(/\/$/, "");
  let currentPath = window.location.pathname;
  if (basePath && currentPath.startsWith(basePath)) {
    currentPath = currentPath.slice(basePath.length);
  }
  if (currentPath.length > 1 && currentPath.endsWith("/")) {
    currentPath = currentPath.slice(0, -1);
  }
  const navHome = document.getElementById("navHome");
  const navMap = document.getElementById("navMap");

  if (navHome) navHome.classList.remove("active");
  if (navMap) navMap.classList.remove("active");

  if (
    (currentPath === "/" || currentPath === "" || currentPath === "/index.html") &&
    navHome
  ) {
    navHome.classList.add("active");
  } else if (currentPath === "/map" && navMap) {
    navMap.classList.add("active");
  }
}

function renderHeaderActions() {
  const headerActions = document.getElementById("headerActions");
  if (!headerActions) return;

  headerActions.innerHTML = "";

  const isLoggedIn = appState.get("isAuthenticated");
  const templateKey = isLoggedIn
    ? "headerActionsLoggedIn"
    : "headerActionsLoggedOut";
  const clone = templateObjects[templateKey].cloneNode(true);

  headerActions.appendChild(clone);

  // Attacher les événements
  if (isLoggedIn) {
    const btnProfile = document.getElementById("btnProfile");
    const btnLogout = document.getElementById("btnLogout");

    if (btnProfile) {
      btnProfile.addEventListener("click", () => {
        window.router.navigate("/profile");
      });
    }

    if (btnLogout) {
      btnLogout.addEventListener("click", async () => {
        await auth.logout();
        appState.set("user", null);
        appState.set("isAuthenticated", false);
        window.router.navigate("./");
      });
    }
  } else {
    const btnLogin = document.getElementById("btnLogin");
    if (btnLogin) {
      btnLogin.addEventListener("click", () => {
        // Déclencher l'ouverture du modal de connexion
        const event = new CustomEvent("openLoginModal");
        window.dispatchEvent(event);
      });
    }
  }
}
