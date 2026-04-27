// Composant Event Card

import FavoriteManager from "../managers/FavoriteManager.js";
import { auth } from "../utils/auth.js";
import { helpers } from "../utils/helpers.js";
import { appState } from "../store/appState.js";

const templateObjects = {};

async function loadTemplate(path) {
  const response = await fetch(path);
  const htmlContent = await response.text();
  const parser = new DOMParser();
  const templateDoc = parser.parseFromString(htmlContent, "text/html");
  const templates = templateDoc.querySelectorAll("template");

  templates.forEach((template) => {
    const templateId = template.id;
    templateObjects[templateId] = template.content;
  });
}

export async function renderEventCards(events, containerId, onEventClick) {
  await loadTemplate("assets/components/eventCard.html");

  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = "";

  if (events.length === 0) {
    container.innerHTML = `
      <div class="col-12">
        <div class="empty-state">
          <i class="bi bi-calendar-x fs-1 mb-3 d-block"></i>
          <p class="fs-5">Aucun événement trouvé</p>
        </div>
      </div>
    `;
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
    if (date) date.textContent = event.date;
    if (time) time.textContent = event.time;

    // Afficher le prix
    if (price) {
      if (event.is_free) {
        price.textContent = "Gratuit";
      } else if (event.ticket_price) {
        price.textContent = `${parseFloat(event.ticket_price).toFixed(2)} €`;
      } else {
        price.textContent = "Gratuit";
      }
    }

    // Configurer le bouton favoris
    if (btnFavorite) {
      btnFavorite.dataset.eventId = event.id;

      // Vérifier si l'événement est déjà en favoris
      const userFavorites = appState.get("favorites") || [];
      const isFavorite = userFavorites.some((fav) => fav.event_id == event.id);

      if (isFavorite) {
        // Si déjà en favoris, cacher le bouton ou le désactiver
        btnFavorite.style.display = "none";
      } else {
        // Gérer le clic pour ajouter aux favoris
        btnFavorite.addEventListener("click", async (e) => {
          e.stopPropagation();
          await addToFavorites(event.id, btnFavorite);
        });
      }
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

// Fonction pour ajouter un événement aux favoris
async function addToFavorites(eventId, btnElement) {
  // Empêcher les clics multiples
  if (btnElement.disabled) {
    return;
  }

  const isAuthenticated = appState.get("isAuthenticated");

  // Si l'utilisateur n'est pas connecté, ouvrir la modale de connexion
  if (!isAuthenticated) {
    helpers.showToast(
      "Vous devez être connecté pour ajouter des favoris",
      "warning",
    );
    const loginModal = document.getElementById("loginModal");
    if (loginModal) {
      const modal = new bootstrap.Modal(loginModal);
      modal.show();
    }
    return;
  }

  const token = auth.getToken();

  // Désactiver le bouton pendant le traitement
  btnElement.disabled = true;
  btnElement.style.opacity = "0.6";
  btnElement.style.cursor = "not-allowed";

  try {
    const result = await FavoriteManager.add(eventId, token);

    if (result.success) {
      // Masquer le bouton une fois ajouté
      btnElement.style.display = "none";
      helpers.showToast("Ajouté aux favoris", "success");

      // Mettre à jour la liste des favoris dans le state
      const userFavorites = appState.get("favorites") || [];
      userFavorites.push({ event_id: eventId });
      appState.set("favorites", userFavorites);
    } else {
      helpers.showToast(result.message || "Erreur lors de l'ajout", "error");
      // Réactiver le bouton en cas d'erreur
      btnElement.disabled = false;
      btnElement.style.opacity = "1";
      btnElement.style.cursor = "pointer";
    }
  } catch (error) {
    helpers.showToast("Erreur réseau", "error");
    // Réactiver le bouton en cas d'erreur
    btnElement.disabled = false;
    btnElement.style.opacity = "1";
    btnElement.style.cursor = "pointer";
  }
}
