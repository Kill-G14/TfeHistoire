// Vue Calendar - Calendrier des événements
// Calendrier écrit à la main (aucune bibliothèque) : grille mensuelle, vue agenda,
// filtre par catégorie, fenêtre du jour et export .ics.

import EventManager from "../managers/EventManager.js";
import { helpers } from "../utils/helpers.js";
import { showEventDetail } from "../components/eventDetail.js";
import { appState } from "../store/appState.js";
import { loadTemplate } from "../utils/templateLoader.js";
import { downloadEventIcs } from "../utils/icsExport.js";

// Métadonnées de la vue
export const meta = {
  title: "Calendrier des Événements - MemoriaEventia",
  description: "Découvrez tous les événements dans un calendrier interactif",
};

const MONTH_NAMES = [
  "Janvier",
  "Février",
  "Mars",
  "Avril",
  "Mai",
  "Juin",
  "Juillet",
  "Août",
  "Septembre",
  "Octobre",
  "Novembre",
  "Décembre",
];

// Une couleur par catégorie (classes définies dans custom.css)
const CATEGORY_CLASSES = {
  "Festival Médiéval": "cat-medieval",
  Carnaval: "cat-carnaval",
  "Reconstitution Historique": "cat-reconstitution",
  "Fête Traditionnelle": "cat-tradition",
  "Célébration Religieuse": "cat-religieuse",
  Autre: "cat-autre",
};
const CATEGORY_NAMES = Object.keys(CATEGORY_CLASSES);

// Nombre maximum de pastilles affichées dans une case du mois
const MAX_PILLS_PER_DAY = 2;

// Template HTML
const templateObjects = {};

// État de la vue
let viewYear = 0;
let viewMonth = 0;
let viewMode = "grid"; // "grid" (mois) ou "agenda"
let activeCategories = new Set(CATEGORY_NAMES);
let allEvents = [];
let unsubscribe = null;

// Limites de navigation : 6 mois en arrière, 1 an en avant
let minMonthIndex = 0;
let maxMonthIndex = 0;

let keyHandler = null;

// ============================================
// Outils de dates
// ============================================

function pad(value) {
  return String(value).padStart(2, "0");
}

