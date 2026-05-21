// Vue Map - Carte interactive des événements

// Imports
import EventManager from "../managers/EventManager.js";
import { helpers } from "../utils/helpers.js";

// Métadonnées de la vue
export const meta = {
  title: "Carte des événements - MemoriaEventia",
  description:
    "Visualisez les événements historiques sur une carte interactive de l'Europe",
};

// Variables globales de la vue
let map = null;
let userMarker = null;
let userPosition = null;
let events = [];
let eventMarkers = [];
let routeLayer = null;
let currentRoute = null;

// Template HTML
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

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Charger le template
  await loadTemplate("assets/templates/views/map.html");

  // Injecter le template
  const clone = templateObjects["mapView"].cloneNode(true);
  container.innerHTML = "";
  container.appendChild(clone);

  // Initialiser la carte
  await initMap();

  // Charger les événements
  await loadEvents();

  // Attacher les événements
  attachEventListeners();
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Nettoyer la carte
  if (map) {
    map.remove();
    map = null;
  }

  // Réinitialiser les variables
  userMarker = null;
  userPosition = null;
  events = [];
  eventMarkers = [];
  routeLayer = null;
  currentRoute = null;
}

// Initialiser la carte Leaflet
async function initMap() {
  // Position par défaut : centre de l'Europe (Bruxelles)
  const defaultLat = 50.8503;
  const defaultLng = 4.3517;
  const defaultZoom = 5;

  // Créer la carte
  map = L.map("map").setView([defaultLat, defaultLng], defaultZoom);

  // Ajouter les tuiles OpenStreetMap
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "© OpenStreetMap contributors",
  }).addTo(map);

  // Essayer de récupérer la position de l'utilisateur
  getUserLocation();
}

// Récupérer la position de l'utilisateur
function getUserLocation() {
  const statusElement = document.getElementById("locationStatus");

  if (!navigator.geolocation) {
    if (statusElement) {
      statusElement.textContent = "Géolocalisation non supportée";
      statusElement.className = "small text-danger";
    }
    return;
  }

  if (statusElement) {
    statusElement.textContent = "Localisation...";
    statusElement.className = "small text-primary";
  }

  navigator.geolocation.getCurrentPosition(
    (position) => {
      userPosition = {
        lat: position.coords.latitude,
        lng: position.coords.longitude,
      };

      // Centrer la carte sur l'utilisateur
      map.setView([userPosition.lat, userPosition.lng], 7);

      // Ajouter un marqueur pour l'utilisateur
      addUserMarker();

      // Recalculer les distances
      if (events.length > 0) {
        updateEventDistances();
        renderEventsList();
      }

      if (statusElement) {
        statusElement.textContent = "Position trouvée";
        statusElement.className = "small text-success";
      }
    },
    (error) => {
      if (statusElement) {
        statusElement.textContent = "Position non disponible";
        statusElement.className = "small text-warning";
      }
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0,
    },
  );
}

// Ajouter le marqueur de l'utilisateur
function addUserMarker() {
  if (!userPosition || !map) return;

  // Supprimer l'ancien marqueur s'il existe
  if (userMarker) {
    map.removeLayer(userMarker);
  }

  // Créer une icône personnalisée pour l'utilisateur
  const userIcon = L.divIcon({
    className: "user-marker",
    html: '<div style="background: #007bff; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
    iconSize: [20, 20],
    iconAnchor: [10, 10],
  });

  // Ajouter le nouveau marqueur
  userMarker = L.marker([userPosition.lat, userPosition.lng], {
    icon: userIcon,
  })
    .addTo(map)
    .bindPopup("<b>Votre position</b>");
}

// Charger les événements
async function loadEvents() {
  const result = await EventManager.getAll();

  if (result.success && result.data) {
    events = result.data;

    // Calculer les distances si l'utilisateur est localisé
    if (userPosition) {
      updateEventDistances();
    }

    // Ajouter les marqueurs sur la carte
    addEventMarkers();

    // Afficher la liste
    renderEventsList();
  } else {
    helpers.showToast(
      result.message || "Erreur lors du chargement des événements",
      "error",
    );
    renderEventsList();
  }
}

