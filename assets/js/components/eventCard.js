// Composant Event Card

import FavoriteManager from "../managers/FavoriteManager.js";
import { auth } from "../utils/auth.js";
import { helpers } from "../utils/helpers.js";
import { appState } from "../store/appState.js";
import { loadTemplate } from "../utils/templateLoader.js";
import { requireLogin } from "./loginModal.js";

const templateObjects = {};

// Affiche des cartes grises animées pendant le chargement des événements
export function renderEventCardsSkeleton(containerId, count = 6) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = "";
  container.setAttribute("aria-busy", "true");

  for (let index = 0; index < count; index++) {
    const column = document.createElement("div");
    column.className = "col";
    column.setAttribute("aria-hidden", "true");

    const card = document.createElement("div");
    card.className = "card eventCard-skeleton h-100";

    const image = document.createElement("div");
    image.className = "skeleton skeleton-image";

    const body = document.createElement("div");
    body.className = "card-body";
    for (const widthClass of ["w-75", "w-100", "w-50"]) {
      const line = document.createElement("div");
      line.className = `skeleton skeleton-line ${widthClass}`;
      body.appendChild(line);
    }

    card.append(image, body);
    column.appendChild(card);
    container.appendChild(column);
  }
}

export async function renderEventCards(
  events,
  containerId,
  onEventClick,
  onResetFilters = null,
) {
  Object.assign(
    templateObjects,
    await loadTemplate("assets/components/eventCard.html"),
  );

  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = "";
  container.removeAttribute("aria-busy");

  if (events.length === 0) {
    container.innerHTML = `
      <div class="col-12">
        <div class="empty-state">
          <i class="bi bi-calendar-x fs-1 mb-3 d-block"></i>
          <p class="fs-5">Aucun événement trouvé</p>
        </div>
      </div>
    `;

    // Proposer de réinitialiser les filtres quand c'est eux qui vident la liste
    if (onResetFilters) {
      const resetButton = document.createElement("button");
      resetButton.type = "button";
      resetButton.className = "btn btn-outline-primary";
      resetButton.textContent = "Réinitialiser les filtres";
      resetButton.addEventListener("click", onResetFilters);
      container.querySelector(".empty-state").appendChild(resetButton);
    }
    return;
  }

  events.forEach((event) => {
    const clone = templateObjects["eventCardTemplate"].cloneNode(true);

    // Remplir les données
    const img = clone.querySelector(".eventCard-image");
    const category = clone.querySelector(".eventCard-category");
    const title = clone.querySelector(".eventCard-title");
    const description = clone.querySelector(".eventCard-description");
    const location = clone.querySelector(".eventCard-location");
    const date = clone.querySelector(".eventCard-date");
    const time = clone.querySelector(".eventCard-time");
    const price = clone.querySelector(".eventCard-price");
    const btnDetails = clone.querySelector(".eventCard-btn-details");
    const card = clone.querySelector(".eventCard");
    const btnFavorite = clone.querySelector(".btn-favorite");

    if (img) {
      img.src = event.image;
      img.alt = event.title;
    }
    if (category) category.textContent = event.category;
    if (title) title.textContent = event.title;
    if (description) description.textContent = event.description;
    if (location) location.textContent = `${event.city}, ${event.country}`;
    if (date) date.textContent = helpers.formatDate(event.date);
    if (time) time.textContent = helpers.formatTime(event.time);

    // Prix par place : "Gratuit" ou montant en euros
    if (price) {
      price.textContent = helpers.formatEventPrice(event.price);
    }

    // Configurer le bouton favoris
    if (btnFavorite) {
      btnFavorite.dataset.eventId = event.id;

      // Cœur plein si l'événement est déjà en favoris
      setFavoriteButtonState(btnFavorite, isEventFavorite(event.id));

      // Un clic bascule : ajout ou retrait selon l'état actuel
      btnFavorite.addEventListener("click", async (e) => {
        e.stopPropagation();
        await toggleFavorite(event.id, btnFavorite);
      });
    }

    // Bouton Voir détails
    if (btnDetails) {
      btnDetails.addEventListener("click", (e) => {
        e.stopPropagation();
        if (onEventClick) {
          onEventClick(event);
        }
      });
    }

    // Événement de clic sur la carte
    if (card) {
      card.addEventListener("click", (e) => {
        // Ignorer le clic si c'est sur un bouton
        if (
          e.target.closest(".btn-favorite") ||
          e.target.closest(".eventCard-btn-reserve") ||
          e.target.closest(".eventCard-btn-details")
        ) {
          return;
        }
        if (onEventClick) {
          onEventClick(event);
        }
      });
    }

    container.appendChild(clone);
  });
}

export function isEventFavorite(eventId) {
  const userFavorites = appState.get("favorites") || [];
  return userFavorites.some((fav) => fav.event_id == eventId);
}

// Cœur plein quand l'événement est en favoris, cœur vide sinon
export function setFavoriteButtonState(btnElement, isFavorite) {
  btnElement.classList.toggle("active", isFavorite);
  btnElement.setAttribute("aria-pressed", String(isFavorite));
  btnElement.setAttribute(
    "aria-label",
    isFavorite ? "Retirer des favoris" : "Ajouter aux favoris",
  );

  const icon = btnElement.querySelector("i");
  if (icon) {
    icon.classList.toggle("bi-heart-fill", isFavorite);
    icon.classList.toggle("bi-heart", !isFavorite);
  }
}

// Ajoute ou retire un événement des favoris selon son état actuel
export async function toggleFavorite(eventId, btnElement) {
  // Empêcher les clics multiples
  if (btnElement.disabled) {
    return;
  }

  // Si l'utilisateur n'est pas connecté : connexion puis ajout automatique
  if (!appState.get("isAuthenticated")) {
    helpers.showToast("Connectez-vous pour ajouter des favoris", "warning");
    requireLogin(() => toggleFavorite(eventId, btnElement));
    return;
  }

  const token = auth.getToken();
  const wasFavorite = isEventFavorite(eventId);

  btnElement.disabled = true;

  try {
    const result = wasFavorite
      ? await FavoriteManager.remove(eventId, token)
      : await FavoriteManager.add(eventId, token);

    if (result.success) {
      const userFavorites = appState.get("favorites") || [];
      const updatedFavorites = wasFavorite
        ? userFavorites.filter((fav) => fav.event_id != eventId)
        : [...userFavorites, { event_id: eventId }];
      appState.set("favorites", updatedFavorites);

      // Tous les cœurs de cet événement (carte de la liste, détail ouvert) restent synchronisés
      document
        .querySelectorAll(`.btn-favorite[data-event-id="${eventId}"]`)
        .forEach((heartButton) =>
          setFavoriteButtonState(heartButton, !wasFavorite),
        );
      setFavoriteButtonState(btnElement, !wasFavorite);
      helpers.showToast(
        wasFavorite ? "Retiré des favoris" : "Ajouté aux favoris",
        "success",
      );
    } else {
      helpers.showToast(result.message || "Erreur", "error");
    }
  } catch (error) {
    helpers.showToast("Erreur réseau", "error");
  } finally {
    btnElement.disabled = false;
  }
}