// Clé AAAA-MM-JJ à partir de la date LOCALE (toISOString passerait en UTC et décalerait le jour)
function toDateKey(date) {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

// Clé AAAA-MM-JJ d'un événement : la date est déjà au format texte, on ne la convertit pas
function getEventDateKey(event) {
  return String(event.date || "").slice(0, 10);
}

// Position d'un mois dans le temps (permet de comparer des mois simplement)
function toMonthIndex(year, month) {
  return year * 12 + month;
}

function getCategoryClass(category) {
  return CATEGORY_CLASSES[category] || "cat-autre";
}

function formatEventTime(event) {
  return event.time ? helpers.formatTime(event.time) : "";
}

// Crée un élément avec classe et texte (textContent : aucune donnée n'est injectée en HTML)
function createElement(tag, className = "", text = "") {
  const element = document.createElement(tag);
  if (className) element.className = className;
  if (text) element.textContent = text;
  return element;
}

// ============================================
// Cycle de vie de la vue
// ============================================

export async function mount(container, params) {
  Object.assign(
    templateObjects,
    await loadTemplate("assets/templates/views/calendar.html"),
  );

  if (!templateObjects["calendarView"]) {
    helpers.showToast("Erreur de chargement de la page", "error");
    return;
  }

  const clone = templateObjects["calendarView"].cloneNode(true);
  container.innerHTML = "";
  container.appendChild(clone);

  // La fenêtre du jour est placée dans le body (une seule fois)
  if (!document.getElementById("dayEventsModalElement")) {
    const modalClone = templateObjects["dayEventsModal"].cloneNode(true);
    document.body.appendChild(modalClone);
  }

  // Limites de navigation
  const today = new Date();
  minMonthIndex = toMonthIndex(today.getFullYear(), today.getMonth()) - 6;
  maxMonthIndex = toMonthIndex(today.getFullYear(), today.getMonth()) + 12;

  // Mois affiché : celui de l'adresse (?month=AAAA-MM) ou le mois courant
  const requested = readMonthFromUrl();
  viewYear = requested ? requested.year : today.getFullYear();
  viewMonth = requested ? requested.month : today.getMonth();

  // Sur téléphone, la liste est plus lisible qu'une grille de 7 colonnes
  viewMode = window.matchMedia("(max-width: 767.98px)").matches ? "agenda" : "grid";
  activeCategories = new Set(CATEGORY_NAMES);

  // Mise à jour automatique après modération admin
  unsubscribe = appState.subscribe("events", handleEventsChange);

  attachCalendarEvents();
  await loadEvents();
  renderCalendar();
}

export async function unmount() {
  if (unsubscribe) {
    unsubscribe();
    unsubscribe = null;
  }

  if (keyHandler) {
    document.removeEventListener("keydown", keyHandler);
    keyHandler = null;
  }

  const modal = document.getElementById("dayEventsModalElement");
  if (modal) {
    modal.remove();
  }
}

// ============================================
// Adresse (?month=AAAA-MM)
// ============================================

function readMonthFromUrl() {
  const value = new URLSearchParams(window.location.search).get("month");
  const match = /^(\d{4})-(\d{2})$/.exec(value || "");
  if (!match) return null;

  const year = Number(match[1]);
  const month = Number(match[2]) - 1;
  if (month < 0 || month > 11) return null;

  const index = toMonthIndex(year, month);
  if (index < minMonthIndex || index > maxMonthIndex) return null;

  return { year, month };
}

// Met à jour l'adresse sans recharger la vue (le lien du mois reste partageable)
function writeMonthToUrl() {
  const today = new Date();
  const isCurrentMonth =
    viewYear === today.getFullYear() && viewMonth === today.getMonth();

  const query = isCurrentMonth ? "" : `?month=${viewYear}-${pad(viewMonth + 1)}`;
  window.history.replaceState(window.history.state, "", window.location.pathname + query);
}

// ============================================
// Données
// ============================================

function handleEventsChange(events) {
  allEvents = events;
  renderCalendar();
}

async function loadEvents() {
  const result = await EventManager.getAll();

  if (result.success) {
    allEvents = result.data || [];
  } else {
    helpers.showToast("Erreur de chargement des événements", "error");
    allEvents = [];
  }
}

// Événements du mois affiché, avant filtre par catégorie
function getMonthEvents() {
  const prefix = `${viewYear}-${pad(viewMonth + 1)}-`;
  return allEvents.filter((event) => getEventDateKey(event).startsWith(prefix));
}

function isEventVisible(event) {
  const category = CATEGORY_CLASSES[event.category] ? event.category : "Autre";
  return activeCategories.has(category);
}

// Événements visibles regroupés par jour (clé AAAA-MM-JJ), triés par heure
function groupVisibleEventsByDay(events) {
  const groups = new Map();

  events.filter(isEventVisible).forEach((event) => {
    const key = getEventDateKey(event);
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key).push(event);
  });

  groups.forEach((dayEvents) =>
    dayEvents.sort((a, b) => String(a.time || "").localeCompare(String(b.time || ""))),
  );

  return groups;
}

// ============================================
// Rendu
// ============================================

function renderCalendar() {
  const monthEvents = getMonthEvents();
  const eventsByDay = groupVisibleEventsByDay(monthEvents);

  renderToolbar();
  renderLegend(monthEvents);
  renderSummary(monthEvents, eventsByDay);

  const gridView = document.getElementById("calendarGridView");
  const agendaView = document.getElementById("calendarAgendaView");
  if (!gridView || !agendaView) return;

  if (viewMode === "grid") {
    gridView.classList.remove("d-none");
    agendaView.classList.add("d-none");
    renderGrid(eventsByDay);
  } else {
    agendaView.classList.remove("d-none");
    gridView.classList.add("d-none");
    renderAgenda(eventsByDay);
  }

  retriggerFade(viewMode === "grid" ? gridView : agendaView);
}

// Relance la petite animation d'apparition à chaque changement de mois
function retriggerFade(element) {
  element.classList.remove("calendar-fade");
  // Lecture forcée : sans elle le navigateur fusionne les deux changements de classe
  void element.offsetWidth;
  element.classList.add("calendar-fade");
}

function renderToolbar() {
  const title = document.getElementById("currentMonthYear");
  if (title) {
    title.textContent = `${MONTH_NAMES[viewMonth]} ${viewYear}`;
  }

  const index = toMonthIndex(viewYear, viewMonth);
  const prevBtn = document.getElementById("prevMonth");
  const nextBtn = document.getElementById("nextMonth");
  if (prevBtn) prevBtn.disabled = index <= minMonthIndex;
  if (nextBtn) nextBtn.disabled = index >= maxMonthIndex;

  const gridBtn = document.getElementById("viewGridBtn");
  const agendaBtn = document.getElementById("viewAgendaBtn");
  if (gridBtn && agendaBtn) {
    gridBtn.classList.toggle("active", viewMode === "grid");
    agendaBtn.classList.toggle("active", viewMode === "agenda");
    gridBtn.setAttribute("aria-pressed", String(viewMode === "grid"));
    agendaBtn.setAttribute("aria-pressed", String(viewMode === "agenda"));
  }
}