// Calculer la distance entre deux points (formule Haversine)
function calculateDistance(lat1, lng1, lat2, lng2) {
  const R = 6371; // Rayon de la Terre en km
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLng = ((lng2 - lng1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLng / 2) *
      Math.sin(dLng / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  const distance = R * c;
  return Math.round(distance);
}

// Mettre à jour les distances des événements
function updateEventDistances() {
  if (!userPosition) return;

  events.forEach((event) => {
    if (event.latitude && event.longitude) {
      event.distance = calculateDistance(
        userPosition.lat,
        userPosition.lng,
        parseFloat(event.latitude),
        parseFloat(event.longitude),
      );
    }
  });
}

// Ajouter les marqueurs d'événements
function addEventMarkers() {
  // Supprimer les anciens marqueurs
  eventMarkers.forEach((marker) => map.removeLayer(marker));
  eventMarkers = [];

  // Ajouter un marqueur pour chaque événement
  events.forEach((event) => {
    if (!event.latitude || !event.longitude) return;

    const marker = L.marker([
      parseFloat(event.latitude),
      parseFloat(event.longitude),
    ]).addTo(map);

    // Créer le contenu de la popup
    const popupContent = createEventPopup(event);
    marker.bindPopup(popupContent);

    // Stocker le marqueur avec l'ID de l'événement
    marker.eventId = event.id;
    eventMarkers.push(marker);

    // Événement au clic sur le marqueur
    marker.on("click", () => {
      highlightEvent(event.id);
    });
  });
}

// Créer le contenu de la popup d'un événement
function createEventPopup(event) {
  const distanceText =
    event.distance !== undefined
      ? `<p class="mb-2"><i class="bi bi-geo-alt me-1"></i><strong>${event.distance} km</strong> de vous</p>`
      : "";

  const routeButton =
    userPosition && event.latitude && event.longitude
      ? `<button class="btn btn-sm btn-primary w-100" onclick="window.showRoute(${event.id})">
         <i class="bi bi-signpost-2 me-1"></i>Voir l'itinéraire
       </button>`
      : "";

  return `
    <div style="min-width: 250px;">
      <h6 class="mb-2">${event.title}</h6>
      ${distanceText}
      <p class="mb-2 small text-muted">
        <i class="bi bi-calendar3 me-1"></i>${helpers.formatDate(event.date)}
      </p>
      <p class="mb-3 small">${event.description ? event.description.substring(0, 100) + "..." : ""}</p>
      <div class="d-grid gap-2">
        <a href="event/${event.id}" data-link class="btn btn-sm btn-outline-primary">
          <i class="bi bi-info-circle me-1"></i>Détails
        </a>
        ${routeButton}
      </div>
    </div>
  `;
}

// Filtrer les événements par distance
function getFilteredEvents() {
  const maxDistance = parseInt(
    document.getElementById("distanceFilter")?.value || 500,
  );
  const sortBy = document.getElementById("sortBy")?.value || "distance";

  let filtered = events.filter((event) => {
    // Si pas de position utilisateur, afficher tous les événements
    if (!userPosition || event.distance === undefined) return true;
    return event.distance <= maxDistance;
  });

  // Trier les événements
  filtered.sort((a, b) => {
    if (sortBy === "distance") {
      if (a.distance === undefined) return 1;
      if (b.distance === undefined) return -1;
      return a.distance - b.distance;
    } else if (sortBy === "name") {
      return a.title.localeCompare(b.title);
    } else if (sortBy === "date") {
      return new Date(a.date) - new Date(b.date);
    }
    return 0;
  });

  return filtered;
}

// Afficher la liste des événements
function renderEventsList() {
  const container = document.getElementById("eventsList");
  if (!container) return;

  const filteredEvents = getFilteredEvents();

  if (filteredEvents.length === 0) {
    container.innerHTML = `
      <div class="text-center py-4">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="text-muted mt-3">Aucun événement trouvé dans cette zone</p>
        <button class="btn btn-sm btn-primary" id="resetFilter">Réinitialiser les filtres</button>
      </div>
    `;
    return;
  }

  container.innerHTML = filteredEvents
    .map(
      (event) => `
    <div class="card mb-3 event-card" data-event-id="${event.id}">
      <div class="card-body">
        <h6 class="card-title mb-2">${event.title}</h6>
        ${
          event.distance !== undefined
            ? `<p class="mb-2 small text-primary">
               <i class="bi bi-geo-alt-fill me-1"></i>${event.distance} km
             </p>`
            : ""
        }
        <p class="mb-2 small text-muted">
          <i class="bi bi-calendar3 me-1"></i>${helpers.formatDate(event.date)}
        </p>
        <p class="mb-3 small text-muted">${event.description ? event.description.substring(0, 80) + "..." : ""}</p>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-primary flex-grow-1 zoom-event" data-event-id="${event.id}">
            <i class="bi bi-zoom-in me-1"></i>Sur la carte
          </button>
          ${
            userPosition && event.latitude && event.longitude
              ? `<button class="btn btn-sm btn-primary flex-grow-1 show-route" data-event-id="${event.id}">
                 <i class="bi bi-signpost-2 me-1"></i>Itinéraire
               </button>`
              : ""
          }
        </div>
      </div>
    </div>
  `,
    )
    .join("");
}

// Mettre en évidence un événement
function highlightEvent(eventId) {
  // Retirer la classe active de toutes les cartes
  document.querySelectorAll(".event-card").forEach((card) => {
    card.classList.remove("border-primary", "shadow");
  });

  // Ajouter la classe active à la carte sélectionnée
  const card = document.querySelector(
    `.event-card[data-event-id="${eventId}"]`,
  );
  if (card) {
    card.classList.add("border-primary", "shadow");
    card.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }
}

// Zoomer sur un événement
function zoomToEvent(eventId) {
  const event = events.find((e) => e.id === eventId);
  if (!event || !event.latitude || !event.longitude) return;

  map.setView([parseFloat(event.latitude), parseFloat(event.longitude)], 12);

  // Ouvrir la popup du marqueur
  const marker = eventMarkers.find((m) => m.eventId === eventId);
  if (marker) {
    marker.openPopup();
  }

  highlightEvent(eventId);
}

// Afficher l'itinéraire vers un événement
async function showRoute(eventId) {
  if (!userPosition) {
    helpers.showToast("Position utilisateur non disponible", "error");
    return;
  }

  const event = events.find((e) => e.id === eventId);
  if (!event || !event.latitude || !event.longitude) {
    helpers.showToast("Position de l'événement non disponible", "error");
    return;
  }

  // Supprimer l'ancien itinéraire
  if (routeLayer) {
    map.removeLayer(routeLayer);
    routeLayer = null;
  }

  // Cacher les options GPS
  const gpsOptions = document.getElementById("gpsOptions");
  if (gpsOptions) {
    gpsOptions.classList.add("d-none");
  }

  // Afficher un message de chargement
  helpers.showToast("Calcul de l'itinéraire...", "info");

  try {
    // Appeler le backend qui gérera l'appel à OpenRouteService
    const apiUrl = window.__APP_CONFIG__?.API_URL || 'https://memoriaeventia.com/BackEnd/Api';
    const response = await fetch(
      `${apiUrl}/routeApi.php`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "getRoute",
          startLat: userPosition.lat,
          startLng: userPosition.lng,
          endLat: parseFloat(event.latitude),
          endLng: parseFloat(event.longitude),
        }),
      },
    );

    const result = await response.json();

    if (
      result.success &&
      result.data &&
      result.data.features &&
      result.data.features.length > 0
    ) {
      const route = result.data.features[0];
      const coordinates = route.geometry.coordinates;

      // Convertir les coordonnées [lng, lat] en [lat, lng] pour Leaflet
      const latLngs = coordinates.map((coord) => [coord[1], coord[0]]);

      // Tracer l'itinéraire sur la carte
      routeLayer = L.polyline(latLngs, {
        color: "#007bff",
        weight: 4,
        opacity: 0.7,
      }).addTo(map);

      // Ajuster la vue pour afficher tout l'itinéraire
      map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });

      // Récupérer les infos du trajet
      const distance = (route.properties.summary.distance / 1000).toFixed(1);
      const durationMinutes = Math.round(
        route.properties.summary.duration / 60,
      );
      const hours = Math.floor(durationMinutes / 60);
      const minutes = durationMinutes % 60;
      const durationText =
        hours > 0
          ? `${hours}h${minutes > 0 ? minutes.toString().padStart(2, "0") : ""}`
          : `${minutes} min`;

      helpers.showToast(
        `Itinéraire calculé : ${distance} km, environ ${durationText}`,
        "success",
      );

      highlightEvent(eventId);

      // Afficher les options GPS
      displayGpsOptions(event, distance, durationText);
    } else {
      helpers.showToast(
        result.message || "Impossible de calculer l'itinéraire",
        "error",
      );
    }
  } catch (error) {
    helpers.showToast("Erreur lors du calcul de l'itinéraire", "error");
  }
}

