// Vue Page d'événement : /event/:id
// Adresse propre à chaque événement : partageable, indexable, avec ses données structurées.

import EventManager from "../managers/EventManager.js";
import { helpers } from "../utils/helpers.js";
import { showEventDetail } from "../components/eventDetail.js";
import {
  setPageMetadata,
  setStructuredData,
  clearStructuredData,
} from "../utils/seo.js";

// Métadonnées par défaut (remplacées une fois l'événement chargé)
export const meta = {
  title: "Événement - MemoriaEventia",
  description: "Détails d'un événement historique sur MemoriaEventia",
};

// Crée un élément avec un texte (textContent : aucune donnée n'est injectée en HTML)
function createElement(tag, className, text = "") {
  const element = document.createElement(tag);
  if (className) element.className = className;
  if (text) element.textContent = text;
  return element;
}

// Ligne d'information avec icône (date, lieu, organisateur...)
function createInfoRow(iconClass, text) {
  const row = createElement("div", "d-flex align-items-start gap-2 mb-2");
  const icon = createElement("i", `bi ${iconClass} text-primary mt-1`);
  icon.setAttribute("aria-hidden", "true");
  row.append(icon, createElement("span", "", text));
  return row;
}

function renderNotFound(container) {
  container.replaceChildren();

  const wrapper = createElement("div", "container py-5 text-center");
  wrapper.style.maxWidth = "640px";
  wrapper.append(
    createElement("h1", "page-title mb-3", "Événement introuvable"),
    createElement(
      "p",
      "text-muted mb-4",
      "Cet événement n'existe pas ou n'est plus disponible.",
    ),
  );

  const link = createElement("a", "btn btn-primary", "Retour aux événements");
  link.href = "./";
  link.setAttribute("data-link", "");
  wrapper.appendChild(link);

  container.appendChild(wrapper);
  setPageMetadata({
    title: "Événement introuvable - MemoriaEventia",
    description: "Cet événement n'est plus disponible.",
    noindex: true,
  });
}

// Données structurées schema.org de l'événement
function buildStructuredData(event, pageUrl) {
  const price = Number(event.price) || 0;
  const time = (event.time || "").slice(0, 5);

  return {
    "@context": "https://schema.org",
    "@type": "Event",
    name: event.title,
    description: event.description,
    startDate: time ? `${event.date}T${time}` : event.date,
    eventStatus: "https://schema.org/EventScheduled",
    eventAttendanceMode: "https://schema.org/OfflineEventAttendanceMode",
    image: [new URL(event.image, document.baseURI).href],
    location: {
      "@type": "Place",
      name: event.city,
      address: {
        "@type": "PostalAddress",
        streetAddress: event.address,
        addressLocality: event.city,
        addressCountry: event.country,
      },
    },
    organizer: event.organizer_name
      ? { "@type": "Person", name: event.organizer_name }
      : undefined,
    offers: {
      "@type": "Offer",
      price: price.toFixed(2),
      priceCurrency: "EUR",
      availability: "https://schema.org/InStock",
      url: pageUrl,
    },
  };
}

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  container.replaceChildren(
    createElement("p", "text-muted text-center py-5", "Chargement de l'événement…"),
  );

  const eventId = Number(params?.id);
  if (!Number.isInteger(eventId) || eventId <= 0) {
    renderNotFound(container);
    return;
  }

  const result = await EventManager.getById(eventId);
  if (!result.success || !result.data) {
    renderNotFound(container);
    return;
  }

  const event = result.data;
  const pageUrl = window.location.origin + window.location.pathname;

  container.replaceChildren();

  const wrapper = createElement("article", "container py-4 py-md-5");
  wrapper.style.maxWidth = "900px";

  const image = createElement("img", "w-100 rounded mb-4 event-page-image");
  image.src = event.image;
  image.alt = event.title;
  image.style.aspectRatio = "16 / 9";
  image.style.objectFit = "cover";

  const title = createElement("h1", "page-title mb-3", event.title);

  const infos = createElement("div", "mb-4");
  infos.append(
    createInfoRow(
      "bi-calendar3",
      `${helpers.formatDate(event.date)} à ${helpers.formatTime(event.time)}`,
    ),
    createInfoRow(
      "bi-geo-alt",
      [event.address, event.city, event.country].filter(Boolean).join(", "),
    ),
    createInfoRow(
      "bi-tag",
      Number(event.price) > 0 ? helpers.formatEventPrice(event.price) : "Gratuit",
    ),
  );
  if (event.organizer_name) {
    infos.appendChild(
      createInfoRow("bi-person-badge", `Organisé par ${event.organizer_name}`),
    );
  }

  const description = createElement("p", "mb-4", event.description || "");
  description.style.whiteSpace = "pre-line";

  // Actions : réserver (ouvre le détail habituel), partager, retour
  const actions = createElement("div", "d-flex flex-wrap gap-2");

  const reserveButton = createElement("button", "btn btn-primary");
  reserveButton.type = "button";
  reserveButton.innerHTML = '<i class="bi bi-ticket-perforated me-2"></i>';
  reserveButton.append("Voir le détail et réserver");
  reserveButton.addEventListener("click", () => showEventDetail(event));

  const shareButton = createElement("button", "btn btn-outline-secondary");
  shareButton.type = "button";
  shareButton.innerHTML = '<i class="bi bi-link-45deg me-2"></i>';
  shareButton.append("Copier le lien");
  shareButton.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(pageUrl);
      helpers.showToast("Lien copié", "success");
    } catch (error) {
      helpers.showToast("Impossible de copier le lien", "error");
    }
  });

  const backLink = createElement("a", "btn btn-link", "Tous les événements");
  backLink.href = "./";
  backLink.setAttribute("data-link", "");

  actions.append(reserveButton, shareButton, backLink);
  wrapper.append(image, title, infos, description, actions);
  container.appendChild(wrapper);

  // Titre, description, image de partage et données structurées propres à cet événement
  const summary = (event.description || "").replace(/\s+/g, " ").trim();
  setPageMetadata({
    title: `${event.title} - MemoriaEventia`,
    description: summary.length > 155 ? `${summary.slice(0, 152)}...` : summary,
    image: event.image,
  });
  setStructuredData(buildStructuredData(event, pageUrl));
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  clearStructuredData();
}

// Export par défaut
export default { mount, unmount, meta };