// Légende : un bouton par catégorie, qui sert aussi de filtre
function renderLegend(monthEvents) {
  const legend = document.getElementById("calendarLegend");
  if (!legend) return;

  const countByCategory = {};
  monthEvents.forEach((event) => {
    const category = CATEGORY_CLASSES[event.category] ? event.category : "Autre";
    countByCategory[category] = (countByCategory[category] || 0) + 1;
  });

  legend.replaceChildren();

  CATEGORY_NAMES.forEach((category) => {
    const isActive = activeCategories.has(category);

    const chip = createElement(
      "button",
      `calendar-chip ${getCategoryClass(category)}${isActive ? "" : " is-off"}`,
    );
    chip.type = "button";
    chip.dataset.category = category;
    chip.setAttribute("aria-pressed", String(isActive));

    const dot = createElement("span", "calendar-chip-dot");
    dot.setAttribute("aria-hidden", "true");

    const count = countByCategory[category] || 0;
    const label = createElement("span", "", `${category} (${count})`);

    chip.append(dot, label);
    legend.appendChild(chip);
  });
}

// Résumé du mois et accès au prochain événement
function renderSummary(monthEvents, eventsByDay) {
  const summary = document.getElementById("calendarSummary");
  const nextButton = document.getElementById("nextEventBtn");

  const visibleCount = [...eventsByDay.values()].reduce(
    (total, dayEvents) => total + dayEvents.length,
    0,
  );

  if (summary) {
    const monthLabel = MONTH_NAMES[viewMonth].toLowerCase();
    if (visibleCount === 0) {
      summary.textContent = `Aucun événement en ${monthLabel} ${viewYear}.`;
    } else {
      const filtered = activeCategories.size < CATEGORY_NAMES.length;
      summary.textContent =
        `${visibleCount} événement${visibleCount > 1 ? "s" : ""} en ${monthLabel} ${viewYear}` +
        (filtered ? " (filtre actif)" : "") +
        ".";
    }
  }

  if (!nextButton) return;

  // Prochain événement à venir (tous mois confondus, catégories filtrées)
  const todayKey = toDateKey(new Date());
  const upcoming = allEvents
    .filter((event) => isEventVisible(event) && getEventDateKey(event) >= todayKey)
    .sort((a, b) =>
      `${getEventDateKey(a)} ${a.time || ""}`.localeCompare(
        `${getEventDateKey(b)} ${b.time || ""}`,
      ),
    )[0];

  if (!upcoming) {
    nextButton.classList.add("d-none");
    return;
  }

  nextButton.classList.remove("d-none");
  nextButton.textContent = `Prochain : ${upcoming.title} (${helpers.formatDate(upcoming.date)})`;
  nextButton.dataset.dateKey = getEventDateKey(upcoming);
}

// ---------- Vue mensuelle ----------

function renderGrid(eventsByDay) {
  const calendarDays = document.getElementById("calendarDays");
  if (!calendarDays) return;

  calendarDays.replaceChildren();

  const firstOfMonth = new Date(viewYear, viewMonth, 1);
  const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

  // Décalage du premier jour avec une semaine qui commence le lundi
  const offset = (firstOfMonth.getDay() + 6) % 7;
  const totalCells = Math.ceil((offset + daysInMonth) / 7) * 7;

  const todayKey = toDateKey(new Date());

  for (let cell = 0; cell < totalCells; cell++) {
    // Les jours des mois voisins complètent les semaines (affichés en gris clair)
    const date = new Date(viewYear, viewMonth, 1 - offset + cell);
    const isOutside = date.getMonth() !== viewMonth;
    const key = toDateKey(date);
    const dayEvents = isOutside ? [] : eventsByDay.get(key) || [];

    calendarDays.appendChild(createDayCell(date, key, todayKey, isOutside, dayEvents));
  }
}