// Afficher les options GPS
function displayGpsOptions(event, distance, duration) {
  const gpsOptions = document.getElementById("gpsOptions");

  if (!gpsOptions) return;

  // Afficher la carte
  gpsOptions.classList.remove("d-none");

  // Déterminer le nom de destination
  const destination = event.city || event.title;

  // Mise à jour des infos de l'itinéraire
  const routeInfo = document.getElementById("routeInfo");
  if (routeInfo) {
    routeInfo.textContent = `Vers ${destination} - ${distance} km, ~${duration}`;
  }

  // Position de destination
  const destLat = parseFloat(event.latitude);
  const destLng = parseFloat(event.longitude);

  // Générer les liens GPS
  // Google Maps
  const googleMapsLink = document.getElementById("openGoogleMaps");
  if (googleMapsLink) {
    googleMapsLink.href = `https://www.google.com/maps/dir/?api=1&origin=${userPosition.lat},${userPosition.lng}&destination=${destLat},${destLng}&travelmode=driving`;
  }

  // Waze
  const wazeLink = document.getElementById("openWaze");
  if (wazeLink) {
    wazeLink.href = `https://www.waze.com/ul?ll=${destLat},${destLng}&navigate=yes`;
  }

  // Apple Maps
  const appleMapsLink = document.getElementById("openAppleMaps");
  if (appleMapsLink) {
    appleMapsLink.href = `http://maps.apple.com/?saddr=${userPosition.lat},${userPosition.lng}&daddr=${destLat},${destLng}&dirflg=d`;
  }
}

// Attacher les événements
function attachEventListeners() {
  // Filtre de distance
  const distanceFilter = document.getElementById("distanceFilter");
  const distanceValue = document.getElementById("distanceValue");

  if (distanceFilter && distanceValue) {
    distanceFilter.addEventListener("input", (e) => {
      distanceValue.textContent = e.target.value;
      renderEventsList();
    });
  }

  // Tri
  const sortBy = document.getElementById("sortBy");
  if (sortBy) {
    sortBy.addEventListener("change", () => {
      renderEventsList();
    });
  }

  // Bouton centrer sur utilisateur
  const centerBtn = document.getElementById("centerOnUser");
  if (centerBtn) {
    centerBtn.addEventListener("click", () => {
      if (userPosition) {
        map.setView([userPosition.lat, userPosition.lng], 7);
      } else {
        getUserLocation();
      }
    });
  }

  // Event delegation pour les boutons de la liste
  const eventsList = document.getElementById("eventsList");
  if (eventsList) {
    eventsList.addEventListener("click", (e) => {
      // Bouton zoom
      if (e.target.closest(".zoom-event")) {
        const eventId = parseInt(
          e.target.closest(".zoom-event").dataset.eventId,
        );
        zoomToEvent(eventId);
      }

      // Bouton itinéraire
      if (e.target.closest(".show-route")) {
        const eventId = parseInt(
          e.target.closest(".show-route").dataset.eventId,
        );
        showRoute(eventId);
      }

      // Bouton reset
      if (e.target.closest("#resetFilter")) {
        document.getElementById("distanceFilter").value = 2000;
        document.getElementById("distanceValue").textContent = "2000";
        renderEventsList();
      }
    });
  }

  // Rendre showRoute accessible globalement pour les popups
  window.showRoute = showRoute;
}

// Export par défaut
export default { mount, unmount, meta };