function createDayCell(date, key, todayKey, isOutside, dayEvents) {
  const hasEvents = dayEvents.length > 0;

  // Un jour avec événements est un vrai bouton : utilisable au clavier
  const cell = createElement(hasEvents ? "button" : "div", "calendar-day");
  if (hasEvents) cell.type = "button";

  if (isOutside) cell.classList.add("outside");
  if (key === todayKey) cell.classList.add("today");
  if (key < todayKey && !isOutside) cell.classList.add("past");
  if (hasEvents) cell.classList.add("has-events");

  const number = createElement("span", "day-number", String(date.getDate()));
  cell.appendChild(number);

  if (!hasEvents) return cell;

  const dayLabel = date.toLocaleDateString("fr-FR", {
    weekday: "long",
    day: "numeric",
    month: "long",
  });
  cell.setAttribute(
    "aria-label",
    `${dayLabel}, ${dayEvents.length} événement${dayEvents.length > 1 ? "s" : ""}`,
  );

  const pills = createElement("span", "day-events");

  dayEvents.slice(0, MAX_PILLS_PER_DAY).forEach((event) => {
    const pill = createElement("span", `event-pill ${getCategoryClass(event.category)}`);
    pill.title = event.title;

    const time = formatEventTime(event);
    if (time) pill.appendChild(createElement("span", "event-pill-time", time));
    pill.appendChild(createElement("span", "event-pill-text", event.title));

    pills.appendChild(pill);
  });

  const remaining = dayEvents.length - MAX_PILLS_PER_DAY;
  if (remaining > 0) {
    pills.appendChild(createElement("span", "event-more", `+${remaining}`));
  }

  cell.appendChild(pills);
  cell.addEventListener("click", () => openDayEventsModal(date, dayEvents));

  return cell;
}

// ---------- Vue agenda ----------

function renderAgenda(eventsByDay) {
  const agenda = document.getElementById("calendarAgendaView");
  if (!agenda) return;

  agenda.replaceChildren();

  if (eventsByDay.size === 0) {
    const empty = createElement("div", "calendar-empty text-center py-5");
    const icon = createElement("i", "bi bi-calendar-x fs-1 text-muted d-block mb-2");
    icon.setAttribute("aria-hidden", "true");
    empty.append(
      icon,
      createElement("p", "text-muted mb-0", "Aucun événement ne correspond à ce mois."),
    );
    agenda.appendChild(empty);
    return;
  }

  const todayKey = toDateKey(new Date());

  [...eventsByDay.keys()].sort().forEach((key) => {
    const [year, month, day] = key.split("-").map(Number);
    const date = new Date(year, month - 1, day);

    const group = createElement("section", "agenda-day");

    const heading = createElement("h3", "agenda-day-title");
    heading.textContent = date.toLocaleDateString("fr-FR", {
      weekday: "long",
      day: "numeric",
      month: "long",
    });
    if (key === todayKey) {
      heading.appendChild(createElement("span", "badge bg-warning text-dark ms-2", "Aujourd'hui"));
    }
    group.appendChild(heading);

    eventsByDay.get(key).forEach((event) => {
      group.appendChild(createEventCard(event, () => showEventDetail(event)));
    });

    agenda.appendChild(group);
  });
}

// ---------- Carte d'événement (agenda et fenêtre du jour) ----------

function createEventCard(event, onOpenDetail) {
  const card = createElement("article", `agenda-card ${getCategoryClass(event.category)}`);

  const image = createElement("img", "agenda-card-image");
  image.src = event.thumbnail || event.image;
  image.alt = "";
  image.loading = "lazy";
  image.width = 96;
  image.height = 96;

  const body = createElement("div", "agenda-card-body");

  const meta = createElement("div", "agenda-card-meta");
  const time = formatEventTime(event);
  if (time) meta.appendChild(createElement("span", "agenda-card-time", time));
  meta.appendChild(
    createElement("span", "agenda-card-category", event.category || "Autre"),
  );

  const title = createElement("h4", "agenda-card-title", event.title);

  const place = createElement("p", "agenda-card-line");
  const placeIcon = createElement("i", "bi bi-geo-alt me-1");
  placeIcon.setAttribute("aria-hidden", "true");
  place.append(placeIcon, [event.city, event.country].filter(Boolean).join(", "));

  const price = createElement("p", "agenda-card-line");
  const priceIcon = createElement("i", "bi bi-tag me-1");
  priceIcon.setAttribute("aria-hidden", "true");
  price.append(
    priceIcon,
    Number(event.price) > 0 ? helpers.formatEventPrice(event.price) : "Gratuit",
  );

  body.append(meta, title, place, price);

  const actions = createElement("div", "agenda-card-actions");

  const detailButton = createElement("button", "btn btn-sm btn-primary", "Détails");
  detailButton.type = "button";
  detailButton.addEventListener("click", onOpenDetail);

  const icsButton = createElement("button", "btn btn-sm btn-outline-secondary");
  icsButton.type = "button";
  icsButton.title = "Ajouter à mon agenda";
  icsButton.setAttribute("aria-label", `Ajouter ${event.title} à mon agenda`);
  const icsIcon = createElement("i", "bi bi-calendar-plus");
  icsIcon.setAttribute("aria-hidden", "true");
  icsButton.appendChild(icsIcon);
  icsButton.addEventListener("click", () => {
    const base = window.location.origin + new URL("./", document.baseURI).pathname;
    downloadEventIcs(event, `${base}event/${event.id}`);
  });

  actions.append(detailButton, icsButton);
  card.append(image, body, actions);

  return card;
}

// ---------- Fenêtre des événements d'un jour ----------

function openDayEventsModal(date, dayEvents) {
  const modal = document.getElementById("dayEventsModalElement");
  if (!modal) return;

  const modalDate = document.getElementById("modalDate");
  if (modalDate) {
    modalDate.textContent = date.toLocaleDateString("fr-FR", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  const list = document.getElementById("modalEventsList");
  if (list) {
    list.replaceChildren();

    dayEvents.forEach((event) => {
      list.appendChild(
        createEventCard(event, () => {
          // Le détail s'ouvre une fois la fenêtre fermée (évite deux couches superposées)
          const bsModal = bootstrap.Modal.getInstance(modal);
          const openDetail = () => showEventDetail(event);

          if (bsModal) {
            helpers.closeModalThen(bsModal, openDetail);
          } else {
            openDetail();
          }
        }),
      );
    });
  }

  new bootstrap.Modal(modal).show();
}

// ============================================
// Interactions
// ============================================

function goToMonth(year, month) {
  const index = toMonthIndex(year, month);
  if (index < minMonthIndex || index > maxMonthIndex) return;

  const normalized = new Date(year, month, 1);
  viewYear = normalized.getFullYear();
  viewMonth = normalized.getMonth();

  writeMonthToUrl();
  renderCalendar();
}

function attachCalendarEvents() {
  document.getElementById("prevMonth")?.addEventListener("click", () => {
    goToMonth(viewYear, viewMonth - 1);
  });

  document.getElementById("nextMonth")?.addEventListener("click", () => {
    goToMonth(viewYear, viewMonth + 1);
  });

  document.getElementById("todayBtn")?.addEventListener("click", () => {
    const today = new Date();
    goToMonth(today.getFullYear(), today.getMonth());
  });

  document.getElementById("viewGridBtn")?.addEventListener("click", () => {
    viewMode = "grid";
    renderCalendar();
  });

  document.getElementById("viewAgendaBtn")?.addEventListener("click", () => {
    viewMode = "agenda";
    renderCalendar();
  });

  // Accès direct au mois du prochain événement
  document.getElementById("nextEventBtn")?.addEventListener("click", (event) => {
    const key = event.currentTarget.dataset.dateKey;
    if (!key) return;
    const [year, month] = key.split("-").map(Number);
    goToMonth(year, month - 1);
  });

  // Légende = filtre : un clic isole une catégorie, un second clic rétablit tout
  document.getElementById("calendarLegend")?.addEventListener("click", (event) => {
    const chip = event.target.closest(".calendar-chip");
    if (!chip) return;

    const category = chip.dataset.category;

    if (activeCategories.size === CATEGORY_NAMES.length) {
      activeCategories = new Set([category]);
    } else if (activeCategories.has(category)) {
      activeCategories.delete(category);
      if (activeCategories.size === 0) {
        activeCategories = new Set(CATEGORY_NAMES);
      }
    } else {
      activeCategories.add(category);
    }

    renderCalendar();
  });

  // Flèches gauche/droite : mois précédent/suivant (sauf dans un champ ou une fenêtre ouverte)
  keyHandler = (event) => {
    if (document.body.classList.contains("modal-open")) return;
    const tag = event.target.tagName;
    if (["INPUT", "SELECT", "TEXTAREA"].includes(tag)) return;

    if (event.key === "ArrowLeft") {
      goToMonth(viewYear, viewMonth - 1);
    } else if (event.key === "ArrowRight") {
      goToMonth(viewYear, viewMonth + 1);
    }
  };
  document.addEventListener("keydown", keyHandler);
}

// Export par défaut
export default { mount, unmount, meta };
